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
            'cache' => false,
            'debug' => true,
        ]);
    }

    /**
     * Redirige vers une route donnée avec des paramètres optionnels.
     *
     * @param string $route La route vers laquelle rediriger
     * @param array $params Paramètres optionnels à ajouter à l'URL
     */
    protected function redirect($route, $params = []) {
        // Utiliser des URLs propres
        $useCleanUrls = true;
        
        if ($useCleanUrls) {
            // Construire l'URL avec le préfixe de l'application
            $url = '/stockocesi/' . $route;
            
            // Ajouter les paramètres s'il y en a
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            // Format d'URL classique avec index.php
            $url = 'index.php?route=' . $route;
            
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $url .= '&' . urlencode($key) . '=' . urlencode($value);
                }
            }
        }

        if (!headers_sent()) {
            header('Location: ' . $url);
            exit();
        } else {
            echo '<script>window.location.href="' . $url . '";</script>';
            exit();
        }
    }

    /**
     * Rend un template Twig avec des données.
     *
     * @param string $template Le nom du template (sans extension .twig)
     * @param array $data Les données à passer au template
     * @return string Le rendu du template
     * @throws \Exception Si le moteur de template n'est pas configuré
     */
    protected function render($template, $data = []) {
        if ($this->templateEngine === null) {
            throw new \Exception("Le moteur de template n'est pas configuré.");
        }

        try {
            // Ajouter les informations de session aux données du template
            if (session_status() === PHP_SESSION_ACTIVE) {
                $data['session'] = $_SESSION;
            }

            // Ajouter le chemin de base pour les assets
            $data['base_url'] = '/stockocesi';
            
            // Afficher directement le contenu
            echo $this->templateEngine->render($template . '.twig', $data);
            return null;
        } catch (\Twig\Error\LoaderError $e) {
            // Log l'erreur et afficher un message plus clair
            error_log($e->getMessage());
            die("Erreur de chargement du template '$template.twig'. Vérifiez que le fichier existe dans le dossier templates.");
        } catch (\Twig\Error\RuntimeError | \Twig\Error\SyntaxError $e) {
            error_log($e->getMessage());
            die("Erreur dans le template '$template.twig': " . $e->getMessage());
        }
    }
}