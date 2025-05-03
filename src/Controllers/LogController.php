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
        $this->ensureAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->logModel->clearLogs()) {
                // Log l'action d'effacement
                $this->logAction('CLEAR_LOGS', 'Effacement de l\'historique des logs');
                $_SESSION['success_message'] = "L'historique a été effacé avec succès.";
            } else {
                $_SESSION['error_message'] = "Une erreur est survenue lors de l'effacement de l'historique.";
            }
        }
        
        $this->redirect('admin/log');
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    private function ensureAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
            $this->redirect('login');
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