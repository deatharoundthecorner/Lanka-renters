<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AdminPaymentVerification Model
 * Handles payment verification, bank slip evidence review, and status updates.
 */
class AdminPaymentVerification {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets pending payments requiring admin review.
     */
    public function getPendingPayments(): array {
        $sql = "SELECT p.*, b.total_price, b.booking_type, b.start_date, b.end_date,
                       uc.name as customer_name, uc.email as customer_email, uc.phone as customer_phone,
                       v.make, v.model, v.license_plate
                FROM `payments` p
                JOIN `bookings` b ON p.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                WHERE p.payment_status = 'pending'
                ORDER BY p.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets reviewed (completed/approved/rejected/refunded) payments.
     */
    public function getReviewedPayments(): array {
        $sql = "SELECT p.*, b.total_price, b.booking_type,
                       uc.name as customer_name, uc.email as customer_email,
                       v.make, v.model, v.license_plate
                FROM `payments` p
                JOIN `bookings` b ON p.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                WHERE p.payment_status IN ('completed', 'failed', 'refunded')
                ORDER BY p.updated_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approves a payment and updates associated booking to confirmed.
     */
    public function approvePayment(int $paymentId): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE `payments` SET `payment_status` = 'completed', `paid_at` = NOW() WHERE `id` = :id");
            $stmt->execute(['id' => $paymentId]);

            // Get booking_id
            $stmtBkg = $this->db->prepare("SELECT booking_id FROM `payments` WHERE `id` = :id");
            $stmtBkg->execute(['id' => $paymentId]);
            $bookingId = $stmtBkg->fetchColumn();

            if ($bookingId) {
                $stmtConfirm = $this->db->prepare("UPDATE `bookings` SET `status` = 'confirmed' WHERE `id` = :bid");
                $stmtConfirm->execute(['bid' => $bookingId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Rejects a payment.
     */
    public function rejectPayment(int $paymentId): bool {
        $stmt = $this->db->prepare("UPDATE `payments` SET `payment_status` = 'failed' WHERE `id` = :id");
        return $stmt->execute(['id' => $paymentId]);
    }
}
