<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Driver Performance Model
 * Computes performance analytics, driving hours, completed trips, and reviews.
 */
class DriverPerformance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves key performance statistics for a specific driver.
     * 
     * @param int $driverId The driver's primary key ID
     * @return array Associative array of statistics
     */
    public function getStatistics($driverId) {
        // 1. Total Completed Trips
        $sqlCompleted = "SELECT COUNT(*) as cnt FROM `bookings` WHERE `driver_id` = :driver_id AND `status` = 'completed'";
        $stmt = $this->db->prepare($sqlCompleted);
        $stmt->execute(['driver_id' => $driverId]);
        $completedTrips = (int)$stmt->fetchColumn();

        // 2. Current Month Completed Trips
        $sqlMonth = "SELECT COUNT(*) as cnt FROM `bookings` 
                     WHERE `driver_id` = :driver_id 
                       AND `status` = 'completed' 
                       AND MONTH(`start_date`) = MONTH(CURRENT_DATE()) 
                       AND YEAR(`start_date`) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->prepare($sqlMonth);
        $stmt->execute(['driver_id' => $driverId]);
        $monthTrips = (int)$stmt->fetchColumn();

        // 3. Total Driving Hours (sum of timestampdiff in hours)
        $sqlHours = "SELECT SUM(TIMESTAMPDIFF(HOUR, `start_date`, `end_date`)) as hours FROM `bookings` 
                     WHERE `driver_id` = :driver_id AND `status` = 'completed'";
        $stmt = $this->db->prepare($sqlHours);
        $stmt->execute(['driver_id' => $driverId]);
        $totalHours = (float)($stmt->fetchColumn() ?? 0.0);

        // 4. Average Rating
        $sqlRating = "SELECT AVG(`driver_rating`) as avg_r FROM `ratings_reviews` WHERE `driver_id` = :driver_id";
        $stmt = $this->db->prepare($sqlRating);
        $stmt->execute(['driver_id' => $driverId]);
        $avgRating = (float)($stmt->fetchColumn() ?? 5.0);

        // 5. Cancelled Trips
        $sqlCancelled = "SELECT COUNT(*) as cnt FROM `bookings` WHERE `driver_id` = :driver_id AND `status` = 'cancelled'";
        $stmt = $this->db->prepare($sqlCancelled);
        $stmt->execute(['driver_id' => $driverId]);
        $cancelledTrips = (int)$stmt->fetchColumn();

        return [
            'completed_trips' => $completedTrips,
            'month_trips'     => $monthTrips,
            'total_hours'     => $totalHours,
            'avg_rating'      => $avgRating,
            'cancelled_trips' => $cancelledTrips
        ];
    }

    /**
     * Retrieves all rating reviews and comments for the driver.
     * 
     * @param int $driverId The driver's ID
     * @return array Array of ratings and reviews
     */
    public function getRatingSummary($driverId) {
        $sql = "SELECT r.*, u.name as customer_name 
                FROM `ratings_reviews` r 
                JOIN `customers` c ON r.customer_id = c.id 
                JOIN `users` u ON c.user_id = u.id 
                WHERE r.`driver_id` = :driver_id 
                ORDER BY r.`created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
