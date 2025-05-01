<?php
require_once ROOT_PATH . '/Src/Controllers/Controller.php';
require_once ROOT_PATH . '/Src/Models/OffreModel.php';
require_once ROOT_PATH . '/Src/Models/EntrepriseModel.php';

class OffreController extends Controller {
    private $offremodel;
    private $entreprisemodel;
    
    public function __construct() {
        parent::__construct();
        $this->offremodel = new OffreModel();
        $this->entreprisemodel = new EntrepriseModel();
    }
    
    public function index() {
        $this->checkPageAccess('VOIR_OFFRE');
        
        require_once ROOT_PATH . '/Src/Controllers/Pagination.php';
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if (!empty($searchTerm)) {
            $totalOffres = $this->offremodel->countOffresBySearch($searchTerm);
            
            $pagination = new Pagination($totalOffres, 5, $page);
            
            $offres = $this->offremodel->searchOffres(
                $searchTerm,
                $pagination->getLimit(), 
                $pagination->getOffset(),
                isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
            );
        } else {
            $totalOffres = $this->offremodel->countAllOffres();
            
            $pagination = new Pagination($totalOffres, 5, $page);
            
            $offres = $this->offremodel->getOffresWithPagination(
                $pagination->getLimit(), 
                $pagination->getOffset(),
                isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
            );
        }
        
        foreach ($offres as &$offre) {
            if (isset($_SESSION['user_id'])) {
                if (isset($offre['is_wishlisted'])) {
                    $offre['isLiked'] = $offre['is_wishlisted'];
                } else {
                    $offre['isLiked'] = $this->offremodel->isOffreLiked($offre['id'], $_SESSION['user_id']);
                }
            } else {
                $offre['isLiked'] = false;
            }
        }
        
        $baseUrl = 'index.php?route=offres';
        if (!empty($searchTerm)) {
            $baseUrl .= '&search=' . urlencode($searchTerm);
        }
        
        echo $this->render('offres', [
            'pageTitle' => 'Offres de stage - StageLink',
            'offres' => $offres,
            'pagination' => $pagination->renderHtml($baseUrl),
            'searchTerm' => $searchTerm
        ]);
    }
    
    public function details() {
        $this->checkPageAccess('VOIR_OFFRE');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            $this->redirect('offres');
        }
        
        $offre = $this->offremodel->getOffreById($id);
        
        if (!$offre) {
            $this->redirect('offres');
        }
        
        $competences = $this->offremodel->getOffreCompetences($id);
        
        $isLiked = false;
        $hasApplied = false;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_GET['message'])) {
            $message = $_GET['message'];
            if ($message === 'candidature_success') {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Votre candidature a été enregistrée avec succès.'
                ];
            } elseif ($message === 'candidature_error') {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'message' => 'Une erreur est survenue lors de l\'enregistrement de votre candidature.'
                ];
            }
        }
        
        if (isset($_SESSION['user_id'])) {
            $isLiked = $this->offremodel->isOffreLiked($id, $_SESSION['user_id']);
            
            require_once ROOT_PATH . '/Src/Models/CandidatureModel.php';
            $candidaturemodel = new CandidatureModel();
            $hasApplied = $candidaturemodel->candidatureExiste($_SESSION['user_id'], $id);
        }
        
        $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
        if (isset($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }
        
        echo $this->render('offre_details', [
            'pageTitle' => $offre['titre'] . ' - StageLink',
            'offre' => $offre,
            'competences' => $competences,
            'isLiked' => $isLiked,
            'hasApplied' => $hasApplied,
            'flash' => $flash
        ]);
    }
    
    public function create() {
        $this->checkPageAccess('CREER_OFFRE');
        
        $entreprises = $this->entreprisemodel->getAllEntreprises();
        
        // Charger les compétences pour le formulaire
        require_once ROOT_PATH . '/Src/Models/CompetenceModel.php';
        $competencemodel = new CompetenceModel();
        $competences = $competencemodel->getAllCompetences();
        
        $mode = 'create';
        $offre = null;
        
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $offre = $this->offremodel->getOffreById($id);
            
            if ($offre) {
                $mode = 'edit';
            }
        }
        
        echo $this->render('offre/creer-offre', [
            'pageTitle' => ($mode === 'create' ? 'Créer une offre' : 'Modifier une offre') . ' - StageLink',
            'entreprises' => $entreprises,
            'competences' => $competences,
            'mode' => $mode,
            'offre' => $offre
        ]);
    }
    
    public function traiter() {
        error_log("=== DÉBUT MÉTHODE TRAITER ===");
        // Suppression temporaire de la vérification des permissions
        // $this->checkPageAccess('GERER_OFFRES');
        
        error_log("Méthode HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("SESSION data: " . print_r($_SESSION, true));
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            error_log("Utilisateur non connecté, redirection vers login");
            $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action.";
            $this->redirect('login');
            return;
        }
        
        // Vérifier les permissions basées uniquement sur le rôle
        if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'ADMIN' && $_SESSION['user_role'] !== 'PILOTE')) {
            error_log("Utilisateur sans permission (rôle: " . ($_SESSION['user_role'] ?? 'non défini') . "), redirection vers accueil");
            $_SESSION['error_message'] = "Vous n'avez pas les permissions nécessaires pour effectuer cette action.";
            $this->redirect('accueil');
            return;
        }
        
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Méthode HTTP incorrecte, redirection vers dashboard");
            $this->redirect('dashboard');
            return;
        }
        
        $action = $_POST['action'] ?? '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        error_log("Action: " . $action);
        error_log("ID: " . $id);
        
        // Traiter la création d'une nouvelle offre
        if ($action === 'create') {
            error_log("Traitement de la création d'une nouvelle offre");
            
            $entrepriseId = isset($_POST['entreprise_id']) ? (int)$_POST['entreprise_id'] : 0;
            $titre = $_POST['titre'] ?? '';
            $description = $_POST['description'] ?? '';
            $baseRemuneration = isset($_POST['base_remuneration']) ? (float)$_POST['base_remuneration'] : 0;
            $dateDebut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : date('Y-m-d');
            $dateFin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : date('Y-m-d', strtotime('+3 months'));
            $competences = isset($_POST['competences']) ? $_POST['competences'] : [];
            
            error_log("Création d'offre - Données reçues: " . json_encode([
                'action' => $action,
                'entrepriseId' => $entrepriseId,
                'titre' => $titre,
                'description' => $description,
                'baseRemuneration' => $baseRemuneration,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'competences' => $competences
            ]));
            
            $offreId = $this->offremodel->createOffre($entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin, $competences);
            
            if ($offreId) {
                error_log("Création réussie pour l'offre ID: " . $offreId);
                $_SESSION['success_message'] = "L'offre a été créée avec succès.";
                $this->redirect('offre_details', ['id' => $offreId]);
                exit();
            } else {
                error_log("Échec de la création de l'offre");
                $_SESSION['error_message'] = "Erreur lors de la création de l'offre.";
                $this->redirect('creer_offre');
                exit();
            }
        }
        elseif ($action === 'update' && $id > 0) {
            error_log("Traitement de l'action update pour l'offre ID: " . $id);
            $entrepriseId = isset($_POST['entreprise_id']) ? (int)$_POST['entreprise_id'] : 0;
            $titre = $_POST['titre'] ?? '';
            $description = $_POST['description'] ?? '';
            $baseRemuneration = isset($_POST['base_remuneration']) ? (float)$_POST['base_remuneration'] : 0;
            $dateDebut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : date('Y-m-d');
            $dateFin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : date('Y-m-d', strtotime('+3 months'));
            $competences = isset($_POST['competences']) ? $_POST['competences'] : [];
            
            error_log("Mise à jour d'offre - Données reçues: " . json_encode([
                'entrepriseId' => $entrepriseId,
                'titre' => $titre,
                'description' => $description,
                'baseRemuneration' => $baseRemuneration,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'competences' => $competences
            ]));
            
            $success = $this->offremodel->updateOffre($id, $entrepriseId, $titre, $description, $baseRemuneration, $dateDebut, $dateFin, $competences);
            
            if ($success) {
                error_log("Mise à jour réussie pour l'offre ID: " . $id);
                $_SESSION['success_message'] = "L'offre a été mise à jour avec succès.";
            } else {
                error_log("Échec de la mise à jour pour l'offre ID: " . $id);
                $_SESSION['error_message'] = "Erreur lors de la mise à jour de l'offre.";
            }
            
            $this->redirect('offre_details', ['id' => $id]);
            exit();
            
        } elseif ($action === 'delete' && $id > 0) {
            error_log("Traitement de l'action delete pour l'offre ID: " . $id);
            
            $success = $this->offremodel->deleteOffre($id);
            
            if ($success) {
                error_log("Suppression réussie pour l'offre ID: " . $id);
                $_SESSION['success_message'] = "L'offre a été supprimée avec succès.";
                $this->redirect('offres');
                exit();
            } else {
                error_log("Échec de la suppression pour l'offre ID: " . $id);
                $_SESSION['error_message'] = "Erreur lors de la suppression de l'offre.";
                $this->redirect('offre_details', ['id' => $id]);
                exit();
            }
        } else {
            error_log("Action non reconnue ou ID invalide, redirection vers dashboard");
            $this->redirect('dashboard');
            exit();
        }
        
        error_log("=== FIN MÉTHODE TRAITER ===");
    }
    
    public function like() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter une offre à vos favoris']);
            return;
        }
        
        $offreId = isset($_POST['offre_id']) ? (int)$_POST['offre_id'] : 0;
        
        if ($offreId <= 0) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'ID d\'offre invalide']);
            return;
        }
        
        $result = $this->offremodel->toggleLike($offreId, $_SESSION['user_id']);
        
        $isLiked = $this->offremodel->isOffreLiked($offreId, $_SESSION['user_id']);
        
        echo json_encode([
            'success' => $result,
            'liked' => $isLiked
        ]);
        exit; 
    }
}
