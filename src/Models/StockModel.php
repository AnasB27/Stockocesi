<?php

namespace App\Models;

class StockModel {
    private $db;

    public function __construct() {
        // Obtenir l'instance de la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouvel article au stock.
     *
     * @param string $nom Le nom de l'article.
     * @param int $quantite La quantité de l'article.
     * @param float $prix Le prix de l'article.
     * @param int $entrepriseId L'ID de l'entreprise associée au stock.
     * @return bool True si l'ajout a réussi, sinon false.
     */
    public function addStock($nom, $quantite, $prix, $entrepriseId) {
        $sql = "INSERT INTO stocks (nom, quantite, prix, entreprise_id, date_ajout) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $quantite, $prix, $entrepriseId]);
    }

    /**
     * Met à jour les informations d'un article dans le stock.
     *
     * @param int $stockId L'ID de l'article dans le stock.
     * @param int $quantite La nouvelle quantité.
     * @param float $prix Le nouveau prix.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updateStock($stockId, $quantite, $prix) {
        $sql = "UPDATE stocks SET quantite = ?, prix = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantite, $prix, $stockId]);
    }

    /**
     * Supprime un article du stock.
     *
     * @param int $stockId L'ID de l'article à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function deleteStock($stockId) {
        $sql = "DELETE FROM stocks WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$stockId]);
    }

    /**
     * Récupère tous les articles du stock pour une entreprise spécifique.
     *
     * @param int $entrepriseId L'ID de l'entreprise.
     * @return array Un tableau contenant les articles du stock.
     */
    public function getStocksByEntreprise($entrepriseId) {
        $sql = "SELECT * FROM stocks WHERE entreprise_id = ? ORDER BY date_ajout DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entrepriseId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les détails d'un article spécifique dans le stock.
     *
     * @param int $stockId L'ID de l'article.
     * @return array|null Les informations de l'article ou null si non trouvé.
     */
    public function getStockById($stockId) {
        $sql = "SELECT * FROM stocks WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId]);
        return $stmt->fetch() ?: null;
    }
}