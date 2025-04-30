<?php
class UtilisateurController {
    private $utilisateurModel;
    
    public function __construct() {
        require_once ROOT_PATH . '/Src/Models/UtilisateurModel.php';
        $this->utilisateurModel = new UtilisateurModel();
    }
    
    public function index() {
        // Vérification des permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Récupérer le rôle de l'utilisateur connecté
        $userRole = $this->utilisateurModel->getUserRole($_SESSION['user_id']);
        
        // Seuls les pilotes et les administrateurs peuvent accéder à cette page
        if (!in_array($userRole['role_code'], ['admin', 'pilote'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Paramètres de pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Filtre par rôle (les pilotes ne peuvent voir que les étudiants)
        $roleFilter = null;
        if ($userRole['role_code'] === 'pilote') {
            $roleFilter = 'etudiant';
        } elseif (isset($_GET['role']) && !empty($_GET['role'])) {
            $roleFilter = $_GET['role'];
        }
        
        // Récupérer les utilisateurs avec pagination
        $users = $this->utilisateurModel->getAllUsers($page, $limit, $roleFilter);
        $totalUsers = $this->utilisateurModel->countUsers($roleFilter);
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalUsers / $limit);
        
        // Récupérer tous les rôles
        $roles = $this->utilisateurModel->getAllRoles();
        
        // Charger le template
        require_once ROOT_PATH . '/Src/Views/Twig.php';
        $twig = Twig::getInstance();
        
        echo $twig->render('dashboard/users.twig', [
            'users' => $users,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'roles' => $roles,
            'user_role' => $userRole,
            'role_filter' => $roleFilter,
            'session' => $_SESSION
        ]);
    }
    
    public function edit() {
        // Vérification des permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Récupérer le rôle de l'utilisateur connecté
        $userRole = $this->utilisateurModel->getUserRole($_SESSION['user_id']);
        
        // Seuls les pilotes et les administrateurs peuvent accéder à cette page
        if (!in_array($userRole['role_code'], ['admin', 'pilote'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Vérifier si un ID d'utilisateur est fourni
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header('Location: index.php?route=manage_users');
            exit;
        }
        
        $userId = (int)$_GET['id'];
        
        // Les pilotes ne peuvent éditer que les étudiants
        if ($userRole['role_code'] === 'pilote') {
            $targetUser = $this->utilisateurModel->getUserById($userId);
            $targetUserRole = $this->utilisateurModel->getUserRole($userId);
            
            if (!$targetUser || $targetUserRole['role_code'] !== 'etudiant') {
                header('Location: index.php?route=manage_users');
                exit;
            }
        }
        
        // Récupérer l'utilisateur à modifier
        $user = $this->utilisateurModel->getUserById($userId);
        
        if (!$user) {
            header('Location: index.php?route=manage_users');
            exit;
        }
        
        // Récupérer tous les rôles (seulement pour les admins)
        $roles = [];
        if ($userRole['role_code'] === 'admin') {
            $roles = $this->utilisateurModel->getAllRoles();
        }
        
        // Traiter le formulaire si soumis
        $message = '';
        $success = false;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
            // Récupérer les données du formulaire
            $userData = [
                'nom' => $_POST['nom'] ?? '',
                'prenom' => $_POST['prenom'] ?? '',
                'email' => $_POST['email'] ?? ''
            ];
            
            // Mot de passe (facultatif)
            if (isset($_POST['mot_de_passe']) && !empty($_POST['mot_de_passe'])) {
                $userData['mot_de_passe'] = $_POST['mot_de_passe'];
            }
            
            // Role (seulement pour les admins)
            if ($userRole['role_code'] === 'admin' && isset($_POST['role_id'])) {
                $userData['role_id'] = (int)$_POST['role_id'];
            }
            
            // Mettre à jour l'utilisateur
            $result = $this->utilisateurModel->updateUser($userId, $userData);
            
            if ($result['success']) {
                $message = 'Utilisateur mis à jour avec succès.';
                $success = true;
                
                // Mettre à jour les informations de l'utilisateur
                $user = $this->utilisateurModel->getUserById($userId);
            } else {
                $message = $result['message'];
            }
        }
        
        // Récupérer le rôle de l'utilisateur
        $userRoleInfo = $this->utilisateurModel->getUserRole($userId);
        
        // Charger le template
        require_once ROOT_PATH . '/Src/Views/Twig.php';
        $twig = Twig::getInstance();
        
        echo $twig->render('dashboard/edit_user.twig', [
            'user' => $user,
            'user_role' => $userRole,
            'user_role_info' => $userRoleInfo,
            'roles' => $roles,
            'message' => $message,
            'success' => $success,
            'session' => $_SESSION
        ]);
    }
    
    public function delete() {
        // Vérification des permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Récupérer le rôle de l'utilisateur connecté
        $userRole = $this->utilisateurModel->getUserRole($_SESSION['user_id']);
        
        // Seuls les administrateurs peuvent supprimer des utilisateurs
        if ($userRole['role_code'] !== 'admin') {
            header('Location: index.php?route=manage_users');
            exit;
        }
        
        // Vérifier si un ID d'utilisateur est fourni
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header('Location: index.php?route=manage_users');
            exit;
        }
        
        $userId = (int)$_GET['id'];
        
        // Ne pas permettre de supprimer son propre compte
        if ($userId === (int)$_SESSION['user_id']) {
            header('Location: index.php?route=manage_users');
            exit;
        }
        
        // Supprimer l'utilisateur
        $result = $this->utilisateurModel->deleteUser($userId);
        
        // Rediriger vers la liste des utilisateurs avec un message
        $redirectUrl = 'index.php?route=manage_users';
        
        if ($result['success']) {
            $redirectUrl .= '&success=1&message=' . urlencode('Utilisateur supprimé avec succès.');
        } else {
            $redirectUrl .= '&error=1&message=' . urlencode($result['message']);
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    public function create() {
        // Vérification des permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Récupérer le rôle de l'utilisateur connecté
        $userRole = $this->utilisateurModel->getUserRole($_SESSION['user_id']);
        
        // Seuls les administrateurs peuvent créer des utilisateurs
        // Les pilotes peuvent créer des étudiants
        $canCreateAnyUser = ($userRole['role_code'] === 'admin');
        $canCreateStudent = ($userRole['role_code'] === 'pilote' || $userRole['role_code'] === 'admin');
        
        if (!$canCreateAnyUser && !$canCreateStudent) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Récupérer tous les rôles
        $roles = [];
        if ($canCreateAnyUser) {
            $roles = $this->utilisateurModel->getAllRoles();
        } else {
            // Pour les pilotes, uniquement le rôle étudiant
            $etudiantRoleId = $this->utilisateurModel->getRoleIdByCode('etudiant');
            if ($etudiantRoleId) {
                $roles = [['id' => $etudiantRoleId, 'nom' => 'Étudiant', 'code' => 'etudiant']];
            }
        }
        
        // Traiter le formulaire si soumis
        $message = '';
        $success = false;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
            // Récupérer les données du formulaire
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $motDePasse = $_POST['mot_de_passe'] ?? '';
            $roleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : null;
            
            // Validation des données
            if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse) || !$roleId) {
                $message = 'Tous les champs sont obligatoires.';
            } else {
                // Si l'utilisateur est un pilote, vérifier qu'il crée bien un étudiant
                if (!$canCreateAnyUser) {
                    $etudiantRoleId = $this->utilisateurModel->getRoleIdByCode('etudiant');
                    if ($roleId !== $etudiantRoleId) {
                        $message = 'Vous ne pouvez créer que des comptes étudiants.';
                    }
                }
                
                if (empty($message)) {
                    // Créer l'utilisateur
                    $result = $this->utilisateurModel->createUser($nom, $prenom, $email, $motDePasse, $roleId);
                    
                    if ($result['success']) {
                        $message = 'Utilisateur créé avec succès.';
                        $success = true;
                    } else {
                        $message = $result['message'];
                    }
                }
            }
        }
        
        // Charger le template
        require_once ROOT_PATH . '/Src/Views/Twig.php';
        $twig = Twig::getInstance();
        
        echo $twig->render('dashboard/create_user.twig', [
            'roles' => $roles,
            'user_role' => $userRole,
            'message' => $message,
            'success' => $success,
            'can_create_any_user' => $canCreateAnyUser,
            'session' => $_SESSION
        ]);
    }
}
