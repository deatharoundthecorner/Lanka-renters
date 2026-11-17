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
                       v.make, v.model, v.license_plate,
                       i.invoice_number, i.invoice_status
                FROM `payments` p
                JOIN `bookings` b ON p.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                LEFT JOIN `invoices` i ON (i.payment_id = p.id OR (i.booking_id = b.id AND i.payment_id IS NULL))
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
        $sql = "SELECT p.*, b.total_price, b.booking_type, b.status as booking_status,
                       uc.name as customer_name, uc.email as customer_email,
                       v.make, v.model, v.license_plate,
                       i.invoice_number, i.invoice_status
                FROM `payments` p
                JOIN `bookings` b ON p.booking_id = b.id
                JOIN `customers` c ON b.customer_id = c.id
                JOIN `users` uc ON c.user_id = uc.id
                JOIN `vehicles` v ON b.vehicle_id = v.id
                LEFT JOIN `invoices` i ON (i.payment_id = p.id OR (i.booking_id = b.id AND i.payment_id IS NULL))
                WHERE p.payment_status IN ('completed', 'failed', 'refunded')
                ORDER BY p.updated_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approves a payment, confirms booking, and synchronizes invoice in a single transaction.
     */
    public function approvePayment(int $paymentId): bool {
        $this->db->beginTransaction();
        try {
            $stmtPay = $this->db->prepare("SELECT id, booking_id, payment_status FROM `payments` WHERE `id` = :id FOR UPDATE");
            $stmtPay->execute(['id' => $paymentId]);
            $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $this->db->rollBack();
                return false;
            }

            $bookingId = (int) $payment['booking_id'];

            $stmt = $this->db->prepare("UPDATE `payments` SET `payment_status` = 'completed', `paid_at` = NOW() WHERE `id` = :id");
            $stmt->execute(['id' => $paymentId]);

            if ($bookingId > 0) {
                $stmtConfirm = $this->db->prepare("UPDATE `bookings` SET `status` = 'confirmed' WHERE `id` = :bid");
                $stmtConfirm->execute(['bid' => $bookingId]);

                // Synchronize corresponding invoice within the same transaction
                $stmtInv = $this->db->prepare("SELECT id, invoice_status, payment_id FROM `invoices` WHERE `booking_id` = :bid LIMIT 1 FOR UPDATE");
                $stmtInv->execute(['bid' => $bookingId]);
                $invoice = $stmtInv->fetch(PDO::FETCH_ASSOC);

                if ($invoice) {
                    if ($invoice['invoice_status'] !== 'paid') {
                        $stmtUpdInv = $this->db->prepare("UPDATE `invoices` SET `invoice_status` = 'paid', `payment_id` = :pid WHERE `id` = :inv_id");
                        $stmtUpdInv->execute(['pid' => $paymentId, 'inv_id' => $invoice['id']]);
                    } elseif (empty($invoice['payment_id'])) {
                        $stmtUpdInv = $this->db->prepare("UPDATE `invoices` SET `payment_id` = :pid WHERE `id` = :inv_id");
                        $stmtUpdInv->execute(['pid' => $paymentId, 'inv_id' => $invoice['id']]);
                    }
                } else {
                    // Create invoice for this booking if none exists yet
                    $stmtBkgDetails = $this->db->prepare(
                        "SELECT b.customer_id, b.booking_type, b.start_date, b.end_date, b.total_price,
                                v.price_per_day, v.price_with_driver_per_day
                         FROM `bookings` b
                         JOIN `vehicles` v ON b.vehicle_id = v.id
                         WHERE b.id = :bid LIMIT 1"
                    );
                    $stmtBkgDetails->execute(['bid' => $bookingId]);
                    $bkgDetails = $stmtBkgDetails->fetch(PDO::FETCH_ASSOC);

                    if ($bkgDetails) {
                        $totalAmount = (float) $bkgDetails['total_price'];
                        $rentalFee = $totalAmount;
                        $driverFee = 0.00;

                        if ($bkgDetails['booking_type'] === 'with_driver') {
                            $rentalDays = (int) ceil((strtotime((string) $bkgDetails['end_date']) - strtotime((string) $bkgDetails['start_date'])) / 86400);
                            if ($rentalDays < 1) {
                                $rentalDays = 1;
                            }
                            $basePricePerDay = (float) ($bkgDetails['price_per_day'] ?? 0);
                            $driverPricePerDay = (float) ($bkgDetails['price_with_driver_per_day'] ?? 0);
                            if ($driverPricePerDay > $basePricePerDay && $basePricePerDay > 0) {
                                $baseRental = round($basePricePerDay * $rentalDays, 2);
                                $driverFee = round(max(0.0, $totalAmount - $baseRental), 2);
                                $rentalFee = round($totalAmount - $driverFee, 2);
                            }
                        }

                        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string) $bookingId, 5, '0', STR_PAD_LEFT);
                        $stmtCheckNum = $this->db->prepare("SELECT COUNT(*) FROM `invoices` WHERE `invoice_number` = :num");
                        $stmtCheckNum->execute(['num' => $invoiceNumber]);
                        if ((int) $stmtCheckNum->fetchColumn() > 0) {
                            $invoiceNumber .= '-' . substr(uniqid(), -4);
                        }

                        $stmtInsInv = $this->db->prepare(
                            "INSERT INTO `invoices` (
                                invoice_number, booking_id, customer_id, payment_id,
                                rental_fee, driver_fee, additional_charges, discount, tax,
                                total_amount, invoice_status
                            ) VALUES (
                                :num, :bid, :cid, :pid,
                                :rental_fee, :driver_fee, 0.00, 0.00, 0.00,
                                :total_amount, 'paid'
                            )"
                        );
                        $stmtInsInv->execute([
                            'num' => $invoiceNumber,
                            'bid' => $bookingId,
                            'cid' => (int) $bkgDetails['customer_id'],
                            'pid' => $paymentId,
                            'rental_fee' => $rentalFee,
                            'driver_fee' => $driverFee,
                            'total_amount' => $totalAmount,
                        ]);
                    }
                }
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

    /**
     * Refunds a payment, marks associated booking as completed, releases vehicle, and cancels invoice.
     * Transactional and idempotent.
     */
    public function refundPayment(int $paymentId): bool {
        $this->db->beginTransaction();
        try {
            $stmtPay = $this->db->prepare("SELECT id, booking_id, payment_status FROM `payments` WHERE `id` = :id FOR UPDATE");
            $stmtPay->execute(['id' => $paymentId]);
            $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $this->db->rollBack();
                return false;
            }

            if ($payment['payment_status'] === 'refunded') {
                $this->db->commit();
                return true;
            }

            $bookingId = (int)$payment['booking_id'];

            // 1. Mark payment as refunded
            $stmtUpd = $this->db->prepare("UPDATE `payments` SET `payment_status` = 'refunded' WHERE `id` = :id");
            $stmtUpd->execute(['id' => $paymentId]);

            // 2. Transition booking to completed if disputed, ongoing, or confirmed
            if ($bookingId > 0) {
                $stmtBooking = $this->db->prepare("UPDATE `bookings` SET `status` = 'completed' WHERE `id` = :bid AND `status` IN ('disputed', 'ongoing', 'confirmed')");
                $stmtBooking->execute(['bid' => $bookingId]);

                // 3. Release vehicle to available if rented
                $stmtVeh = $this->db->prepare("
                    UPDATE vehicles v
                    JOIN bookings b ON b.vehicle_id = v.id
                    SET v.status = 'available'
                    WHERE b.id = :bid AND v.status = 'rented'
                ");
                $stmtVeh->execute(['bid' => $bookingId]);

                // 4. Update invoice status to cancelled (valid enum: pending, paid, cancelled)
                $stmtInv = $this->db->prepare("UPDATE `invoices` SET `invoice_status` = 'cancelled' WHERE `booking_id` = :bid");
                $stmtInv->execute(['bid' => $bookingId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Refund Payment Error: " . $e->getMessage());
            return false;
        }
    }
}

