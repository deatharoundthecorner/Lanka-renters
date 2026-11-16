<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Customer Model
 * Manages database operations on the 'customers' table.
 */
class Customer {
    private ?PDO $db = null;

    /**
     * Model constructor.
     * Initializes connection utilizing the Singleton Database helper.
     */
    private function connection(): PDO {
        if ($this->db === null) {
            $this->db = Database::getInstance()->getConnection();
        }

        return $this->db;
    }

    /**
     * Builds display-safe Customer identity data from the authenticated user.
     * It deliberately accepts the session user record rather than request IDs.
     */
    public function identityFromAuthenticatedUser(array $user): array {
        if (($user['role'] ?? null) !== 'customer') {
            throw new InvalidArgumentException('A customer session is required.');
        }

        return [
            'user_id' => (int) ($user['id'] ?? 0),
            'name' => trim((string) ($user['name'] ?? 'Customer')) ?: 'Customer',
            'email' => trim((string) ($user['email'] ?? '')),
        ];
    }

    /**
     * Creates a new customer profile.
     * 
     * @param int $userId The associated user ID from the users table
     * @return int|false Last inserted ID or false on failure
     */
    public function create($userId) {
        $sql = "INSERT INTO `customers` (`user_id`, `verification_status`) VALUES (:user_id, 'pending')";
        $db = $this->connection();
        $stmt = $db->prepare($sql);
        if ($stmt->execute(['user_id' => $userId])) {
            return (int)$db->lastInsertId();
        }
        return false;
    }

    /**
     * Finds a customer profile by the associated user ID.
     * 
     * @param int $userId
     * @return array|false Customer record or false if not found
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM `customers` WHERE `user_id` = :user_id LIMIT 1";
        $stmt = $this->connection()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Updates customer profile details by customer ID.
     * 
     * @param int $id The customer primary ID
     * @param array $data Customer-editable profile fields
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];

        // Verification status is intentionally not customer-editable.
        $allowedFields = ['nic_number', 'driving_license_number'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `customers` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->connection()->prepare($sql);
        return $stmt->execute($params);
    }
}
