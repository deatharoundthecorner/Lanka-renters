<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminIncidentManagement Model
 * Handles incident reporting review, evidence photo inspection, and status resolutions.
 */
class AdminIncidentManagement {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves all incidents with reporter, vehicle, booking, and evidence details.
     */
    public function getIncidents(array $filters = []): array {
        $sql = "SELECT i.*,
                       ur.name as reporter_name, ur.email as reporter_email, ur.role as reporter_role,
                       b.booking_type, b.start_date, b.end_date,
                       uc.name as customer_name,
                       v.make, v.model, v.license_plate, v.vehicle_type,
                       uo.name as owner_name,
                       (SELECT ip.photo_path FROM `incident_photos` ip WHERE ip.incident_id = i.id LIMIT 1) as evidence_photo
                FROM `incidents` i
                JOIN `users` ur ON i.reported_by = ur.id
                JOIN `bookings` b ON i.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                JOIN `users` uo ON vo.user_id = uo.id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates incident status ('reported', 'investigating', 'resolved').
     * When resolved, atomically transitions the related active ongoing booking to 'disputed'.
     */
    public function updateStatus(int $incidentId, string $status): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE `incidents` SET `status` = :status WHERE `id` = :id");
            $stmt->execute(['status' => $status, 'id' => $incidentId]);

            // When incident is resolved, move the related ongoing booking to disputed
            if ($status === 'resolved') {
                $stmtBooking = $this->db->prepare("
                    UPDATE `bookings` b
                    JOIN `incidents` i ON i.booking_id = b.id
                    SET b.status = 'disputed'
                    WHERE i.id = :incident_id AND b.status = 'ongoing'
                ");
                $stmtBooking->execute(['incident_id' => $incidentId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Incident updateStatus error: " . $e->getMessage());
            return false;
        }
    }
}

