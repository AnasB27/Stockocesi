# 📦 Stock O' CESI – Application de gestion de stock

![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
![Twig](https://img.shields.io/badge/twig-%23323330.svg?style=for-the-badge&logo=twig&logoColor=%23FFDD39)
![MySQL](https://img.shields.io/badge/mysql-%234479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/apache-%23D42029.svg?style=for-the-badge&logo=apache&logoColor=white)

---

## 📋 Description du projet

**Stock O' CESI** est une application web complète pour la gestion de stocks multi-magasins. Elle permet de gérer les produits, les mouvements de stock, les utilisateurs (avec rôles), les historiques, et propose des outils de recherche et de contact. L’interface est responsive et adaptée à tous les profils d’utilisateurs (Admin, Manager, Employé).

---

## ✨ Fonctionnalités principales

- **Gestion des produits** : Ajout, modification, suppression, gestion des seuils d’alerte.
- **Mouvements de stock** : Entrées, sorties, historique détaillé et filtrable.
- **Gestion des utilisateurs** : Création, modification, suppression, gestion des rôles (Admin, Manager, Employé).
- **Gestion multi-magasins** : Association des utilisateurs à un magasin, gestion indépendante des stocks par magasin.
- **Recherche instantanée** : Filtrage dynamique des produits côté client (JavaScript).
- **Contact** : Formulaire pour contacter l’administrateur.
- **Mentions légales** : Page dédiée accessible depuis le footer.

---

## 🛠️ Technologies et outils utilisés

- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Backend** : PHP 8+ (architecture MVC)
- **Base de données** : MySQL/MariaDB
- **Moteur de templates** : Twig
- **Gestion des dépendances** : Composer
- **Serveur** : Apache (WAMP/XAMPP recommandé)

---

## 🏗️ Architecture et méthodes

- **MVC** : Séparation stricte entre Modèles (`src/Models`), Vues (`templates/` avec Twig) et Contrôleurs (`src/Controllers`).
- **Routage** : Centralisé dans `index.php` avec gestion des routes par switch/case et expressions régulières.
- **Sécurité** :
  - Vérification systématique du rôle utilisateur (`$_SESSION['user_role']`) et du magasin (`$_SESSION['store_id']`) avant chaque action sensible.
  - Utilisation de requêtes SQL préparées pour éviter les injections.
  - Hachage des mots de passe avec `password_hash`.
  - Validation côté client (JS) et côté serveur (PHP).
- **AJAX/Fetch** : Utilisé pour les suppressions, l’historique, la recherche sans rechargement de page.
- **Responsive Design** : CSS personnalisé pour une expérience fluide sur tous supports.

---

## ⚠️ Problème de session et affichage du stock

L’affichage du stock est **directement lié à la session PHP**.  
**Voici le fonctionnement précis :**

- Lorsqu’un utilisateur se connecte, ses informations (rôle, magasin, etc.) sont stockées dans `$_SESSION`.
- **Pour les Admins** : ils voient tous les stocks, car aucune restriction n’est appliquée sur le magasin.
- **Pour les Managers/Employés** : ils ne voient que le stock du magasin associé à `$_SESSION['store_id']`.

### Problème rencontré

Si la session n’est pas initialisée correctement (par exemple, si l’utilisateur n’est pas connecté, ou si `$_SESSION['store_id']` est absent ou corrompu), alors :
- La méthode de récupération des stocks échoue (`getStocksByEntreprise($_SESSION['store_id'])` reçoit une valeur vide ou incorrecte).
- La page peut afficher une erreur, rester vide, ou rediriger vers la page de connexion.

### Solution mise en place

- **Vérification stricte** de la présence et de la validité de `$_SESSION['user_role']` et `$_SESSION['store_id']` avant chaque affichage ou action sur le stock.
- **Redirection automatique** vers la page de connexion si la session est absente ou incomplète.
- **Message d’erreur explicite** si l’accès est refusé ou si la session est invalide.
- **Initialisation de la session** dès le début de chaque script nécessitant une authentification (`session_start();`).

---

## 🚀 Installation

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/AnasB27/Web_Rattrapage.git
   ```
2. **Importer la base de données** :
   - Créez une base MySQL nommée `stock_management`.
   - Importez le fichier SQL fourni :
     ```bash
     mysql -u username -p stockocesi < bdd/database.sql
     ```
3. **Configurer l’environnement** :
   - Renseignez les accès BDD dans le fichier `.env` à la racine du projet.
4. **Installer les dépendances PHP** :
   ```bash
   composer install
   ```
5. **Lancer le serveur local** (WAMP/XAMPP) et accéder à [http://localhost/stockocesi](http://localhost/stockocesi).

---

## 📁 Structure du projet

```
stockocesi/
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── config/
│   └── Exceptions
├── bdd              
├── static/               # CSS, JS, images
├── templates/            # Templates Twig
├── vendor/               # Dépendances Composer
├── .env                  # Paramètres de connexion BDD
├── composer.json
├── index.php             # Point d'entrée (route)
└── README.md
```

---


---

## 📜 Licence

© 2025 Stock O' CESI. Tous droits réservés.

---

