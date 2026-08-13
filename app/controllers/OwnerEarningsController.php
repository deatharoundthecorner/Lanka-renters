<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * Lanka Renters - Owner Earnings Controller
 * Aggregates payment and booking revenue data for the authenticated vehicle owner.
 * All queries enforce ownership via session-resolved owner_id.
 */
class OwnerEarningsController {
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
     * Returns earnings summary and per-vehicle breakdown.
     */
    public function getEarningsSummary(): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            // Overall earnings from completed bookings
            $stmtTotal = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.total_price ELSE 0 END), 0) AS total_completed,
                    COALESCE(SUM(CASE WHEN b.status IN ('confirmed','ongoing') THEN b.total_price ELSE 0 END), 0) AS total_pending
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE v.owner_id = :owner_id
            ");
            $stmtTotal->execute(['owner_id' => $ownerId]);
            $totals = $stmtTotal->fetch(PDO::FETCH_ASSOC);

            // Per-vehicle breakdown
            $stmtVehicle = $this->db->prepare("
                SELECT
                    v.id,
                    v.make,
                    v.model,
                    v.license_plate,
                    COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.total_price ELSE 0 END), 0) AS earned,
                    COUNT(CASE WHEN b.status = 'completed' THEN 1 END) AS trips
                FROM vehicles v
                LEFT JOIN bookings b ON b.vehicle_id = v.id
                WHERE v.owner_id = :owner_id
                GROUP BY v.id, v.make, v.model, v.license_plate
                ORDER BY earned DESC
            ");
            $stmtVehicle->execute(['owner_id' => $ownerId]);
            $vehicleBreakdown = $stmtVehicle->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success'          => true,
                'total_completed'  => (float)($totals['total_completed'] ?? 0),
                'total_pending'    => (float)($totals['total_pending'] ?? 0),
                'vehicle_breakdown' => $vehicleBreakdown,
                'owner'            => $auth['owner'],
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
