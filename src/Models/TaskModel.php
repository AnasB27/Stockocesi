<?php
namespace App\Models;

class TaskModel {
    const TODO_STATUS = "todo";
    const DONE_STATUS = "done";

    private $db;

    /**
     * TaskModel constructor.
     */
    public function __construct() {
        // Initialiser la connexion à la base de données
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les tâches.
     *
     * @return array Un tableau contenant toutes les tâches.
     */
    public function getAllTasks() {
        $stmt = $this->db->query("SELECT * FROM tasks");
        return $stmt->fetchAll();
    }

    /**
     * Récupère une tâche spécifique par son ID.
     *
     * @param int $id L'ID de la tâche.
     * @return array|null La tâche correspondante ou null si elle n'existe pas.
     */
    public function getTask($id) {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Récupère toutes les tâches avec le statut 'done'.
     *
     * @return array Un tableau de tâches avec le statut 'done'.
     */
    public function getDoneTasks() {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE status = :status");
        $stmt->execute(['status' => self::DONE_STATUS]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère toutes les tâches avec le statut 'todo'.
     *
     * @return array Un tableau de tâches avec le statut 'todo'.
     */
    public function getToDoTasks() {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE status = :status");
        $stmt->execute(['status' => self::TODO_STATUS]);
        return $stmt->fetchAll();
    }

    /**
     * Ajoute une nouvelle tâche.
     *
     * @param string $task La description de la tâche.
     * @return bool True si l'insertion a réussi, sinon false.
     */
    public function addTask($task) {
        $stmt = $this->db->prepare("INSERT INTO tasks (task, status) VALUES (:task, :status)");
        return $stmt->execute(['task' => $task, 'status' => self::TODO_STATUS]);
    }

    /**
     * Marque une tâche comme terminée.
     *
     * @param int $id L'ID de la tâche.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function checkTask($id) {
        $task = $this->getTask($id);
        if (!$task) {
            return false; // La tâche n'existe pas
        }

        return $this->updateTask($id, $task['task'], self::DONE_STATUS);
    }

    /**
     * Marque une tâche comme non terminée.
     *
     * @param int $id L'ID de la tâche.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function uncheckTask($id) {
        $task = $this->getTask($id);
        if (!$task) {
            return false; // La tâche n'existe pas
        }

        return $this->updateTask($id, $task['task'], self::TODO_STATUS);
    }

    /**
     * Met à jour une tâche avec un nouveau statut.
     *
     * @param int $id L'ID de la tâche.
     * @param string $task La description de la tâche.
     * @param string $status Le nouveau statut.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    private function updateTask($id, $task, $status) {
        $stmt = $this->db->prepare("UPDATE tasks SET task = :task, status = :status WHERE id = :id");
        return $stmt->execute(['task' => $task, 'status' => $status, 'id' => $id]);
    }

    /**
     * Supprime une tâche par son ID.
     *
     * @param int $id L'ID de la tâche.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function deleteTask($id) {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}