<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\StoreModel;
use App\Models\LogModel;

class AddAccountController extends Controller {
    private $userModel;
    private $storeModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->storeModel = new StoreModel();
        $this->logModel = new LogModel();
    }

    /**
     * Affiche la page d'ajout de compte
     */
    public function showAddAccount() {
        $this->ensureAdmin();
        
        $stores = $this->storeModel->getAllStores();
        
        echo $this->render('account/add-account', [
            'pageTitle' => 'Créer un compte - Stock O\' CESI',
            'current_page' => 'add-account',
            'stores' => $stores,
            'error' => $_SESSION['error_message'] ?? null,
            'success' => $_SESSION['success_message'] ?? null
        ]);
        
        unset($_SESSION['error_message'], $_SESSION['success_message']);
    }

    /**
     * Traite l'ajout d'un nouveau compte
     */
    public function addAccount() {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/add-account');
            return;
        }

        // Validation des données
        $required = ['name', 'firstname', 'email', 'password', 'store_id', 'role'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires";
                $this->redirect('admin/add-account');
                return;
            }
        }

        // Vérification de l'email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "L'adresse email n'est pas valide";
            $this->redirect('admin/add-account');
            return;
        }

        // Vérification si l'email existe déjà
        if ($this->userModel->emailExists($_POST['email'])) {
            $_SESSION['error_message'] = "Cette adresse email est déjà utilisée";
            $this->redirect('admin/add-account');
            return;
        }

        // Création du compte
        $userData = [
            'name' => $_POST['name'],
            'firstname' => $_POST['firstname'],
            'email' => $_POST['email'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'store_id' => $_POST['store_id'],
            'role' => $_POST['role']
        ];

        if ($this->userModel->createUser($userData)) {
            // Log de l'action
            $this->logModel->addLog([
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'action' => 'Création de compte',
                'details' => "Création du compte pour {$_POST['firstname']} {$_POST['name']}",
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['success_message'] = "Le compte a été créé avec succès";
        } else {
            $_SESSION['error_message'] = "Une erreur est survenue lors de la création du compte";
        }

        $this->redirect('admin/add-account');
    }

    /**
     * Vérifie que l'utilisateur est admin
     */
    private function ensureAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
            $this->redirect('login');
            exit;
        }
    }
}