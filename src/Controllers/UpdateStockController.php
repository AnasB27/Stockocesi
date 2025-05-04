<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Exceptions\StockException;

class UpdateStockController extends Controller {
    private $stockModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
    }

    public function updateStock() {
        $this->checkManagerPermission();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('stock');
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $this->validateUpdateData($input);

            if ($this->stockModel->updateStock($data['stock_id'], [
                'name' => $data['name'],
                'description' => $data['description'],
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'alert_threshold' => $data['seuil_alerte']
            ])) {
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Mise à jour de stock',
                    'details' => "Mise à jour du stock ID:{$data['stock_id']} (Nom: {$data['name']}, Qté: {$data['quantity']}, Prix: {$data['price']}€)",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $this->sendJsonResponse(true, "Stock mis à jour avec succès");
            } else {
                $this->sendJsonResponse(false, "Erreur lors de la mise à jour du stock");
            }
        } catch (StockException $e) {
            $this->sendJsonResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendJsonResponse(false, "Une erreur est survenue lors de la mise à jour");
        }
    }

    private function validateUpdateData(array $data): array {
        $stockId = $this->validateNumericInput($data['stock_id'] ?? 0, 'ID du stock', 1);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $quantity = $this->validateNumericInput($data['quantite'] ?? 0, 'quantité', 0);
        $price = $this->validateNumericInput($data['prix'] ?? 0, 'prix', 0);
        $seuilAlerte = $this->validateNumericInput($data['seuil_alerte'] ?? 0, 'seuil d\'alerte', 0);
        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $subcategoryId = isset($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;

        if ($name === '') {
            throw new StockException("Le nom du produit est obligatoire");
        }

        return [
            'stock_id' => $stockId,
            'name' => $name,
            'description' => $description,
            'quantity' => $quantity,
            'price' => $price,
            'seuil_alerte' => $seuilAlerte,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId
        ];
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