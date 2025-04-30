<?php
require_once ROOT_PATH . '/src/controllers/controller.php';
require_once ROOT_PATH . '/src/models/utilisateurmodel.php';
require_once ROOT_PATH . '/src/models/offremodel.php';
require_once ROOT_PATH . '/src/models/entreprisemodel.php';

class dashboardcontroller extends controller {
    private $utilisateurmodel;
    private $offremodel;
    private $entreprisemodel;
    
    public function __construct() {
        parent::__construct();
        $this->utilisateurmodel = new utilisateurmodel();
        $this->offremodel = new offremodel();
        $this->entreprisemodel = new entreprisemodel();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        
        if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'ADMIN' && $_SESSION['user_role'] !== 'PILOTE')) {
            $this->redirect('accueil');
        }
        
        $userData = $this->utilisateurmodel->getUserById($_SESSION['user_id']);
        $userRole = $this->utilisateurmodel->getUserRole($_SESSION['user_id']);
        
        $userPermissions = [
            'GERER_ENTREPRISES' => $this->hasPermission('GERER_ENTREPRISES'),
            'GERER_OFFRES' => $this->hasPermission('GERER_OFFRES'),
            'GERER_UTILISATEURS' => $this->hasPermission('GERER_UTILISATEURS'),
            'CREER_OFFRE' => $this->hasPermission('CREER_OFFRE')
        ];
        
        $entreprises = $this->entreprisemodel->getAllEntreprises();
        
        $competences = $this->offremodel->getAllCompetences();
        
        $totalOffres = count($this->offremodel->getAllOffres());
        $totalEntreprises = count($this->entreprisemodel->getAllEntreprises());
        
        $roles = $this->utilisateurmodel->getAllRoles();
        
        // Récupérer les étudiants et les pilotes
        $etudiants = [];
        $pilotes = [];
        
        // Paramètres de pagination et recherche pour les étudiants
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 5; // Nombre d'étudiants par page
        
        // Paramètres de pagination et recherche pour les pilotes
        $searchTermPilote = isset($_GET['search_pilote']) ? trim($_GET['search_pilote']) : '';
        $pagePilote = isset($_GET['page_pilote']) ? max(1, intval($_GET['page_pilote'])) : 1;
        $limitPilote = 5; // Nombre de pilotes par page
        
        // Récupérer les étudiants (accessible à tous les utilisateurs du dashboard)
        $etudiantRoleId = $this->utilisateurmodel->getRoleIdByCode('ETUDIANT');
        if ($etudiantRoleId) {
            if (!empty($searchTerm) || $page > 1) {
                $etudiants = $this->utilisateurmodel->searchUsersByRole($etudiantRoleId, $searchTerm, $page, $limit);
                $totalEtudiants = $this->utilisateurmodel->countUsersByRole($etudiantRoleId, $searchTerm);
                $totalPages = ceil($totalEtudiants / $limit);
            } else {
                $etudiants = $this->utilisateurmodel->getUsersByRoleId($etudiantRoleId);
                $totalEtudiants = count($etudiants);
                $totalPages = ceil($totalEtudiants / $limit);
                
                // Si le nombre d'étudiants dépasse la limite, on applique la pagination manuellement
                if ($totalEtudiants > $limit) {
                    $etudiants = $this->utilisateurmodel->searchUsersByRole($etudiantRoleId, '', $page, $limit);
                }
            }
        } else {
            $totalEtudiants = 0;
            $totalPages = 0;
        }
        
        // Récupérer les pilotes (visible uniquement par les administrateurs)
        if ($userRole['role_code'] === 'ADMIN') {
            $piloteRoleId = $this->utilisateurmodel->getRoleIdByCode('PILOTE');
            if ($piloteRoleId) {
                if (!empty($searchTermPilote) || $pagePilote > 1) {
                    $pilotes = $this->utilisateurmodel->searchUsersByRole($piloteRoleId, $searchTermPilote, $pagePilote, $limitPilote);
                    $totalPilotes = $this->utilisateurmodel->countUsersByRole($piloteRoleId, $searchTermPilote);
                    $totalPagesPilote = ceil($totalPilotes / $limitPilote);
                } else {
                    $pilotes = $this->utilisateurmodel->getUsersByRoleId($piloteRoleId);
                    $totalPilotes = count($pilotes);
                    $totalPagesPilote = ceil($totalPilotes / $limitPilote);
                    
                    // Si le nombre de pilotes dépasse la limite, on applique la pagination manuellement
                    if ($totalPilotes > $limitPilote) {
                        $pilotes = $this->utilisateurmodel->searchUsersByRole($piloteRoleId, '', $pagePilote, $limitPilote);
                    }
                }
            } else {
                $totalPilotes = 0;
                $totalPagesPilote = 0;
            }
        }
        
        // Vérifier si on est en mode édition d'utilisateur
        $editMode = false;
        $userToEdit = null;
        
        // Nouveau: Gérer l'édition via POST pour plus de sécurité
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            if (isset($_POST['id'])) {
                $userId = (int)$_POST['id'];
            } elseif (isset($_GET['id'])) { 
                $userId = (int)$_GET['id']; // Garder la compatibilité avec les anciennes URL
            } else {
                $userId = 0;
            }
            
            if ($userId > 0) {
                $userToEdit = $this->utilisateurmodel->getUserById($userId);
                $userToEditRole = $this->utilisateurmodel->getUserRole($userId);
                
                // Vérifier les permissions pour l'édition
                if ($userToEdit) {
                    // Les pilotes ne peuvent éditer que les étudiants
                    if ($userRole['role_code'] === 'PILOTE' && $userToEditRole['role_code'] !== 'ETUDIANT') {
                        $this->redirect('dashboard', ['error' => 'Vous n\'êtes pas autorisé à modifier ce type de compte.']);
                    } else {
                        $editMode = true;
                        $userToEdit['role_code'] = $userToEditRole['role_code'];
                    }
                }
            }
        }
        
        $error = isset($_GET['error']) ? $_GET['error'] : null;
        $success = isset($_GET['success']) ? $_GET['success'] : null;
        
        echo $this->render('dashboard', [
            'pageTitle' => 'Tableau de bord - StageLink',
            'userData' => $userData,
            'userRole' => $userRole,
            'totalOffres' => $totalOffres,
            'totalEntreprises' => $totalEntreprises,
            'entreprises' => $entreprises,
            'competences' => $competences,
            'userPermissions' => $userPermissions,
            'roles' => $roles,
            'etudiants' => $etudiants,
            'pilotes' => $pilotes,
            'error' => $error,
            'success' => $success,
            'editMode' => $editMode,
            'userToEdit' => $userToEdit,
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalEtudiants
            ],
            'paginationPilote' => [
                'page' => $pagePilote,
                'totalPages' => $totalPagesPilote ?? 0,
                'totalItems' => $totalPilotes ?? 0
            ],
            'searchTerm' => $searchTerm,
            'searchTermPilote' => $searchTermPilote
        ]);
    }
    
    public function traiterUtilisateur() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        
        $userRole = $this->utilisateurmodel->getUserRole($_SESSION['user_id']);
        $isAdmin = ($userRole['role_code'] === 'ADMIN');
        $isPilote = ($userRole['role_code'] === 'PILOTE');
        
        if (!$isAdmin && !$isPilote) {
            $this->redirect('accueil');
        }
        
        $action = $_POST['action'] ?? ($_GET['action'] ?? '');
        
        // Création d'un nouvel utilisateur
        if ($action === 'create') {
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $mot_de_passe = $_POST['mot_de_passe'] ?? '';
            $role = $_POST['role'] ?? '';
            
            error_log("Création d'utilisateur - Données reçues: " . 
                      "Nom: $nom, Prénom: $prenom, Email: $email, " . 
                      "Mot de passe: [masqué], Rôle: $role");
            
            if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($role)) {
                error_log("Erreur de validation: champs manquants");
                $this->redirect('dashboard', ['error' => 'Tous les champs sont obligatoires.']);
                return;
            }
            
            // Les pilotes ne peuvent créer que des comptes ETUDIANT
            if ($isPilote && strtoupper($role) !== 'ETUDIANT') {
                $this->redirect('dashboard', ['error' => 'Vous n\'êtes pas autorisé à créer ce type de compte.']);
                return;
            }
            
            $result = $this->utilisateurmodel->createUser($nom, $prenom, $email, $mot_de_passe, $role);
            
            if ($result['success']) {
                $this->redirect('dashboard', ['success' => 'Utilisateur créé avec succès.']);
            } else {
                $this->redirect('dashboard', ['error' => $result['message']]);
            }
        } 
        // Modification d'un utilisateur existant
        else if ($action === 'update') {
            $userId = $_POST['user_id'] ?? 0;
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $mot_de_passe = $_POST['mot_de_passe'] ?? '';
            $role = $_POST['role'] ?? '';
            
            // Déboguer les données reçues
            error_log("Modification d'utilisateur - Données reçues: user_id=$userId, nom=$nom, prenom=$prenom, email=$email, role=$role");
            
            if (empty($userId) || empty($nom) || empty($prenom) || empty($email) || empty($role)) {
                $this->redirect('dashboard', ['error' => 'Tous les champs obligatoires doivent être remplis.']);
                return;
            }
            
            // Vérification des permissions
            $targetUser = $this->utilisateurmodel->getUserById($userId);
            $targetUserRole = $this->utilisateurmodel->getUserRole($userId);
            
            if (!$targetUser) {
                $this->redirect('dashboard', ['error' => 'Utilisateur non trouvé.']);
                return;
            }
            
            // Les pilotes ne peuvent modifier que les étudiants
            if ($isPilote && $targetUserRole['role_code'] !== 'ETUDIANT') {
                $this->redirect('dashboard', ['error' => 'Vous n\'êtes pas autorisé à modifier ce type de compte.']);
                return;
            }
            
            // Préparer les données à mettre à jour avec debug supplémentaire
            $data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email
            ];
            
            // Debug pour voir si on obtient bien le rôle
            error_log("ROLE CODE REÇU: $role");
            
            // Conversion du code de rôle en role_id avec plus de débug
            if (!empty($role)) {
                // Assurons-nous que le rôle est en majuscules
                $role = strtoupper($role);
                $roleId = $this->utilisateurmodel->getRoleIdByCode($role);
                error_log("Role ID obtenu pour $role: " . ($roleId ?: 'null'));
                
                if ($roleId) {
                    $data['role_id'] = $roleId;
                } else {
                    // Si le rôle n'est pas trouvé, essayons en minuscules
                    $roleLower = strtolower($role);
                    $roleId = $this->utilisateurmodel->getRoleIdByCode($roleLower);
                    error_log("Role ID obtenu pour $roleLower: " . ($roleId ?: 'null'));
                    
                    if ($roleId) {
                        $data['role_id'] = $roleId;
                    } else {
                        $this->redirect('dashboard', ['error' => 'Rôle non valide.']);
                        return;
                    }
                }
            }
            
            // N'inclure le mot de passe que s'il est fourni
            if (!empty($mot_de_passe)) {
                $data['mot_de_passe'] = $mot_de_passe;
            }
            
            error_log("Données finales pour mise à jour: " . print_r($data, true));
            
            $result = $this->utilisateurmodel->updateUser($userId, $data);
            
            if ($result['success']) {
                $this->redirect('dashboard', ['success' => 'Utilisateur mis à jour avec succès.']);
            } else {
                $this->redirect('dashboard', ['error' => $result['message']]);
            }
        } 
        // Suppression d'un utilisateur
        else if ($action === 'delete') {
            $userId = $_GET['id'] ?? 0;
            
            if (empty($userId)) {
                $this->redirect('dashboard', ['error' => 'ID utilisateur non spécifié.']);
                return;
            }
            
            // Vérification pour empêcher la suppression de son propre compte
            if ($userId == $_SESSION['user_id']) {
                $this->redirect('dashboard', ['error' => 'Vous ne pouvez pas supprimer votre propre compte.']);
                return;
            }
            
            // Vérification des permissions
            $targetUser = $this->utilisateurmodel->getUserById($userId);
            $targetUserRole = $this->utilisateurmodel->getUserRole($userId);
            
            if (!$targetUser) {
                $this->redirect('dashboard', ['error' => 'Utilisateur non trouvé.']);
                return;
            }
            
            // Les pilotes ne peuvent supprimer que les étudiants
            if ($isPilote && $targetUserRole['role_code'] !== 'ETUDIANT') {
                $this->redirect('dashboard', ['error' => 'Vous n\'êtes pas autorisé à supprimer ce type de compte.']);
                return;
            }
            
            $result = $this->utilisateurmodel->deleteUser($userId);
            
            if ($result['success']) {
                $this->redirect('dashboard', ['success' => 'Utilisateur supprimé avec succès.']);
            } else {
                $this->redirect('dashboard', ['error' => $result['message']]);
            }
        } else {
            $this->redirect('dashboard');
        }
    }
    
    // Fonction pour récupérer les données d'un utilisateur (pour l'édition AJAX)
    public function getUser() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
            return;
        }
        
        $userId = $_GET['id'] ?? 0;
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
            return;
        }
        
        // Vérifier les permissions
        $isAdmin = $_SESSION['user_role'] === 'ADMIN';
        $isPilote = $_SESSION['user_role'] === 'PILOTE';
        
        // Les pilotes ne peuvent voir que les étudiants
        if ($isPilote) {
            $targetUserRole = $this->utilisateurmodel->getUserRole($userId);
            if ($targetUserRole['role_code'] !== 'ETUDIANT') {
                echo json_encode(['success' => false, 'message' => 'Permission refusée']);
                return;
            }
        }
        
        // Récupérer les données de l'utilisateur
        $user = $this->utilisateurmodel->getUserById($userId);
        $userRole = $this->utilisateurmodel->getUserRole($userId);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            return;
        }
        
        // Ajouter le rôle aux données utilisateur
        $user['role'] = $userRole['role_code'];
        
        // Supprimer le mot de passe des données renvoyées
        unset($user['mot_de_passe']);
        
        // Définir les headers pour JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
        exit;
    }
}
?>
