<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Exceptions\StockException;

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
    
        try {
            $stocks = $this->stockModel->getStocksByEntreprise($_SESSION['store_id']);
            $lowStockProducts = $this->stockModel->getLowStockProducts($_SESSION['store_id']);
            $categories = $this->stockModel->getAllCategories();
    
            // Debug des catégories
            error_log('Categories récupérées : ' . print_r($categories, true));
    
            // Correction ici : echo au lieu de return
            echo $this->render('entreprises/store.twig', [
                'pageTitle' => 'Gestion des stocks - Stock O\' CESI',
                'current_page' => 'stock',
                'products' => $stocks,
                'lowStockProducts' => $lowStockProducts,
                'session' => $_SESSION,
                'canManageStock' => in_array($_SESSION['user_role'], ['Admin', 'Manager']),
                'categories' => $categories,
                'debug' => true
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors du chargement des stocks: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors du chargement des stocks";
            $this->redirect('error');
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
            error_log('Données du mouvement : ' . print_r($data, true));
            
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
                
                $this->sendJsonResponse(true, "Mouvement de stock enregistré avec succès");
            } else {
                throw new StockException("Échec de l'enregistrement du mouvement");
            }
        } catch (StockException $e) {
            $this->sendJsonResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendJsonResponse(false, "Une erreur est survenue lors de l'enregistrement du mouvement");
        }
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

    private function sendJsonResponse(bool $success, string $message, array $data = []): void {
        $response = array_merge([
            'success' => $success,
            'message' => $message
        ], $data);
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    private function checkManagerPermission(): void {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            throw new StockException("Accès refusé. Vous n'avez pas les permissions nécessaires.");
        }
    }
}