<?php

namespace App\Models;
use PDO;

class StoreModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouveau magasin.
     */
    public function addStore($nom, $email, $identifiant, $typeProduit) {
        try {
            $sql = "INSERT INTO store (name, email, identifier, product_type, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nom, $email, $identifiant, $typeProduit]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout du magasin : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour les informations d'un magasin.
     */
    public function updateStore($storeId, $nom, $email, $identifiant, $typeProduit) {
        try {
            $sql = "UPDATE store 
                    SET name = ?, email = ?, identifier = ?, product_type = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nom, $email, $identifiant, $typeProduit, $storeId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du magasin : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime un magasin.
     */
    public function deleteStore($storeId) {
        try {
            $this->db->beginTransaction();

            // Mettre à jour les utilisateurs liés à ce magasin
            $sql = "UPDATE user SET store_id = NULL WHERE store_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$storeId]);

            // Supprimer le magasin
            $sql = "DELETE FROM store WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$storeId]);

            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression du magasin : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère tous les magasins.
     */
    public function getAllStores() {
        try {
            $sql = "SELECT * FROM store ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des magasins : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les détails d'un magasin spécifique.
     */
    public function getStoreById($storeId) {
        try {
            $sql = "SELECT * FROM store WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$storeId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du magasin : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère les catégories d'un magasin (entreprise).
     */
    public function getCategoriesByEntreprise($entrepriseId): array {
        try {
            // On récupère d'abord le type de magasin
            $sql = "SELECT product_type FROM store WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$entrepriseId]);
            $storeType = $stmt->fetchColumn();
    
            if (!$storeType) {
                return [];
            }
    
            // Puis on récupère les catégories correspondantes à ce type
            $sql = "SELECT id, name FROM category WHERE store_type = ? ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$storeType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
            return [];
        }
    }
}