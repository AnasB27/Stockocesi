<?php
namespace App\Controllers;
use App\Models\UserModel;
use App\Controllers\Controller;


class UserController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    public function loginPage() {
        echo $this->templateEngine->render('/stockocesi/templates/account/login.twig', [
            'pageTitle' => 'Connexion'
        ]);
    }
    /**
     * Gère la connexion de l'utilisateur.
     */
    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si l'utilisateur est déjà connecté, redirigez-le vers la page d'accueil
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/accueil');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Authentification de l'utilisateur
            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                // Stocker les informations de l'utilisateur dans la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role']; // Exemple : 'Admin', 'Manager', 'Employee'
                $_SESSION['store_id'] = $user['store_id'] ?? null; // Magasin lié (si applicable)

                // Rediriger en fonction du rôle
                if ($user['role'] === 'Admin') {
                    $this->redirect('/admin/log');
                } elseif ($user['role'] === 'Manager' || $user['role'] === 'Employee') {
                    $this->redirect('/store/accueil');
                } else {
                    $this->redirect('/accueil');
                }
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }

        // Afficher la page de connexion
        echo $this->render('login', [
            'error' => $error,
            'pageTitle' => 'Connexion - Stock Management'
        ]);
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
        $this->redirect('/login');
    }

    /**
     * Affiche les employés ou gestionnaires liés à un magasin.
     *
     * @param int $storeId L'ID du magasin.
     */
    public function showUsersByStore($storeId) {
        $users = $this->userModel->getUsersByStore($storeId);

        echo $this->render('store/users', [
            'users' => $users,
            'pageTitle' => 'Utilisateurs du magasin'
        ]);
    }

    /**
     * Assigne un utilisateur (employé ou gestionnaire) à un magasin.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param int $storeId L'ID du magasin.
     */
    public function assignUserToStore($userId, $storeId) {
        if ($this->userModel->assignUserToStore($userId, $storeId)) {
            echo "Utilisateur assigné au magasin avec succès.";
        } else {
            echo "Erreur lors de l'assignation de l'utilisateur au magasin.";
        }
    }
}