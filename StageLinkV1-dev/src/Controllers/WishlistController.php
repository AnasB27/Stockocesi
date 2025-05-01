<?php
require_once ROOT_PATH . '/Src/Controllers/Controller.php';
require_once ROOT_PATH . '/Src/Models/OffreModel.php';

class WishlistController extends Controller {
    private $offremodel;
    private $db;
    
    public function __construct() {
        parent::__construct(); // Appel au constructeur parent pour initialiser Twig
        $this->offremodel = new offremodel();
        $this->db = database::getInstance()->getConnection();
    }   
    
    public function toggleLike() {
        // Vérification de la session et réponse JSON appropriée
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
            exit();
        }
        
        error_log("toggleLike - Session: " . print_r($_SESSION, true));
        
        // Récupérer l'ID de l'offre soit depuis POST soit depuis GET
        $offre_id = isset($_POST['offre_id']) ? (int)$_POST['offre_id'] : 
                   (isset($_GET['offre_id']) ? (int)$_GET['offre_id'] : 0);
        
        if (!$offre_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID de l\'offre manquant']);
            exit();
        }

        $utilisateur_id = $_SESSION['user_id'];
        error_log("Utilisateur ID: $utilisateur_id, Offre ID: $offre_id");
        
        try {
            $db = database::getInstance()->getConnection();
            $db->beginTransaction();
            
            error_log("Vérification si l'offre est déjà aimée");
            $stmt = $db->prepare("SELECT 1 FROM wishlist WHERE utilisateur_id = ? AND offre_id = ?");
            $stmt->bindValue(1, $utilisateur_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $offre_id, PDO::PARAM_INT);
            $stmt->execute();
            $exists = $stmt->rowCount() > 0;
            error_log("Existe déjà: " . ($exists ? 'Oui' : 'Non'));
            
            if ($exists) {
                error_log("Suppression du like");
                $stmt = $db->prepare("DELETE FROM wishlist WHERE utilisateur_id = ? AND offre_id = ?");
                $stmt->bindValue(1, $utilisateur_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $offre_id, PDO::PARAM_INT);
                $result = $stmt->execute();
                error_log("Résultat suppression: " . ($result ? 'Succès' : 'Échec'));
            } else {
                error_log("Ajout du like");
                $stmt = $db->prepare("INSERT INTO wishlist (utilisateur_id, offre_id) VALUES (?, ?)");
                $stmt->bindValue(1, $utilisateur_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $offre_id, PDO::PARAM_INT);
                $result = $stmt->execute();
                error_log("Résultat ajout: " . ($result ? 'Succès' : 'Échec'));
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Erreur SQL: " . json_encode($errorInfo));
                }
            }
            
            $db->commit();
            error_log("Transaction commited");
            
            $response = [
                'success' => true,
                'action' => $exists ? 'removed' : 'added'
            ];
            error_log("Réponse: " . json_encode($response));
            
            header('Content-Type: application/json');
            echo json_encode($response);
            error_log('**** FIN toggle_like ****');
            
        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            if (isset($db)) {
                $db->rollBack();
                error_log("Transaction rollback");
            }
            
            http_response_code(500);
            echo json_encode([ 
                'success' => false, 
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ]);
            error_log('**** FIN toggle_like avec erreur ****');
        }
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        
        $sql = "SELECT o.*, e.nom as entreprise_nom 
                FROM offres o
                JOIN entreprises e ON o.entreprise_id = e.id
                JOIN wishlist w ON o.id = w.offre_id
                WHERE w.utilisateur_id = ?
                ORDER BY o.date_debut DESC";
                
        $stmt = $this->db->prepare($sql); 
        $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $offres = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $offres[] = $row;
        }
        
        echo $this->render('wishlist/wishlist', [
            'pageTitle' => 'Mes offres favorites - StageLink',
            'offres' => $offres
        ]);
    }
}
?>
