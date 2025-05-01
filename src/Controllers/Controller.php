<?php

namespace App\Controllers;

use App\Models\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller {
    protected $db;
    protected $templateEngine;

    public function __construct() {
        // Initialiser la connexion à la base de données
        $this->db = Database::getInstance()->getConnection();

        // Initialiser le moteur de templates Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->templateEngine = new Environment($loader, [
            'cache' => false, // Désactiver le cache pour le développement
            'debug' => true,  // Activer le mode debug
        ]);
    }

    /**
     * Redirige vers une URL donnée.
     *
     * @param string $url L'URL vers laquelle rediriger.
     */
    public function redirect($url) {
        if (!headers_sent()) {
            header("Location: $url");
            exit;
        } else {
            echo "<script>window.location.href='$url';</script>";
            exit;
        }
    }

    /**
     * Rend un template Twig avec des données.
     *
     * @param string $template Le nom du template (sans extension .twig).
     * @param array $data Les données à passer au template.
     * @return string Le rendu du template.
     * @throws \Exception Si le moteur de template n'est pas configuré.
     */
    protected function render($template, $data = []) {
        if ($this->templateEngine === null) {
            throw new \Exception("Le moteur de template n'est pas configuré.");
        }

        try {
            return $this->templateEngine->render($template . '.twig', $data);
        } catch (\Twig\Error\LoaderError $e) {
            throw new \Exception("Erreur de chargement du template : " . $e->getMessage());
        } catch (\Twig\Error\RuntimeError $e) {
            throw new \Exception("Erreur d'exécution du template : " . $e->getMessage());
        } catch (\Twig\Error\SyntaxError $e) {
            throw new \Exception("Erreur de syntaxe dans le template : " . $e->getMessage());
        }
    }
}