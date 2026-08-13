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
