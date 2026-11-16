<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminDriverVerification Model
 * Manages driver profile and document verification logic.
 */
class AdminDriverVerification {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets pending drivers or drivers with pending documents.
     */
    public function getPendingDrivers(): array {
        $sql = "SELECT d.*, u.name, u.email, u.phone, u.status as user_status,
                       (SELECT vo.id FROM `driver_owner_links` dol 
                        JOIN `vehicle_owners` vo ON dol.owner_id = vo.id 
                        WHERE dol.driver_id = d.id AND dol.status = 'accepted' LIMIT 1) as owner_id,
                       (SELECT u2.name FROM `driver_owner_links` dol 
                        JOIN `vehicle_owners` vo ON dol.owner_id = vo.id 
                        JOIN `users` u2 ON vo.user_id = u2.id 
                        WHERE dol.driver_id = d.id AND dol.status = 'accepted' LIMIT 1) as owner_name
                FROM `drivers` d
                JOIN `users` u ON d.user_id = u.id
                WHERE EXISTS (
                    SELECT 1 FROM `driver_documents` dd WHERE dd.driver_id = d.id AND dd.status = 'pending'
                ) OR u.status = 'pending'
                ORDER BY d.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets registered/approved drivers.
     */
    public function getRegisteredDrivers(): array {
        $sql = "SELECT d.*, u.name, u.email, u.phone, u.status as user_status,
                       (SELECT u2.name FROM `driver_owner_links` dol 
                        JOIN `vehicle_owners` vo ON dol.owner_id = vo.id 
                        JOIN `users` u2 ON vo.user_id = u2.id 
                        WHERE dol.driver_id = d.id AND dol.status = 'accepted' LIMIT 1) as owner_name
                FROM `drivers` d
                JOIN `users` u ON d.user_id = u.id
                ORDER BY d.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets documents for a driver.
     */
    public function getDriverDocuments(int $driverId): array {
        $sql = "SELECT * FROM `driver_documents` WHERE `driver_id` = :driver_id ORDER BY `uploaded_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approves driver user status and driver documents.
     */
    public function approveDriver(int $driverId): bool {
        // Fetch user_id for driver
        $stmt = $this->db->prepare("SELECT user_id FROM `drivers` WHERE `id` = :id");
        $stmt->execute(['id' => $driverId]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $stmtUser = $this->db->prepare("UPDATE `users` SET `status` = 'active' WHERE `id` = :uid");
            $stmtUser->execute(['uid' => $userId]);
        }

        $stmtDoc = $this->db->prepare("UPDATE `driver_documents` SET `status` = 'approved' WHERE `driver_id` = :did AND `status` = 'pending'");
        $stmtDoc->execute(['did' => $driverId]);

        return true;
    }

    /**
     * Rejects driver status/documents.
     */
    public function rejectDriver(int $driverId, string $reason = ''): bool {
        $stmtDoc = $this->db->prepare("UPDATE `driver_documents` SET `status` = 'rejected', `rejection_reason` = :reason WHERE `driver_id` = :did AND `status` = 'pending'");
        $stmtDoc->execute(['did' => $driverId, 'reason' => $reason]);
        return true;
    }
}
