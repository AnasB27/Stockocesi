<?php

namespace App\Models;

class MessageModel {
    private $db;

    public function __construct() {
        // Obtenir l'instance de la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Envoie un message à l'administrateur.
     *
     * @param int $userId L'ID de l'utilisateur qui envoie le message.
     * @param string $subject Le sujet du message.
     * @param string $content Le contenu du message.
     * @return bool True si le message a été envoyé avec succès, sinon false.
     */
    public function sendMessage($userId, $subject, $content) {
        $sql = "INSERT INTO messages (utilisateur_id, sujet, contenu, date_envoi) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $subject, $content]);
    }

    /**
     * Récupère tous les messages envoyés à l'administrateur.
     *
     * @return array Un tableau contenant tous les messages.
     */
    public function getAllMessages() {
        $sql = "SELECT m.id, m.sujet, m.contenu, m.date_envoi, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom
                FROM messages m
                INNER JOIN utilisateurs u ON m.utilisateur_id = u.id
                ORDER BY m.date_envoi DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un message spécifique par son ID.
     *
     * @param int $messageId L'ID du message.
     * @return array|null Les informations du message ou null si non trouvé.
     */
    public function getMessageById($messageId) {
        $sql = "SELECT m.id, m.sujet, m.contenu, m.date_envoi, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom
                FROM messages m
                INNER JOIN utilisateurs u ON m.utilisateur_id = u.id
                WHERE m.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$messageId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Supprime un message par son ID.
     *
     * @param int $messageId L'ID du message à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function deleteMessage($messageId) {
        $sql = "DELETE FROM messages WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$messageId]);
    }
}