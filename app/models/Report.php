<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Report Model
 * Aggregates real platform metrics for monthly revenue, booking distribution, vehicle popularity, and workload.
 */
class Report {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Aggregates total revenue and commission stats.
     */
    public function getFinancialSummary(): array {
        $sql = "SELECT 
                    COALESCE(SUM(total_price), 0) as gross_revenue,
                    COALESCE(SUM(total_price * 0.10), 0) as total_commission,
                    COALESCE(SUM(total_price * 0.90), 0) as total_payouts,
                    COUNT(id) as total_bookings
                FROM `bookings`
                WHERE `status` = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'gross_revenue' => 0,
            'total_commission' => 0,
            'total_payouts' => 0,
            'total_bookings' => 0
        ];
    }

    /**
     * Gets bookings breakdown by status.
     */
    public function getBookingsByStatus(): array {
        $sql = "SELECT `status`, COUNT(*) as count FROM `bookings` GROUP BY `status` ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets vehicle category popularity.
     */
    public function getVehicleCategoryPopularity(): array {
        $sql = "SELECT v.vehicle_type, COUNT(b.id) as booking_count, COALESCE(SUM(b.total_price), 0) as total_spent
                FROM `vehicles` v
                LEFT JOIN `bookings` b ON b.vehicle_id = v.id
                GROUP BY v.vehicle_type
                ORDER BY booking_count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
