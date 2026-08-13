<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * ReplacementRequest Model
 * Encapsulates replacement vehicle requests logged during vehicle incidents.
 */
class ReplacementRequest {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets replacement requests for vehicles belonging to an owner.
     *
     * @param int $ownerId
     * @param string|null $statusFilter
     * @return array
     */
    public function getRequestsByOwner($ownerId, $statusFilter = null) {
        $sql = "SELECT rr.*, 
                       inc.description as incident_description, inc.severity as incident_severity, inc.incident_date,
                       b.id as booking_ref,
                       ov.make as orig_make, ov.model as orig_model, ov.license_plate as orig_plate,
                       rv.make as rep_make, rv.model as rep_model, rv.license_plate as rep_plate,
                       u.name as requester_name, u.role as requester_role
                FROM replacement_requests rr
                JOIN incidents inc ON rr.incident_id = inc.id
                JOIN bookings b ON rr.booking_id = b.id
                JOIN vehicles ov ON rr.original_vehicle_id = ov.id
                LEFT JOIN vehicles rv ON rr.replacement_vehicle_id = rv.id
                JOIN users u ON rr.requested_by = u.id
                WHERE (ov.owner_id = :owner_id OR (rv.id IS NOT NULL AND rv.owner_id = :owner_id2))";

        $params = [
            'owner_id'  => (int)$ownerId,
            'owner_id2' => (int)$ownerId
        ];

        if (!empty($statusFilter)) {
            $sql .= " AND rr.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY rr.created_at DESC, rr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets replacement request summary stats for an owner.
     *
     * @param int $ownerId
     * @return array
     */
    public function getSummaryByOwner($ownerId) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN rr.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN rr.status IN ('approved', 'dispatched', 'delivered') THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN rr.status IN ('rejected', 'cancelled') THEN 1 ELSE 0 END) as rejected
                FROM replacement_requests rr
                JOIN vehicles ov ON rr.original_vehicle_id = ov.id
                LEFT JOIN vehicles rv ON rr.replacement_vehicle_id = rv.id
                WHERE (ov.owner_id = :owner_id OR (rv.id IS NOT NULL AND rv.owner_id = :owner_id2))";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'owner_id'  => (int)$ownerId,
            'owner_id2' => (int)$ownerId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total'    => (int)($row['total'] ?? 0),
            'pending'  => (int)($row['pending'] ?? 0),
            'approved' => (int)($row['approved'] ?? 0),
            'rejected' => (int)($row['rejected'] ?? 0),
        ];
    }

    /**
     * Updates replacement request status (with ownership authorization check).
     *
     * @param int $requestId
     * @param int $ownerId
     * @param string $newStatus
     * @param int|null $replacementVehicleId
     * @param string|null $remarks
     * @return bool
     */
    public function updateStatus($requestId, $ownerId, $newStatus, $replacementVehicleId = null, $remarks = null) {
        $allowedStatuses = ['approved', 'rejected', 'dispatched', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return false;
        }

        // Verify request belongs to owner's vehicle
        $sqlCheck = "SELECT rr.id FROM replacement_requests rr
                     JOIN vehicles ov ON rr.original_vehicle_id = ov.id
                     LEFT JOIN vehicles rv ON rr.replacement_vehicle_id = rv.id
                     WHERE rr.id = :request_id 
                       AND (ov.owner_id = :owner_id OR (rv.id IS NOT NULL AND rv.owner_id = :owner_id2))";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([
            'request_id' => (int)$requestId,
            'owner_id'   => (int)$ownerId,
            'owner_id2'  => (int)$ownerId
        ]);
        if (!$stmt->fetch()) {
            return false;
        }

        $sql = "UPDATE replacement_requests 
                SET status = :status, 
                    replacement_vehicle_id = COALESCE(:replacement_vehicle_id, replacement_vehicle_id),
                    admin_remarks = COALESCE(:remarks, admin_remarks),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status'                 => $newStatus,
            'replacement_vehicle_id' => $replacementVehicleId ? (int)$replacementVehicleId : null,
            'remarks'                => $remarks ? trim($remarks) : null,
            'id'                     => (int)$requestId
        ]);
    }
}
