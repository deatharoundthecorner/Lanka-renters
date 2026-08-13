<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * Lanka Renters - Owner Booking Controller
 * Retrieves bookings for vehicles owned by the authenticated vehicle owner.
 * All queries enforce owner_id from session — never from client input.
 */
class OwnerBookingController {
    private $db;
    private $ownerModel;

    public function __construct() {
        $this->db         = Database::getInstance()->getConnection();
        $this->ownerModel = new VehicleOwner();
    }

    /**
     * Resolves authenticated owner profile from session.
     */
    private function getAuthenticatedOwner(): array {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception('Unauthorized access.');
        }
        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            throw new Exception('Vehicle owner profile not found.');
        }
        return ['user' => $user, 'owner' => $owner];
    }

    /**
     * Retrieves all bookings for vehicles owned by the authenticated owner.
     * Includes customer info and vehicle info for display.
     */
    public function getOwnerBookings(): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            $stmt = $this->db->prepare("
                SELECT
                    b.id,
                    b.status,
                    b.booking_type,
                    b.start_date,
                    b.end_date,
                    b.total_price,
                    b.pickup_status,
                    b.created_at,
                    u.name  AS customer_name,
                    u.phone AS customer_phone,
                    u.email AS customer_email,
                    v.make,
                    v.model,
                    v.license_plate,
                    v.vehicle_type
                FROM bookings b
                JOIN vehicles  v ON b.vehicle_id  = v.id
                JOIN customers c ON b.customer_id = c.id
                JOIN users     u ON c.user_id     = u.id
                WHERE v.owner_id = :owner_id
                ORDER BY b.created_at DESC
            ");
            $stmt->execute(['owner_id' => (int)$ownerId]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'bookings' => $bookings, 'owner' => $auth['owner']];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'bookings' => []];
        }
    }

    /**
     * Returns booking summary statistics for the authenticated owner.
     */
    public function getBookingSummary(): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN b.status = 'confirmed'       THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN b.status = 'ongoing'         THEN 1 ELSE 0 END) AS ongoing,
                    SUM(CASE WHEN b.status = 'completed'       THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN b.status = 'cancelled'       THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN b.status = 'pending_payment' THEN 1 ELSE 0 END) AS pending_payment
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE v.owner_id = :owner_id
            ");
            $stmt->execute(['owner_id' => (int)$ownerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
}
