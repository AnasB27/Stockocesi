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
        // Call the welcomePage method of the TaskController
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
        // Call the loginPage method of the UserController
        $userController->loginPage();
        break;
    case 'logout':
        // Call the logout method of the UserController
        $userController->logout();
        break;
    default:
        // Return a 404 error for unknown routes
        http_response_code(404);
        echo '404 Not Found';
        break;
}