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
}
