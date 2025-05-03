<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Exceptions\StockException;

class AddStockController extends Controller {
    private $stockModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
    }

    public function addStock() {
        try {
            $this->checkManagerPermission();
        
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new StockException("Méthode non autorisée");
            }

            $data = $this->validateStockData($_POST);
            
            // Debug des données reçues
            error_log('Données reçues pour ajout : ' . print_r($data, true));
            
            if ($this->stockModel->addStock(
                $data['name'],
                $data['quantity'],
                $data['price'],
                $_SESSION['store_id'],
                $data['category_id'],
                $data['alert_threshold']
            )) {
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Ajout de produit',
                    'details' => "Ajout du produit {$data['name']} (Qté: {$data['quantity']}, Prix: {$data['price']}€)",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                $this->sendJsonResponse(true, "Produit ajouté avec succès");
            } else {
                throw new StockException("Échec de l'ajout du produit");
            }
        } catch (StockException $e) {
            error_log("Erreur lors de l'ajout du stock: " . $e->getMessage());
            $this->sendJsonResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            error_log("Erreur système lors de l'ajout du stock: " . $e->getMessage());
            $this->sendJsonResponse(false, "Une erreur est survenue lors de l'ajout du produit");
        }
    }

    private function validateStockData(array $data): array {
        if (empty($data)) {
            throw new StockException("Aucune donnée reçue");
        }

        $name = $this->validateInput($data['name'] ?? '', 'nom');
        $quantity = $this->validateNumericInput($data['quantity'] ?? 0, 'quantité', 0);
        $price = $this->validateNumericInput($data['price'] ?? 0, 'prix', 0);
        $categoryId = $this->validateNumericInput($data['category_id'] ?? 0, 'catégorie', 1);
        $alertThreshold = $this->validateNumericInput($data['alert_threshold'] ?? 5, 'seuil d\'alerte', 1);

        return [
            'name' => $name,
            'quantity' => $quantity,
            'price' => $price,
            'category_id' => $categoryId,
            'alert_threshold' => $alertThreshold
        ];
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