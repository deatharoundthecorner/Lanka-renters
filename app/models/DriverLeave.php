<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - DriverLeave Model
 * Manages database operations on the 'driver_leaves' table, supporting leave requests,
 * admin status updates, and checking active leaves.
 */
class DriverLeave {
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
     * Registers a new leave request for a driver.
     * Default status is set to 'pending'.
     * 
     * @param array $data Contains driver_id, start_date, end_date, reason
     * @return int|false The leave request primary ID or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO `driver_leaves` (`driver_id`, `start_date`, `end_date`, `reason`, `status`) 
                VALUES (:driver_id, :start_date, :end_date, :reason, 'pending')";
        $stmt = $this->db->prepare($sql);
        
        $params = [
            'driver_id'  => $data['driver_id'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'] ?? null
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Returns all leave requests filed by a specific driver, ordered newest first.
     * 
     * @param int $driverId The driver primary ID
     * @return array Array of leave request records
     */
    public function getByDriverId($driverId) {
        $sql = "SELECT * FROM `driver_leaves` WHERE `driver_id` = :driver_id ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns all pending leave requests currently awaiting approval.
     * Joins user details so that the reviewing Admin knows which driver submitted the request.
     * 
     * @return array Array of pending leave request records with driver details
     */
    public function getPendingRequests() {
        $sql = "SELECT dl.*, u.name as driver_name, u.email as driver_email 
                FROM `driver_leaves` dl 
                JOIN `drivers` d ON dl.driver_id = d.id 
                JOIN `users` u ON d.user_id = u.id 
                WHERE dl.status = 'pending' 
                ORDER BY dl.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates the status and reviewing Admin ID of a leave request.
     * 
     * @param int $id The leave request primary ID
     * @param string $status The target status ('pending', 'approved', 'rejected')
     * @param int $approvedBy The user ID of the admin reviewing the request
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException if the status value is invalid
     */
    public function updateStatus($id, $status, $approvedBy) {
        $allowedStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid leave status: " . $status);
        }

        $sql = "UPDATE `driver_leaves` 
                SET `status` = :status, `approved_by` = :approved_by 
                WHERE `id` = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status'      => $status,
            'approved_by' => $approvedBy,
            'id'          => $id
        ]);
    }

    /**
     * Verifies if a driver is on an approved leave for a specific date.
     * Useful for booking validators to block busy/on-leave drivers.
     * 
     * @param int $driverId The driver primary ID
     * @param string $date Date string format (YYYY-MM-DD)
     * @return bool True if on approved leave, false otherwise
     */
    public function hasActiveLeave($driverId, $date) {
        $sql = "SELECT COUNT(*) as `leave_count` 
                FROM `driver_leaves` 
                WHERE `driver_id` = :driver_id 
                  AND :date BETWEEN `start_date` AND `end_date` 
                  AND `status` = 'approved'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id' => $driverId,
            'date'      => $date
        ]);
        
        $result = $stmt->fetch();
        return ($result['leave_count'] ?? 0) > 0;
    }
}
