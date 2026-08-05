<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Driver Model
 * Manages database operations on the 'drivers' table, including availability adjustments,
 * rating updates, active vehicle assignments, and dashboard data aggregation.
 */
class Driver {
    // Database connection instance
    private $db;

    /**
     * Model constructor.
     * Initializes connection utilizing the Singleton Database helper.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new driver profile record linked to a user account.
     * Sets default values of 'off_duty' status and 0.00 rating.
     * 
     * @param int $userId The associated user ID
     * @return int|false The driver's database ID or false on failure
     */
    public function create($userId) {
        $sql = "INSERT INTO `drivers` (`user_id`, `availability_status`, `rating_avg`) 
                VALUES (:user_id, 'off_duty', 0.00)";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute(['user_id' => $userId])) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Retrieves driver profile metadata by primary key ID, joined with basic user details.
     * 
     * @param int $id The driver's primary key ID
     * @return array|false The driver details or false if not found
     */
    public function findById($id) {
        $sql = "SELECT d.*, u.name, u.email, u.phone, u.status as user_status 
                FROM `drivers` d 
                JOIN `users` u ON d.user_id = u.id 
                WHERE d.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves driver profile metadata by user ID, joined with basic user details.
     * 
     * @param int $userId The user's ID
     * @return array|false The driver details or false if not found
     */
    public function findByUserId($userId) {
        $sql = "SELECT d.*, u.name, u.email, u.phone, u.status as user_status 
                FROM `drivers` d 
                JOIN `users` u ON d.user_id = u.id 
                WHERE d.user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Updates the driver's current availability status.
     * 
     * @param int $driverId The driver primary key ID
     * @param string $status Target status ('available', 'busy', 'off_duty')
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException if status is invalid
     */
    public function updateAvailability($driverId, $status) {
        $allowedStatuses = ['available', 'busy', 'off_duty'];
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid availability status provided: " . $status);
        }

        $sql = "UPDATE `drivers` SET `availability_status` = :status WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id'     => $driverId
        ]);
    }

    /**
     * Updates the driver's average rating.
     * 
     * @param int $driverId The driver primary key ID
     * @param float $rating The new rating score
     * @return bool True on success, false on failure
     */
    public function updateRating($driverId, $rating) {
        $sql = "UPDATE `drivers` SET `rating_avg` = :rating WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'rating' => $rating,
            'id'     => $driverId
        ]);
    }

    /**
     * Retrieves all vehicles currently assigned to the driver through active vehicle assignments.
     * 
     * @param int $driverId The driver primary key ID
     * @return array Array of assigned vehicles
     */
    public function getAssignedVehicles($driverId) {
        $sql = "SELECT v.*, va.assigned_at, va.unassigned_at 
                FROM `vehicle_assignments` va 
                JOIN `vehicles` v ON va.vehicle_id = v.id 
                WHERE va.driver_id = :driver_id 
                  AND va.status = 'active' 
                  AND va.unassigned_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Gathers aggregated stats for the Driver dashboard:
     * - Current availability status
     * - Total active assigned vehicles
     * - Active rating
     * - Verification status calculated based on uploaded documents.
     * 
     * @param int $driverId The driver primary key ID
     * @return array|false Dashboard stats array, or false on driver not found
     */
    public function getDashboardData($driverId) {
        // 1. Fetch core driver availability and rating metrics
        $sqlDriver = "SELECT `availability_status`, `rating_avg` FROM `drivers` WHERE `id` = :driver_id LIMIT 1";
        $stmtDriver = $this->db->prepare($sqlDriver);
        $stmtDriver->execute(['driver_id' => $driverId]);
        $driver = $stmtDriver->fetch();

        if (!$driver) {
            return false;
        }

        // 2. Count active vehicle assignments
        $sqlVehicles = "SELECT COUNT(*) as `assigned_count` 
                        FROM `vehicle_assignments` 
                        WHERE `driver_id` = :driver_id 
                          AND `status` = 'active' 
                          AND `unassigned_at` IS NULL";
        $stmtVehicles = $this->db->prepare($sqlVehicles);
        $stmtVehicles->execute(['driver_id' => $driverId]);
        $vehicles = $stmtVehicles->fetch();
        $vehicleCount = $vehicles['assigned_count'] ?? 0;

        // 3. Resolve consolidated verification status from uploaded driver documents:
        // - Returns 'rejected' if any document is rejected.
        // - Returns 'pending' if they lack all 3 required documents (nic, license, police report) or if any is pending.
        // - Returns 'approved' if they have all 3 files and all are approved.
        $sqlStatus = "SELECT 
                        CASE 
                            -- If no documents uploaded at all, it is pending
                            WHEN COUNT(`id`) = 0 THEN 'pending'
                            -- If any document is rejected, the overall verification is rejected
                            WHEN SUM(CASE WHEN `verification_status` = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
                            -- If they do not have all 3 distinct required documents uploaded, status is pending
                            WHEN COUNT(DISTINCT `document_type`) < 3 THEN 'pending'
                            -- If any document is still pending review, status is pending
                            WHEN SUM(CASE WHEN `verification_status` = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
                            -- Otherwise, all 3 are uploaded and approved
                            ELSE 'approved'
                        END as `verification_status`
                      FROM `driver_documents`
                      WHERE `driver_id` = :driver_id";
        $stmtStatus = $this->db->prepare($sqlStatus);
        $stmtStatus->execute(['driver_id' => $driverId]);
        $status = $stmtStatus->fetch();
        $verificationStatus = $status['verification_status'] ?? 'pending';

        return [
            'availability_status'    => $driver['availability_status'],
            'rating'                 => (float)$driver['rating_avg'],
            'assigned_vehicle_count' => (int)$vehicleCount,
            'verification_status'    => $verificationStatus
        ];
    }

    /**
     * Updates the pickup status of a booking and logs it in the tracking history.
     * Operates atomically within a database transaction.
     * 
     * @param int $bookingId The booking ID
     * @param int $userId The ID of the user performing the update (driver)
     * @param string $status The new status value
     * @param float|null $latitude GPS latitude
     * @param float|null $longitude GPS longitude
     * @return bool True on success, false on failure
     */
    public function addPickupTracking($bookingId, $userId, $status, $latitude = null, $longitude = null) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Verify the booking is assigned to this driver (checking by user_id linked to driver)
            $sqlCheck = "SELECT b.id FROM `bookings` b 
                         JOIN `drivers` d ON b.driver_id = d.id 
                         WHERE b.id = :booking_id AND d.user_id = :user_id";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([
                'booking_id' => $bookingId,
                'user_id'    => $userId
            ]);
            if (!$stmtCheck->fetch()) {
                throw new Exception("Unauthorized: This booking is not assigned to you.");
            }

            // 2. Update the booking's current pickup status (and mark completed if dropped off)
            if ($status === 'dropped_off') {
                $sqlBooking = "UPDATE `bookings` SET `pickup_status` = :status, `status` = 'completed' WHERE `id` = :booking_id";
            } else {
                $sqlBooking = "UPDATE `bookings` SET `pickup_status` = :status WHERE `id` = :booking_id";
            }
            $stmtBooking = $this->db->prepare($sqlBooking);
            $stmtBooking->execute([
                'status'     => $status,
                'booking_id' => $bookingId
            ]);

            // 2. Insert record into pickup_tracking history
            $sqlTracking = "INSERT INTO `pickup_tracking` (`booking_id`, `status`, `updated_by`, `latitude`, `longitude`) 
                            VALUES (:booking_id, :status, :updated_by, :latitude, :longitude)";
            $stmtTracking = $this->db->prepare($sqlTracking);
            $stmtTracking->execute([
                'booking_id' => $bookingId,
                'status'     => $status,
                'updated_by' => $userId,
                'latitude'   => $latitude,
                'longitude'  => $longitude
            ]);

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retrieves all bookings assigned to the driver.
     * 
     * @param int $driverId The driver primary key ID
     * @return array Array of bookings
     */
    public function getBookings($driverId) {
        $sql = "SELECT b.*, u.name as customer_name, u.phone as customer_phone, 
                       v.make as vehicle_make, v.model as vehicle_model, v.license_plate as vehicle_plate 
                FROM `bookings` b 
                JOIN `customers` c ON b.customer_id = c.id 
                JOIN `users` u ON c.user_id = u.id 
                JOIN `vehicles` v ON b.vehicle_id = v.id 
                WHERE b.driver_id = :driver_id 
                ORDER BY b.start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves all available, verified drivers.
     * 
     * @return array List of available verified drivers
     */
    public function getAvailableVerifiedDrivers() {
        $sql = "SELECT d.id, u.name, d.rating_avg as rating, d.availability_status,
                       (SELECT COUNT(*) FROM `bookings` b WHERE b.driver_id = d.id AND b.status = 'completed') as completed_trips
                FROM `drivers` d
                JOIN `users` u ON d.user_id = u.id
                WHERE u.status = 'active'
                  AND d.availability_status = 'available'
                  AND (
                    SELECT 
                      CASE 
                        WHEN COUNT(dd.id) = 0 THEN 'pending'
                        WHEN SUM(CASE WHEN dd.verification_status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
                        WHEN COUNT(DISTINCT dd.document_type) < 3 THEN 'pending'
                        WHEN SUM(CASE WHEN dd.verification_status = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
                        ELSE 'approved'
                      END
                    FROM `driver_documents` dd
                    WHERE dd.driver_id = d.id
                  ) = 'approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Checks if a driver has a conflicting booking.
     * Overlap formula: (start1 < end2) AND (end1 > start2)
     */
    public function hasBookingConflict($driverId, $startDate, $endDate) {
        $sql = "SELECT COUNT(*) FROM `bookings` 
                WHERE `driver_id` = :driver_id 
                  AND `status` NOT IN ('cancelled', 'completed')
                  AND :start_date < `end_date` 
                  AND :end_date > `start_date`";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id'  => $driverId,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
