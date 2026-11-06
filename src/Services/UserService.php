<?php
/**
 * User Service & Account Management
 * Business rules: password hashing, role permissions, self-deactivation guard, last active admin protection.
 */

class UserService {
    private $db;

    public const ROLES = ['administrator', 'coordinator', 'staff'];

    public function __construct($pdo = null) {
        $this->db = $pdo ?: Database::getConnection();
    }

    public function getAll($search = null, $role = null) {
        $sql = "SELECT id, name, email, role, is_active, created_at FROM users WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (name ILIKE :search OR email ILIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        if ($role !== null && in_array($role, self::ROLES, true)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }

        $sql .= " ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Alias for compatibility
    public function getAllUsers($search = null, $role = null) {
        return $this->getAll($search, $role);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, is_active, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Alias for compatibility
    public function getUserById($id) {
        return $this->getById($id);
    }

    public function countActiveAdministrators() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'administrator' AND is_active = TRUE");
        return (int)$stmt->fetchColumn();
    }

    public function create($data) {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'staff';
        $isActive = !empty($data['is_active']);

        if ($password === '') {
            throw new Exception("All fields (name, email, password) are required.");
        }

        $this->validateUserFields($name, $email, $role);

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $insertStmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role, is_active)
            VALUES (:name, :email, :password_hash, :role, :is_active)
            RETURNING id
        ");

        $insertStmt->execute([
            ':name'          => $name,
            ':email'         => $email,
            ':password_hash' => $hash,
            ':role'          => $role,
            ':is_active'     => $isActive ? 'true' : 'false',
        ]);

        return (int)$insertStmt->fetchColumn();
    }

    // Alias for compatibility
    public function createUser($data) {
        return $this->create($data);
    }

    public function update($id, $data) {
        $user = $this->getById($id);
        if (!$user) {
            throw new Exception("User not found.");
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? $user['role'];
        $isActive = array_key_exists('is_active', $data) ? !empty($data['is_active']) : (bool)$user['is_active'];
        $password = $data['password'] ?? null;

        $this->validateUserFields($name, $email, $role, (int)$id);

        // Self-deactivation prevention rule (Page 4)
        if ((int)$id === (int)Auth::id() && !$isActive) {
            throw new Exception("Action denied: You cannot deactivate your own account.");
        }

        // Last active administrator rule enforcement
        if ($user['role'] === 'administrator' && $user['is_active']) {
            if ($role !== 'administrator' || !$isActive) {
                if ($this->countActiveAdministrators() <= 1) {
                    throw new Exception("Action denied: Cannot remove, demote or deactivate the last active administrator.");
                }
            }
        }

        $sql = "UPDATE users SET name = :name, email = :email, role = :role, is_active = :is_active";
        $params = [
            ':name'      => $name,
            ':email'     => $email,
            ':role'      => $role,
            ':is_active' => $isActive ? 'true' : 'false',
            ':id'        => (int)$id,
        ];

        if ($password !== null && trim($password) !== '') {
            $sql .= ", password_hash = :hash";
            $params[':hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";

        $updateStmt = $this->db->prepare($sql);
        return $updateStmt->execute($params);
    }

    // Alias for compatibility
    public function updateUser($id, $data) {
        return $this->update($id, $data);
    }

    public function toggleStatus($id) {
        $user = $this->getById($id);
        if (!$user) {
            throw new Exception("User not found.");
        }

        if ((int)$id === (int)Auth::id()) {
            throw new Exception("Action denied: You cannot deactivate your own account.");
        }

        $newStatus = !$user['is_active'];

        // If deactivating an administrator, ensure they are not the last active administrator
        if ($user['role'] === 'administrator' && !$newStatus) {
            if ($this->countActiveAdministrators() <= 1) {
                throw new Exception("Action denied: Cannot remove, demote or deactivate the last active administrator.");
            }
        }

        $stmt = $this->db->prepare("UPDATE users SET is_active = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute([
            ':status' => $newStatus ? 'true' : 'false',
            ':id'     => (int)$id,
        ]);
    }

    private function validateUserFields($name, $email, $role, $id = null) {
        if ($name === '' || $email === '') {
            throw new Exception("All fields (name, email, password) are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please provide a valid email address.");
        }

        if (!in_array($role, self::ROLES, true)) {
            throw new Exception("Invalid user role specified.");
        }

        $sql = "SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)";
        $params = [':email' => $email];
        if ($id !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = (int)$id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("An account with this email address already exists.");
        }
    }
}

// Aliased as User for backward and forward compatibility
class User extends UserService {}

