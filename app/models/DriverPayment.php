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
        $sql = "SELECT SUM(dp.amount) as total_earnings, COUNT(dp.id) as trips_count 
                FROM `driver_payments` dp
                WHERE dp.driver_id = :driver_id";
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
     * Table columns: Booking ID, Date, Customer, Amount, Status
     * 
     * @param int $driverId The driver's ID
     * @return array Array of payment records
     */
    public function getPaymentHistory($driverId) {
        $sql = "SELECT dp.booking_id, b.start_date as booking_date, u.name as customer_name,
                       dp.amount, dp.payment_status, dp.created_at
                FROM `driver_payments` dp
                JOIN `bookings` b ON dp.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` u ON c.user_id = u.id
                WHERE dp.driver_id = :driver_id
                ORDER BY dp.created_at DESC";
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
                    SUM(CASE WHEN dp.payment_status = 'paid' THEN dp.amount ELSE 0 END) as paid,
                    SUM(CASE WHEN dp.payment_status = 'pending' THEN dp.amount ELSE 0 END) as pending,
                    SUM(dp.amount) as total
                FROM `driver_payments` dp
                WHERE dp.driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'paid'    => (float)($result['paid'] ?? 0.0),
            'pending' => (float)($result['pending'] ?? 0.0),
            'total'   => (float)($result['total'] ?? 0.0)
        ];
    }

    /**
     * Creates a new driver payment record.
     */
    public function createPayment($driverId, $bookingId, $amount, $status = 'pending') {
        $sql = "INSERT INTO `driver_payments` (`driver_id`, `booking_id`, `amount`, `payment_status`) 
                VALUES (:driver_id, :booking_id, :amount, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'driver_id'  => $driverId,
            'booking_id' => $bookingId,
            'amount'     => $amount,
            'status'     => $status
        ]);
    }

    /**
     * Retrieves monthly earnings for the driver.
     */
    public function getMonthlyEarnings($driverId) {
        $sql = "SELECT SUM(amount) as monthly_earnings 
                FROM `driver_payments` 
                WHERE driver_id = :driver_id 
                  AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return (float)($stmt->fetchColumn() ?? 0.0);
    }
}
