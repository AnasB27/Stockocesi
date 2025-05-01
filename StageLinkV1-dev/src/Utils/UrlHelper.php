<?php

class urlhelper {
    /**
     * Génère une URL propre pour la route spécifiée
     * 
     * @param string $route Nom de la route
     * @param array $params Paramètres additionnels (optionnel)
     * @return string URL générée
     */
    public static function generateUrl($route, $params = []) {
        // Vérifier si les URLs propres sont activées
        $useCleanUrls = true;
        
        if ($useCleanUrls) {
            $baseUrl = '/StageLinkV1/' . $route;
            
            // Ajouter les paramètres s'il y en a
            if (!empty($params)) {
                $baseUrl .= '?' . http_build_query($params);
            }
            
            return $baseUrl;
        } else {
            // Fallback vers l'ancien format d'URL
            $url = 'index.php?route=' . $route;
            
            // Ajouter les paramètres s'il y en a
            if (!empty($params)) {
                $url .= '&' . http_build_query($params);
            }
            
            return $url;
        }
    }
}

