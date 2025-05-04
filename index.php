<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */

require "vendor/autoload.php";

// Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
]);


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

// Démarrer la session si elle n'est pas déjà démarrée (peut être origine de l'erreur d'affichage de stock)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the URI from the request 
$uri = $_GET['uri'] ?? '/';

// Normalize the URI (remove leading/trailing slashes)
$uri = trim($uri, '/');


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
    // --- Gestion des stocks ---
    case 'stock':
        $stockController->showStock();
        break;

    case 'stock/add-stock':
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/stock');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $addStockController->addStock();
        } else {
            $stockController->showAddStockForm();
        }
        break;

    case 'stock/delete':
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deleteStockController->deleteStock(); 
        } else {
            header('Location: /stockocesi/stock');
        }
        break;
    case 'stock/update':
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/stock');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updateStockController->updateStock();
        } else {
            header('Location: /stockocesi/stock');
        }
        break;

    case 'stock/movement':
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/stock');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stockController->recordStockMovement();
        } else {
            header('Location: /stockocesi/stock');
        }
        break;
    
    
    // --- Gestion des comptes ---
    case 'admin/add-account':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $addAccountController->addAccount();
        } else {
            $addAccountController->showAddAccount();
        }
        break;

    case 'admin/manage-accounts':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/login');
            exit;
        }
        $userController->manageAccounts();
        break;

    case (preg_match('/^admin\/delete-account\/(\d+)$/', $uri, $matches) ? true : false):
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
        $userController->deleteAccount($matches[1]);
        break;

    case (preg_match('/^admin\/edit-account\/(\d+)$/', $uri, $matches) ? true : false):
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/login');
            exit;
        }
        $userController->editAccount($matches[1]);
        break;

    // --- Authentification ---
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

    // --- Gestion des magasins ---
    case 'store':
        $storeController->showStorePage();
        break;

    case 'admin/log':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['action']) && $data['action'] === 'clear_logs') {
                $logController->clearLogs();
            } else {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Action non valide']);
            }
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

    case 'admin/manage-stores':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            header('Location: /stockocesi/login');
            exit;
        }
        $storeController->manageStores();
        break;

    case (preg_match('/^admin\/delete-store\/(\d+)$/', $uri, $matches) ? true : false):
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }
        $storeController->deleteStore($matches[1]);
        break;

        case (preg_match('/^admin\/edit-store\/(\d+)$/', $uri, $matches) ? true : false):
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
                $_SESSION['error_message'] = "Accès refusé";
                header('Location: /stockocesi/login');
                exit;
            }
            $storeController->editStore($matches[1]);
            break;
    
        case 'error':
            $_SESSION['error_message'] = $_SESSION['error_message'] ?? "Une erreur est survenue.";
            header('Location: /stockocesi/stock');
            exit;
        
        case 'stock/history-general':
            if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                exit;
            }
            $stockController->getGeneralStockHistory();
            break;
        
        
        case 'mentions':
            echo $twig->render('layout/mentions.twig', ['session' => $_SESSION]);
            break;
        
        case 'stock/clear-history':
            if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'Manager'])) {
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                exit;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $stockController->clearStockHistory();
            } else {
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            }
            break;
        default:
            http_response_code(404);
            echo '404 Not Found';
            break;
}