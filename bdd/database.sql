-- Active: 1738234474941@@127.0.0.1@3306@stock_management
-- Create the database
CREATE DATABASE IF NOT EXISTS stock_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_management;

-- Users table (Fx1)
CREATE TABLE IF NOT EXISTS store (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    identifier VARCHAR(50) NOT NULL UNIQUE,
    product_type VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table (Fx1)
CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    store_id INT,
    role ENUM('Admin', 'Manager', 'Employee') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES store(id) ON DELETE SET NULL
);


-- Action logs table (Fx15)
CREATE TABLE IF NOT EXISTS log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(100),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);

-- Categories table (Fx4)
-- Création de la table categories si elle n'existe pas
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des catégories par défaut
INSERT INTO category (name) VALUES 
('Alimentaire'),
('Boissons'),
('Produits d\'entretien'),
('Fournitures de bureau'),
('Électronique'),
('Hygiène'),
('Autres')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Suppliers table (Fx9)
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    address TEXT,
    phone VARCHAR(20)
);

-- Products table (Fx3)
CREATE TABLE IF NOT EXISTS product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    quantity INT DEFAULT 0 CHECK (quantity >= 0),
    alert_threshold INT DEFAULT 5 CHECK (alert_threshold >= 0),
    category_id INT NOT NULL,
    supplier_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE RESTRICT
);

-- Stock entries table (Fx5)
CREATE TABLE IF NOT EXISTS stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantite INT NOT NULL DEFAULT 0,
    prix DECIMAL(10,2) NOT NULL,
    seuil_alerte INT DEFAULT 10,
    category_id INT,
    entreprise_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL,
    FOREIGN KEY (entreprise_id) REFERENCES store(id)
);

-- Replenishment orders table (Fx11)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_id INT NOT NULL,
    quantity INT NOT NULL,
    movement_type ENUM('entry', 'exit') NOT NULL,
    reason TEXT,
    user_id INT NOT NULL,
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id)
);

-- Generated reports table (Fx12)
CREATE TABLE IF NOT EXISTS report (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('movements', 'forecasts') NOT NULL,
    start_period DATE NOT NULL,
    end_period DATE NOT NULL,
    content JSON NOT NULL, -- Structured data storage
    generation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    generator_id INT NOT NULL,
    FOREIGN KEY (generator_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Insert default admin account (make sure to hash the password in production)
INSERT INTO store (name, email, identifier, product_type) 
VALUES ('Siège', 'admin@stockocesi.fr', 'SIEGE001', 'Admin')
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO user (name, email, password, role)
VALUES ('Anas', 'anas.bazi@viacesi.fr', 'Rewal136?', 'Admin');

INSERT INTO product (name, description, price, quantity, alert_threshold, category_id, supplier_id)
VALUES ('Produit Test', 'Description du produit test', 10.00, 100, 5, 1, 1)

