<?php 
namespace App\Controllers;

use App\Models\TaskModel;

class TaskController extends Controller {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new TaskModel();
    }

    /**
     * Affiche la page d'accueil.
     */
    public function welcomePage() {
        echo $this->templateEngine->render('accueil/accueil.twig', [
            'pageTitle' => 'Accueil - Gestion des stocks'
        ]);
    }
/**  */
    public function accueilPage() {
        echo $this->render('accueil/accueil', [
            'pageTitle' => 'Accueil - Gestion des stocks',
            'current_page' => 'accueil'
        ]);
    }
    
    /**
     * Affiche le journal d'action avec la liste des tâches.
     */
    public function logPage() {
        $tasks = $this->model->getAllTasks(); // Récupère toutes les tâches
        echo $this->templateEngine->render('journal/log.twig', [
            'tasks' => $tasks,
            'pageTitle' => 'Journal d\'action'
        ]);
    }

    /**
     * Ajoute une nouvelle tâche.
     */
    public function addTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
            $task = $_POST['task'];
            $this->model->addTask($task); // Ajoute la tâche via le modèle
            header('Location: /log'); // Redirige vers le journal d'action
            exit;
        } else {
            header('Location: /log'); // Redirige si le paramètre 'task' est manquant
            exit;
        }
    }

    /**
     * Marque une tâche comme terminée.
     */
    public function checkTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $taskId = (int) $_POST['id'];
            $this->model->checkTask($taskId); // Tâche terminée
            header('Location: /log'); // Redirige vers le journal d'action
            exit;
        } else {
            header('Location: /log'); // Redirige si le paramètre 'id' est manquant
            exit;
        }
    }

    /**
     * Marque une tâche comme non terminée.
     */
    public function uncheckTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $taskId = (int) $_POST['id'];
            $this->model->uncheckTask($taskId); // Marque la tâche comme non terminée
            header('Location: /log'); // Redirige vers le journal d'action
            exit;
        } else {
            header('Location: /log'); // Redirige si le paramètre 'id' est manquant
            exit;
        }
    }

    /**
     * Supprime une tâche.
     */
    public function deleteTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $taskId = (int) $_POST['id'];
            $this->model->deleteTask($taskId); // Supprime la tâche
            header('Location: /log'); // Redirige vers le journal d'action
            exit;
        } else {
            header('Location: /log'); // Redirige si le paramètre 'id' est manquant
            exit;
        }
    }

    /**
     * Affiche la page "À propos".
     */
    public function contactPage() {
        echo $this->render('static/contact', [
            'pageTitle' => 'Contact - Stock O\' CESI'
        ]);
    }
    
    public function mentionsPage() {
        echo $this->render('static/mentions', [
            'pageTitle' => 'Mentions légales - Stock O\' CESI'
        ]);
    }
}