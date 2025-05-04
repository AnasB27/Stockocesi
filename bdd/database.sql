-- Création de la base
CREATE DATABASE IF NOT EXISTS stock_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_management;

-- Suppression des tables si elles existent déjà
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stocks;
DROP TABLE IF EXISTS subcategory;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS log;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS store;

-- Table des magasins
CREATE TABLE IF NOT EXISTS store (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL, 
    identifier VARCHAR(50) NOT NULL UNIQUE,
    product_type VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    store_id INT,
    role ENUM('Admin', 'Manager', 'Employee') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES store(id) ON DELETE SET NULL
);

-- Table des logs d'action
CREATE TABLE IF NOT EXISTS log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(100),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);

-- Table des catégories
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    store_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_category_store_type (name, store_type)
);

-- Table des sous-catégories
CREATE TABLE IF NOT EXISTS subcategory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    main_category INT NOT NULL,
    UNIQUE KEY uniq_subcat_main (name, main_category)
);

-- Table des stocks
CREATE TABLE IF NOT EXISTS stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantite INT NOT NULL DEFAULT 0,
    prix DECIMAL(10,2) NOT NULL,
    seuil_alerte INT DEFAULT 10,
    category_id INT,
    subcategory_id INT,
    entreprise_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategory(id) ON DELETE SET NULL,
    FOREIGN KEY (entreprise_id) REFERENCES store(id) ON DELETE CASCADE
);

-- Table des mouvements de stock
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

-- Insertion des magasins
INSERT IGNORE INTO store (name, email, identifier, product_type) VALUES 
('Siège', 'admin@stockocesi.fr', 'SIEGE001', 'Alimentaire'),
('Boutique Mode', 'mode@stockocesi.fr', 'MODE001', 'Vêtement'),
('TechStore', 'tech@stockocesi.fr', 'TECH001', 'Électronique');



-- Insertion de l'admin par défaut
INSERT IGNORE INTO user (name, firstname, email, password, role, store_id)
SELECT 'Admin', 'Admin', 'anas.bazi@viacesi.fr', 'Rewal136?', 'Admin', id
FROM store 
WHERE identifier = 'SIEGE001';

-- Insertion d'un produit test
INSERT IGNORE INTO stocks (
    name, 
    description,
    quantite,
    prix,
    seuil_alerte,
    category_id,
    subcategory_id,
    entreprise_id
) 
SELECT 
    'Pommes Golden',
    'Pommes Golden fraîches',
    100,
    2.50,
    20,
    c.id,
    s.id,
    st.id
FROM category c
JOIN subcategory s ON s.main_category = c.store_type
JOIN store st ON st.identifier = 'SIEGE001'
WHERE c.name = 'Fruits et Légumes' 
AND c.store_type = 'Alimentaire'
AND s.name = 'Fruits'
LIMIT 1;

-- Vérification
SELECT 'Stores' as 'Table', COUNT(*) as 'Count' FROM store
UNION ALL
SELECT 'Categories', COUNT(*) FROM category
UNION ALL
SELECT 'Subcategories', COUNT(*) FROM subcategory
UNION ALL
SELECT 'Stocks', COUNT(*) FROM stocks;


ALTER TABLE subcategory
ADD CONSTRAINT fk_main_category FOREIGN KEY (main_category) REFERENCES category(id) ON DELETE CASCADE;

SELECT * FROM subcategory;

-- Catégories pour Textile, Tech, Alimentaire
INSERT IGNORE INTO category (name, store_type) VALUES
('Textile', 'Textile'),
('Tech', 'Électronique'),
('Alimentaire', 'Alimentaire');

-- Sous-catégories Textile
INSERT INTO subcategory (name, main_category)
SELECT 'T-shirts', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Pantalons', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Robes', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Manteaux', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Chaussettes', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Ceintures', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Chaussures', c.id FROM category c WHERE c.name = 'Textile'
UNION ALL SELECT 'Accessoires', c.id FROM category c WHERE c.name = 'Textile';

-- Sous-catégories Tech
INSERT INTO subcategory (name, main_category)
SELECT 'Smartphones', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Tablettes', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'PC Portables', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Ordinateurs de bureau', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Écrans', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'TV/Audio', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Casques', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Câbles', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Accessoires', c.id FROM category c WHERE c.name = 'Tech'
UNION ALL SELECT 'Gaming', c.id FROM category c WHERE c.name = 'Tech';

-- Sous-catégories Alimentaire
INSERT INTO subcategory (name, main_category)
SELECT 'Fruits', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Légumes', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Fromages', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Yaourts', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Boissons', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Eaux', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Épicerie', c.id FROM category c WHERE c.name = 'Alimentaire'
UNION ALL SELECT 'Snacks', c.id FROM category c WHERE c.name = 'Alimentaire';


-- Catégories pour Textile, Tech, Alimentaire
INSERT IGNORE INTO category (name, store_type) VALUES
('Textile', 'Textile'),
('Tech', 'Électronique'),
('Alimentaire', 'Alimentaire');



UPDATE store SET product_type = 'Textile' WHERE product_type = 'Vêtement';
UPDATE store SET product_type = 'Tech' WHERE product_type = 'Electronique';

select * from stocks;
