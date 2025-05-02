<?php

namespace App\Models;

class StockModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouvel article au stock
     * @throws \PDOException si l'insertion échoue
     */
    public function addStock(string $nom, int $quantite, float $prix, int $entrepriseId, int $categoryId, int $alertThreshold): bool {
        try {
            $sql = "INSERT INTO stocks (name, quantite, prix, entreprise_id, category_id, seuil_alerte, date_ajout) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $nom, 
                $quantite, 
                $prix, 
                $entrepriseId, 
                $categoryId, 
                $alertThreshold
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout du stock: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère toutes les catégories
     * @throws \PDOException si la requête échoue
     */
    public function getAllCategories(): array {
        try {
            $sql = "SELECT id, name FROM category ORDER BY name ASC";  // Changé de 'categories' à 'category'
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 
     * Debug
     * @throws \PDOException
     */

     public function categoryExists(int $categoryId): bool {
        try {
            $sql = "SELECT EXISTS(SELECT 1 FROM category WHERE id = ?) as exists_flag";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
            return (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la vérification de la catégorie: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Récupère tous les articles d'une entreprise
     * @throws \PDOException si la requête échoue
     */
    public function getStocksByEntreprise(int $entrepriseId): array {
        try {
            $sql = "SELECT s.*, c.name as category_name 
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    WHERE s.entreprise_id = ? 
                    ORDER BY s.date_ajout DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$entrepriseId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des stocks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les articles en stock faible
     * @throws \PDOException si la requête échoue
     */
    public function getLowStockProducts(int $entrepriseId): array {
        try {
            $sql = "SELECT s.*, c.name as category_name 
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    WHERE s.entreprise_id = ? AND s.quantite <= s.seuil_alerte 
                    ORDER BY s.quantite ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$entrepriseId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des stocks faibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour les informations d'un article
     * @throws \PDOException si la mise à jour échoue
     */
    public function updateStock(int $stockId, int $quantite, float $prix): bool {
        try {
            $sql = "UPDATE stocks SET quantite = ?, prix = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantite, $prix, $stockId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du stock: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime un article du stock
     * @throws \PDOException si la suppression échoue
     */
    public function deleteStock(int $stockId): bool {
        try {
            $this->db->beginTransaction();

            // Supprimer d'abord les mouvements associés
            $sql = "DELETE FROM stock_movements WHERE stock_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$stockId]);

            // Puis supprimer l'article
            $sql = "DELETE FROM stocks WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$stockId]);

            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression du stock: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enregistre un mouvement de stock
     * @throws \PDOException si l'enregistrement échoue
     */
    public function recordMovement(int $stockId, int $quantity, string $type, string $reason, int $userId): bool {
        try {
            $sql = "INSERT INTO stock_movements (stock_id, quantity, movement_type, reason, user_id, movement_date) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$stockId, $quantity, $type, $reason, $userId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'enregistrement du mouvement: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStockById(int $stockId): ?array {
        try {
            $sql = "SELECT s.*, c.name as category_name 
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    WHERE s.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$stockId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du stock: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function addStockQuantity(int $stockId, int $quantity): bool {
        try {
            $sql = "UPDATE stocks SET quantite = quantite + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantity, $stockId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout de quantité: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function removeStockQuantity(int $stockId, int $quantity): bool {
        try {
            $sql = "UPDATE stocks SET quantite = quantite - ? WHERE id = ? AND quantite >= ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantity, $stockId, $quantity]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la réduction de quantité: " . $e->getMessage());
            throw $e;
        }
    }


}