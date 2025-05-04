<?php

namespace App\Models;

class LogModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un log dans la base de données
     * 
     * @param array $data Les données du log
     * @return bool True si l'ajout a réussi, false sinon
     */
    public function addLog($data) {
        $sql = "INSERT INTO log (user_id, user_name, action, details, timestamp) 
                VALUES (:user_id, :user_name, :action, :details, :timestamp)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'user_name' => $data['user_name'],
            'action' => $data['action'],
            'details' => $data['details'],
            'timestamp' => $data['timestamp']
        ]);
    }

    /**
     * Récupère tous les logs
     * 
     * @return array La liste des logs
     */
    public function getAllLogs() {
        $sql = "SELECT * FROM log ORDER BY timestamp DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Efface tous les logs
     * 
     * @return bool True si la suppression a réussi, false sinon
     */
    public function clearLogs() {
        try {
            $this->db->beginTransaction();
            $sql = "DELETE FROM log";
            $result = $this->db->exec($sql);
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression des logs : " . $e->getMessage());
            return false;
        }
    }
}