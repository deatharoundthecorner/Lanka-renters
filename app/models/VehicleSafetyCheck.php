<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Vehicle Safety Checklist Model
 * Manages pre-trip safety checklists (brakes, lights, tires, fuel).
 */
class VehicleSafetyCheck {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Checks if a safety checklist has already been submitted for a booking.
     * 
     * @param int $bookingId Booking ID
     * @return array|false The checklist record if found, or false
     */
    public function getByBooking($bookingId) {
        $sql = "SELECT * FROM `driver_vehicle_checks` WHERE `booking_id` = :booking_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new vehicle safety check record.
     * 
     * @param array $data Safety checklist values
     * @return int|false The auto-incremented ID on success, or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO `driver_vehicle_checks` (`driver_id`, `vehicle_id`, `booking_id`, `brakes`, `lights`, `tires`, `fuel`) 
                VALUES (:driver_id, :vehicle_id, :booking_id, :brakes, :lights, :tires, :fuel)";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'driver_id'  => $data['driver_id'],
            'vehicle_id' => $data['vehicle_id'],
            'booking_id' => $data['booking_id'],
            'brakes'     => (int)$data['brakes'],
            'lights'     => (int)$data['lights'],
            'tires'      => (int)$data['tires'],
            'fuel'       => (int)$data['fuel']
        ]);

        if ($success) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }
}
