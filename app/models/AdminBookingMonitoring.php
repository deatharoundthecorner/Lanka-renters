<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminBookingMonitoring Model
 * Handles administration queries for monitoring all bookings across the platform.
 */
class AdminBookingMonitoring {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets all bookings with complete entity relations (Customer, Vehicle, Owner, Driver, Payment).
     */
    public function getAllBookings(array $filters = []): array {
        $sql = "SELECT b.*,
                       uc.name as customer_name, uc.email as customer_email, uc.phone as customer_phone,
                       v.make, v.model, v.license_plate, v.vehicle_type,
                       uo.name as owner_name, uo.phone as owner_phone,
                       ud.name as driver_name, ud.phone as driver_phone,
                       p.id as payment_id, p.payment_status, p.amount as payment_amount
                FROM `bookings` b
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                JOIN `vehicle_owners` vo ON v.owner_id = vo.id
                JOIN `users` uo ON vo.user_id = uo.id
                LEFT JOIN `drivers` d ON b.driver_id = d.id
                LEFT JOIN `users` ud ON d.user_id = ud.id
                LEFT JOIN `payments` p ON p.booking_id = b.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (uc.name LIKE :search OR v.license_plate LIKE :search OR uo.name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
