<?php

namespace App\Models;

class StockModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
 * Ajoute un nouvel article au stock
 * @param string $nom Nom du produit
 * @param int $quantite Quantité du produit
 * @param float $prix Prix du produit
 * @param int $entrepriseId ID de l'entreprise
 * @param int $categoryId ID de la catégorie
 * @param int $alertThreshold Seuil d'alerte
 * @return bool
 */
public function addStock($nom, $quantite, $prix, $entrepriseId, $categoryId, $alertThreshold) {
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
}

    /**
     * Met à jour les informations d'un article
     */
    public function updateStock($stockId, $quantite, $prix) {
        $sql = "UPDATE stocks SET quantite = ?, prix = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantite, $prix, $stockId]);
    }

    /**
     * Supprime un article du stock
     */
    public function deleteStock($stockId) {
        // Supprimer d'abord les mouvements associés
        $sql = "DELETE FROM stock_movements WHERE stock_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);

        // Puis supprimer l'article
        $sql = "DELETE FROM stocks WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$stockId]);
    }

    /**
     * Récupère tous les articles d'une entreprise
     */
    public function getStocksByEntreprise($entrepriseId) {
        $sql = "SELECT s.*, c.name as category_name 
                FROM stocks s 
                LEFT JOIN categories c ON s.category_id = c.id 
                WHERE s.entreprise_id = ? 
                ORDER BY s.date_ajout DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entrepriseId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère toutes les catégories
     * @return array Liste des catégories
     */
    public function getAllCategories(): array {
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un article spécifique
     */
    public function getStockById($stockId) {
        $sql = "SELECT s.*, c.name as category_name 
                FROM stocks s 
                LEFT JOIN categories c ON s.category_id = c.id 
                WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Ajoute une quantité à un article
     */
    public function addStockQuantity($stockId, $quantity) {
        $sql = "UPDATE stocks SET quantite = quantite + ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $stockId]);
    }

    /**
     * Retire une quantité d'un article
     */
    public function removeStockQuantity($stockId, $quantity) {
        // Vérifier le stock disponible
        $currentStock = $this->getStockById($stockId);
        if (!$currentStock || $currentStock['quantite'] < $quantity) {
            return false;
        }

        $sql = "UPDATE stocks SET quantite = quantite - ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $stockId]);
    }

    /**
     * Enregistre un mouvement de stock
     */
    public function recordMovement($stockId, $quantity, $type, $reason, $userId) {
        $sql = "INSERT INTO stock_movements (stock_id, quantity, movement_type, reason, user_id, movement_date) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$stockId, $quantity, $type, $reason, $userId]);
    }

    /**
     * Récupère l'historique des mouvements
     */
    public function getStockMovements($stockId) {
        $sql = "SELECT sm.*, u.name as user_name 
                FROM stock_movements sm 
                LEFT JOIN user u ON sm.user_id = u.id 
                WHERE sm.stock_id = ? 
                ORDER BY sm.movement_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les articles en stock faible
     */
    public function getLowStockProducts($entrepriseId) {
        $sql = "SELECT s.*, c.name as category_name 
                FROM stocks s 
                LEFT JOIN categories c ON s.category_id = c.id 
                WHERE s.entreprise_id = ? AND s.quantite <= s.seuil_alerte 
                ORDER BY s.quantite ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entrepriseId]);
        return $stmt->fetchAll();
    }

    /**
     * Met à jour le seuil d'alerte
     */
    public function updateAlertThreshold($stockId, $threshold) {
        $sql = "UPDATE stocks SET seuil_alerte = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$threshold, $stockId]);
    }
}