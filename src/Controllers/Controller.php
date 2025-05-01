<?php

namespace App\Controllers;

use App\Models\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller {
    protected $db;
    protected $templateEngine;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->templateEngine = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
    }

    public function redirect($url) {
        header("Location: $url");
        exit;
    }

    protected function render($template, $data = []) {
        if ($this->templateEngine === null) {
            throw new \Exception("Le moteur de template n'est pas configuré.");
        }

        return $this->templateEngine->render($template . '.twig', $data);
    }
}