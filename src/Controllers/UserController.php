<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\LogModel;
use App\Models\StoreModel;

class UserController extends Controller {
    private $userModel;
    private $logModel;
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->logModel = new LogModel();
        $this->storeModel = new StoreModel();
    }

    private function ensureAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = "Veuillez vous connecter";
            $this->redirect('login');
            exit;
        }
    }

    public function loginPage() {
        echo $this->render('account/login', [
            'pageTitle' => 'Connexion',
            'current_page' => 'login',
            'error' => ''
        ]);
    }

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
                LogController::logAction(
                    'Connexion',
                    "Connexion de l'utilisateur : {$user['name']} ({$user['email']})"
                );

                $this->redirect('accueil');
            } else {
                LogController::logAction(
                    'Tentative de connexion échouée',
                    "Email: $email"
                );
                
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

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_name'])) {
            LogController::logAction(
                'Déconnexion',
                "Déconnexion de l'utilisateur : {$_SESSION['user_name']}"
            );
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

    public function manageAccounts() {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès refusé";
            $this->redirect('accueil');
            return;
        }
    
        $accounts = $this->userModel->getAllAccounts();
        
        echo $this->render('account/manage-accounts', [
            'pageTitle' => 'Gestion des comptes',
            'current_page' => 'manage-accounts',
            'accounts' => $accounts,
            'is_super_admin' => $_SESSION['user_role'] === 'Admin'
        ]);
    }

    public function deleteAccount($id) {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            return;
        }

        $account = $this->userModel->getUserById($id);
        if (!$account) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Compte non trouvé']);
            return;
        }
    
        if ($this->userModel->deleteUser($id)) {
            LogController::logAction(
                'Suppression compte',
                "Compte supprimé : {$account['name']} {$account['firstname']} (ID: $id)"
            );
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }

    public function editAccount($id) {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès non autorisé";
            $this->redirect('accueil');
            return;
        }
    
        $account = $this->userModel->getUserById($id);
        
        if (!$account) {
            $_SESSION['error_message'] = "Compte non trouvé";
            $this->redirect('admin/manage-accounts');
            return;
        }
    
        $stores = $this->storeModel->getAllStores();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['name']) || empty($_POST['firstname']) || empty($_POST['email'])) {
                $_SESSION['error_message'] = "Veuillez remplir tous les champs obligatoires";
            } else {
                $updateData = [
                    'id' => $id,
                    'name' => trim($_POST['name']),
                    'firstname' => trim($_POST['firstname']),
                    'email' => trim($_POST['email']),
                    'role' => $_POST['role'] ?? 'Employee',
                    'store_id' => !empty($_POST['store_id']) ? $_POST['store_id'] : null
                ];
    
                if (!empty($_POST['password'])) {
                    $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
    
                try {
                    if ($this->userModel->updateUser($updateData)) {
                        LogController::logAction(
                            'Modification compte',
                            "Compte modifié : {$updateData['name']} {$updateData['firstname']} (ID: $id)"
                        );
                        $_SESSION['success_message'] = "Compte mis à jour avec succès";
                        $this->redirect('admin/manage-accounts');
                        return;
                    }
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = "Erreur lors de la mise à jour du compte : " . $e->getMessage();
                }
            }
        }
    
        echo $this->render('account/edit-account', [
            'pageTitle' => 'Modifier un compte',
            'current_page' => 'edit-account',
            'account' => $account,
            'stores' => $stores,
            'is_super_admin' => $_SESSION['user_role'] === 'Admin'
        ]);
    }

    public function assignUserToStore($userId, $storeId) {
        $this->ensureAuthenticated();
        
        if ($_SESSION['user_role'] !== 'Admin') {
            $_SESSION['error_message'] = "Accès non autorisé";
            $this->redirect('accueil');
            return;
        }

        if ($this->userModel->assignUserToStore($userId, $storeId)) {
            $user = $this->userModel->getUserById($userId);
            $store = $this->storeModel->getStoreById($storeId);
            
            LogController::logAction(
                'Assignation magasin',
                "Utilisateur {$user['name']} assigné au magasin {$store['name']}"
            );
            $_SESSION['success_message'] = "Utilisateur assigné au magasin avec succès";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'assignation de l'utilisateur au magasin";
        }
        
        $this->redirect('admin/manage-accounts');
    }
}