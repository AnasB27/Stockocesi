<?php

namespace App\Controllers;

use App\Models\LogModel;

class LogController extends Controller {
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->logModel = new LogModel();
    }

    /**
     * Affiche la page du journal d'activité
     */
    public function showLog() {
        $this->ensureAdmin();
        
        $logs = $this->logModel->getAllLogs();
        
        echo $this->render('admin/log', [
            'pageTitle' => 'Journal d\'activité',
            'current_page' => 'log',
            'logs' => $logs
        ]);
    }

    /**
     * Efface tout l'historique des logs
     */
    public function clearLogs() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
        header('Content-Type: application/json');
    
        // Vérification admin avant tout
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Accès refusé'
            ]);
            exit;
        }
    
        try {
            if ($this->logModel->clearLogs()) {
                echo json_encode([
                    'success' => true,
                    'message' => "L'historique a été effacé avec succès."
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Une erreur est survenue lors de l'effacement de l'historique."
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    private function ensureAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
            // Si c'est une requête AJAX (JSON)
            if (
                isset($_SERVER['HTTP_ACCEPT']) &&
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
            ) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                exit;
            } else {
                $this->redirect('login');
            }
        }
    }

    /**
     * Enregistre automatiquement une action dans les logs
     */
    public static function logAction($action, $details = '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $logModel = new LogModel();
        
        return $logModel->addLog([
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? 'System',
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
}