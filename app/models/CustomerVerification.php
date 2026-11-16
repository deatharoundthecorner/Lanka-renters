<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Admin review of Customer identity evidence.
 *
 * Customers upload NIC / driving licence files into customer_verification_documents
 * (one row per customer and document type, see CustomerPortal::upsertVerificationDocument).
 * The document rows are the source of truth for each document's state; the customers.identity_*
 * and customers.license_* columns keep the Admin review audit trail, and customers.verification_status
 * stays the single status the Customer portal reads.
 */
final class CustomerVerification
{
    private const REVIEW_STATUSES = ['approved', 'rejected', 'resubmission_required'];
    private const STORAGE_PREFIX = 'customer-verification-documents/';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    public function getCurrentDocuments(int $customerId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, customer_id, document_type, original_name, file_path,
                    verification_status AS status, rejection_reason, updated_at AS uploaded_at
             FROM customer_verification_documents
             WHERE customer_id = :customer_id AND document_type IN ('nic', 'driving_license')"
        );
        $statement->execute(['customer_id' => $customerId]);
        $documents = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $document) {
            $documents[(string) $document['document_type']] = $document;
        }
        return $documents;
    }

    public function listForAdmin(string $status = ''): array
    {
        $sql = 'SELECT c.id AS customer_id, u.name, u.email, u.phone,
                       c.nic_number, c.driving_license_number, c.terms_accepted_at, '
            . $this->derivedStatusColumns() . '
                FROM customers c
                INNER JOIN users u ON u.id = c.user_id
                ' . $this->documentJoins() . "
                WHERE u.role = 'customer'";
        if ($status === 'pending') {
            $sql .= " AND (nic.verification_status = 'pending' OR lic.verification_status = 'pending')";
        }
        $sql .= ' ORDER BY c.updated_at DESC, c.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findForAdmin(int $customerId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT c.*, u.name, u.email, u.phone, u.status AS user_status, '
            . $this->derivedStatusColumns() . '
             FROM customers c
             INNER JOIN users u ON u.id = c.user_id
             ' . $this->documentJoins() . "
             WHERE c.id = :customer_id AND u.role = 'customer'
             LIMIT 1"
        );
        $statement->execute(['customer_id' => $customerId]);
        $customer = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($customer)) {
            return null;
        }
        $customer['documents'] = $this->getCurrentDocuments($customerId);
        return $customer;
    }

    public function review(int $customerId, string $reviewType, string $status, string $reason, int $adminId): void
    {
        if (!in_array($reviewType, ['identity', 'driving_license'], true)) {
            throw new DomainException('Invalid review type.');
        }
        if (!in_array($status, self::REVIEW_STATUSES, true)) {
            throw new DomainException('Invalid review status.');
        }
        if ($status !== 'approved' && $reason === '') {
            throw new DomainException('A reason is required for rejection or resubmission.');
        }

        $this->db->beginTransaction();
        try {
            $customer = $this->findForAdmin($customerId);
            if ($customer === null) {
                throw new DomainException('Customer verification record was not found.');
            }

            $documentType = $reviewType === 'identity' ? 'nic' : 'driving_license';
            $document = $customer['documents'][$documentType] ?? null;
            if (!is_array($document) || $document['status'] !== 'pending') {
                throw new DomainException('There is no reviewable current document for this action.');
            }

            // Document rows only know pending / approved / rejected; a resubmission request is a
            // rejection with a reason, and the audit column below keeps the finer-grained value.
            $documentStatement = $this->db->prepare(
                'UPDATE customer_verification_documents
                 SET verification_status = :status, rejection_reason = :reason
                 WHERE id = :document_id AND customer_id = :customer_id'
            );
            $documentStatement->execute([
                'status' => $status === 'approved' ? 'approved' : 'rejected',
                'reason' => $status === 'approved' ? null : $reason,
                'document_id' => (int) $document['id'],
                'customer_id' => $customerId,
            ]);

            $columnPrefix = $reviewType === 'identity' ? 'identity' : 'license';
            $customerColumn = $reviewType === 'identity' ? 'identity_verification_status' : 'driving_license_status';
            $auditStatement = $this->db->prepare(
                "UPDATE customers SET {$customerColumn} = :status,
                        {$columnPrefix}_rejection_reason = :reason,
                        {$columnPrefix}_reviewed_by = :admin_id,
                        {$columnPrefix}_reviewed_at = CURRENT_TIMESTAMP
                 WHERE id = :customer_id"
            );
            $auditStatement->execute([
                'status' => $status,
                'reason' => $status === 'approved' ? null : $reason,
                'admin_id' => $adminId,
                'customer_id' => $customerId,
            ]);

            $this->syncCustomerStatus($customerId);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Returns the document with storage_key relative to storage/customer-verification-documents/.
     */
    public function findDocumentForAccess(int $documentId, int $userId, bool $admin): ?array
    {
        $sql = 'SELECT d.id, d.customer_id, d.document_type, d.file_path
                FROM customer_verification_documents d
                INNER JOIN customers c ON c.id = d.customer_id
                INNER JOIN users u ON u.id = c.user_id
                WHERE d.id = :document_id';
        $params = ['document_id' => $documentId];
        if (!$admin) {
            $sql .= ' AND u.id = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' LIMIT 1';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $document = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($document) || !str_starts_with((string) $document['file_path'], self::STORAGE_PREFIX)) {
            return null;
        }
        $document['storage_key'] = substr((string) $document['file_path'], strlen(self::STORAGE_PREFIX));
        return $document;
    }

    /**
     * customers.verification_status is what the Customer portal and booking rules read:
     * approved once the NIC (and a submitted licence) are approved, rejected if either was rejected.
     */
    private function syncCustomerStatus(int $customerId): void
    {
        $documents = $this->getCurrentDocuments($customerId);
        $statuses = array_map(static fn (array $document): string => (string) $document['status'], $documents);
        $nic = $statuses['nic'] ?? null;
        if (in_array('rejected', $statuses, true)) {
            $overall = 'rejected';
        } elseif ($nic === 'approved' && ($statuses['driving_license'] ?? 'approved') === 'approved') {
            $overall = 'approved';
        } else {
            $overall = 'pending';
        }
        $statement = $this->db->prepare('UPDATE customers SET verification_status = :status WHERE id = :customer_id');
        $statement->execute(['status' => $overall, 'customer_id' => $customerId]);
    }

    private function documentJoins(): string
    {
        return "LEFT JOIN customer_verification_documents nic
                    ON nic.customer_id = c.id AND nic.document_type = 'nic'
                LEFT JOIN customer_verification_documents lic
                    ON lic.customer_id = c.id AND lic.document_type = 'driving_license'";
    }

    private function derivedStatusColumns(): string
    {
        return "CASE WHEN nic.id IS NULL THEN 'not_submitted'
                     WHEN nic.verification_status = 'rejected' AND c.identity_verification_status = 'resubmission_required'
                         THEN 'resubmission_required'
                     ELSE nic.verification_status END AS identity_verification_status,
                CASE WHEN lic.id IS NULL THEN 'not_submitted'
                     WHEN lic.verification_status = 'rejected' AND c.driving_license_status = 'resubmission_required'
                         THEN 'resubmission_required'
                     ELSE lic.verification_status END AS driving_license_status,
                (lic.id IS NOT NULL) AS has_driving_license,
                nic.rejection_reason AS identity_rejection_reason,
                lic.rejection_reason AS license_rejection_reason";
    }
}
