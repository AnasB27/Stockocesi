<?php

namespace App\Models;

class UserModel {
    private $db;

    public function __construct() {
        // Obtenir l'instance de la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Authentifie un utilisateur avec son email et son mot de passe.
     *
     * @param string $email L'email de l'utilisateur.
     * @param string $password Le mot de passe de l'utilisateur.
     * @return array|null Les informations de l'utilisateur si l'authentification réussit, sinon null.
     */
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM utilisateurs WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user) {
            // Vérifie le mot de passe haché
            if (password_verify($password, $user['mot_de_passe'])) {
                return $user;
            } 
            // Si le mot de passe n'est pas haché, le hacher et le mettre à jour
            else if ($password === $user['mot_de_passe']) {
                $this->updatePasswordHash($user['id'], $password);
                return $user;
            }
        }

        return null;
    }

    /**
     * Met à jour le mot de passe haché d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $password Le mot de passe en clair.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updatePasswordHash($userId, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Récupère un utilisateur par son ID.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array|null Les informations de l'utilisateur ou null si non trouvé.
     */
    public function getUserById($userId) {
        $sql = "SELECT * FROM utilisateurs WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère le rôle d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return string|null Le rôle de l'utilisateur ou null si non trouvé.
     */
    public function getUserRole($userId) {
        $sql = "SELECT role FROM utilisateurs WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();
        return $result['role'] ?? null;
    }

    /**
     * Récupère les permissions d'un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array|null Les permissions de l'utilisateur ou null si non trouvé.
     */
    public function getUserPermissions($userId) {
        $sql = "SELECT permissions FROM utilisateurs WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();
        return $result['permissions'] ? explode(',', $result['permissions']) : null;
    }
}