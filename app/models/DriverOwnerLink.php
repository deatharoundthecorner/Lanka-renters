<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Driver Owner Link Model
 * Manages links between drivers and vehicle owners, status transitions, and lookups.
 */
class DriverOwnerLink {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates or updates a link request from owner to driver.
     * Enforces the status flow and unique pairing.
     * 
     * @param int $driverId Driver ID
     * @param int $ownerId Vehicle Owner ID
     * @return bool True on success
     */
    public function requestLink($driverId, $ownerId) {
        $sql = "INSERT INTO `driver_owner_links` (`driver_id`, `owner_id`, `status`, `created_at`, `accepted_at`) 
                VALUES (:driver_id, :owner_id, 'pending', CURRENT_TIMESTAMP, NULL)
                ON DUPLICATE KEY UPDATE `status` = 'pending', `created_at` = CURRENT_TIMESTAMP, `accepted_at` = NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'driver_id' => $driverId,
            'owner_id'  => $ownerId
        ]);
    }

    /**
     * Updates the link status (accepted, rejected, blocked).
     * 
     * @param int $linkId Link ID
     * @param string $status Target status ('accepted', 'rejected', 'blocked')
     * @return bool True on success
     */
    public function updateStatus($linkId, $status) {
        $allowed = ['accepted', 'rejected', 'blocked'];
        if (!in_array($status, $allowed)) {
            throw new InvalidArgumentException("Invalid status transition.");
        }

        $acceptedAtSql = ($status === 'accepted') ? ", `accepted_at` = CURRENT_TIMESTAMP" : "";

        $sql = "UPDATE `driver_owner_links` 
                SET `status` = :status {$acceptedAtSql} 
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id'     => $linkId
        ]);
    }

    /**
     * Retrieves a connection link by its ID.
     */
    public function findById($linkId) {
        $sql = "SELECT * FROM `driver_owner_links` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $linkId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves active/accepted drivers linked to a specific vehicle owner.
     * 
     * @param int $ownerId Vehicle Owner ID
     * @return array List of accepted drivers with user details
     */
    public function getAcceptedDriversByOwner($ownerId) {
        $sql = "SELECT dol.*, d.availability_status, d.rating_avg, u.name, u.email, u.phone 
                FROM `driver_owner_links` dol
                JOIN `drivers` d ON dol.driver_id = d.id
                JOIN `users` u ON d.user_id = u.id
                WHERE dol.owner_id = :owner_id AND dol.status = 'accepted'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner_id' => $ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves link requests for a specific driver.
     * 
     * @param int $driverId Driver ID
     * @param string|null $status Filter by status
     * @return array List of owner connection requests
     */
    public function getLinksByDriver($driverId, $status = null) {
        $sql = "SELECT dol.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
                       (SELECT COUNT(*) FROM `vehicles` v WHERE v.owner_id = dol.owner_id) as vehicle_count
                FROM `driver_owner_links` dol
                JOIN `vehicle_owners` vo ON dol.owner_id = vo.id
                JOIN `users` u ON vo.user_id = u.id
                WHERE dol.driver_id = :driver_id";
        
        $params = ['driver_id' => $driverId];
        if ($status !== null) {
            $sql .= " AND dol.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY dol.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of links for a driver by status.
     */
    public function getLinkCountByDriver($driverId, $status) {
        $sql = "SELECT COUNT(*) FROM `driver_owner_links` WHERE `driver_id` = :driver_id AND `status` = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id' => $driverId,
            'status'    => $status
        ]);
        return (int)$stmt->fetchColumn();
    }
}
