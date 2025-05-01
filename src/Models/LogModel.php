<?php

namespace App\Models;

class LogModel {
    private $db;

    public function __construct() {
        // Obtenir l'instance de la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Enregistre une action dans le journal.
     *
     * @param int $userId L'ID de l'utilisateur ayant effectué l'action.
     * @param string $action La description de l'action effectuée.
     * @return bool True si l'enregistrement a réussi, sinon false.
     */
    public function logAction($userId, $action) {
        $sql = "INSERT INTO journal_actions (utilisateur_id, action, date_action) VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $action]);
    }

    /**
     * Récupère toutes les actions du journal.
     *
     * @return array Un tableau contenant toutes les actions enregistrées.
     */
    public function getAllLogs() {
        $sql = "SELECT ja.id, ja.action, ja.date_action, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom
                FROM journal_actions ja
                INNER JOIN utilisateurs u ON ja.utilisateur_id = u.id
                ORDER BY ja.date_action DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les actions d'un utilisateur spécifique.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array Un tableau contenant les actions de l'utilisateur.
     */
    public function getLogsByUser($userId) {
        $sql = "SELECT ja.id, ja.action, ja.date_action
                FROM journal_actions ja
                WHERE ja.utilisateur_id = ?
                ORDER BY ja.date_action DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Supprime toutes les actions du journal.
     *
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function clearLogs() {
        $sql = "DELETE FROM journal_actions";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
}