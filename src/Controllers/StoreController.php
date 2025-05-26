<?php

namespace App\Controllers;

use App\Models\StoreModel;
use App\Controllers\LogController;

class StoreController extends Controller {
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->storeModel = new StoreModel();
    }

    private function ensureAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = "Veuillez vous connecter";
            $this->redirect('login');
            exit;
        }
    }

    public function showStorePage() {
        $this->ensureAuthenticated();
        
        $userStore = $this->storeModel->getStoreById($_SESSION['store_id']);
        
        echo $this->render('store/store', [
            'pageTitle' => 'Mon magasin - Stock O\' CESI',
            'current_page' => 'store',
            'store' => $userStore
        ]);
    }

    public function manageStores() {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            $this->redirect('accueil');
            return;
        }
    
        $stores = $this->storeModel->getAllStores();
        
        echo $this->render('store/manage-store', [
            'pageTitle' => 'Gestion des magasins - Stock O\' CESI',
            'current_page' => 'manage-stores',
            'stores' => $stores
        ]);
    }

    public function addStore() {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            $this->redirect('accueil');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $identifiant = $_POST['identifier'] ?? '';
            $typeProduit = $_POST['product_type'] ?? '';

            if (empty($nom) || empty($email) || empty($identifiant) || empty($typeProduit)) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires";
            } else {
                if ($this->storeModel->addStore($nom, $email, $identifiant, $typeProduit)) {
                    // Log de l'ajout du magasin
                    LogController::logAction(
                        'Ajout magasin',
                        "Nouveau magasin ajouté : $nom ($identifiant)"
                    );
                    $_SESSION['success_message'] = "Magasin ajouté avec succès";
                    $this->redirect('admin/manage-stores');
                    return;
                } else {
                    $_SESSION['error_message'] = "Erreur lors de l'ajout du magasin";
                }
            }
        }

        echo $this->render('store/add-store', [
            'pageTitle' => 'Ajouter un magasin - Stock O\' CESI',
            'current_page' => 'add-store'
        ]);
    }

    public function editStore($id) {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            $this->redirect('accueil');
            return;
        }

        $store = $this->storeModel->getStoreById($id);
        
        if (!$store) {
            $_SESSION['error_message'] = "Magasin non trouvé";
            $this->redirect('admin/manage-stores');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $identifiant = $_POST['identifier'] ?? '';
            $typeProduit = $_POST['product_type'] ?? '';

            if (empty($nom) || empty($email) || empty($identifiant) || empty($typeProduit)) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires";
            } else {
                if ($this->storeModel->updateStore($id, $nom, $email, $identifiant, $typeProduit)) {
                    // Log de la modification du magasin
                    LogController::logAction(
                        'Modification magasin',
                        "Magasin modifié : $nom (ID: $id)"
                    );
                    $_SESSION['success_message'] = "Magasin mis à jour avec succès";
                    $this->redirect('admin/manage-stores');
                    return;
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la mise à jour du magasin";
                }
            }
        }

        echo $this->render('store/edit-store', [
            'pageTitle' => 'Modifier un magasin - Stock O\' CESI',
            'current_page' => 'edit-store',
            'store' => $store
        ]);
    }

    public function deleteStore($id) {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            return;
        }

        $store = $this->storeModel->getStoreById($id);
        if (!$store) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Magasin non trouvé']);
            return;
        }

        if ($this->storeModel->deleteStore($id)) {
            // Log de la suppression du magasin
            LogController::logAction(
                'Suppression magasin',
                "Magasin supprimé : {$store['name']} (ID: $id)"
            );
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }
}