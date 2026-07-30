<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Driver Payment Model
 * Queries historical payment transactions and summaries of earnings for completed trips.
 */
class DriverPayment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves total earnings summary for completed trips.
     * 
     * @param int $driverId The driver's primary key ID
     * @return array Associative array containing total_earnings and completed_trips_count
     */
    public function getEarningsSummary($driverId) {
        $sql = "SELECT SUM(p.amount) as total_earnings, COUNT(b.id) as trips_count 
                FROM `bookings` b
                JOIN `payments` p ON b.id = p.booking_id
                WHERE b.driver_id = :driver_id 
                  AND b.status = 'completed' 
                  AND p.payment_status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_earnings' => (float)($result['total_earnings'] ?? 0.0),
            'trips_count'    => (int)($result['trips_count'] ?? 0)
        ];
    }

    /**
     * Retrieves detailed payment records for bookings assigned to the driver.
     * 
     * @param int $driverId The driver's ID
     * @return array Array of payment records
     */
    public function getPaymentHistory($driverId) {
        $sql = "SELECT b.id as booking_id, b.start_date, b.end_date, 
                       v.make, v.model, v.license_plate,
                       p.amount, p.payment_status, p.paid_at
                FROM `bookings` b
                JOIN `vehicles` v ON b.vehicle_id = v.id
                LEFT JOIN `payments` p ON b.id = p.booking_id
                WHERE b.driver_id = :driver_id AND b.status = 'completed'
                ORDER BY b.end_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves split of earnings: Paid, Pending, and Total.
     * 
     * @param int $driverId The driver's ID
     * @return array Array with paid, pending, and total keys
     */
    public function getEarningsSplit($driverId) {
        $sql = "SELECT 
                    SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END) as paid,
                    SUM(CASE WHEN p.payment_status = 'pending' OR p.payment_status IS NULL THEN p.amount ELSE 0 END) as pending,
                    SUM(COALESCE(p.amount, 0)) as total
                FROM `bookings` b
                LEFT JOIN `payments` p ON b.id = p.booking_id
                WHERE b.driver_id = :driver_id AND b.status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'paid'    => (float)($result['paid'] ?? 0.0),
            'pending' => (float)($result['pending'] ?? 0.0),
            'total'   => (float)($result['total'] ?? 0.0)
        ];
    }
}
