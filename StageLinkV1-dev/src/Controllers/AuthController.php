<?php
require_once ROOT_PATH . '/src/controllers/controller.php';
require_once ROOT_PATH . '/src/models/utilisateurmodel.php';

class authcontroller extends controller {
    private $utilisateurmodel;
    
    public function __construct() {
        parent::__construct();
        $this->utilisateurmodel = new utilisateurmodel();
    }
    
    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->redirect('accueil');
        }
        
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['mot_de_passe'] ?? '';
            
            $user = $this->utilisateurmodel->authenticate($email, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                
                $role = $this->utilisateurmodel->getUserRole($user['id']);
                $_SESSION['user_role'] = $role['role_code'];
                
                $_SESSION['permissions'] = $this->utilisateurmodel->getUserPermissions($user['id']);
                
                $this->redirect('accueil');
            } else {
                $error = 'Email ou mot de passe incorrect';
            }
        }
        
        echo $this->render('login', [
            'error' => $error,
            'pageTitle' => 'Connexion - StageLink'
        ]);
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        $this->redirect('login');
    }
}






