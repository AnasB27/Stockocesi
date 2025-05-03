<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\LogModel;

class UserController extends Controller {
    private $userModel;
    private $logModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->logModel = new LogModel();
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     * Si non, redirige vers la page de connexion.
     */
    private function ensureAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
    }

    /**
     * Affiche la page de connexion.
     */
    public function loginPage() {
        echo $this->render('account/login', [
            'pageTitle' => 'Connexion',
            'current_page' => 'login',
            'error' => ''
        ]);
    }

    /**
     * Gère la connexion de l'utilisateur.
     */

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['store_id'] = $user['store_id'] ?? null;

                // Log de connexion
                $this->logModel->addLog([
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'action' => 'Connexion',
                    'details' => 'Connexion réussie',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                // Redirection en fonction du rôle
                if ($user['role'] === 'Admin') {
                    $this->redirect('accueil');
                } else {
                    // Pour les employés et gestionnaires
                    $this->redirect('accueil');
                }
            } else {
                echo $this->render('account/login', [
                    'pageTitle' => 'Connexion',
                    'current_page' => 'login',
                    'error' => 'Email ou mot de passe incorrect'
                ]);
            }
        } else {
            $this->loginPage();
        }
    }
    /**
     * Gère la déconnexion de l'utilisateur.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Supprimer toutes les données de session
        $_SESSION = [];

        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Détruire la session
        session_destroy();

        // Rediriger vers la page de connexion
        $this->redirect('login');
    }

    /**
     * Exemple de méthode nécessitant une authentification.
     * Affiche les employés ou gestionnaires liés à un magasin.
     *
     * @param int $storeId L'ID du magasin.
     */
    public function showUsersByStore($storeId) {
        $this->ensureAuthenticated(); // Vérifie si l'utilisateur est connecté

        $users = $this->userModel->getUsersByStore($storeId);

        echo $this->render('store/users', [
            'users' => $users,
            'pageTitle' => 'Utilisateurs du magasin'
        ]);
    }

    /**
     * Exemple de méthode nécessitant une authentification.
     * Assigne un utilisateur (employé ou gestionnaire) à un magasin.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param int $storeId L'ID du magasin.
     */
    public function assignUserToStore($userId, $storeId) {
        $this->ensureAuthenticated(); // Vérifie si l'utilisateur est connecté

        if ($this->userModel->assignUserToStore($userId, $storeId)) {
            echo "Utilisateur assigné au magasin avec succès.";
        } else {
            echo "Erreur lors de l'assignation de l'utilisateur au magasin.";
        }
    }
}