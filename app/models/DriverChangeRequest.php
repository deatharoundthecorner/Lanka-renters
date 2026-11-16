<?php

require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/Notification.php';
require_once dirname(__DIR__) . '/models/CustomerBooking.php';

class DriverChangeRequestException extends RuntimeException
{
}

/**
 * Lanka Renters - DriverChangeRequest Model
 * Manages database-backed customer driver change requests, driver eligibility
 * validation, and atomic owner approval/rejection transactions.
 */
class DriverChangeRequest
{
    private const REQUIRED_DRIVER_DOCUMENTS = ['nic', 'driving_license', 'police_report'];
    private const ELIGIBLE_BOOKING_STATUSES = ['confirmed', 'ongoing'];

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Creates a new database-backed driver change request for an authenticated customer.
     */
    public function create(int $customerUserId, int $bookingId, int $requestedDriverId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw new DriverChangeRequestException('Explain the reason using at least 10 characters.');
        }

        // 1. Verify booking ownership and eligibility
        $stmt = $this->db->prepare("
            SELECT b.id, b.status, b.booking_type, b.driver_id, b.start_date, b.end_date,
                   v.id AS vehicle_id, v.owner_id, v.make, v.model,
                   c.user_id AS customer_user_id
            FROM bookings b
            JOIN customers c ON c.id = b.customer_id
            JOIN vehicles v ON v.id = b.vehicle_id
            WHERE b.id = :booking_id
              AND c.user_id = :customer_user_id
            LIMIT 1
        ");
        $stmt->execute([
            'booking_id'        => $bookingId,
            'customer_user_id'  => $customerUserId,
        ]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new DriverChangeRequestException('Booking not found or does not belong to your account.');
        }

        if ($booking['booking_type'] !== 'with_driver' || empty($booking['driver_id'])) {
            throw new DriverChangeRequestException('Driver change requests are only allowed for with-driver bookings with an assigned driver.');
        }

        if (!in_array($booking['status'], self::ELIGIBLE_BOOKING_STATUSES, true)) {
            throw new DriverChangeRequestException('Driver change requests are only available for confirmed or ongoing bookings.');
        }

        $currentDriverId = (int)$booking['driver_id'];
        if ($requestedDriverId === $currentDriverId) {
            throw new DriverChangeRequestException('The requested driver is already assigned to this booking.');
        }

        // 2. Prevent duplicate pending requests
        $stmtPending = $this->db->prepare("
            SELECT id FROM driver_change_requests
            WHERE booking_id = :booking_id AND status = 'pending'
            LIMIT 1
        ");
        $stmtPending->execute(['booking_id' => $bookingId]);
        if ($stmtPending->fetchColumn()) {
            throw new DriverChangeRequestException('A pending driver change request already exists for this booking.');
        }

        // 3. Validate requested driver eligibility
        $this->assertDriverEligible(
            $requestedDriverId,
            (int)$booking['owner_id'],
            $booking['start_date'],
            $booking['end_date'],
            $bookingId
        );

        // 4. Insert into database
        $sql = "INSERT INTO driver_change_requests (
                    booking_id,
                    requested_by,
                    current_driver_id,
                    requested_driver_id,
                    reason,
                    status
                ) VALUES (
                    :booking_id,
                    :requested_by,
                    :current_driver_id,
                    :requested_driver_id,
                    :reason,
                    'pending'
                )";
        $stmtInsert = $this->db->prepare($sql);
        $stmtInsert->execute([
            'booking_id'          => $bookingId,
            'requested_by'        => $customerUserId,
            'current_driver_id'   => $currentDriverId,
            'requested_driver_id' => $requestedDriverId,
            'reason'              => $reason,
        ]);

        $requestId = (int)$this->db->lastInsertId();

        return [
            'id'                  => $requestId,
            'booking_id'          => $bookingId,
            'requested_by'        => $customerUserId,
            'current_driver_id'   => $currentDriverId,
            'requested_driver_id' => $requestedDriverId,
            'reason'              => $reason,
            'status'              => 'pending',
        ];
    }

    /**
     * Retrieves a single driver change request by ID with all related display details.
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT dcr.*,
                       b.status AS booking_status, b.booking_type, b.start_date, b.end_date,
                       v.id AS vehicle_id, v.make, v.model, v.license_plate, v.owner_id,
                       u_req.name AS requester_name, u_req.email AS requester_email, u_req.phone AS requester_phone,
                       u_curr.name AS current_driver_name, u_curr.phone AS current_driver_phone,
                       u_req_drv.name AS requested_driver_name, u_req_drv.phone AS requested_driver_phone,
                       d_req.rating_avg AS requested_driver_rating,
                       u_rev.name AS reviewer_name
                FROM driver_change_requests dcr
                JOIN bookings b ON b.id = dcr.booking_id
                JOIN vehicles v ON v.id = b.vehicle_id
                JOIN users u_req ON u_req.id = dcr.requested_by
                JOIN drivers d_curr ON d_curr.id = dcr.current_driver_id
                JOIN users u_curr ON u_curr.id = d_curr.user_id
                JOIN drivers d_req ON d_req.id = dcr.requested_driver_id
                JOIN users u_req_drv ON u_req_drv.id = d_req.user_id
                LEFT JOIN users u_rev ON u_rev.id = dcr.reviewed_by
                WHERE dcr.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Lists all requests created by a specific customer user.
     */
    public function findByCustomer(int $customerUserId): array
    {
        $sql = "SELECT dcr.*,
                       b.status AS booking_status, b.start_date, b.end_date,
                       v.make, v.model, v.license_plate,
                       u_curr.name AS current_driver_name,
                       u_req_drv.name AS requested_driver_name
                FROM driver_change_requests dcr
                JOIN bookings b ON b.id = dcr.booking_id
                JOIN vehicles v ON v.id = b.vehicle_id
                JOIN drivers d_curr ON d_curr.id = dcr.current_driver_id
                JOIN users u_curr ON u_curr.id = d_curr.user_id
                JOIN drivers d_req ON d_req.id = dcr.requested_driver_id
                JOIN users u_req_drv ON u_req_drv.id = d_req.user_id
                WHERE dcr.requested_by = :customer_user_id
                ORDER BY dcr.created_at DESC, dcr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['customer_user_id' => $customerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lists requests for bookings associated with vehicles owned by an owner.
     */
    public function findByOwner(int $ownerId, ?string $statusFilter = null): array
    {
        $params = ['owner_id' => $ownerId];
        $sql = "SELECT dcr.*,
                       b.status AS booking_status, b.start_date, b.end_date,
                       v.make, v.model, v.license_plate,
                       u_req.name AS customer_name, u_req.phone AS customer_phone,
                       u_curr.name AS current_driver_name,
                       u_req_drv.name AS requested_driver_name, d_req.rating_avg AS requested_driver_rating
                FROM driver_change_requests dcr
                JOIN bookings b ON b.id = dcr.booking_id
                JOIN vehicles v ON v.id = b.vehicle_id
                JOIN users u_req ON u_req.id = dcr.requested_by
                JOIN drivers d_curr ON d_curr.id = dcr.current_driver_id
                JOIN users u_curr ON u_curr.id = d_curr.user_id
                JOIN drivers d_req ON d_req.id = dcr.requested_driver_id
                JOIN users u_req_drv ON u_req_drv.id = d_req.user_id
                WHERE v.owner_id = :owner_id";

        if ($statusFilter !== null && $statusFilter !== '') {
            $sql .= " AND dcr.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY dcr.created_at DESC, dcr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lists pending requests, optionally filtered by owner.
     */
    public function findPending(?int $ownerId = null): array
    {
        return $this->findByOwner($ownerId ?? 0, 'pending');
    }

    /**
     * Retrieves all requests for admin oversight.
     */
    public function getAllRequests(?string $statusFilter = null): array
    {
        $params = [];
        $sql = "SELECT dcr.*,
                       b.status AS booking_status, b.start_date, b.end_date,
                       v.make, v.model, v.license_plate, v.owner_id,
                       u_own.name AS owner_name,
                       u_req.name AS customer_name,
                       u_curr.name AS current_driver_name,
                       u_req_drv.name AS requested_driver_name,
                       u_rev.name AS reviewer_name
                FROM driver_change_requests dcr
                JOIN bookings b ON b.id = dcr.booking_id
                JOIN vehicles v ON v.id = b.vehicle_id
                JOIN vehicle_owners vo ON vo.id = v.owner_id
                JOIN users u_own ON u_own.id = vo.user_id
                JOIN users u_req ON u_req.id = dcr.requested_by
                JOIN drivers d_curr ON d_curr.id = dcr.current_driver_id
                JOIN users u_curr ON u_curr.id = d_curr.user_id
                JOIN drivers d_req ON d_req.id = dcr.requested_driver_id
                JOIN users u_req_drv ON u_req_drv.id = d_req.user_id
                LEFT JOIN users u_rev ON u_rev.id = dcr.reviewed_by";

        if ($statusFilter !== null && $statusFilter !== '') {
            $sql .= " WHERE dcr.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY dcr.created_at DESC, dcr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Summary statistics for an owner.
     */
    public function getSummaryByOwner(int $ownerId): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN dcr.status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN dcr.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN dcr.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                    SUM(CASE WHEN dcr.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
                FROM driver_change_requests dcr
                JOIN bookings b ON b.id = dcr.booking_id
                JOIN vehicles v ON v.id = b.vehicle_id
                WHERE v.owner_id = :owner_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner_id' => $ownerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total'     => (int)($row['total'] ?? 0),
            'pending'   => (int)($row['pending'] ?? 0),
            'approved'  => (int)($row['approved'] ?? 0),
            'rejected'  => (int)($row['rejected'] ?? 0),
            'cancelled' => (int)($row['cancelled'] ?? 0),
        ];
    }

    /**
     * Atomically approves a pending driver change request.
     * Enforces complete transaction safety with row locking and revalidation.
     */
    public function approve(int $requestId, int $reviewerUserId, int $ownerId, ?string $remarks = null): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Lock the request row
            $stmt = $this->db->prepare("SELECT * FROM driver_change_requests WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new DriverChangeRequestException('Driver change request not found.');
            }
            if ($request['status'] !== 'pending') {
                throw new DriverChangeRequestException('Request is no longer pending review.');
            }

            // 2. Lock the booking and vehicle row
            $stmtBooking = $this->db->prepare("
                SELECT b.id, b.status, b.booking_type, b.driver_id, b.start_date, b.end_date,
                       v.id AS vehicle_id, v.owner_id
                FROM bookings b
                JOIN vehicles v ON v.id = b.vehicle_id
                WHERE b.id = :booking_id
                FOR UPDATE
            ");
            $stmtBooking->execute(['booking_id' => (int)$request['booking_id']]);
            $booking = $stmtBooking->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new DriverChangeRequestException('Associated booking not found.');
            }

            // 3. Verify fleet ownership
            if ((int)$booking['owner_id'] !== $ownerId) {
                throw new DriverChangeRequestException('Unauthorized: Booking does not belong to your vehicle fleet.');
            }

            // 4. Verify booking eligibility & current driver integrity
            if (!in_array($booking['status'], self::ELIGIBLE_BOOKING_STATUSES, true)) {
                throw new DriverChangeRequestException('The booking is no longer in an eligible active status for driver change.');
            }
            if ($booking['booking_type'] !== 'with_driver') {
                throw new DriverChangeRequestException('The booking is not a with-driver booking.');
            }
            if ((int)$booking['driver_id'] !== (int)$request['current_driver_id']) {
                throw new DriverChangeRequestException('Current assigned driver on the booking has changed unexpectedly.');
            }

            // 5. Revalidate requested driver eligibility within the locked transaction
            $this->assertDriverEligible(
                (int)$request['requested_driver_id'],
                (int)$booking['owner_id'],
                $booking['start_date'],
                $booking['end_date'],
                (int)$booking['id'],
                true
            );

            // 6. Atomically update bookings.driver_id
            $stmtUpdateBooking = $this->db->prepare("
                UPDATE bookings
                SET driver_id = :new_driver_id
                WHERE id = :booking_id
            ");
            $stmtUpdateBooking->execute([
                'new_driver_id' => (int)$request['requested_driver_id'],
                'booking_id'    => (int)$booking['id'],
            ]);

            // 7. Update driver_change_requests status to approved
            $stmtUpdateRequest = $this->db->prepare("
                UPDATE driver_change_requests
                SET status = 'approved',
                    reviewed_by = :reviewed_by,
                    reviewed_at = CURRENT_TIMESTAMP,
                    owner_remarks = :remarks
                WHERE id = :id
            ");
            $stmtUpdateRequest->execute([
                'reviewed_by' => $reviewerUserId,
                'remarks'     => $remarks !== null ? trim($remarks) : null,
                'id'          => $requestId,
            ]);

            // 8. In-app notifications
            $notif = new Notification();

            // A. Customer notification
            $notif->create(
                (int)$request['requested_by'],
                'Driver Change Approved',
                'Your driver change request for booking #' . $booking['id'] . ' has been approved.',
                'booking',
                (int)$booking['id']
            );

            // B. New driver notification
            $stmtNewDrv = $this->db->prepare("SELECT user_id FROM drivers WHERE id = :id");
            $stmtNewDrv->execute(['id' => (int)$request['requested_driver_id']]);
            $newDriverUserId = (int)$stmtNewDrv->fetchColumn();
            if ($newDriverUserId > 0) {
                $notif->create(
                    $newDriverUserId,
                    'New Booking Assignment',
                    'You have been assigned to booking #' . $booking['id'] . '.',
                    'booking',
                    (int)$booking['id']
                );
            }

            // C. Previous driver notification
            $stmtPrevDrv = $this->db->prepare("SELECT user_id FROM drivers WHERE id = :id");
            $stmtPrevDrv->execute(['id' => (int)$request['current_driver_id']]);
            $prevDriverUserId = (int)$stmtPrevDrv->fetchColumn();
            if ($prevDriverUserId > 0) {
                $notif->create(
                    $prevDriverUserId,
                    'Assignment Updated',
                    'Your assignment to booking #' . $booking['id'] . ' has been changed.',
                    'booking',
                    (int)$booking['id']
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Atomically rejects a pending driver change request.
     * Original driver assignment remains untouched.
     */
    public function reject(int $requestId, int $reviewerUserId, int $ownerId, ?string $remarks = null): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Lock the request row
            $stmt = $this->db->prepare("SELECT * FROM driver_change_requests WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new DriverChangeRequestException('Driver change request not found.');
            }
            if ($request['status'] !== 'pending') {
                throw new DriverChangeRequestException('Request is no longer pending review.');
            }

            // 2. Lock the booking and verify ownership
            $stmtBooking = $this->db->prepare("
                SELECT b.id, b.status, v.owner_id
                FROM bookings b
                JOIN vehicles v ON v.id = b.vehicle_id
                WHERE b.id = :booking_id
                FOR UPDATE
            ");
            $stmtBooking->execute(['booking_id' => (int)$request['booking_id']]);
            $booking = $stmtBooking->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new DriverChangeRequestException('Associated booking not found.');
            }

            if ((int)$booking['owner_id'] !== $ownerId) {
                throw new DriverChangeRequestException('Unauthorized: Booking does not belong to your vehicle fleet.');
            }

            // 3. Update driver_change_requests status to rejected
            $remarksClean = $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null;
            $stmtUpdateRequest = $this->db->prepare("
                UPDATE driver_change_requests
                SET status = 'rejected',
                    reviewed_by = :reviewed_by,
                    reviewed_at = CURRENT_TIMESTAMP,
                    owner_remarks = :remarks
                WHERE id = :id
            ");
            $stmtUpdateRequest->execute([
                'reviewed_by' => $reviewerUserId,
                'remarks'     => $remarksClean,
                'id'          => $requestId,
            ]);

            // 4. In-app notification to Customer
            $notif = new Notification();
            $message = 'Your driver change request for booking #' . $booking['id'] . ' has been rejected.';
            if ($remarksClean !== null) {
                $message .= ' Remarks: ' . $remarksClean;
            }
            $notif->create(
                (int)$request['requested_by'],
                'Driver Change Rejected',
                $message,
                'booking',
                (int)$booking['id']
            );

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancels a pending request on behalf of the customer who created it.
     */
    public function cancel(int $requestId, int $customerUserId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM driver_change_requests
                WHERE id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new DriverChangeRequestException('Driver change request not found.');
            }
            if ((int)$request['requested_by'] !== $customerUserId) {
                throw new DriverChangeRequestException('Unauthorized to cancel this request.');
            }
            if ($request['status'] !== 'pending') {
                throw new DriverChangeRequestException('Only pending requests can be cancelled.');
            }

            $stmtUpdate = $this->db->prepare("
                UPDATE driver_change_requests
                SET status = 'cancelled'
                WHERE id = :id
            ");
            $stmtUpdate->execute(['id' => $requestId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retrieves eligible replacement drivers for a specific booking owned by the customer.
     */
    public function getEligibleDriversForBooking(int $bookingId, int $customerUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT b.id, b.status, b.booking_type, b.driver_id, b.start_date, b.end_date,
                   v.owner_id
            FROM bookings b
            JOIN customers c ON c.id = b.customer_id
            JOIN vehicles v ON v.id = b.vehicle_id
            WHERE b.id = :booking_id
              AND c.user_id = :customer_user_id
            LIMIT 1
        ");
        $stmt->execute([
            'booking_id'        => $bookingId,
            'customer_user_id'  => $customerUserId,
        ]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking || $booking['booking_type'] !== 'with_driver' || empty($booking['driver_id'])) {
            return [];
        }

        $customerBooking = new CustomerBooking($this->db);
        $drivers = $customerBooking->getEligibleDrivers(
            (int)$booking['owner_id'],
            $booking['start_date'],
            $booking['end_date'],
            (int)$booking['id']
        );

        // Exclude the current assigned driver
        return array_values(array_filter($drivers, static function (array $d) use ($booking): bool {
            return (int)$d['id'] !== (int)$booking['driver_id'];
        }));
    }

    /**
     * Asserts driver eligibility against canonical rules. Throws DriverChangeRequestException on failure.
     */
    public function assertDriverEligible(
        int $driverId,
        int $ownerId,
        string $startDate,
        string $endDate,
        int $excludeBookingId = 0,
        bool $forUpdate = false
    ): void {
        // 1. Driver exists, user is active and role is driver
        $stmtDriver = $this->db->prepare("
            SELECT d.id, d.availability_status, u.status AS user_status, u.role
            FROM drivers d
            JOIN users u ON u.id = d.user_id
            WHERE d.id = :driver_id
            LIMIT 1
        ");
        $stmtDriver->execute(['driver_id' => $driverId]);
        $driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            throw new DriverChangeRequestException('Requested driver does not exist.');
        }
        if ($driver['role'] !== 'driver' || $driver['user_status'] !== 'active') {
            throw new DriverChangeRequestException('Requested driver account is not active.');
        }
        if ($driver['availability_status'] !== 'available') {
            throw new DriverChangeRequestException('Requested driver is not currently marked as available.');
        }

        // 2. Driver-Owner link accepted
        $stmtLink = $this->db->prepare("
            SELECT id FROM driver_owner_links
            WHERE driver_id = :driver_id AND owner_id = :owner_id AND status = 'accepted'
            LIMIT 1
        ");
        $stmtLink->execute([
            'driver_id' => $driverId,
            'owner_id'  => $ownerId,
        ]);
        if (!$stmtLink->fetchColumn()) {
            throw new DriverChangeRequestException('Requested driver is not assigned to this vehicle owner fleet.');
        }

        // 3. Approved documents
        $docExpiry = substr($endDate, 0, 10);
        $reqCount = count(self::REQUIRED_DRIVER_DOCUMENTS);
        $stmtDocs = $this->db->prepare("
            SELECT COUNT(DISTINCT document_type)
            FROM driver_documents
            WHERE driver_id = :driver_id
              AND document_type IN ('nic', 'driving_license', 'police_report')
              AND status = 'approved'
              AND is_current = 1
              AND (expiry_date IS NULL OR expiry_date >= :doc_expiry)
        ");
        $stmtDocs->execute([
            'driver_id'  => $driverId,
            'doc_expiry' => $docExpiry,
        ]);
        if ((int)$stmtDocs->fetchColumn() < $reqCount) {
            throw new DriverChangeRequestException('Requested driver does not have all required approved and unexpired documents.');
        }

        // 4. Leave overlap
        $stmtLeave = $this->db->prepare("
            SELECT id FROM driver_leaves
            WHERE driver_id = :driver_id
              AND status = 'approved'
              AND start_date < DATE(:leave_end)
              AND DATE_ADD(end_date, INTERVAL 1 DAY) > DATE(:leave_start)
            LIMIT 1
        ");
        $stmtLeave->execute([
            'driver_id'   => $driverId,
            'leave_start' => $startDate,
            'leave_end'   => $endDate,
        ]);
        if ($stmtLeave->fetchColumn()) {
            throw new DriverChangeRequestException('Requested driver is on approved leave during the requested period.');
        }

        // 5. Booking conflict
        $conflictSql = "
            SELECT id FROM bookings
            WHERE driver_id = :driver_id
              AND status IN ('pending_approval', 'pending_payment', 'confirmed', 'ongoing')
              AND start_date < :booking_end
              AND end_date > :booking_start
        ";
        $params = [
            'driver_id'     => $driverId,
            'booking_start' => $startDate,
            'booking_end'   => $endDate,
        ];
        if ($excludeBookingId > 0) {
            $conflictSql .= " AND id <> :exclude_booking_id";
            $params['exclude_booking_id'] = $excludeBookingId;
        }
        $conflictSql .= " LIMIT 1";

        if ($forUpdate) {
            $conflictSql .= " FOR UPDATE";
        }

        $stmtConflict = $this->db->prepare($conflictSql);
        $stmtConflict->execute($params);
        if ($stmtConflict->fetchColumn()) {
            throw new DriverChangeRequestException('Requested driver has a conflicting booking during this period.');
        }
    }
}
