<?php
require_once ROOT_PATH . '/src/controllers/controller.php';
require_once ROOT_PATH . '/src/models/entreprisemodel.php';
require_once ROOT_PATH . '/src/controllers/Pagination.php';


class entreprisecontroller extends controller {
    private $entreprisemodel;
    
    public function __construct() {
        parent::__construct();
        $this->entreprisemodel = new entreprisemodel();
    }
    
    public function index() {
        $this->checkPageAccess('VOIR_ENTREPRISE');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if (!empty($searchTerm)) {
            // Recherche avec terme
            $totalEntreprises = $this->entreprisemodel->countEntreprisesBySearch($searchTerm);
            
            $pagination = new Pagination($totalEntreprises, 5, $page);
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $entreprises = $this->entreprisemodel->searchEntreprises(
                $searchTerm,
                $pagination->getLimit(), 
                $pagination->getOffset(),
                $userId
            );
        } else {
            // Sans recherche
            $totalEntreprises = $this->entreprisemodel->countAllEntreprises();
            
            $pagination = new Pagination($totalEntreprises, 5, $page);
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $entreprises = $this->entreprisemodel->getEntreprisesWithPaginationAndRatings(
                $pagination->getLimit(), 
                $pagination->getOffset(),
                $userId
            );
        }
        
        $baseUrl = 'index.php?route=entreprises';
        if (!empty($searchTerm)) {
            $baseUrl .= '&search=' . urlencode($searchTerm);
        }
        
        echo $this->render('Entreprises', [
            'pageTitle' => 'Entreprises - StageLink',
            'entreprises' => $entreprises,
            'pagination' => $pagination->renderHtml($baseUrl),
            'searchTerm' => $searchTerm
        ]);
    }
    
    
    public function details() {
        $this->checkPageAccess('VOIR_ENTREPRISE');
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            $this->redirect('entreprises');
        }
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $entreprise = $this->entreprisemodel->getEntrepriseWithRatingsById($id, $userId);
        
        if (!$entreprise) {
            $this->redirect('entreprises');
        }
        
        $evaluations = $this->entreprisemodel->getEvaluations($id);
        
        $offres = $this->entreprisemodel->getOffresEntreprise($id);
        
        $totalCandidatures = $this->entreprisemodel->getTotalCandidaturesEntreprise($id);
        
        echo $this->render('entreprise_details', [
            'pageTitle' => $entreprise['nom'] . ' - StageLink',
            'entreprise' => $entreprise,
            'evaluations' => $evaluations,
            'offres' => $offres,
            'totalCandidatures' => $totalCandidatures
        ]);
    }
    
    public function traiter() {
        $this->checkPageAccess('GERER_ENTREPRISES');
        
        error_log("Méthode traiter() appelée dans entreprisecontroller");
        error_log("Méthode HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Méthode HTTP non autorisée: " . $_SERVER['REQUEST_METHOD']);
            $this->redirect('dashboard');
            return;
        }
        
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        
        error_log("Action: " . $action);
        error_log("ID: " . $id);
        
        $errors = [];
        
        // Pour l'action delete, on ne vérifie que l'ID
        if ($action === 'delete') {
            if ($id <= 0) {
                $errors[] = "ID d'entreprise invalide pour la suppression.";
                error_log("ID d'entreprise invalide pour la suppression: " . $id);
            }
        } else {
            // Pour les autres actions, on vérifie tous les champs
            if (empty($nom)) {
                $errors[] = "Le nom de l'entreprise est requis.";
            }
            
            if (empty($description)) {
                $errors[] = "La description de l'entreprise est requise.";
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Une adresse email valide est requise.";
            }
        }
        
        if (empty($errors)) {
            if ($action === 'create') {
                $success = $this->entreprisemodel->createEntreprise($nom, $description, $email, $telephone);
                if ($success) {
                    $_SESSION['success_message'] = "L'entreprise a été créée avec succès.";
                    $this->redirect('dashboard');
                } else {
                    $_SESSION['error_message'] = "Une erreur est survenue lors de la création de l'entreprise.";
                    $this->redirect('dashboard');
                }
            } elseif ($action === 'update' && $id > 0) {
                error_log("Tentative de mise à jour de l'entreprise ID: " . $id);
                $success = $this->entreprisemodel->updateEntreprise($id, $nom, $description, $email, $telephone);
                error_log("Résultat de la mise à jour: " . ($success ? "Succès" : "Échec"));
                
                if ($success) {
                    $_SESSION['success_message'] = "L'entreprise a été mise à jour avec succès.";
                    $this->redirect('entreprise_details', ['id' => $id]);
                } else {
                    $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour de l'entreprise.";
                    $this->redirect('entreprise_details', ['id' => $id]);
                }
            } elseif ($action === 'delete' && $id > 0) {
                error_log("Tentative de suppression de l'entreprise ID: " . $id);
                
                // Vérifier si l'entreprise existe avant de tenter de la supprimer
                $entreprise = $this->entreprisemodel->getEntrepriseById($id);
                if (!$entreprise) {
                    error_log("Entreprise ID: " . $id . " n'existe pas");
                    $_SESSION['error_message'] = "L'entreprise n'existe pas.";
                    $this->redirect('entreprises');
                    return;
                }
                
                $success = $this->entreprisemodel->deleteEntreprise($id);
                error_log("Résultat de la suppression: " . ($success ? "Succès" : "Échec"));
                
                if ($success) {
                    $_SESSION['success_message'] = "L'entreprise a été supprimée avec succès.";
                    $this->redirect('entreprises');
                } else {
                    $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression de l'entreprise.";
                    $this->redirect('entreprise_details', ['id' => $id]);
                }
            } else {
                error_log("Action non reconnue ou ID invalide: " . $action . ", ID: " . $id);
                $_SESSION['error_message'] = "Action non reconnue.";
                $this->redirect('dashboard');
            }
        } else {
            error_log("Erreurs de validation: " . implode(', ', $errors));
            $_SESSION['error_message'] = implode('<br>', $errors);
            if ($action === 'create') {
                $this->redirect('dashboard');
            } else {
                $this->redirect('entreprise_details', ['id' => $id]);
            }
        }
    }
    
    public function rate()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = "Vous devez être connecté pour noter une entreprise.";
            header('Location: index.php?route=login');
            exit;
        }

        $entrepriseId = isset($_POST['entreprise_id']) ? intval($_POST['entreprise_id']) : 0;
        $note = isset($_POST['note']) ? intval($_POST['note']) : 0;
        $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
        
        if ($entrepriseId <= 0 || $note < 1 || $note > 5) {
            $_SESSION['error_message'] = "Données invalides pour l'évaluation.";
            header('Location: index.php?route=entreprise_details&id=' . $entrepriseId);
            exit;
        }

        $result = $this->entreprisemodel->rateEntreprise($entrepriseId, $_SESSION['user_id'], $note, $commentaire);
        
        if ($result) {
            $_SESSION['success_message'] = "Votre évaluation a été enregistrée avec succès.";
        } else {
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'enregistrement de votre évaluation.";
        }

        header('Location: index.php?route=entreprise_details&id=' . $entrepriseId);
        exit;
    }
}
