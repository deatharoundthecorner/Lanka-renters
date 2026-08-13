<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - ReplacementRequest Model
 * Handles emergency vehicle/driver replacement requests during active incidents.
 */
class ReplacementRequest {
    private PDO $db;

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

     * Gets all replacement requests with detailed incident, booking, and vehicle info.
     */
    public function getAllRequests(): array {
        $sql = "SELECT rr.*,
                       b.booking_type, b.start_date, b.end_date,
                       uc.name as customer_name, uc.phone as customer_phone,
                       v1.make as orig_make, v1.model as orig_model, v1.license_plate as orig_plate, v1.vehicle_type,
                       v2.make as rep_make, v2.model as rep_model, v2.license_plate as rep_plate,
                       ud.name as driver_name,
                       i.severity as incident_severity, i.description as incident_desc
                FROM `replacement_requests` rr
                JOIN `bookings` b ON rr.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v1 ON rr.original_vehicle_id = v1.id
                LEFT JOIN `vehicles` v2 ON rr.replacement_vehicle_id = v2.id
                LEFT JOIN `drivers` d ON b.driver_id = d.id
                LEFT JOIN `users` ud ON d.user_id = ud.id
                LEFT JOIN `incidents` i ON rr.incident_id = i.id
                ORDER BY rr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets available replacement vehicles matching type.
     */
    public function getEligibleVehicles(string $vehicleType): array {
        $sql = "SELECT v.*, uo.name as owner_name
                FROM `vehicles` v
                JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                JOIN `users` uo ON vo.user_id = uo.id
                WHERE v.status = 'available' AND v.verification_status = 'approved'";
        $params = [];
        if (!empty($vehicleType)) {
            $sql .= " AND v.vehicle_type = :vtype";
            $params['vtype'] = $vehicleType;
        }
        $sql .= " ORDER BY v.make ASC";
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
     * Assigns replacement vehicle and updates request status.
     */
    public function assignReplacement(int $requestId, int $replacementVehicleId, string $remarks = '', string $status = 'approved'): bool {
        $sql = "UPDATE `replacement_requests`
                SET `replacement_vehicle_id` = :rvid,
                    `admin_remarks` = :remarks,
                    `status` = :status
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'rvid' => $replacementVehicleId,
            'remarks' => $remarks,
            'status' => $status,
            'id' => $requestId
        ]);
    }
}
