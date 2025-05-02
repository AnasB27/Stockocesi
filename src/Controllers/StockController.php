<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;

class StockController extends Controller {
    private $stockModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
    }

    
   
    public function showStock() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
    
        $stocks = $this->stockModel->getStocksByEntreprise($_SESSION['store_id']);
        $lowStockProducts = $this->stockModel->getLowStockProducts($_SESSION['store_id']);
    
        return $this->render('store/store', [ // Correction du chemin du template
            'pageTitle' => 'Gestion des stocks - Stock O\' CESI',
            'current_page' => 'stock',
            'products' => $stocks,
            'lowStockProducts' => $lowStockProducts,
            'session' => $_SESSION,
            'canManageStock' => in_array($_SESSION['user_role'], ['Admin', 'Manager']),
            'categories' => $this->stockModel->getAllCategories() // Ajout des catégories
        ]);
    }
    
    public function addStock() {
        $this->checkManagerPermission();
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = ['success' => false, 'message' => ''];
        
            // Récupération et validation des données
            $nom = $_POST['name'] ?? '';
            $quantite = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $prix = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $alertThreshold = isset($_POST['alert_threshold']) ? (int)$_POST['alert_threshold'] : 5;
        
            // Validation des données
            if (empty($nom) || $quantite < 0 || $prix < 0 || $categoryId <= 0) {
                $response['message'] = "Données invalides";
                echo json_encode($response);
                exit;
            }
                
            if ($this->stockModel->addStock($nom, $quantite, $prix, $_SESSION['store_id'], $categoryId, $alertThreshold)) {
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Ajout de produit',
                    'details' => "Ajout du produit $nom (Qté: $quantite, Prix: {$prix}€)",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $response['success'] = true;
                $response['message'] = "Produit ajouté avec succès";
            } else {
                $response['message'] = "Erreur lors de l'ajout du produit";
            }
            
            echo json_encode($response);
            exit;
        }
    }
    
    public function deleteStock($stockId) {
        $this->checkManagerPermission();
    
        // Vérifier si le stock existe
        $stock = $this->stockModel->getStockById($stockId);
        if (!$stock) {
            echo json_encode(['success' => false, 'message' => 'Stock non trouvé']);
            exit;
        }
    
        if ($this->stockModel->deleteStock($stockId)) {
            $this->logModel->addLog([
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'action' => 'Suppression de stock',
                'details' => "Suppression du produit ID:$stockId",
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        exit;
    }
    public function updateStock() {
        $this->checkManagerPermission();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Initialisation des variables avec des valeurs par défaut
            $stockId = 0;
            $prix = 0.0;
            $quantite = 0;

            // Récupération des valeurs POST avec vérification
            if (isset($_POST['stock_id']) && !empty($_POST['stock_id'])) {
                $stockId = (int)$_POST['stock_id'];
            }
            if (isset($_POST['quantity'])) {
                $quantite = (int)$_POST['quantity'];
            }
            if (isset($_POST['price'])) {
                $prix = (float)$_POST['price'];
            }

            // Validation des données
            if ($stockId <= 0 || $quantite < 0 || $prix < 0) {
                $_SESSION['error_message'] = "Données invalides";
                $this->redirect('stock');
                return;
            }

            // Vérifier si le stock existe
            $stock = $this->stockModel->getStockById($stockId);
            if (!$stock) {
                $_SESSION['error_message'] = "Stock non trouvé";
                $this->redirect('stock');
                return;
            }
            
            if ($this->stockModel->updateStock($stockId, $quantite, $prix)) {
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Mise à jour de stock',
                    'details' => "Mise à jour du stock ID:$stockId (Qté: $quantite, Prix: {$prix}€)",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $_SESSION['success_message'] = "Stock mis à jour avec succès";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la mise à jour du stock";
            }
            $this->redirect('stock');
        }
    }
    private function checkManagerPermission() {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé. Vous n'avez pas les permissions nécessaires.";
            $this->redirect('stock');
            exit;
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

        $stockId = $_POST['stock_id'] ?? '';
        $quantity = (int)$_POST['quantity'] ?? 0;
        $type = $_POST['movement_type'] ?? '';
        $reason = $_POST['reason'] ?? '';

        if ($quantity <= 0) {
            $_SESSION['error_message'] = "La quantité doit être supérieure à 0";
            $this->redirect('stock');
            return;
        }

        $success = false;
        if ($type === 'entry') {
            $success = $this->stockModel->addStockQuantity($stockId, $quantity);
            $action = 'Entrée de stock';
        } elseif ($type === 'exit') {
            $success = $this->stockModel->removeStockQuantity($stockId, $quantity);
            $action = 'Sortie de stock';
        }

        if ($success) {
            $this->stockModel->recordMovement($stockId, $quantity, $type, $reason, $_SESSION['user_id']);
            
            $this->logModel->addLog([
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'action' => $action,
                'details' => "$action - Produit ID:$stockId, Quantité:$quantity, Raison:$reason",
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['success_message'] = "Mouvement de stock enregistré avec succès";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement du mouvement";
        }

        $this->redirect('stock');
    }
}