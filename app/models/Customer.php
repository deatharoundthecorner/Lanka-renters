<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Customer Model
 * Manages database operations on the 'customers' table.
 */
class Customer {
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
     * Creates a new customer profile.
     * 
     * @param int $userId The associated user ID from the users table
     * @return int|false Last inserted ID or false on failure
     */
    public function create($userId) {
        $sql = "INSERT INTO `customers` (`user_id`, `verification_status`) VALUES (:user_id, 'pending')";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute(['user_id' => $userId])) {
            return (int)$this->db->lastInsertId();
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
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Updates customer profile details by customer ID.
     * 
     * @param int $id The customer primary ID
     * @param array $data Array of updates (nic_number, driving_license_number, verification_status)
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['nic_number', 'driving_license_number', 'verification_status'];
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
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
