<?php
require_once ROOT_PATH . '/src/controllers/Controller.php';
require_once ROOT_PATH . '/src/models/UserModel.php';

class LoginController extends Controller {
    private $utilisateurModel;

    public function __construct() {
        parent::__construct();
        $this->utilisateurModel = new UtilisateurModel();
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
            $user = $this->utilisateurModel->authenticate($email, $password);

            if ($user) {
                // Stocker les informations de l'utilisateur dans la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_role'] = $user['role']; // Exemple : 'admin' ou 'user'

                // Rediriger vers la page d'accueil
                $this->redirect('/accueil');
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        }

        // Afficher la page de connexion
        echo $this->render('login', [
            'error' => $error,
            'pageTitle' => 'Connexion - Stock O\' CESI'
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

        
        session_destroy();

        
        $this->redirect('/accueil');
    }
}