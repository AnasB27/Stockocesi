<?php
namespace App\Models;

use PDO;
use PDOException;
use Dotenv\Dotenv;


class Database {
    private static $instance = null;
    private $conn;


    private function __construct() {

        $rootPath = dirname(dirname(dirname(__FILE__))); 
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // configuration base de données
        $servername = $_ENV['DB_HOST'] ?? 'localhost';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        $dbname = $_ENV['DB_NAME'] ?? 'stock_management';

        try {
            // connection PDO
            $this->conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     *
     * @return Database singleton instance.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     *
     * @return PDO 
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Prepare an SQL statement.
     *
     * @param string $sql The SQL query to prepare.
     * @return PDOStatement The prepared statement.
     */
    

    /**
     * Execute a direct SQL query.
     *
     * @param string $sql The SQL query to execute.
     * @return PDOStatement The result of the query.
     */
    

    /**
     * Get the ID of the last inserted row.
     *
     * @return string The ID of the last inserted row.
     */
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
}