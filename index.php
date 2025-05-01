<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */

require "vendor/autoload.php";
// Définir la constante ROOT_PATH pour les chemins absolus
define('ROOT_PATH', __DIR__);

use App\Controllers\TaskController;
use App\Controllers\UserController;

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

// Route the request
switch ($uri) {
    case '':
        $taskController->welcomePage();
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
    case 'login':
        $userController->login();
        break;
    case 'logout':
        $userController->logout();
        break;
    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}