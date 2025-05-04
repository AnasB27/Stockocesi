<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Exceptions\StockException;

class DeleteStockController extends Controller {
    private $stockModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
    }

    public function deleteStock() {
        $this->checkManagerPermission();
    
        $input = json_decode(file_get_contents('php://input'), true);
        $stockId = $input['stock_id'] ?? null;
        $quantity = $input['quantity'] ?? null;
    
        if (!$stockId || !$quantity || $quantity <= 0) {
            $this->sendJsonResponse(false, "Paramètres invalides");
        }
    
        $stock = $this->stockModel->getStockById($stockId);
        if (!$stock) {
            $this->sendJsonResponse(false, "Stock non trouvé");
        }
    
        if ($quantity >= $stock['quantite']) {
            // Suppression totale
            $success = $this->stockModel->deleteStock($stockId);
            $msg = "Produit supprimé du stock";
            $removedQty = $stock['quantite'];
        } else {
            // Suppression partielle
            $success = $this->stockModel->removeStockQuantity($stockId, $quantity);
            $msg = "Quantité supprimée du stock";
            $removedQty = $quantity;
        }
    
        if ($success) {
            // Enregistrement du mouvement de sortie dans l'historique
            $this->stockModel->recordMovement(
                $stockId,
                $removedQty,
                'exit',
                'Suppression de stock',
                $_SESSION['user_id']
            );
    
            $this->logModel->addLog([
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'action' => 'Suppression de stock',
                'details' => "Suppression de $removedQty unité(s) du produit ID:$stockId",
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $this->sendJsonResponse(true, $msg);
        } else {
            $this->sendJsonResponse(false, "Erreur lors de la suppression");
        }
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