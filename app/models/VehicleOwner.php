<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Vehicle Owner Model
 * Manages database operations on the 'vehicle_owners' table.
 */
class VehicleOwner {
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
     * Creates a new vehicle owner profile.
     * 
     * @param int $userId The associated user ID from the users table
     * @return int|false Last inserted ID or false on failure
     */
    public function create($userId) {
        $sql = "INSERT INTO `vehicle_owners` (`user_id`, `owner_type`, `verification_status`) 
                VALUES (:user_id, 'individual', 'pending')";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute(['user_id' => $userId])) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Finds a vehicle owner profile by the associated user ID.
     * 
     * @param int $userId
     * @return array|false Owner record or false if not found
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM `vehicle_owners` WHERE `user_id` = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Updates vehicle owner profile details by owner ID.
     * 
     * @param int $id The owner primary ID
     * @param array $data Array of updates (owner_type, bank_name, bank_account_no, bank_branch, verification_status)
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['owner_type', 'bank_name', 'bank_account_no', 'bank_branch', 'verification_status'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `vehicle_owners` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
