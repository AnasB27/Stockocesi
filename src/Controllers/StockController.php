<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Models\StoreModel;
use App\Exceptions\StockException;

class StockController extends Controller {
    private $stockModel;
    private $logModel;
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
        $this->storeModel = new StoreModel();
    }

    public function showStock() {
        // Vérification de la session
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
        
        try {
            // Récupération des informations du magasin
            $store = $this->storeModel->getStoreById($_SESSION['store_id']);
            if (!$store) {
                throw new \Exception("Magasin non trouvé");
            }
    
            // Vérification des données du magasin
            if (!isset($store['product_type_id']) || !isset($store['product_type'])) {
                throw new \Exception("Configuration du magasin incomplète");
            }
    
            // Définition des permissions
            $isAdmin = $_SESSION['user_role'] === 'Admin';
            $isManager = $_SESSION['user_role'] === 'Manager';
            $canManageStock = $isAdmin || $isManager;
    
            // Récupération des données
            $stocks = $this->stockModel->getStocksByEntreprise($_SESSION['store_id']);
            $lowStockProducts = $this->stockModel->getLowStockProducts($_SESSION['store_id']);
    
            // Variables de base pour le template
            $templateVars = [
                'pageTitle' => 'Gestion des stocks - Stock O\' CESI',
                'current_page' => 'stock',
                'products' => $stocks,
                'lowStockProducts' => $lowStockProducts,
                'session' => $_SESSION,
                'canManageStock' => $canManageStock,
                'store' => $store,
                'isAdmin' => $isAdmin,
                'isManager' => $isManager,
                'categories' => [],
                'subcategories' => [],
                'error_message' => $_SESSION['error_message'] ?? null,
                'success_message' => $_SESSION['success_message'] ?? null
            ];
    
            // Gestion des catégories selon le rôle
            if ($isAdmin) {
                $templateVars['categories'] = $this->stockModel->getAllCategories();
                $templateVars['subcategories'] = $this->stockModel->getAllSubcategories();
            } elseif ($isManager) {
                $templateVars['categories'] = [
                    ['id' => $store['product_type_id'], 'name' => $store['product_type']]
                ];
                $templateVars['subcategories'] = $this->stockModel->getSubcategoriesByMainCategory($store['product_type']);
            }
    
            // Nettoyage des messages de session après utilisation
            unset($_SESSION['error_message'], $_SESSION['success_message']);
    
            // Rendu du template
            echo $this->render('store/store.twig', $templateVars);
    
        } catch (\Exception $e) {
            error_log("Erreur lors du chargement des stocks: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors du chargement des stocks";
            $this->redirect('error');
        }
    }



    public function showAddStockForm() {
        try {
            // Vérification des permissions
            $this->checkManagerPermission();
        
            $store = $this->storeModel->getStoreById($_SESSION['store_id']);
            if (!$store) {
                throw new \Exception("Magasin non trouvé");
            }
    
            // Vérification des données du magasin
            if (!isset($store['product_type_id']) || !isset($store['product_type'])) {
                throw new \Exception("Configuration du magasin incomplète");
            }
    
            // Préparer les variables pour le template
            $templateVars = [
                'pageTitle' => 'Ajouter un produit - Stock O\' CESI',
                'current_page' => 'add-stock',
                'session' => $_SESSION,
                'store' => $store,
                'isAdmin' => $_SESSION['user_role'] === 'Admin',
                'error_message' => $_SESSION['error_message'] ?? null,
                'success_message' => $_SESSION['success_message'] ?? null,
                'categories' => [],
                'subcategories' => []
            ];
    
            // Gestion des catégories selon le rôle
            if ($_SESSION['user_role'] === 'Admin') {
                $templateVars['categories'] = $this->stockModel->getAllCategories();
                $templateVars['subcategories'] = $this->stockModel->getAllSubcategories();
            } else {
                // Vérification supplémentaire pour le Manager
                if (!$store['product_type_id'] || !$store['product_type']) {
                    throw new \Exception("Type de produit non défini pour ce magasin");
                }
    
                $templateVars['categories'] = [
                    ['id' => $store['product_type_id'], 'name' => $store['product_type']]
                ];
                $templateVars['subcategories'] = $this->stockModel->getSubcategoriesByMainCategory($store['product_type']);
            }
    
            // Rendu du template
            echo $this->render('stock/add-stock.twig', $templateVars);
        
        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage du formulaire d'ajout: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors du chargement du formulaire";
            $this->redirect('stock');
        }
    }

    public function showEditStock($stockId) {
        $this->checkManagerPermission();
        
        try {
            $store = $this->storeModel->getStoreById($_SESSION['store_id']);
            $stock = $this->stockModel->getStockById($stockId);

            if (!$stock) {
                throw new \Exception("Produit non trouvé");
            }

            $templateVars = [
                'pageTitle' => 'Modifier un produit',
                'stock' => $stock,
                'store' => $store,
                'session' => $_SESSION
            ];

            if ($_SESSION['user_role'] === 'Admin') {
                $templateVars['categories'] = $this->stockModel->getAllCategories();
                $templateVars['subcategories'] = $this->stockModel->getAllSubcategories();
                $templateVars['isAdmin'] = true;
            } else {
                $templateVars['subcategories'] = $this->stockModel->getSubcategoriesByMainCategory($store['product_type']);
                $templateVars['isAdmin'] = false;
            }

            echo $this->render('stock/edit-stock.twig', $templateVars);
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Erreur lors du chargement du produit";
            $this->redirect('stock');
        }
    }

    public function recordStockMovement() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('stock');
            return;
        }

        try {
            $data = $this->validateMovementData($_POST);
            $success = ($data['type'] === 'entry') ? 
                $this->stockModel->addStockQuantity($data['stock_id'], $data['quantity']) :
                $this->stockModel->removeStockQuantity($data['stock_id'], $data['quantity']);

            if ($success) {
                $this->stockModel->recordMovement(
                    $data['stock_id'],
                    $data['quantity'],
                    $data['type'],
                    $data['reason'],
                    $_SESSION['user_id']
                );
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => $data['type'] === 'entry' ? 'Entrée de stock' : 'Sortie de stock',
                    'details' => "{$data['type']} - Produit ID:{$data['stock_id']}, Quantité:{$data['quantity']}, Raison:{$data['reason']}",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $_SESSION['success_message'] = "Mouvement de stock enregistré avec succès";
            } else {
                $_SESSION['error_message'] = "Échec de l'enregistrement du mouvement";
            }
        } catch (StockException $e) {
            $_SESSION['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            error_log("Erreur lors du mouvement de stock: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'enregistrement du mouvement";
        }
        $this->redirect('stock');
    }

    private function validateMovementData(array $data): array {
        if (empty($data)) {
            throw new StockException("Aucune donnée reçue pour le mouvement");
        }
        $stockId = $this->validateNumericInput($data['stock_id'] ?? 0, 'ID du stock', 1);
        $quantity = $this->validateNumericInput($data['quantity'] ?? 0, 'quantité', 1);
        $type = $this->validateMovementType($data['movement_type'] ?? '');
        $reason = $this->validateInput($data['reason'] ?? '', 'raison');
        return [
            'stock_id' => $stockId,
            'quantity' => $quantity,
            'type' => $type,
            'reason' => $reason
        ];
    }

    private function validateMovementType(string $type): string {
        if (!in_array($type, ['entry', 'exit'])) {
            throw new StockException("Type de mouvement invalide");
        }
        return $type;
    }

    private function validateInput(string $value, string $fieldName): string {
        $value = trim($value);
        if (empty($value)) {
            throw new StockException("Le champ $fieldName est requis");
        }
        return $value;
    }

    private function validateNumericInput($value, string $fieldName, float $min = 0): float {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $min) {
            throw new StockException("La valeur du champ $fieldName est invalide");
        }
        return $value;
    }

    private function checkManagerPermission(): void {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les permissions nécessaires.";
            $this->redirect('stock');
            exit;
        }
    }
}