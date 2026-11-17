<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Guarantor Model
 * Handles customer guarantor persistence, validation, and administrative verification.
 * Uses the Customer-owned customer_guarantors table: `nic` and `rejection_reason` are exposed
 * as `nic_passport` and `admin_remarks` for the Admin screens.
 */
class Guarantor
{
    private ?PDO $db = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    private function connection(): PDO
    {
        if ($this->db === null) {
            $this->db = Database::getInstance()->getConnection();
        }
        return $this->db;
    }

    /**
     * Finds the guarantor profile for a given customer ID.
     */
    public function findByCustomerId(int $customerId): ?array
    {
        $stmt = $this->connection()->prepare("
            SELECT g.*, g.nic AS nic_passport, g.rejection_reason AS admin_remarks, u.name as reviewer_name
            FROM customer_guarantors g
            LEFT JOIN users u ON g.reviewed_by = u.id
            WHERE g.customer_id = :customer_id
            ORDER BY g.id DESC
            LIMIT 1
        ");
        $stmt->execute(['customer_id' => $customerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Finds the guarantor profile using the customer's user_id from session.
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->connection()->prepare("
            SELECT g.*, g.nic AS nic_passport, g.rejection_reason AS admin_remarks, c.id as customer_record_id, u.name as reviewer_name
            FROM customers c
            LEFT JOIN customer_guarantors g ON g.customer_id = c.id
            LEFT JOIN users u ON g.reviewed_by = u.id
            WHERE c.user_id = :user_id
            ORDER BY g.id DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Checks if the customer has an approved guarantor.
     */
    public function isApprovedForCustomer(int $customerId): bool
    {
        $stmt = $this->connection()->prepare("
            SELECT verification_status
            FROM customer_guarantors
            WHERE customer_id = :customer_id
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['customer_id' => $customerId]);
        $status = $stmt->fetchColumn();
        return $status === 'approved';
    }

    /**
     * Saves or updates a customer's guarantor submission.
     */
    public function saveGuarantor(int $customerId, array $data): int
    {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $nicPassport = trim((string)($data['nic_passport'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $relationship = trim((string)($data['relationship'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));

        if ($fullName === '' || $nicPassport === '' || $phone === '' || $relationship === '' || $address === '') {
            throw new InvalidArgumentException('All required guarantor fields must be provided.');
        }

        $existing = $this->findByCustomerId($customerId);
        $db = $this->connection();

        if ($existing && !empty($existing['id'])) {
            $stmt = $db->prepare("
                UPDATE customer_guarantors
                SET full_name = :full_name,
                    nic = :nic_passport,
                    phone = :phone,
                    email = :email,
                    relationship = :relationship,
                    address = :address,
                    verification_status = 'pending',
                    rejection_reason = NULL,
                    reviewed_by = NULL,
                    reviewed_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND customer_id = :customer_id
            ");
            $stmt->execute([
                'id' => (int)$existing['id'],
                'customer_id' => $customerId,
                'full_name' => $fullName,
                'nic_passport' => $nicPassport,
                'phone' => $phone,
                'email' => $email !== '' ? $email : null,
                'relationship' => $relationship,
                'address' => $address,
            ]);
            return (int)$existing['id'];
        }

        $stmt = $db->prepare("
            INSERT INTO customer_guarantors
            (customer_id, full_name, nic, phone, email, relationship, address, verification_status)
            VALUES
            (:customer_id, :full_name, :nic_passport, :phone, :email, :relationship, :address, 'pending')
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'full_name' => $fullName,
            'nic_passport' => $nicPassport,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'relationship' => $relationship,
            'address' => $address,
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Lists all guarantors for admin review.
     */
    public function getAllForAdmin(?string $status = null): array
    {
        $sql = "
            SELECT g.*, g.nic AS nic_passport, g.rejection_reason AS admin_remarks, 
                   u.name as customer_name,
                   u.email as customer_email,
                   u.phone as customer_phone,
                   c.nic_number as customer_nic,
                   c.verification_status as customer_verification_status,
                   rev.name as reviewer_name
            FROM customer_guarantors g
            JOIN customers c ON g.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN users rev ON g.reviewed_by = rev.id
        ";
        $params = [];
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= " WHERE g.verification_status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY (g.verification_status = 'pending') DESC, g.updated_at DESC";

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin review action (approve or reject with remarks).
     */
    public function reviewGuarantor(int $guarantorId, int $adminUserId, string $action, ?string $remarks = null): bool
    {
        if (!in_array($action, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid review action.');
        }

        $stmt = $this->connection()->prepare("
            UPDATE customer_guarantors
            SET verification_status = :status,
                rejection_reason = :remarks,
                reviewed_by = :admin_id,
                reviewed_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'status' => $action,
            'remarks' => $remarks ? trim($remarks) : null,
            'admin_id' => $adminUserId,
            'id' => $guarantorId,
        ]);
    }
}
