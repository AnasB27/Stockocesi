<?php

require_once __DIR__ . '/../models/utilisateurmodel.php';

class Auth {
    public static function hasPermission($userId, $permissionCode) {
        $utilisateurmodel = new utilisateurmodel();
        return $utilisateurmodel->hasPermission($userId, $permissionCode);
    }
    
    public static function getUserPermissions($userId) {
        $utilisateurmodel = new utilisateurmodel();
        return $utilisateurmodel->getUserPermissions($userId);
    }
    
    public static function getUserRole($userId) {
        $utilisateurmodel = new utilisateurmodel();
        return $utilisateurmodel->getUserRole($userId);
    }
    
    public static function checkPageAccess($requiredPermission) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit();
        }
        
        if (!self::hasPermission($_SESSION['user_id'], $requiredPermission)) {
            header('Location: index.php?route=accueil');
            exit();
        }
    }
}

?>
