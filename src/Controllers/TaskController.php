<?php 
namespace App\Controllers;

use App\Models\TaskModel;

class TaskController extends Controller {
    private $model;
    private $templateEngine;

    public function __construct($templateEngine) {
        parent::__construct();
        $this->model = new TaskModel();
        $this->templateEngine = $templateEngine;
    }

    /**
     * Affiche la page d'accueil avec la liste des tâches.
     */
    public function welcomePage() {
        $tasks = $this->model->getAllTasks(); // Récupère toutes les tâches
        echo $this->templateEngine->render('todo.twig.html', ['tasks' => $tasks]);
    }

    /**
     * Ajoute une nouvelle tâche.
     */
    public function addTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
            $task = $_POST['task'];
            $this->model->addTask($task); // Ajoute la tâche via le modèle
            header('Location: /'); // Redirige vers la page d'accueil
            exit;
        } else {
            header('Location: /'); // Redirige si le paramètre 'task' est manquant
            exit;
        }
    }

    /**
     * Marque une tâche comme terminée.
     */
    public function checkTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $taskId = (int) $_POST['id'];
            $this->model->checkTask($taskId); // Marque la tâche comme terminée
            header('Location: /'); // Redirige vers la page d'accueil
            exit;
        } else {
            header('Location: /'); // Redirige si le paramètre 'id' est manquant
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
            header('Location: /'); // Redirige vers la page d'accueil
            exit;
        } else {
            header('Location: /'); // Redirige si le paramètre 'id' est manquant
            exit;
        }
    }

    /**
     * Affiche l'historique des tâches.
     */
    public function historyPage() {
        $tasks = $this->model->getAllTasks(); // Récupère toutes les tâches
        echo $this->templateEngine->render('history.twig.html', ['tasks' => $tasks]);
    }

    /**
     * Affiche la page "À propos".
     */
    public function aboutPage() {
        echo $this->templateEngine->render('about.twig.html');
    }
}