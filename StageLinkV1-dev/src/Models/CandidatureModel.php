<?php
class candidaturemodel {
    private $db;
    
    public function __construct() {
        require_once ROOT_PATH . '/src/models/database.php';
        $this->db = database::getInstance();
    }
    
    public function creerCandidature($utilisateur_id, $offre_id, $lettre_motivation, $cv) {
        try {
            if ($this->candidatureExiste($utilisateur_id, $offre_id)) {
                return [
                    'success' => false,
                    'message' => 'Vous avez déjà postulé à cette offre'
                ];
            }
            
            $sql = "INSERT INTO candidatures (utilisateur_id, offre_id, lettre_motivation, cv, date_candidature) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            try {
                $stmt->execute([$utilisateur_id, $offre_id, $lettre_motivation, $cv]);
                return [
                    'success' => true,
                    'id' => $this->db->getLastInsertId()
                ];
            } catch (PDOException $e) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement de la candidature: ' . $e->getMessage()
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    public function candidatureExiste($utilisateur_id, $offre_id) {
        $sql = "SELECT COUNT(*) as count FROM candidatures WHERE utilisateur_id = ? AND offre_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateur_id, $offre_id]);
        $row = $stmt->fetch();
        
        return $row['count'] > 0;
    }
    
    public function getCandidatureById($id) {
        $sql = "SELECT c.id, c.utilisateur_id, c.offre_id, c.lettre_motivation, c.cv, c.date_candidature, o.titre as offre_titre, e.nom as entreprise_nom 
                FROM candidatures c
                JOIN offres o ON c.offre_id = o.id
                JOIN entreprises e ON o.entreprise_id = e.id
                WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    public function getCandidaturesByUtilisateur($utilisateur_id) {
        $sql = "SELECT c.id, c.utilisateur_id, c.offre_id, c.lettre_motivation, c.cv, c.date_candidature, o.titre as offre_titre, e.nom as entreprise_nom 
                FROM candidatures c
                JOIN offres o ON c.offre_id = o.id
                JOIN entreprises e ON o.entreprise_id = e.id
                WHERE c.utilisateur_id = ?
                ORDER BY c.date_candidature DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateur_id]);
        
        return $stmt->fetchAll();
    }
    
    public function supprimerCandidature($id) {
        $candidature = $this->getCandidatureById($id);
        if ($candidature && !empty($candidature['cv'])) {
            if (file_exists(ROOT_PATH . '/' . $candidature['cv'])) {
                unlink(ROOT_PATH . '/' . $candidature['cv']);
            }
        }
        
        $sql = "DELETE FROM candidatures WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }
}




