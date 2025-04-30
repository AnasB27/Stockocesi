<?php

namespace App\Models;

class StoreModel {
    private $db;

    public function __construct() {
        // Obtenir l'instance de la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouveau magasin.
     *
     * @param string $nom Le nom du magasin.
     * @param string $email L'adresse mail à contacter.
     * @param string $identifiant L'identifiant unique du magasin.
     * @param string $typeProduit Le type de produit associé au magasin.
     * @return bool True si l'ajout a réussi, sinon false.
     */
    public function addStore($nom, $email, $identifiant, $typeProduit) {
        $sql = "INSERT INTO magasins (nom, email, identifiant, type_produit, date_creation) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $email, $identifiant, $typeProduit]);
    }

    /**
     * Met à jour les informations d'un magasin.
     *
     * @param int $storeId L'ID du magasin.
     * @param string $nom Le nouveau nom du magasin.
     * @param string $email La nouvelle adresse mail à contacter.
     * @param string $identifiant Le nouvel identifiant unique du magasin.
     * @param string $typeProduit Le nouveau type de produit associé au magasin.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updateStore($storeId, $nom, $email, $identifiant, $typeProduit) {
        $sql = "UPDATE magasins SET nom = ?, email = ?, identifiant = ?, type_produit = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $email, $identifiant, $typeProduit, $storeId]);
    }

    /**
     * Supprime un magasin.
     *
     * @param int $storeId L'ID du magasin à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function deleteStore($storeId) {
        $sql = "DELETE FROM magasins WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$storeId]);
    }

    /**
     * Récupère tous les magasins.
     *
     * @return array Un tableau contenant tous les magasins.
     */
    public function getAllStores() {
        $sql = "SELECT * FROM magasins ORDER BY date_creation DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les détails d'un magasin spécifique.
     *
     * @param int $storeId L'ID du magasin.
     * @return array|null Les informations du magasin ou null si non trouvé.
     */
    public function getStoreById($storeId) {
        $sql = "SELECT * FROM magasins WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$storeId]);
        return $stmt->fetch() ?: null;
    }
}