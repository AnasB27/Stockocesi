<?php

namespace App\Controllers;

use App\Models\StoreModel;
use App\Models\LogModel;

class AddStoreController extends Controller {
    private $storeModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->storeModel = new StoreModel();
        $this->logModel = new LogModel();
    }

    /**
     * Affiche la page d'ajout de magasin
     */
    public function showAddStore() {
        $this->ensureAdmin();
        
        echo $this->render('store/add-store', [
            'pageTitle' => 'Ajouter un magasin - Stock O\' CESI',
            'current_page' => 'add-store',
            'error' => $_SESSION['error_message'] ?? null,
            'success' => $_SESSION['success_message'] ?? null
        ]);
        
        unset($_SESSION['error_message'], $_SESSION['success_message']);
    }

    /**
     * Traite l'ajout d'un nouveau magasin
     */
    public function addStore() {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/add-store');
            return;
        }

        // Validation des données
        $required = ['name', 'email', 'identifier', 'product_type'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires";
                $this->redirect('admin/add-store');
                return;
            }
        }

        // Vérification de l'email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "L'adresse email n'est pas valide";
            $this->redirect('admin/add-store');
            return;
        }

        // Ajout du magasin
        if ($this->storeModel->addStore(
            $_POST['name'],
            $_POST['email'],
            $_POST['identifier'],
            $_POST['product_type']
        )) {
            // Log de l'action
            $this->logModel->addLog([
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'action' => 'Création de magasin',
                'details' => "Création du magasin {$_POST['name']}",
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['success_message'] = "Le magasin a été ajouté avec succès";
        } else {
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'ajout du magasin";
        }

        $this->redirect('admin/add-store');
    }

    /**
     * Vérifie que l'utilisateur est admin
     */
    private function ensureAdmin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
            $this->redirect('login');
            exit;
        }
    }
}