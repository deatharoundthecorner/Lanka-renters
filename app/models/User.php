<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - User Model
 * Manages database operations on the 'users' table, including credentials, 
 * lookups, hashing verification, status modifications, and registrations.
 */
class User {
    // Database connection instance
    private $db;

    /**
     * Model constructor.
     * Initializes connection utilizing the Singleton Database helper.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Locates a user account record matching the provided email address.
     * Crucial for user login verification.
     * 
     * @param string $email The unique user email address
     * @return array|false The user's database record (associative array) or false if not found
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM `users` WHERE `email` = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Retrieves full user metadata by primary key ID.
     * 
     * @param int $id The user's ID
     * @return array|false The user record or false if not found
     */
    public function findById($id) {
        $sql = "SELECT * FROM `users` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    /**
     * Updates a user's own editable contact fields (name, phone).
     * Email/role/status are intentionally excluded from self-service edits.
     *
     * @param int $id
     * @param array $data ['name' => ..., 'phone' => ...]
     * @return bool
     */
    public function updateContactInfo($id, $data) {
        $sql = "UPDATE `users` SET `name` = :name, `phone` = :phone WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'    => $id,
            'name'  => $data['name'],
            'phone' => $data['phone'],
        ]);
    }

    /**
     * Registers a new user inside the system.
     * Automatically secures raw credentials with standard crypt hashing.
     * 
     * @param array $data Associative array containing 'name', 'email', 'password', 'phone', 'role', and optional 'status'
     * @return int|false Returns the auto-incremented database ID on success, or false on failure
     */
    public function create($data) {
        // Encrypt raw passwords securely using the current default algorithm (Bcrypt)
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) 
                VALUES (:name, :email, :password_hash, :phone, :role, :status)";
        
        $stmt = $this->db->prepare($sql);
        
        $status = $data['status'] ?? 'active';
        
        $params = [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => $passwordHash,
            'phone'         => $data['phone'],
            'role'          => $data['role'],
            'status'        => $status
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Verifies if a user-submitted plaintext password matches the secure database hash.
     * 
     * @param string $password The plaintext password entered by the user
     * @param string $hash The hashed password from the database
     * @return bool True if they match, false otherwise
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Updates a user status value (e.g. 'active', 'inactive', 'suspended').
     * 
     * @param int $id The user's database ID
     * @param string $status Target status value
     * @return bool True on query success, false on failure
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE `users` SET `status` = :status WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'     => $id,
            'status' => $status
        ]);
    }

    /**
     * Returns platform users without exposing password hashes.
     */
    public function getAllManagedUsers() {
        $sql = "SELECT `id`, `name`, `email`, `phone`, `role`, `status`, `created_at`, `updated_at`
                FROM `users` ORDER BY `created_at` DESC, `id` DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Creates a user and the matching role profile as one transaction.
     */
    public function createManagedUser($data) {
        $this->db->beginTransaction();
        try {
            $userId = $this->create($data);
            if (!$userId) {
                throw new RuntimeException('User record could not be created.');
            }

            $this->createRoleProfile($userId, $data['role']);
            $this->db->commit();
            return $userId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Updates Admin-managed account fields and keeps the role profile aligned.
     */
    public function updateManagedUser($id, $data) {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT `role` FROM `users` WHERE `id` = :id LIMIT 1 FOR UPDATE');
            $lock->execute(['id' => (int)$id]);
            $existing = $lock->fetch();
            if (!$existing) {
                $this->db->rollBack();
                return false;
            }

            $oldRole = $existing['role'];
            $newRole = $data['role'];
            if ($oldRole !== $newRole) {
                $this->deleteRoleProfile((int)$id, $oldRole);
            }

            $sql = "UPDATE `users`
                    SET `name` = :name, `email` = :email, `phone` = :phone,
                        `role` = :role, `status` = :status
                    WHERE `id` = :id";
            $statement = $this->db->prepare($sql);
            $statement->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => $newRole,
                'status' => $data['status'],
                'id' => (int)$id,
            ]);

            if ($oldRole !== $newRole) {
                $this->createRoleProfile((int)$id, $newRole);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Deletes a platform user. Existing foreign keys decide whether historical
     * records permit the deletion.
     */
    public function deleteManagedUser($id) {
        $statement = $this->db->prepare('DELETE FROM `users` WHERE `id` = :id');
        $statement->execute(['id' => (int)$id]);
        return $statement->rowCount() > 0;
    }

    private function createRoleProfile($userId, $role) {
        $queries = [
            'customer' => 'INSERT INTO `customers` (`user_id`) VALUES (:user_id)',
            'owner' => 'INSERT INTO `vehicle_owners` (`user_id`) VALUES (:user_id)',
            'driver' => 'INSERT INTO `drivers` (`user_id`) VALUES (:user_id)',
        ];
        if (isset($queries[$role])) {
            $statement = $this->db->prepare($queries[$role]);
            $statement->execute(['user_id' => (int)$userId]);
        }
    }

    private function deleteRoleProfile($userId, $role) {
        $tables = [
            'customer' => 'customers',
            'owner' => 'vehicle_owners',
            'driver' => 'drivers',
        ];
        if (isset($tables[$role])) {
            $statement = $this->db->prepare("DELETE FROM `{$tables[$role]}` WHERE `user_id` = :user_id");
            $statement->execute(['user_id' => (int)$userId]);
        }
    }
}
