<?php
class OffreModel {
    private $db;
    
    public function __construct() {
        require_once ROOT_PATH . '/Src/Models/Database.php';
        $this->db = database::getInstance();
    }
    
    public function getAllOffres() {
        $sql = "SELECT o.*, e.nom as entreprise_nom 
                FROM offres o
                JOIN entreprises e ON o.entreprise_id = e.id
                ORDER BY o.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $offres = [];
        while ($row = $stmt->fetch()) {
            $row['competences'] = $this->getCompetencesForOffre($row['id']);
            $offres[] = $row;
        }
        
        return $offres;
    }
    
    public function getOffreById($id) {
        $sql = "SELECT o.*, 
                e.nom AS entreprise_nom, 
                e.email AS entreprise_email,
                e.telephone AS entreprise_telephone,
                COUNT(c.id) as nombre_candidatures
                FROM offres o
                JOIN entreprises e ON o.entreprise_id = e.id
                LEFT JOIN candidatures c ON o.id = c.offre_id
                WHERE o.id = ?
                GROUP BY o.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch();
        if ($row) {
            $row['competences'] = $this->getCompetencesForOffre($id);
            return $row;
        }
        
        return null;
    }
    
    public function createOffre($entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin, $competences = []) {
        $sql = "INSERT INTO offres (entreprise_id, titre, description, base_remuneration, date_debut, date_fin) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([$entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin]);
            $offreId = $this->db->getLastInsertId();
            error_log("Offre créée avec l'ID: " . $offreId);
            
            if (!empty($competences) && $offreId) {
                foreach ($competences as $competenceId) {
                    $this->addOffreCompetence($offreId, $competenceId);
                }
            }
            
            return $offreId;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'offre: " . $e->getMessage());
            return false;
        }
    }
    
    public function addOffreCompetence($offreId, $competenceId) {
        $sql = "INSERT INTO offres_competences (offre_id, competence_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([$offreId, $competenceId]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de la compétence $competenceId à l'offre $offreId: " . $e->getMessage());
            return false;
        }
    }
    
    public function addOffreCompetences($offreId, $competenceIds) {
        error_log("addOffreCompetences - offreId: $offreId, competenceIds: " . json_encode($competenceIds));
        
        if (empty($competenceIds)) {
            error_log("Aucune compétence à ajouter");
            return true;
        }
        
        $this->deleteOffreCompetences($offreId);
        
        $sql = "INSERT INTO offres_competences (offre_id, competence_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Erreur préparation requête: " . $this->db->getConnection()->errorInfo()[2]);
            return false;
        }
        
        $success = true;
        foreach ($competenceIds as $competenceId) {
            error_log("Ajout compétence $competenceId à l'offre $offreId");
            try {
                $stmt->execute([$offreId, $competenceId]);
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout de la compétence $competenceId: " . $e->getMessage());
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function deleteOffreCompetences($offreId) {
        $sql = "DELETE FROM offres_competences WHERE offre_id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$offreId]);
    }
    
    public function updateOffre($id, $entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin, $competences = []) {
        $this->db->getConnection()->beginTransaction();
        
        try {
            $sql = "UPDATE offres SET entreprise_id = ?, titre = ?, description = ?, base_remuneration = ?, date_debut = ?, date_fin = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([$entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin, $id]);
            
            if (!$result) {
                error_log("Erreur lors de la mise à jour de l'offre ID: $id - " . print_r($this->db->getConnection()->errorInfo(), true));
                $this->db->getConnection()->rollBack();
                return false;
            }
            
            // Mettre à jour les compétences si fournies
            if (!empty($competences)) {
                // Supprimer les compétences existantes
                $this->deleteOffreCompetences($id);
                
                // Ajouter les nouvelles compétences
                foreach ($competences as $competenceId) {
                    $this->addOffreCompetence($id, $competenceId);
                }
            }
            
            $this->db->getConnection()->commit();
            return true;
        } catch (PDOException $e) {
            error_log("Exception lors de la mise à jour de l'offre ID: $id - " . $e->getMessage());
            $this->db->getConnection()->rollBack();
            return false;
        }
    }
    
    public function deleteOffre($id) {
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Vérifier si l'offre existe
            $checkSql = "SELECT id FROM offres WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                error_log("Tentative de suppression d'une offre inexistante (ID: $id)");
                return false;
            }
            
            // 1. Supprimer les entrées dans la table Wishlist liées à cette offre
            $sqlWishlist = "DELETE FROM wishlist WHERE offre_id = :id";
            $stmtWishlist = $this->db->prepare($sqlWishlist);
            $stmtWishlist->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtWishlist->execute();
            error_log("Entrées Wishlist supprimées pour l'offre ID: $id");
            
            // 2. Supprimer les compétences liées à cette offre
            $sqlCompetencesOffres = "DELETE FROM offres_competences WHERE offre_id = :id";
            $stmtCompetencesOffres = $this->db->prepare($sqlCompetencesOffres);
            $stmtCompetencesOffres->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCompetencesOffres->execute();
            error_log("Compétences supprimées pour l'offre ID: $id");
            
            // 3. Supprimer les candidatures liées à cette offre
            $sqlCandidatures = "DELETE FROM candidatures WHERE offre_id = :id";
            $stmtCandidatures = $this->db->prepare($sqlCandidatures);
            $stmtCandidatures->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCandidatures->execute();
            error_log("Candidatures supprimées pour l'offre ID: $id");
            
            // 4. Finalement, supprimer l'offre elle-même
            $sqlOffre = "DELETE FROM offres WHERE id = :id";
            $stmtOffre = $this->db->prepare($sqlOffre);
            $stmtOffre->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmtOffre->execute();
            
            if (!$result) {
                error_log("Erreur lors de la suppression de l'offre ID: $id - " . print_r($this->db->getConnection()->errorInfo(), true));
                $this->db->getConnection()->rollBack();
                return false;
            }
            
            $this->db->getConnection()->commit();
            error_log("Offre ID: $id supprimée avec succès");
            return true;
        } catch (PDOException $e) {
            error_log("Exception lors de la suppression de l'offre ID: $id - " . $e->getMessage());
            $this->db->getConnection()->rollBack();
            return false;
        }
    }
    
    public function getOffreCompetences($offreId) {
        $sql = "SELECT c.* 
                FROM competences c
                JOIN offres_competences oc ON c.id = oc.competence_id
                WHERE oc.offre_id = ?
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offreId]);
        
        return $stmt->fetchAll();
    }
    
    public function getCompetencesForOffre($offreId) {
        return $this->getOffreCompetences($offreId);
    }
    
    public function isOffreLiked($offreId, $utilisateurId) {
        $sql = "SELECT COUNT(*) as count 
                FROM wishlist 
                WHERE offre_id = ? AND utilisateur_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offreId, $utilisateurId]);
        $row = $stmt->fetch();
        
        return $row['count'] > 0;
    }
    
    public function toggleLike($offreId, $utilisateurId) {
        if ($this->isOffreLiked($offreId, $utilisateurId)) {
            $sql = "DELETE FROM wishlist WHERE offre_id = ? AND utilisateur_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$offreId, $utilisateurId]);
        } else {
            $sql = "INSERT INTO wishlist (offre_id, utilisateur_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$offreId, $utilisateurId]);
        }
    }

    public function getAllCompetences() {
        $sql = "SELECT * FROM competences ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function countAllOffres() {
        $sql = "SELECT COUNT(*) as total FROM offres";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
    
    public function countOffresBySearch($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        $sql = "SELECT COUNT(*) as total FROM offres WHERE titre LIKE :searchTerm";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }
    
    public function searchOffres($searchTerm, $limit, $offset, $userId = null) {
        $searchTerm = '%' . $searchTerm . '%';
        $sql = "SELECT o.*, e.nom as entreprise_nom 
                FROM offres o
                JOIN entreprises e ON o.entreprise_id = e.id
                WHERE o.titre LIKE :searchTerm
                ORDER BY o.date_debut DESC
                LIMIT :offset, :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $offres = [];
        while ($row = $stmt->fetch()) {
            $row['competences'] = $this->getCompetencesForOffre($row['id']);
            if ($userId) {
                $row['is_wishlisted'] = $this->isOffreLiked($row['id'], $userId);
            }
            $offres[] = $row;
        }
        
        return $offres;
    }
    
    public function getOffresWithPagination($limit, $offset, $userId = null) {
        $sql = "SELECT o.*, e.nom as entreprise_nom 
                FROM offres o
                JOIN entreprises e ON o.entreprise_id = e.id
                ORDER BY o.date_debut DESC
                LIMIT :offset, :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $offres = [];
        while ($row = $stmt->fetch()) {
            $row['competences'] = $this->getCompetencesForOffre($row['id']);
            if ($userId) {
                $row['is_wishlisted'] = $this->isOffreLiked($row['id'], $userId);
            }
            $offres[] = $row;
        }
        
        return $offres;
    }
    
}
?>
