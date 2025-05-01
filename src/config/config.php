<?php
require_once dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$rootPath = dirname(dirname(dirname(__FILE__))); 
$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->safeLoad();

$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'StageLink';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
?>
