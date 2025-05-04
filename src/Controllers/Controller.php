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

        // Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->templateEngine = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
    }

    /**
     * Redirige vers une route donnée avec des paramètres optionnels.
     *
     * @param string $route 
     * @param array $params 
     */
    protected function redirect($route, $params = []) {
        
        $useCleanUrls = true;
        
        if ($useCleanUrls) {
          
            $url = '/stockocesi/' . $route;
            
            
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
     * @param string $template 
     * @param array $data 
     * @return string 
     * @throws \Exception Si le moteur de template n'est pas configuré
     */
    protected function render($template, $data = []) {
        if ($this->templateEngine === null) {
            throw new \Exception("Le moteur de template n'est pas configuré.");
        }

        try {
            
            if (session_status() === PHP_SESSION_ACTIVE) {
                $data['session'] = $_SESSION;
            }

          
            $data['base_url'] = '/stockocesi';
            
            
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