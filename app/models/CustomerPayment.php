<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/** Customer-owned payment reads and pending bank-transfer submissions. */
final class CustomerPayment
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    public function getPayableBooking(int $sessionUserId, int $bookingId): ?array
    {
        $statement = $this->db->prepare($this->payableBookingSql(false));
        $statement->execute(['user_id' => $sessionUserId, 'booking_id' => $bookingId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $row['blocking_payment'] = $this->getBlockingPayment((int) $row['customer_id'], $bookingId);
        $row['latest_payment'] = $this->getLatestPayment((int) $row['customer_id'], $bookingId);
        return $row;
    }

    public function createCustomerPayment(
        int $sessionUserId,
        int $bookingId,
        string $method,
        string $proofPath,
        string $transactionReference
    ): int {
        if ($method !== 'bank_transfer') {
            throw new DomainException('Only bank transfer is available for Customer submission.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare($this->payableBookingSql(true));
            $statement->execute(['user_id' => $sessionUserId, 'booking_id' => $bookingId]);
            $booking = $statement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking)) {
                throw new DomainException('This booking is not eligible for payment.');
            }
            if ($booking['user_status'] !== 'active' || (float) $booking['total_price'] <= 0) {
                throw new DomainException('This booking is not eligible for payment.');
            }

            $blockingPayment = $this->getBlockingPayment((int) $booking['customer_id'], $bookingId, true);
            if (is_array($blockingPayment)) {
                throw new DomainException('A pending or completed payment already exists for this booking.');
            }
            $latestPayment = $this->getLatestPayment((int) $booking['customer_id'], $bookingId, true);
            if (is_array($latestPayment) && $latestPayment['payment_status'] === 'refunded') {
                throw new DomainException('A refunded payment requires coordinated support before another submission.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO payments
                 (booking_id, amount, payment_method, payment_status, payment_slip_path,
                  transaction_reference, paid_at)
                 VALUES (:booking_id, :amount, :payment_method, 'pending', :proof_path,
                         :transaction_reference, NULL)"
            );
            $insert->execute([
                'booking_id' => $bookingId,
                'amount' => $booking['total_price'],
                'payment_method' => $method,
                'proof_path' => $proofPath,
                'transaction_reference' => $transactionReference !== '' ? $transactionReference : null,
            ]);
            $paymentId = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $paymentId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function countCustomerPayments(int $sessionUserId, string $status): int
    {
        $sql = 'SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id = p.booking_id
                JOIN customers c ON c.id = b.customer_id WHERE c.user_id = :user_id';
        $parameters = ['user_id' => $sessionUserId];
        if ($status !== '') {
            $sql .= ' AND p.payment_status = :status';
            $parameters['status'] = $status;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    public function getCustomerPayments(int $sessionUserId, string $status, int $limit, int $offset): array
    {
        $sql = 'SELECT p.id, p.booking_id, p.amount, p.payment_method, p.payment_status,
                       p.transaction_reference, p.paid_at, p.created_at,
                       v.make, v.model, v.license_plate
                FROM payments p JOIN bookings b ON b.id = p.booking_id
                JOIN customers c ON c.id = b.customer_id JOIN vehicles v ON v.id = b.vehicle_id
                WHERE c.user_id = :user_id';
        if ($status !== '') {
            $sql .= ' AND p.payment_status = :status';
        }
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset';
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':user_id', $sessionUserId, PDO::PARAM_INT);
        if ($status !== '') {
            $statement->bindValue(':status', $status);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCustomerPayment(int $sessionUserId, int $paymentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.booking_id, p.amount, p.payment_method, p.payment_status,
                    p.payment_slip_path, p.transaction_reference, p.paid_at, p.created_at, p.updated_at,
                    b.booking_type, b.start_date, b.end_date, b.total_price, b.status AS booking_status,
                    v.make, v.model, v.license_plate, u.name AS customer_name
             FROM payments p JOIN bookings b ON b.id = p.booking_id
             JOIN customers c ON c.id = b.customer_id JOIN users u ON u.id = c.user_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE p.id = :payment_id AND c.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['payment_id' => $paymentId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function findCustomerPaymentProof(int $sessionUserId, int $paymentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.payment_slip_path
             FROM payments p JOIN bookings b ON b.id = p.booking_id
             JOIN customers c ON c.id = b.customer_id
             WHERE p.id = :payment_id AND c.user_id = :user_id
               AND p.payment_slip_path IS NOT NULL LIMIT 1'
        );
        $statement->execute(['payment_id' => $paymentId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function getBookingPaymentSummary(int $sessionUserId, int $bookingId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT b.id AS booking_id, b.booking_type, b.start_date, b.end_date, b.total_price,
                    b.status AS booking_status, v.make, v.model, v.license_plate, u.name AS customer_name
             FROM bookings b JOIN customers c ON c.id = b.customer_id JOIN users u ON u.id = c.user_id
             JOIN vehicles v ON v.id = b.vehicle_id
             WHERE b.id = :booking_id AND c.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
        $booking = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($booking)) {
            return null;
        }
        $booking['payment'] = $this->getLatestPaymentByUser($sessionUserId, $bookingId);
        return $booking;
    }

    private function payableBookingSql(bool $forUpdate): string
    {
        return "SELECT b.id, b.customer_id, b.booking_type, b.start_date, b.end_date, b.total_price,
                       b.status, v.make, v.model, v.license_plate, u.status AS user_status
                FROM bookings b JOIN customers c ON c.id = b.customer_id
                JOIN users u ON u.id = c.user_id JOIN vehicles v ON v.id = b.vehicle_id
                WHERE b.id = :booking_id AND c.user_id = :user_id AND u.role = 'customer'
                  AND b.status = 'pending_payment' LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '');
    }

    private function getBlockingPayment(int $customerId, int $bookingId, bool $forUpdate = false): ?array
    {
        $statement = $this->db->prepare(
            "SELECT p.id, p.payment_status FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE p.booking_id = :booking_id AND b.customer_id = :customer_id
               AND p.payment_status IN ('pending', 'completed')
             ORDER BY p.created_at DESC, p.id DESC LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '')
        );
        $statement->execute(['booking_id' => $bookingId, 'customer_id' => $customerId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function getLatestPayment(int $customerId, int $bookingId, bool $forUpdate = false): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.payment_status, p.amount, p.created_at FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE p.booking_id = :booking_id AND b.customer_id = :customer_id
             ORDER BY p.created_at DESC, p.id DESC LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '')
        );
        $statement->execute(['booking_id' => $bookingId, 'customer_id' => $customerId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function getLatestPaymentByUser(int $sessionUserId, int $bookingId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.amount, p.payment_method, p.payment_status, p.transaction_reference,
                    p.paid_at, p.created_at
             FROM payments p JOIN bookings b ON b.id = p.booking_id
             JOIN customers c ON c.id = b.customer_id
             WHERE p.booking_id = :booking_id AND c.user_id = :user_id
             ORDER BY p.created_at DESC, p.id DESC LIMIT 1'
        );
        $statement->execute(['booking_id' => $bookingId, 'user_id' => $sessionUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }
}
