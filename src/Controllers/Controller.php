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
        // Initialiser la connexion à la base de données
        $this->db = Database::getInstance()->getConnection();

        // Initialiser Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->templateEngine = new Environment($loader, [
            'cache' => false, // Désactiver le cache pour le développement
        ]);
    }

    /**
     * Affiche la page d'accueil.
     */
    public function index() {
        echo $this->render('accueil/accueil', [
            'pageTitle' => "Accueil - Stock O' Cesi"
        ]);
    }

    /**
     * Gère les erreurs 404.
     */
    public function notFound() {
        http_response_code(404);
        echo $this->render('errors/404', [
            'pageTitle' => 'Page non trouvée'
        ]);
    }

    /**
     * Redirige vers une autre URL.
     *
     * @param string $url L'URL vers laquelle rediriger.
     */
    protected function redirect($url) {
        header("Location: $url");
        exit;
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