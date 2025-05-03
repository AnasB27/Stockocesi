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

    public function deleteStock($stockId) {
        $this->checkManagerPermission();
    
        try {
            $stock = $this->stockModel->getStockById($stockId);
            if (!$stock) {
                throw new StockException('Stock non trouvé');
            }
    
            if ($this->stockModel->deleteStock($stockId)) {
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Suppression de stock',
                    'details' => "Suppression du produit ID:$stockId",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $this->sendJsonResponse(true, "Stock supprimé avec succès");
            }
        } catch (StockException $e) {
            $this->sendJsonResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendJsonResponse(false, "Une erreur est survenue lors de la suppression");
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