<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminVehicleVerification Model
 * Handles vehicle approval, verification, and status monitoring.
 */
class AdminVehicleVerification {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets pending vehicles waiting for review.
     */
    public function getPendingVehicles(): array {
        $sql = "SELECT v.*, vo.owner_type, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
                FROM `vehicles` v
                JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                JOIN `users` u ON vo.user_id = u.id
                WHERE v.verification_status = 'pending'
                ORDER BY v.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets registered / reviewed vehicles.
     */
    public function getRegisteredVehicles(): array {
        $sql = "SELECT v.*, vo.owner_type, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
                FROM `vehicles` v
                JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                JOIN `users` u ON vo.user_id = u.id
                WHERE v.verification_status IN ('approved', 'rejected')
                ORDER BY v.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approves a vehicle.
     */
    public function approveVehicle(int $vehicleId): bool {
        $sql = "UPDATE `vehicles` SET `verification_status` = 'approved', `status` = 'available' WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $vehicleId]);
    }

    /**
     * Rejects a vehicle.
     */
    public function rejectVehicle(int $vehicleId): bool {
        $sql = "UPDATE `vehicles` SET `verification_status` = 'rejected', `status` = 'unavailable' WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $vehicleId]);
    }
}
