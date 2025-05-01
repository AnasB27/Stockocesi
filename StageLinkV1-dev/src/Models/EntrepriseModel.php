<?php
class entreprisemodel {
    private $db;
    
    public function __construct() {
        require_once ROOT_PATH . '/src/models/database.php';
        $this->db = database::getInstance();
    }

    public function countAllEntreprises() {
        $sql = "SELECT COUNT(*) as total FROM entreprises";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
    
    public function countEntreprisesBySearch($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        $sql = "SELECT COUNT(*) as total FROM entreprises WHERE nom LIKE :searchTerm";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
    
    public function searchEntreprises($searchTerm, $limit, $offset, $userId = null) {
        $searchTerm = '%' . $searchTerm . '%';
        $sql = "SELECT e.*, 
                COALESCE(AVG(ev.note), 0) as note_moyenne,
                COUNT(ev.id) as nombre_avis,
                user_eval.note as user_note
                FROM entreprises e
                LEFT JOIN evaluations ev ON e.id = ev.entreprise_id
                LEFT JOIN (
                    SELECT entreprise_id, note 
                    FROM evaluations 
                    WHERE utilisateur_id = :userId
                ) user_eval ON e.id = user_eval.entreprise_id
                WHERE e.nom LIKE :searchTerm
                GROUP BY e.id
                ORDER BY e.nom
                LIMIT :offset, :limit";
    
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll();
    }

    public function getEntreprisesWithPaginationAndRatings($limit, $offset, $userId = null) {
        $sql = "SELECT e.*, 
                COALESCE(AVG(ev.note), 0) as note_moyenne,
                COUNT(ev.id) as nombre_avis,
                user_eval.note as user_note
                FROM entreprises e
                LEFT JOIN evaluations ev ON e.id = ev.entreprise_id
                LEFT JOIN (
                    SELECT entreprise_id, note 
                    FROM evaluations 
                    WHERE utilisateur_id = :userId
                ) user_eval ON e.id = user_eval.entreprise_id
                GROUP BY e.id
                ORDER BY e.nom
                LIMIT :offset, :limit";
    
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll();
    }

    
    public function getAllEntreprises() {
        $sql = "SELECT * FROM entreprises ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getAllEntreprisesWithRatings($userId = null) {
        $sql = "SELECT e.*, 
                COALESCE(AVG(ev.note), 0) as note_moyenne,
                COUNT(ev.id) as nombre_avis,
                user_eval.note as user_note
                FROM entreprises e
                LEFT JOIN evaluations ev ON e.id = ev.entreprise_id
                LEFT JOIN (
                    SELECT entreprise_id, note 
                    FROM evaluations 
                    WHERE utilisateur_id = :userId
                ) user_eval ON e.id = user_eval.entreprise_id
                GROUP BY e.id
                ORDER BY e.nom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getEntrepriseById($id) {
        $sql = "SELECT * FROM entreprises WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }
    
    public function getEntrepriseWithRatingsById($id, $userId = null) {
        $sql = "SELECT e.*, 
                COALESCE(AVG(ev.note), 0) as note_moyenne,
                COUNT(ev.id) as nombre_evaluations,
                user_eval.note IS NOT NULL as user_has_rated,
                user_eval.note as user_note
                FROM entreprises e
                LEFT JOIN evaluations ev ON e.id = ev.entreprise_id
                LEFT JOIN (
                    SELECT entreprise_id, note 
                    FROM evaluations 
                    WHERE utilisateur_id = :userId
                ) user_eval ON e.id = user_eval.entreprise_id
                WHERE e.id = :id
                GROUP BY e.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch() ?: null;
    }
    
    public function createEntreprise($nom, $description, $email, $telephone) {
        $sql = "SELECT COUNT(*) as count FROM entreprises WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        
        if ($row['count'] > 0) {
            return false;
        }
        
        $sql = "INSERT INTO entreprises (nom, description, email, telephone) 
                VALUES (:nom, :description, :email, :telephone)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    public function updateEntreprise($id, $nom, $description, $email, $telephone) {
        $checkSql = "SELECT id FROM entreprises WHERE email = :email AND id != :id";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return false;
        }
        
        $sql = "UPDATE entreprises 
                SET nom = :nom, description = :description, email = :email, telephone = :telephone 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function deleteEntreprise($id) {
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Vérifier si l'entreprise existe
            $checkSql = "SELECT id FROM entreprises WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                error_log("Tentative de suppression d'une entreprise inexistante (ID: $id)");
                return false;
            }
            
            // 1. Supprimer les entrées dans la table Wishlist liées aux offres de cette entreprise
            $sqlWishlist = "DELETE FROM wishlist WHERE offre_id IN (SELECT id FROM offres WHERE entreprise_id = :id)";
            $stmtWishlist = $this->db->prepare($sqlWishlist);
            $stmtWishlist->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtWishlist->execute();
            error_log("Entrées Wishlist supprimées pour les offres de l'entreprise ID: $id");
            
            // 2. Supprimer les compétences liées aux offres de cette entreprise
            $sqlCompetencesOffres = "DELETE FROM offres_competences WHERE offre_id IN (SELECT id FROM offres WHERE entreprise_id = :id)";
            $stmtCompetencesOffres = $this->db->prepare($sqlCompetencesOffres);
            $stmtCompetencesOffres->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCompetencesOffres->execute();
            error_log("Compétences des offres supprimées pour l'entreprise ID: $id");
            
            // 3. Supprimer les candidatures liées aux offres de cette entreprise
            $sqlCandidatures = "DELETE FROM candidatures WHERE offre_id IN (SELECT id FROM offres WHERE entreprise_id = :id)";
            $stmtCandidatures = $this->db->prepare($sqlCandidatures);
            $stmtCandidatures->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCandidatures->execute();
            error_log("Candidatures supprimées pour l'entreprise ID: $id");
            
            // 4. Supprimer les évaluations liées à cette entreprise
            $sqlEvaluations = "DELETE FROM evaluations WHERE entreprise_id = :id";
            $stmtEvaluations = $this->db->prepare($sqlEvaluations);
            $stmtEvaluations->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtEvaluations->execute();
            error_log("Évaluations supprimées pour l'entreprise ID: $id");
            
            // 5. Supprimer les offres liées à cette entreprise
            $sqlOffres = "DELETE FROM offres WHERE entreprise_id = :id";
            $stmtOffres = $this->db->prepare($sqlOffres);
            $stmtOffres->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtOffres->execute();
            error_log("Offres supprimées pour l'entreprise ID: $id");
            
            // 6. Finalement, supprimer l'entreprise elle-même
            $sqlEntreprise = "DELETE FROM entreprises WHERE id = :id";
            $stmtEntreprise = $this->db->prepare($sqlEntreprise);
            $stmtEntreprise->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmtEntreprise->execute();
            
            if (!$result) {
                error_log("Erreur lors de la suppression de l'entreprise ID: $id - " . print_r($this->db->getConnection()->errorInfo(), true));
                $this->db->getConnection()->rollBack();
                return false;
            }
            
            $this->db->getConnection()->commit();
            error_log("Entreprise ID: $id supprimée avec succès");
            return true;
        } catch (PDOException $e) {
            error_log("Exception lors de la suppression de l'entreprise ID: $id - " . $e->getMessage());
            $this->db->getConnection()->rollBack();
            return false;
        }
    }
    
    public function rateEntreprise($entrepriseId, $utilisateurId, $note, $commentaire = '')
    {
        try {
            $sql = "SELECT id FROM evaluations WHERE entreprise_id = :entrepriseId AND utilisateur_id = :utilisateurId";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':entrepriseId', $entrepriseId, PDO::PARAM_INT);
            $stmt->bindParam(':utilisateurId', $utilisateurId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                $noteId = $row['id'];
                $sql = "UPDATE evaluations SET note = :note, commentaire = :commentaire WHERE id = :noteId";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':note', $note, PDO::PARAM_INT);
                $stmt->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
                $stmt->bindParam(':noteId', $noteId, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                $sql = "INSERT INTO evaluations (entreprise_id, utilisateur_id, note, commentaire) VALUES (:entrepriseId, :utilisateurId, :note, :commentaire)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':entrepriseId', $entrepriseId, PDO::PARAM_INT);
                $stmt->bindParam(':utilisateurId', $utilisateurId, PDO::PARAM_INT);
                $stmt->bindParam(':note', $note, PDO::PARAM_INT);
                $stmt->bindParam(':commentaire', $commentaire, PDO::PARAM_STR);
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getEvaluations($entrepriseId) {
        // Requête SQL simplifiée avec alias clairs
        $sql = "SELECT 
                ev.id as id, 
                ev.entreprise_id, 
                ev.utilisateur_id, 
                ev.note, 
                ev.commentaire,
                u.prenom, 
                u.nom
                FROM evaluations ev
                JOIN utilisateurs u ON ev.utilisateur_id = u.id
                WHERE ev.entreprise_id = :entrepriseId
                ORDER BY ev.id DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':entrepriseId', $entrepriseId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOffresEntreprise($entrepriseId) {
        $sql = "SELECT o.*, 
                COUNT(c.id) as nombre_candidatures 
                FROM offres o 
                LEFT JOIN candidatures c ON o.id = c.offre_id
                WHERE o.entreprise_id = :entrepriseId
                GROUP BY o.id
                ORDER BY o.date_debut DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':entrepriseId', $entrepriseId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getTotalCandidaturesEntreprise($entrepriseId) {
        $sql = "SELECT COUNT(c.id) as total_candidatures
                FROM candidatures c
                JOIN offres o ON c.offre_id = o.id
                WHERE o.entreprise_id = :entrepriseId";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':entrepriseId', $entrepriseId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        
        return $row ? $row['total_candidatures'] : 0;
    }
}
