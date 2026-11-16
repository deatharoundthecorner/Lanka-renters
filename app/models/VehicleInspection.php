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
        $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($inspections !== []) {
            $ids = array_column($inspections, 'id');
            $photosMap = $this->getPhotosByInspectionIds($ids);
            foreach ($inspections as &$insp) {
                $insp['photos'] = $photosMap[$insp['id']] ?? [];
            }
            unset($insp);
        }
        return $inspections;
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

    /**
     * Checks if a passing pre-rental inspection exists for a specific booking.
     */
    public function hasPassedPreRentalInspection(int $bookingId): bool {
        $stmt = $this->db->prepare("
            SELECT id FROM vehicle_inspections
            WHERE booking_id = :booking_id
              AND inspection_type = 'pre_rental'
              AND status = 'pass'
            LIMIT 1
        ");
        $stmt->execute(['booking_id' => $bookingId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Retrieves the latest pre-rental inspection for a specific booking.
     */
    public function getPreRentalInspectionForBooking(int $bookingId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM vehicle_inspections
            WHERE booking_id = :booking_id
              AND inspection_type = 'pre_rental'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['booking_id' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Retrieves confirmed bookings for an owner that are eligible for pre-rental inspection.
     * Includes vehicle and customer information, plus any existing pre-rental inspection record.
     */
    public function getEligibleConfirmedBookingsByOwner(int $ownerId): array {
        $stmt = $this->db->prepare("
            SELECT b.id, b.start_date, b.end_date, b.booking_type, b.status, b.pickup_status,
                   v.id AS vehicle_id, v.make, v.model, v.license_plate,
                   u.name AS customer_name, u.phone AS customer_phone,
                   vi.id AS pre_rental_inspection_id,
                   vi.status AS pre_rental_status
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN customers c ON b.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN vehicle_inspections vi ON vi.booking_id = b.id AND vi.inspection_type = 'pre_rental'
            WHERE v.owner_id = :owner_id
              AND b.status = 'confirmed'
            ORDER BY b.start_date ASC, b.id ASC
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a pre-rental inspection for a confirmed booking belonging to the authenticated owner.
     */
    public function createPreRentalInspection(
        int $bookingId,
        int $ownerUserId,
        int $odometerReading,
        string $fuelLevel,
        string $exteriorCondition,
        string $interiorCondition,
        string $status,
        ?string $comments = null
    ): array {
        // 1. Verify booking exists, is confirmed, and vehicle belongs to owner
        $stmt = $this->db->prepare("
            SELECT b.id, b.status, b.vehicle_id, v.owner_id
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN vehicle_owners vo ON v.owner_id = vo.id
            WHERE b.id = :booking_id AND vo.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id'    => $ownerUserId,
        ]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return [
                'success' => false,
                'error'   => 'Booking not found or does not belong to your vehicle fleet.'
            ];
        }

        if ($booking['status'] !== 'confirmed') {
            return [
                'success' => false,
                'error'   => 'Pre-rental inspection can only be recorded for confirmed bookings.'
            ];
        }

        // 2. Prevent duplicate pre-rental inspection
        $existing = $this->getPreRentalInspectionForBooking($bookingId);
        if ($existing) {
            return [
                'success' => false,
                'error'   => 'A pre-rental inspection has already been recorded for Booking #' . $bookingId . '.'
            ];
        }

        // 3. Validate status ENUM
        if (!in_array($status, ['pass', 'fail', 'needs_maintenance'], true)) {
            return [
                'success' => false,
                'error'   => 'Invalid inspection status specified.'
            ];
        }

        // 4. Insert pre-rental inspection record
        $insertStmt = $this->db->prepare("
            INSERT INTO vehicle_inspections (
                vehicle_id,
                booking_id,
                inspected_by,
                inspection_type,
                odometer_reading,
                exterior_condition,
                interior_condition,
                fuel_level,
                status,
                comments
            ) VALUES (
                :vehicle_id,
                :booking_id,
                :inspected_by,
                'pre_rental',
                :odometer_reading,
                :exterior_condition,
                :interior_condition,
                :fuel_level,
                :status,
                :comments
            )
        ");

        $insertStmt->execute([
            'vehicle_id'         => (int)$booking['vehicle_id'],
            'booking_id'         => $bookingId,
            'inspected_by'       => $ownerUserId,
            'odometer_reading'   => $odometerReading,
            'exterior_condition' => trim($exteriorCondition),
            'interior_condition' => trim($interiorCondition),
            'fuel_level'         => trim($fuelLevel),
            'status'             => $status,
            'comments'           => $comments !== null && trim($comments) !== '' ? trim($comments) : null,
        ]);

        $newId = (int)$this->db->lastInsertId();

        return [
            'success'       => true,
            'inspection_id' => $newId,
            'message'       => 'Pre-rental inspection #INSP-' . $newId . ' recorded successfully for Booking #' . $bookingId . '.'
        ];
    }

    /**
     * Checks if a post-rental inspection exists for a specific booking.
     */
    public function hasPostRentalInspection(int $bookingId): bool {
        $stmt = $this->db->prepare("
            SELECT id FROM vehicle_inspections
            WHERE booking_id = :booking_id
              AND inspection_type = 'post_rental'
            LIMIT 1
        ");
        $stmt->execute(['booking_id' => $bookingId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Retrieves the latest post-rental inspection for a specific booking.
     */
    public function getPostRentalInspectionForBooking(int $bookingId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM vehicle_inspections
            WHERE booking_id = :booking_id
              AND inspection_type = 'post_rental'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['booking_id' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Retrieves ongoing bookings for an owner that are eligible for post-rental inspection upon return.
     * Includes vehicle and customer information, plus any existing post-rental inspection record.
     */
    public function getEligibleOngoingBookingsByOwner(int $ownerId): array {
        $stmt = $this->db->prepare("
            SELECT b.id, b.start_date, b.end_date, b.booking_type, b.status, b.pickup_status,
                   v.id AS vehicle_id, v.make, v.model, v.license_plate,
                   u.name AS customer_name, u.phone AS customer_phone,
                   vi.id AS post_rental_inspection_id,
                   vi.status AS post_rental_status
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN customers c ON b.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN vehicle_inspections vi ON vi.booking_id = b.id AND vi.inspection_type = 'post_rental'
            WHERE v.owner_id = :owner_id
              AND b.status = 'ongoing'
            ORDER BY b.start_date ASC, b.id ASC
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a post-rental inspection for an ongoing booking belonging to the authenticated owner.
     */
    public function createPostRentalInspection(
        int $bookingId,
        int $ownerUserId,
        int $odometerReading,
        string $fuelLevel,
        string $exteriorCondition,
        string $interiorCondition,
        string $status,
        ?string $comments = null
    ): array {
        // 1. Verify booking exists, is ongoing, and vehicle belongs to owner
        $stmt = $this->db->prepare("
            SELECT b.id, b.status, b.vehicle_id, v.owner_id
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN vehicle_owners vo ON v.owner_id = vo.id
            WHERE b.id = :booking_id AND vo.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([
            'booking_id' => $bookingId,
            'user_id'    => $ownerUserId,
        ]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            return [
                'success' => false,
                'error'   => 'Booking not found or does not belong to your vehicle fleet.'
            ];
        }

        if ($booking['status'] !== 'ongoing') {
            return [
                'success' => false,
                'error'   => 'Post-rental inspection can only be recorded for ongoing bookings.'
            ];
        }

        // 2. Prevent duplicate post-rental inspection
        $existing = $this->getPostRentalInspectionForBooking($bookingId);
        if ($existing) {
            return [
                'success' => false,
                'error'   => 'A post-rental inspection has already been recorded for Booking #' . $bookingId . '.'
            ];
        }

        // 3. Validate status ENUM
        if (!in_array($status, ['pass', 'fail', 'needs_maintenance'], true)) {
            return [
                'success' => false,
                'error'   => 'Invalid inspection status specified.'
            ];
        }

        // 4. Insert post-rental inspection record
        $insertStmt = $this->db->prepare("
            INSERT INTO vehicle_inspections (
                vehicle_id,
                booking_id,
                inspected_by,
                inspection_type,
                odometer_reading,
                exterior_condition,
                interior_condition,
                fuel_level,
                status,
                comments
            ) VALUES (
                :vehicle_id,
                :booking_id,
                :inspected_by,
                'post_rental',
                :odometer_reading,
                :exterior_condition,
                :interior_condition,
                :fuel_level,
                :status,
                :comments
            )
        ");

        $insertStmt->execute([
            'vehicle_id'         => (int)$booking['vehicle_id'],
            'booking_id'         => $bookingId,
            'inspected_by'       => $ownerUserId,
            'odometer_reading'   => $odometerReading,
            'exterior_condition' => trim($exteriorCondition),
            'interior_condition' => trim($interiorCondition),
            'fuel_level'         => trim($fuelLevel),
            'status'             => $status,
            'comments'           => $comments !== null && trim($comments) !== '' ? trim($comments) : null,
        ]);

        $newId = (int)$this->db->lastInsertId();

        return [
            'success'       => true,
            'inspection_id' => $newId,
            'message'       => 'Post-rental inspection #INSP-' . $newId . ' recorded successfully for Booking #' . $bookingId . '.'
        ];
    }

    /**
     * Adds an inspection photographic record.
     */
    public function addPhoto(int $inspectionId, string $photoPath, ?string $caption = null): int {
        $stmt = $this->db->prepare("
            INSERT INTO vehicle_inspection_photos (inspection_id, photo_path, caption)
            VALUES (:inspection_id, :photo_path, :caption)
        ");
        $stmt->execute([
            'inspection_id' => $inspectionId,
            'photo_path'    => $photoPath,
            'caption'       => $caption !== null && trim($caption) !== '' ? trim($caption) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Retrieves all photos for a single inspection.
     */
    public function getPhotosByInspectionId(int $inspectionId): array {
        $stmt = $this->db->prepare("
            SELECT id, inspection_id, photo_path, caption, created_at
            FROM vehicle_inspection_photos
            WHERE inspection_id = :inspection_id
            ORDER BY id ASC
        ");
        $stmt->execute(['inspection_id' => $inspectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves photos grouped by inspection ID for an array of inspection IDs.
     */
    public function getPhotosByInspectionIds(array $inspectionIds): array {
        if ($inspectionIds === []) {
            return [];
        }
        $intIds = array_values(array_filter(array_map('intval', $inspectionIds)));
        if ($intIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($intIds), '?'));
        $stmt = $this->db->prepare("
            SELECT id, inspection_id, photo_path, caption, created_at
            FROM vehicle_inspection_photos
            WHERE inspection_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $stmt->execute($intIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['inspection_id']][] = $row;
        }
        return $grouped;
    }
}
