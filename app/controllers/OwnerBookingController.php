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
                    v.vehicle_type,
                    (SELECT vi.status FROM vehicle_inspections vi WHERE vi.booking_id = b.id AND vi.inspection_type = 'pre_rental' ORDER BY vi.id DESC LIMIT 1) AS pre_rental_inspection_status,
                    (SELECT vi.status FROM vehicle_inspections vi WHERE vi.booking_id = b.id AND vi.inspection_type = 'post_rental' ORDER BY vi.id DESC LIMIT 1) AS post_rental_inspection_status,
                    (SELECT rr.id FROM return_requests rr WHERE rr.booking_id = b.id AND rr.status IN ('pending', 'approved') ORDER BY rr.id DESC LIMIT 1) AS return_request_id,
                    (SELECT rr.proposed_return_at FROM return_requests rr WHERE rr.booking_id = b.id AND rr.status IN ('pending', 'approved') ORDER BY rr.id DESC LIMIT 1) AS proposed_return_at,
                    (SELECT rr.customer_note FROM return_requests rr WHERE rr.booking_id = b.id AND rr.status IN ('pending', 'approved') ORDER BY rr.id DESC LIMIT 1) AS return_customer_note
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
                    SUM(CASE WHEN b.status = 'pending_approval' THEN 1 ELSE 0 END) AS pending_approval,
                    SUM(CASE WHEN b.status = 'pending_payment'  THEN 1 ELSE 0 END) AS pending_payment,
                    SUM(CASE WHEN b.status = 'confirmed'        THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN b.status = 'ongoing'          THEN 1 ELSE 0 END) AS ongoing,
                    SUM(CASE WHEN b.status = 'completed'        THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN b.status = 'cancelled'        THEN 1 ELSE 0 END) AS cancelled
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

    /**
     * Approves a pending_approval booking for a vehicle owned by the authenticated owner.
     * Transitions status from 'pending_approval' to 'pending_payment'.
     */
    public function approveBooking(int $bookingId): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = (int)$auth['owner']['id'];

            $stmt = $this->db->prepare("
                UPDATE bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                SET b.status = 'pending_payment'
                WHERE b.id = :booking_id
                  AND v.owner_id = :owner_id
                  AND b.status = 'pending_approval'
            ");
            $stmt->execute([
                'booking_id' => $bookingId,
                'owner_id'   => $ownerId,
            ]);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'error'   => 'Booking could not be approved. It may not exist, may not belong to your fleet, or is not awaiting approval.'
                ];
            }

            return ['success' => true, 'message' => 'Booking #' . $bookingId . ' approved successfully. It is now awaiting customer payment.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejects a pending_approval booking for a vehicle owned by the authenticated owner.
     * Transitions status from 'pending_approval' to 'cancelled'.
     */
    public function rejectBooking(int $bookingId): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = (int)$auth['owner']['id'];

            $stmt = $this->db->prepare("
                UPDATE bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                SET b.status = 'cancelled'
                WHERE b.id = :booking_id
                  AND v.owner_id = :owner_id
                  AND b.status = 'pending_approval'
            ");
            $stmt->execute([
                'booking_id' => $bookingId,
                'owner_id'   => $ownerId,
            ]);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'error'   => 'Booking could not be rejected. It may not exist, may not belong to your fleet, or is not awaiting approval.'
                ];
            }

            return ['success' => true, 'message' => 'Booking #' . $bookingId . ' has been rejected and cancelled.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Hands over a confirmed vehicle to the customer.
     * Requires an authenticated owner, ownership of vehicle, confirmed booking status,
     * and an existing passing pre-rental inspection.
     * Transitions bookings.status from 'confirmed' to 'ongoing' and bookings.pickup_status to 'picked_up'.
     * Inserts an audit record into pickup_tracking atomically.
     */
    public function handoverVehicle(int $bookingId): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = (int)$auth['owner']['id'];
            $userId  = (int)$auth['user']['id'];

            $this->db->beginTransaction();

            // 1. Verify booking exists, is confirmed, and vehicle belongs to owner
            $stmt = $this->db->prepare("
                SELECT b.id, b.status, b.pickup_status, b.vehicle_id, b.customer_id
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.id = :booking_id AND v.owner_id = :owner_id
                FOR UPDATE
            ");
            $stmt->execute([
                'booking_id' => $bookingId,
                'owner_id'   => $ownerId,
            ]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Booking could not be found or does not belong to your vehicle fleet.'
                ];
            }

            if ($booking['status'] !== 'confirmed') {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Vehicle handover can only be performed for confirmed bookings.'
                ];
            }

            // 2. Verify passing pre-rental inspection exists
            $inspStmt = $this->db->prepare("
                SELECT id FROM vehicle_inspections
                WHERE booking_id = :booking_id
                  AND inspection_type = 'pre_rental'
                  AND status = 'pass'
                LIMIT 1
            ");
            $inspStmt->execute(['booking_id' => $bookingId]);
            if (!$inspStmt->fetchColumn()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Cannot hand over vehicle. A passing pre-rental inspection must be recorded before handover.'
                ];
            }

            // 3. Verify customer has an approved guarantor for rental liability
            $guarStmt = $this->db->prepare("
                SELECT verification_status
                FROM customer_guarantors
                WHERE customer_id = :customer_id
                ORDER BY id DESC
                LIMIT 1
            ");
            $guarStmt->execute(['customer_id' => (int)$booking['customer_id']]);
            $guarStatus = $guarStmt->fetchColumn();
            if ($guarStatus !== 'approved') {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Cannot hand over vehicle. Customer has not completed an approved guarantor verification required for rental liability.'
                ];
            }

            // 3. Update bookings status to ongoing and pickup_status to picked_up
            $updateStmt = $this->db->prepare("
                UPDATE bookings
                SET status = 'ongoing', pickup_status = 'picked_up'
                WHERE id = :booking_id
            ");
            $updateStmt->execute(['booking_id' => $bookingId]);

            // 4. Update vehicle status to rented
            $updateVehStmt = $this->db->prepare("
                UPDATE vehicles
                SET status = 'rented'
                WHERE id = :vehicle_id
            ");
            $updateVehStmt->execute(['vehicle_id' => (int)$booking['vehicle_id']]);

            // 5. Insert pickup_tracking history record
            $trackStmt = $this->db->prepare("
                INSERT INTO pickup_tracking (booking_id, status, updated_by, driver_note)
                VALUES (:booking_id, 'picked_up', :updated_by, :driver_note)
            ");
            $trackStmt->execute([
                'booking_id'  => $bookingId,
                'updated_by'  => $userId,
                'driver_note' => 'Vehicle handed over to customer by owner.',
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Vehicle handed over successfully. Booking #' . $bookingId . ' is now active and ongoing.'
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Completes a rental upon return of the vehicle.
     * Requires an authenticated owner, ownership of vehicle, ongoing booking status,
     * and an existing post-rental inspection.
     * Transitions bookings.status from 'ongoing' to 'completed' and bookings.pickup_status to 'dropped_off'.
     * Inserts an audit record into pickup_tracking atomically.
     */
    public function completeRental(int $bookingId): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = (int)$auth['owner']['id'];
            $userId  = (int)$auth['user']['id'];

            $this->db->beginTransaction();

            // 1. Lock the booking row and verify ownership and ongoing status
            $stmt = $this->db->prepare("
                SELECT b.id, b.status, b.pickup_status, b.vehicle_id
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.id = :booking_id AND v.owner_id = :owner_id
                FOR UPDATE
            ");
            $stmt->execute([
                'booking_id' => $bookingId,
                'owner_id'   => $ownerId,
            ]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Booking could not be found or does not belong to your vehicle fleet.'
                ];
            }

            // 2. Reject non-ongoing statuses
            if ($booking['status'] !== 'ongoing') {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Vehicle return can only be completed for ongoing bookings.'
                ];
            }

            // 3. Verify a post-rental inspection exists
            $inspStmt = $this->db->prepare("
                SELECT id FROM vehicle_inspections
                WHERE booking_id = :booking_id
                  AND inspection_type = 'post_rental'
                LIMIT 1
            ");
            $inspStmt->execute(['booking_id' => $bookingId]);
            if (!$inspStmt->fetchColumn()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Cannot complete return. A post-rental inspection must be recorded before vehicle check-in.'
                ];
            }

            // 4. Update bookings status to completed and pickup_status to dropped_off
            $updateStmt = $this->db->prepare("
                UPDATE bookings
                SET status = 'completed',
                    pickup_status = 'dropped_off'
                WHERE id = :booking_id
                  AND status = 'ongoing'
            ");
            $updateStmt->execute(['booking_id' => $bookingId]);

            // 5. Restore vehicle status to available
            $updateVehStmt = $this->db->prepare("
                UPDATE vehicles
                SET status = 'available'
                WHERE id = :vehicle_id
            ");
            $updateVehStmt->execute(['vehicle_id' => (int)$booking['vehicle_id']]);

            // 6. Insert pickup_tracking history record
            $trackStmt = $this->db->prepare("
                INSERT INTO pickup_tracking (
                    booking_id,
                    status,
                    updated_by,
                    driver_note
                ) VALUES (
                    :booking_id,
                    'dropped_off',
                    :updated_by,
                    :driver_note
                )
            ");
            $trackStmt->execute([
                'booking_id'  => $bookingId,
                'updated_by'  => $userId,
                'driver_note' => 'Vehicle returned by customer and checked in by owner.',
            ]);

            // Close any active customer return request atomically with the existing check-in.
            $returnStmt = $this->db->prepare(
                "UPDATE return_requests
                 SET status = 'completed', reviewed_by = :reviewed_by, reviewed_at = CURRENT_TIMESTAMP
                 WHERE booking_id = :booking_id AND status IN ('pending', 'approved')"
            );
            $returnStmt->execute(['reviewed_by' => $userId, 'booking_id' => $bookingId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Vehicle check-in completed successfully. Booking #' . $bookingId . ' is now marked as completed.'
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
