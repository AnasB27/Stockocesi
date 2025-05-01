<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */

require "vendor/autoload.php";

use App\Controllers\TaskController;

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the URI from the request
$uri = $_GET['uri'] ?? '/';

// Normalize the URI (remove leading/trailing slashes)
$uri = trim($uri, '/');

// Initialize the controller
$controller = new TaskController();

// Route the request
switch ($uri) {
    case '':
        // Call the welcomePage method of the controller
        $controller->welcomePage();
        break;
    case 'add_task':
        // Call the addTask method of the controller
        $controller->addTask();
        break;
    case 'check_task':
        // Call the checkTask method of the controller
        $controller->checkTask();
        break;
    case 'history':
        // Call the logPage method of the controller
        $controller->logPage();
        break;
    case 'uncheck_task':
        // Call the uncheckTask method of the controller
        $controller->uncheckTask();
        break;
    case 'delete_task':
        // Call the deleteTask method of the controller
        $controller->deleteTask();
        break;
    case 'about':
        // Call the aboutPage method of the controller
        $controller->aboutPage();
        break;
    default:
        // Return a 404 error for unknown routes
        http_response_code(404);
        echo '404 Not Found';
        break;
}