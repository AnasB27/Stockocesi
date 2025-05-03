<?php

namespace App\Models;

class StoreModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouveau magasin.
     */
    public function addStore($nom, $email, $identifiant, $typeProduit) {
        $sql = "INSERT INTO store (name, email, identifier, product_type, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $email, $identifiant, $typeProduit]);
    }

    /**
     * Met à jour les informations d'un magasin.
     */
    public function updateStore($storeId, $nom, $email, $identifiant, $typeProduit) {
        $sql = "UPDATE store 
                SET name = ?, email = ?, identifier = ?, product_type = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $email, $identifiant, $typeProduit, $storeId]);
    }

    /**
     * Supprime un magasin.
     */
    public function deleteStore($storeId) {
        $sql = "DELETE FROM store WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$storeId]);
    }

    /**
     * Récupère tous les magasins.
     */
    public function getAllStores() {
        $sql = "SELECT * FROM store ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les détails d'un magasin spécifique.
     */
    public function getStoreById($storeId) {
        $sql = "SELECT * FROM store WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$storeId]);
        return $stmt->fetch() ?: null;
    }
}