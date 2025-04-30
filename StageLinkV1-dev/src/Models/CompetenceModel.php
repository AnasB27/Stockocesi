<?php
class competencemodel {
    private $db;
    
    public function __construct() {
        require_once ROOT_PATH . '/src/models/database.php';
        $this->db = database::getInstance();
    }
    
    public function getAllCompetences() {
        $sql = "SELECT * FROM competences ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getCompetenceById($id) {
        $sql = "SELECT * FROM competences WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    public function createCompetence($nom) {
        $sql = "SELECT COUNT(*) as count FROM competences WHERE nom = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nom]);
        $row = $stmt->fetch();
        
        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'Cette compétence existe déjà.'];
        }
        
        $sql = "INSERT INTO competences (nom) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([$nom]);
            return ['success' => true, 'id' => $this->db->getLastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la création de la compétence: ' . $e->getMessage()];
        }
    }
    
    public function updateCompetence($id, $nom) {
        $sql = "UPDATE competences SET nom = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$nom, $id]);
    }
    
    public function deleteCompetence($id) {
        $sql = "DELETE FROM competences WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }
}



