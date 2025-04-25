-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_stock;

-- Table des utilisateurs (Fx1)
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Gestionnaire', 'Employé') NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des journaux d'actions (Fx15)
CREATE TABLE journal_action (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    action TEXT NOT NULL,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

-- Table des catégories (Fx4)
CREATE TABLE categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL
);

-- Table des fournisseurs (Fx9)
CREATE TABLE fournisseur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    adresse TEXT,
    telephone VARCHAR(20)
);

-- Table des produits (Fx3)
CREATE TABLE produit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL CHECK (prix >= 0),
    quantite INT DEFAULT 0 CHECK (quantite >= 0),
    seuil_alerte INT DEFAULT 5 CHECK (seuil_alerte >= 0), -- Pour Fx8
    categorie_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    FOREIGN KEY (categorie_id) REFERENCES categorie(id) ON DELETE RESTRICT,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseur(id) ON DELETE RESTRICT
);

-- Table des entrées de stock (Fx5)
CREATE TABLE entree_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    quantite INT NOT NULL CHECK (quantite > 0),
    date_entree DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseur(id) ON DELETE CASCADE
);

-- Table des sorties de stock (Fx6)
CREATE TABLE sortie_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    quantite INT NOT NULL CHECK (quantite > 0),
    motif VARCHAR(255) NOT NULL, -- Vente, usage interne, etc.
    date_sortie DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE
);

-- Table des commandes de réapprovisionnement (Fx11)
CREATE TABLE commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    fournisseur_id INT NOT NULL, -- Ajout pour lier au fournisseur
    quantite INT NOT NULL CHECK (quantite > 0),
    statut ENUM('en_attente', 'en_cours', 'livrée', 'annulée') DEFAULT 'en_attente',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produit(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseur(id) ON DELETE CASCADE
);

-- Table des rapports générés (Fx12)
CREATE TABLE rapport (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('mouvements', 'previsions') NOT NULL,
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    contenu JSON NOT NULL, -- Stockage structuré des données
    date_generation DATETIME DEFAULT CURRENT_TIMESTAMP,
    generateur_id INT NOT NULL,
    FOREIGN KEY (generateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);