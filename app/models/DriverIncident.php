<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Driver Incident Model
 * Handles reporting of vehicle accidents, client issues, and mechanical problems.
 */
class DriverIncident {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Checks if a booking is currently assigned to a driver.
     * Security check ensuring drivers only report incidents on their own trips.
     * 
     * @param int $bookingId Booking ID
     * @param int $driverId Driver profile ID
     * @return bool True if assigned, false otherwise
     */
    public function isBookingAssignedToDriver($bookingId, $driverId) {
        $sql = "SELECT COUNT(*) FROM `bookings` WHERE `id` = :booking_id AND `driver_id` = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'booking_id' => $bookingId,
            'driver_id'  => $driverId
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Creates a new incident report.
     * 
     * @param array $data Incident details
     * @return int|false The auto-incremented incident ID, or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO `incidents` (`booking_id`, `reported_by`, `description`, `incident_date`, `severity`, `status`) 
                VALUES (:booking_id, :reported_by, :description, :incident_date, :severity, 'reported')";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'booking_id'    => $data['booking_id'],
            'reported_by'   => $data['reported_by'],
            'description'   => $data['description'],
            'incident_date' => $data['incident_date'],
            'severity'      => $data['severity']
        ]);

        if ($success) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Links a photo attachment to an incident record.
     * 
     * @param int $incidentId Incident ID
     * @param string $photoPath Relative path to file
     * @return bool True on success, false on failure
     */
    public function addPhoto($incidentId, $photoPath) {
        $sql = "INSERT INTO `incident_photos` (`incident_id`, `photo_path`) VALUES (:incident_id, :photo_path)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'incident_id' => $incidentId,
            'photo_path'  => $photoPath
        ]);
    }
}
