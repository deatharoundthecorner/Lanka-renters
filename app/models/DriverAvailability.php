<?php

require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/DriverLeave.php';

/**
 * Lanka Renters - DriverAvailability Model
 * Handles the logic of tracking and modifying driver availability states inside 
 * the 'drivers' table, integrating checks for approved leaves.
 */
class DriverAvailability {
    // Database connection instance
    private $db;
    
    // Hold instance of DriverLeave model
    private $leaveModel;

    /**
     * Model constructor.
     * Initializes connection and imports the DriverLeave model.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->leaveModel = new DriverLeave();
    }

    /**
     * Updates the current driver availability status.
     * 
     * @param int $driverId The driver primary key ID
     * @param string $status Target status ('available', 'busy', 'off_duty')
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException if status is invalid
     */
    public function updateStatus($driverId, $status) {
        $allowedStatuses = ['available', 'busy', 'off_duty'];
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid availability status: " . $status);
        }

        $sql = "UPDATE `drivers` SET `availability_status` = :status WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id'     => $driverId
        ]);
    }

    /**
     * Retrieves the current availability status string of a driver.
     * 
     * @param int $driverId The driver primary key ID
     * @return string|false Status string or false if driver doesn't exist
     */
    public function getStatus($driverId) {
        $sql = "SELECT `availability_status` FROM `drivers` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $driverId]);
        $result = $stmt->fetch();
        return $result['availability_status'] ?? false;
    }

    /**
     * Direct shortcut to set driver status to 'off_duty'.
     * 
     * @param int $driverId The driver primary key ID
     * @return bool True on success, false on failure
     */
    public function setOffDuty($driverId) {
        return $this->updateStatus($driverId, 'off_duty');
    }

    /**
     * Direct shortcut to set driver status to 'available'.
     * 
     * @param int $driverId The driver primary key ID
     * @return bool True on success, false on failure
     */
    public function setAvailable($driverId) {
        return $this->updateStatus($driverId, 'available');
    }

    /**
     * Determines if a driver is actively available to take trips.
     * Conditions required for availability:
     * - The availability_status column must equal 'available'.
     * - The driver must not have any approved, active leaves matching the current date.
     * 
     * @param int $driverId The driver primary key ID
     * @return bool True if available and not on leave, false otherwise
     */
    public function isAvailable($driverId) {
        // 1. Verify status is active
        $status = $this->getStatus($driverId);
        if ($status !== 'available') {
            return false;
        }

        // 2. Query DriverLeave model for today's date
        $today = date('Y-m-d');
        $onLeave = $this->leaveModel->hasActiveLeave($driverId, $today);

        return !$onLeave;
    }
}
