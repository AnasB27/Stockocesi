<?php

namespace App\Controllers;

use App\Models\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Controller : Un contrôleur générique pour gérer les fonctionnalités principales.
 */
class Controller {
    protected $db;
    protected $templateEngine;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Initialiser Twig avec le bon chemin vers les templates
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->templateEngine = new Environment($loader, [
            'cache' => false, // Désactiver le cache pour le développement
        ]);
    }

    /**
     * Rendu d'une vue avec Twig.
     *
     * @param string $template Le nom du fichier de template.
     * @param array $data Les données à passer au template.
     * @return string Le rendu HTML.
     */
    protected function render($template, $data = []) {
        if ($this->templateEngine === null) {
            throw new \Exception("Le moteur de template n'est pas configuré.");
        }

        return $this->templateEngine->render($template . '.twig', $data);
    }
}