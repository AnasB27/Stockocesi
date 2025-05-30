<?php

namespace App\Models;


use PDO;

class StockModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addStock(array $data): bool {
        try {
            $sql = "INSERT INTO stocks (
                name, description, quantite, prix, 
                seuil_alerte, category_id, subcategory_id, 
                entreprise_id, date_ajout
            ) VALUES (
                :name, :description, :quantity, :price,
                :alert_threshold, :category_id, :subcategory_id,
                :store_id, NOW()
            )";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'alert_threshold' => $data['alert_threshold'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'store_id' => $data['store_id']
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout du stock: " . $e->getMessage());
            return false;
        }
    }

    public function getStocksByEntreprise($entrepriseId): array {
        try {
            $sql = "SELECT s.*, c.name as category_name, 
                    sc.name as subcategory_name, st.name as store_name 
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    LEFT JOIN subcategory sc ON s.subcategory_id = sc.id
                    LEFT JOIN store st ON s.entreprise_id = st.id
                    WHERE s.entreprise_id = :entreprise_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['entreprise_id' => $entrepriseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            return [];
        }
    }

    public function getLowStockProducts(int $entrepriseId): array {
        try {
            $sql = "SELECT s.*, c.name as category_name, sc.name as subcategory_name
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    LEFT JOIN subcategory sc ON s.subcategory_id = sc.id
                    WHERE s.entreprise_id = :entreprise_id 
                    AND s.quantite <= s.seuil_alerte";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['entreprise_id' => $entrepriseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des stocks faibles: " . $e->getMessage());
            return [];
        }
    }

    public function getCategoryById(int $id): ?array {
        try {
            $sql = "SELECT * FROM category WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de la catégorie: " . $e->getMessage());
            return null;
        }
    }

    public function getSubcategoryById(int $id): ?array {
        try {
            $sql = "SELECT * FROM subcategory WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de la sous-catégorie: " . $e->getMessage());
            return null;
        }
    }

    public function getAllCategories(): array {
        try {
            $sql = "SELECT id, name, store_type FROM category ORDER BY store_type, name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
            return [];
        }
    }

    public function getAllSubcategories(): array {
        try {
            $sql = "SELECT id, name, main_category FROM subcategory ORDER BY main_category, name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des sous-catégories: " . $e->getMessage());
            return [];
        }
    }

    public function getSubcategoriesByMainCategory(string $mainCategory): array {
        try {
            $sql = "SELECT id, name FROM subcategory WHERE main_category = ? ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mainCategory]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des sous-catégories: " . $e->getMessage());
            return [];
        }
    }

    public function updateStock(int $stockId, array $data): bool {
        try {
            $sql = "UPDATE stocks SET 
                    name = :name,
                    quantite = :quantity,
                    prix = :price,
                    category_id = :category_id,
                    subcategory_id = :subcategory_id,
                    seuil_alerte = :alert_threshold
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'alert_threshold' => $data['alert_threshold'],
                'id' => $stockId
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du stock: " . $e->getMessage());
            return false;
        }
    }

    public function deleteStock(int $stockId): bool {
        try {
            $this->db->beginTransaction();

            $sql = "DELETE FROM stock_movements WHERE stock_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$stockId]);

            $sql = "DELETE FROM stocks WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$stockId]);

            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression du stock: " . $e->getMessage());
            return false;
        }
    }

    public function addStockQuantity(int $stockId, int $quantity): bool {
        try {
            $sql = "UPDATE stocks SET quantite = quantite + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantity, $stockId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'ajout de quantité: " . $e->getMessage());
            return false;
        }
    }

    public function removeStockQuantity(int $stockId, int $quantity): bool {
        try {
            $sql = "UPDATE stocks SET quantite = quantite - ? 
                    WHERE id = ? AND quantite >= ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantity, $stockId, $quantity]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la réduction de quantité: " . $e->getMessage());
            return false;
        }
    }

    public function recordMovement(int $stockId, int $quantity, string $type, string $reason, int $userId): bool {
        try {
            $sql = "INSERT INTO stock_movements (
                    stock_id, quantity, movement_type, reason, user_id, movement_date
                ) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$stockId, $quantity, $type, $reason, $userId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de l'enregistrement du mouvement: " . $e->getMessage());
            return false;
        }
    }

    public function getStockById(int $stockId): ?array {
        try {
            $sql = "SELECT s.*, c.name as category_name, 
                    sc.name as subcategory_name, st.name as store_name 
                    FROM stocks s 
                    LEFT JOIN category c ON s.category_id = c.id 
                    LEFT JOIN subcategory sc ON s.subcategory_id = sc.id
                    LEFT JOIN store st ON s.entreprise_id = st.id
                    WHERE s.id = :stock_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['stock_id' => $stockId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du stock: " . $e->getMessage());
            return null;
        }
    }
}