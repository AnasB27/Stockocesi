<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */

require "vendor/autoload.php";

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Définir la constante ROOT_PATH pour les chemins absolus
define('ROOT_PATH', __DIR__);

use App\Controllers\TaskController;
use App\Controllers\UserController;
use App\Controllers\LogController;
use App\Controllers\AddAccountController;
use App\Controllers\AddStoreController;
use App\Controllers\StoreController;
use App\Controllers\StockController;
use App\Controllers\AddStockController;
use App\Controllers\DeleteStockController;
use App\Controllers\UpdateStockController;

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the URI from the request
$uri = $_GET['uri'] ?? '/';

// Normalize the URI (remove leading/trailing slashes)
$uri = trim($uri, '/');

// Initialize the controllers
$taskController = new TaskController();
$userController = new UserController();
$logController = new LogController();
$addAccountController = new AddAccountController();
$addStoreController = new AddStoreController();
$storeController = new StoreController();
$stockController = new StockController();
$addStockController = new AddStockController();
$deleteStockController = new DeleteStockController();
$updateStockController = new UpdateStockController();

// Route the request
switch ($uri) {
    case '':
        $taskController->welcomePage();
        break;
    case 'accueil':
        $taskController->accueilPage();
        break;
    case 'add_task':
        $taskController->addTask();
        break;
    case 'check_task':
        $taskController->checkTask();
        break;
    case 'history':
        $taskController->logPage();
        break;
    case 'uncheck_task':
        $taskController->uncheckTask();
        break;
    case 'delete_task':
        $taskController->deleteTask();
        break;
    case 'about':
        $taskController->aboutPage();
        break;
    case 'stock':
        $stockController->showStock();
        break;
    case 'stock/add':
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $addStockController->addStock();
        } else {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/stock');
            exit;
        }
        break;
    case 'stock/delete':
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            if (isset($_POST['stock_id'])) {
                $deleteStockController->deleteStock($_POST['stock_id']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID du stock manquant']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        }
        break;
    case 'stock/update':
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $updateStockController->updateStock();
        } else {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/stock');
            exit;
        }
        break;
    case 'stock/movement':
        $stockController->recordStockMovement();
        break;
    case 'admin/add-account':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $addAccountController->addAccount();
        } else {
            $addAccountController->showAddAccount();
        }
        break;
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->login();
        } else {
            $userController->loginPage();
        }
        break;
    case 'logout':
        $userController->logout();
        break;
    case 'store':
        $storeController->showStorePage();
        break;
    case 'admin/log':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
            $logController->clearLogs();
        } else {
            $logController->showLog();
        }
        break;
    case 'admin/add-store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $addStoreController->addStore();
        } else {
            $addStoreController->showAddStore();
        }
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}