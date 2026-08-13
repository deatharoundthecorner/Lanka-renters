<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - EmailLog Model
 * Handles email notifications audit logging.
 */
class EmailLog {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets email logs.
     */
    public function getAll(): array {
        $sql = "SELECT * FROM `email_logs` ORDER BY `sent_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts an email log record.
     */
    public function logEmail(array $data): bool {
        $sql = "INSERT INTO `email_logs` (`recipient_type`, `recipient_name`, `recipient_email`, `subject`, `booking_id`, `status`, `failure_reason`)
                VALUES (:type, :name, :email, :subject, :bid, :status, :reason)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'type' => $data['recipient_type'] ?? 'customer',
            'name' => $data['recipient_name'],
            'email' => $data['recipient_email'],
            'subject' => $data['subject'],
            'bid' => $data['booking_id'] ?? null,
            'status' => $data['status'] ?? 'sent',
            'reason' => $data['failure_reason'] ?? null
        ]);
    }
}
