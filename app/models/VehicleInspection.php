<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * VehicleInspection Model
 * Encapsulates inspection records for vehicles.
 */
class VehicleInspection {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets all inspections for vehicles belonging to a specific owner.
     *
     * @param int $ownerId
     * @param string|null $statusFilter
     * @return array
     */
    public function getInspectionsByOwner($ownerId, $statusFilter = null) {
        $sql = "SELECT vi.*, 
                       v.make, v.model, v.license_plate, v.image_url,
                       b.id as booking_ref,
                       u.name as inspector_name, u.role as inspector_role
                FROM vehicle_inspections vi
                JOIN vehicles v ON vi.vehicle_id = v.id
                JOIN users u ON vi.inspected_by = u.id
                LEFT JOIN bookings b ON vi.booking_id = b.id
                WHERE v.owner_id = :owner_id";

        $params = ['owner_id' => (int)$ownerId];

        if (!empty($statusFilter) && in_array($statusFilter, ['pass', 'fail', 'needs_maintenance'], true)) {
            $sql .= " AND vi.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY vi.inspection_date DESC, vi.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets inspection summary stats for an owner's fleet.
     *
     * @param int $ownerId
     * @return array
     */
    public function getSummaryByOwner($ownerId) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN vi.status = 'pass' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN vi.status = 'needs_maintenance' THEN 1 ELSE 0 END) as needs_maintenance,
                    SUM(CASE WHEN vi.status = 'fail' THEN 1 ELSE 0 END) as failed
                FROM vehicle_inspections vi
                JOIN vehicles v ON vi.vehicle_id = v.id
                WHERE v.owner_id = :owner_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner_id' => (int)$ownerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total'             => (int)($row['total'] ?? 0),
            'passed'            => (int)($row['passed'] ?? 0),
            'needs_maintenance' => (int)($row['needs_maintenance'] ?? 0),
            'failed'            => (int)($row['failed'] ?? 0),
        ];
    }
}
