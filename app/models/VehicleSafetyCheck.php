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

    /**
     * Returns a safety checklist by its primary key ID.
     * 
     * @param int $id The check ID
     * @return array|false The check record or false
     */
    public function getById($id) {
        $sql = "SELECT * FROM `driver_vehicle_checks` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all safety checks logged by a specific driver, ordered newest first.
     * 
     * @param int $driverId The driver ID
     * @return array Array of checklist records
     */
    public function getByDriverId($driverId) {
        $sql = "SELECT dvc.*, v.make, v.model, v.license_plate, b.pickup_status 
                FROM `driver_vehicle_checks` dvc
                JOIN `vehicles` v ON dvc.vehicle_id = v.id
                JOIN `bookings` b ON dvc.booking_id = b.id
                WHERE dvc.driver_id = :driver_id
                ORDER BY dvc.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates safety check checklist records.
     * 
     * @param int $id The check ID
     * @param array $data Contains brakes, lights, tires, fuel
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $sql = "UPDATE `driver_vehicle_checks` 
                SET `brakes` = :brakes, `lights` = :lights, `tires` = :tires, `fuel` = :fuel
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'brakes' => (int)$data['brakes'],
            'lights' => (int)$data['lights'],
            'tires'  => (int)$data['tires'],
            'fuel'   => (int)$data['fuel'],
            'id'     => $id
        ]);
    }

    /**
     * Deletes a safety check record.
     * 
     * @param int $id The check ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        $sql = "DELETE FROM `driver_vehicle_checks` WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
