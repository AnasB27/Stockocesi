<?php

namespace App\Controllers;

use App\Models\StockModel;
use App\Models\LogModel;
use App\Models\StoreModel;
use App\Exceptions\StockException;

class AddStockController extends Controller {
    private $stockModel;
    private $logModel;
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
        $this->logModel = new LogModel();
        $this->storeModel = new StoreModel();
    }


    public function addStock() {
        try {
            $this->checkManagerPermission();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new StockException("Méthode non autorisée");
            }

            // Récupération des données du formulaire
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'quantity' => $_POST['quantity'] ?? 0,
                'price' => $_POST['price'] ?? 0,
                'alert_threshold' => $_POST['alert_threshold'] ?? 5,
                'category_id' => $_POST['category_id'] ?? null,
                'subcategory_id' => $_POST['subcategory_id'] ?? null,
            ];

            // Récupération du type de magasin
            $store = $this->storeModel->getStoreById($_SESSION['store_id']);
            if (!$store) {
                throw new StockException("Magasin non trouvé");
            }

            // Validation des données avec le contexte du magasin
            $validatedData = $this->validateStockData($data, $store['product_type']);

            // Ajout des données du magasin
            $validatedData['store_id'] = $_SESSION['store_id'];

            // Ajout en base de données
            if ($this->stockModel->addStock($validatedData)) {
                // Enregistrement du log
                $this->logModel->addLog([
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name'],
                    'action' => 'Ajout de produit',
                    'details' => "Ajout du produit {$validatedData['name']} " .
                                "(Qté: {$validatedData['quantity']}, " .
                                "Prix: {$validatedData['price']}€)",
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                $_SESSION['success_message'] = "Produit ajouté avec succès";
                header('Location: /stockocesi/stock');
                exit;
            } else {
                throw new StockException("Échec de l'ajout du produit");
            }

        } catch (StockException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /stockocesi/stock/add-stock');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur système lors de l'ajout du stock: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'ajout du produit";
            header('Location: /stockocesi/stock/add-stock');
            exit;
        }
    }

    private function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new StockException("Données JSON invalides");
            }
            return $data;
        }
        return $_POST;
    }

    private function validateStockData(array $data, string $storeType): array {
        if (empty($data)) {
            throw new StockException("Aucune donnée reçue");
        }

        // Validation des champs obligatoires
        $validated = [
            'name' => $this->validateInput($data['name'] ?? '', 'nom'),
            'quantity' => $this->validateNumericInput($data['quantity'] ?? 0, 'quantité', 0),
            'price' => $this->validateNumericInput($data['price'] ?? 0, 'prix', 0),
            'alert_threshold' => $this->validateNumericInput($data['alert_threshold'] ?? 5, 'seuil d\'alerte', 1)
        ];

        // Validation de la catégorie si fournie
        if (!empty($data['category_id'])) {
            $categoryId = (int)$data['category_id'];
            $category = $this->stockModel->getCategoryById($categoryId);
            
            if (!$category || $category['store_type'] !== $storeType) {
                throw new StockException("Catégorie invalide pour ce type de magasin");
            }
            $validated['category_id'] = $categoryId;
        }

        // Validation de la sous-catégorie si fournie
        if (!empty($data['subcategory_id'])) {
            $subcategoryId = (int)$data['subcategory_id'];
            $subcategory = $this->stockModel->getSubcategoryById($subcategoryId);
            
            if (!$subcategory || $subcategory['main_category'] !== $storeType) {
                throw new StockException("Sous-catégorie invalide pour ce type de magasin");
            }
            $validated['subcategory_id'] = $subcategoryId;
        }

        return $validated;
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