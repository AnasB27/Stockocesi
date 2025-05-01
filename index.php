<?php
/**
 * This is the router, the main entry point of the application.
 * It handles the routing and dispatches requests to the appropriate controller methods.
 */

require "vendor/autoload.php";

use App\Controllers\TaskController;

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => false // Disable cache for development
]);

// Get the URI from the request
$uri = $_GET['uri'] ?? '/';

// Initialize the controller
$controller = new TaskController();

// Route the request
switch ($uri) {
    case '/':
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