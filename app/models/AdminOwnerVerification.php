<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminOwnerVerification Model
 * Handles database operations for vehicle owner verification and management.
 */
class AdminOwnerVerification {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves pending vehicle owner registrations.
     */
    public function getPendingOwners(): array {
        $sql = "SELECT vo.*, u.name, u.email, u.phone, u.status as user_status
                FROM `vehicle_owners` vo
                JOIN `users` u ON vo.user_id = u.id
                WHERE vo.verification_status = 'pending'
                ORDER BY vo.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves reviewed/registered vehicle owners.
     */
    public function getReviewedOwners(): array {
        $sql = "SELECT vo.*, u.name, u.email, u.phone, u.status as user_status,
                       (SELECT COUNT(*) FROM `vehicles` v WHERE v.owner_id = vo.id) as total_vehicles
                FROM `vehicle_owners` vo
                JOIN `users` u ON vo.user_id = u.id
                WHERE vo.verification_status IN ('approved', 'rejected')
                ORDER BY vo.updated_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approves a vehicle owner registration.
     */
    public function approveOwner(int $ownerId): bool {
        $sql = "UPDATE `vehicle_owners` SET `verification_status` = 'approved' WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $ownerId]);
    }

    /**
     * Rejects a vehicle owner registration.
     */
    public function rejectOwner(int $ownerId): bool {
        $sql = "UPDATE `vehicle_owners` SET `verification_status` = 'rejected' WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $ownerId]);
    }
}
