<?php
require_once ROOT_PATH . '/Src/config/TwigConfig.php';

class controller {
    protected $twig;
    
    public function __construct() {
        try {
            $this->twig = TwigConfig::getInstance()->getTwig();
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'initialisation de Twig: ' . $e->getMessage());
            die('Erreur lors de l\'initialisation de Twig: ' . $e->getMessage());
        }
    }
    
    protected function render($view, $data = []) {
        $templatePath = $this->getTemplatePath($view);
        
        try {
            return $this->twig->render($templatePath, $data);
        } catch (\Twig\Error\LoaderError $e) {
            error_log('Erreur Twig: ' . $e->getMessage());
            return 'Erreur: Template non trouvé - ' . $templatePath;
        } catch (\Exception $e) {
            error_log('Erreur: ' . $e->getMessage());
            return 'Erreur lors du rendu du template: ' . $e->getMessage();
        }
    }
    
    private function getTemplatePath($view) {
        if (strpos($view, '/') !== false) {
            return $view . '.twig';
        } 
        else if ($view === 'offres' || $view === 'offre_details' || $view === 'creer-offre' || $view === 'confirmation_candidature' || $view === 'mes_candidatures') {
            return 'offre/' . $view . '.twig';
        } else if ($view === 'Entreprises' || $view === 'entreprise_details') {
            return 'entreprise/' . $view . '.twig';
        } else {
            return $view . '/' . $view . '.twig';
        }
    }
    
    protected function hasPermission($permissionCode) {
        require_once ROOT_PATH . '/Src/Models/UtilisateurModel.php';
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userModel = new utilisateurmodel();
        return $userModel->hasPermission($_SESSION['user_id'], $permissionCode);
    }
    
    protected function checkPageAccess($permissionCode) {
        if (!$this->hasPermission($permissionCode)) {
            $this->redirect('accueil');
        }
    }
    
    protected function redirect($route, $params = []) {
        // Utiliser des URLs propres si possible
        $useCleanUrls = true;
        
        if ($useCleanUrls) {
            $url = '/StageLinkV1/' . $route;
            
            // Ajouter les paramètres s'il y en a
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            // Fallback vers l'ancien format d'URL
            $url = 'index.php?route=' . $route;
            
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $url .= '&' . $key . '=' . urlencode($value);
                }
            }
        }
        
        header('Location: ' . $url);
        exit();
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
