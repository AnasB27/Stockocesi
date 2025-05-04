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
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $_SESSION['error_message'] = "Veuillez vous connecter";
            $this->redirect('login');
            exit;
        }
    
        $isAdmin = $_SESSION['user_role'] === 'Admin';
        $isManager = $_SESSION['user_role'] === 'Manager';
        $canManageStock = $isAdmin || $isManager;
    
        try {
            // Pour les non admins, il faut un store_id
            if (!$isAdmin && !isset($_SESSION['store_id'])) {
                throw new \Exception("Aucun magasin associé à ce compte");
            }
    
            $store = isset($_SESSION['store_id']) ? $this->storeModel->getStoreById($_SESSION['store_id']) : null;
    
            // Récupérer les stocks
            if ($isAdmin) {
                // L'admin voit tous les stocks de toutes les entreprises
                $stocks = $this->stockModel->getStocksByEntreprise(null, true);
                $lowStockProducts = $this->stockModel->getAllLowStockProducts();
            } else {
                // Manager/Employé : stocks du magasin courant
                $stocks = $this->stockModel->getStocksByEntreprise($_SESSION['store_id']);
                $lowStockProducts = $this->stockModel->getLowStockProducts($_SESSION['store_id']);
            }
    
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
                'categories' => $canManageStock ? $this->stockModel->getAllCategories() : [],
                'subcategories' => $canManageStock ? $this->stockModel->getAllSubcategories() : [],
                'error_message' => $_SESSION['error_message'] ?? null,
                'success_message' => $_SESSION['success_message'] ?? null
            ];
    
            unset($_SESSION['error_message'], $_SESSION['success_message']);
            echo $this->render('store/store', $templateVars);
    
        } catch (\Exception $e) {
            error_log("Erreur lors du chargement des stocks: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors du chargement des stocks";
            $this->redirect('login');
        }
    }

    public function showAddStockForm() {
        try {
            $isAdmin = $_SESSION['user_role'] === 'Admin';
            $isManager = $_SESSION['user_role'] === 'Manager';
            $canManageStock = $isAdmin || $isManager;

            if (!$isAdmin && !isset($_SESSION['store_id'])) {
                throw new \Exception("Aucun magasin associé à ce compte");
            }
            if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
                throw new \Exception("Accès refusé");
            }
        
            $store = isset($_SESSION['store_id']) ? $this->storeModel->getStoreById($_SESSION['store_id']) : null;
            
        
            if ($isAdmin) {
                $categories = $this->stockModel->getAllCategories();
                $subcategories = $this->stockModel->getAllSubcategories();
            } else {
                $productType = $store['product_type'] ?? null;
                $categories = $this->stockModel->getCategoriesByType($productType);

                $mainCategory = !empty($categories) ? $categories[0]['id'] : null;
                $subcategories = $mainCategory ? $this->stockModel->getSubcategoriesByMainCategory($mainCategory) : [];
            }
        
            $templateVars = [
                'pageTitle' => 'Ajouter un produit - Stock O\' CESI',
                'current_page' => 'add-stock',
                'session' => $_SESSION,
                'store' => $store,
                'isAdmin' => $isAdmin,
                'error_message' => $_SESSION['error_message'] ?? null,
                'success_message' => $_SESSION['success_message'] ?? null,
                'categories' => $categories,
                'subcategories' => $subcategories
            ];
        
            unset($_SESSION['error_message'], $_SESSION['success_message']);
        
            echo $this->render('stock/add-stock', $templateVars);
        
        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage du formulaire d'ajout: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors du chargement du formulaire";
            header('Location: /stockocesi/stock');
            exit;
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


    public function getGeneralStockHistory() {
        if (!isset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['store_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
        $storeId = $_SESSION['store_id'];
        $history = $this->stockModel->getGeneralStockHistory($storeId);
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    public function clearStockHistory() {
        // Autorisation : seulement Admin ou Manager
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
    
        try {
            $result = $this->stockModel->clearStockHistory();
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression de l'historique du stock : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        exit;
    }

    private function checkManagerPermission(): void {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les permissions nécessaires.";
            header('Location: /stockocesi/stock');
            exit;
        }
    }


}