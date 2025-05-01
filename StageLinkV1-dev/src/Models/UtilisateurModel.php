<?php
class utilisateurmodel {
    private $db;
    
    public function __construct() {
        require_once ROOT_PATH . '/Src/Models/Database.php';
        $this->db = database::getInstance();
    }
    
    public function getUserById($userId) {
        $sql = "SELECT * FROM utilisateurs WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch() ?: null;
    }
    
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM utilisateurs WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        $row = $stmt->fetch();
        
        if ($row) {
            if (password_verify($password, $row['mot_de_passe'])) {
                return $row;
            } 
            else if ($password === $row['mot_de_passe']) {
                $this->updatePasswordHash($row['id'], $password);
                return $row;
            }
        }
        
        return null;
    }
    
    public function updatePasswordHash($userId, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    public function getUserRole($userId) {
        $sql = "SELECT r.code as role_code, r.nom as role_nom
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch() ?: null;
    }
    
    public function getUserPermissions($userId) {
        $sql = "SELECT DISTINCT p.code
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[] = $row['code'];
        }
        
        return $permissions;
    }
    
    public function hasPermission($userId, $permissionCode) {
        $sql = "SELECT COUNT(*) as count 
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND p.code = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $permissionCode]);
        $row = $stmt->fetch();
        
        return $row['count'] > 0;
    }
    
    public function createUser($nom, $prenom, $email, $mot_de_passe, $role_id) {
        $sql = "SELECT COUNT(*) as count FROM utilisateurs WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        
        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'Un utilisateur avec cet email existe déjà.'];
        }
        
        $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([$nom, $prenom, $email, $hashedPassword, $role_id]);
            $insertId = $this->db->getLastInsertId();
            return ['success' => true, 'id' => $insertId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage()];
        }
    }
    
    public function getAllRoles() {
        $sql = "SELECT * FROM roles";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getRoleIdByCode($roleCode) {
        $sql = "SELECT id FROM roles WHERE code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleCode]);
        
        $row = $stmt->fetch();
        return $row ? $row['id'] : null;
    }
    
    // Récupère tous les utilisateurs avec pagination et filtrage par rôle
    public function getAllUsers($page = 1, $limit = 10, $roleCode = null) {
        $offset = ($page - 1) * $limit;
        
        $params = [];
        $whereClause = '';
        
        if ($roleCode) {
            $whereClause = "WHERE r.code = ?";
            $params[] = $roleCode;
        }
        
        $sql = "SELECT u.*, r.nom as role_nom, r.code as role_code
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                $whereClause
                ORDER BY u.nom, u.prenom
                LIMIT ?, ?";
        
        $params[] = $offset;
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    // Compte le nombre total d'utilisateurs (pour la pagination)
    public function countUsers($roleCode = null) {
        $params = [];
        $whereClause = '';
        
        if ($roleCode) {
            $whereClause = "WHERE r.code = ?";
            $params[] = $roleCode;
        }
        
        $sql = "SELECT COUNT(*) as total 
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                $whereClause";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'];
    }
    
    // Met à jour un utilisateur existant
    public function updateUser($userId, $data) {
        // Débogage: Journaliser les données reçues
        error_log("updateUser - userId: $userId - Données reçues: " . print_r($data, true));
        
        // Si un email est fourni, vérifier qu'il n'existe pas déjà pour un autre utilisateur
        if (isset($data['email'])) {
            $sql = "SELECT COUNT(*) as count FROM utilisateurs WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['email'], $userId]);
            $row = $stmt->fetch();
            
            if ($row['count'] > 0) {
                return ['success' => false, 'message' => 'Un utilisateur avec cet email existe déjà.'];
            }
        }
        
        // Construire la requête de mise à jour en fonction des données fournies
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if ($field === 'mot_de_passe' && !empty($value)) {
                // Hasher le mot de passe s'il est fourni
                $value = password_hash($value, PASSWORD_DEFAULT);
            } elseif ($field === 'mot_de_passe' && empty($value)) {
                // Si le mot de passe est vide, ne pas le mettre à jour
                continue;
            }
            
            $updateFields[] = "$field = ?";
            $params[] = $value;
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'Aucune donnée à mettre à jour.'];
        }
        
        $sql = "UPDATE utilisateurs SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $userId;
        
        // Débogage: Journaliser la requête SQL et les paramètres
        error_log("updateUser - Requête SQL: $sql");
        error_log("updateUser - Paramètres: " . print_r($params, true));
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("updateUser - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage()];
        }
    }
    
    // Supprime un utilisateur
    public function deleteUser($userId) {
        $sql = "DELETE FROM utilisateurs WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([$userId]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage()];
        }
    }
    
    // Récupère tous les utilisateurs par rôle
    public function getUsersByRoleId($roleId) {
        $sql = "SELECT u.*, r.nom as role_nom, r.code as role_code
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                WHERE u.role_id = ?
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId]);
        
        return $stmt->fetchAll();
    }
    
    // Recherche et pagination des utilisateurs par rôle
    public function searchUsersByRole($roleId, $searchTerm = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $params = [$roleId];
        
        $searchClause = '';
        if (!empty($searchTerm)) {
            $searchClause = "AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$searchTerm%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Intégrer directement les valeurs de LIMIT dans la chaîne SQL
        $sql = "SELECT u.*, r.nom as role_nom, r.code as role_code
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                WHERE u.role_id = ? $searchClause
                ORDER BY u.nom, u.prenom
                LIMIT $offset, $limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    // Compte le nombre total d'utilisateurs par rôle (pour la pagination)
    public function countUsersByRole($roleId, $searchTerm = '') {
        $params = [$roleId];
        
        $searchClause = '';
        if (!empty($searchTerm)) {
            $searchClause = "AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$searchTerm%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql = "SELECT COUNT(*) as total 
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id
                WHERE u.role_id = ? $searchClause";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'];
    }
}
