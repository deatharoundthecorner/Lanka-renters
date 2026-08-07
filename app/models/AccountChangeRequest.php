<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - AccountChangeRequest Model
 * Manages database requests for changing usernames/emails.
 */
class AccountChangeRequest {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new pending account credentials change request.
     * 
     * @param int $userId The users.id value
     * @param string $changeType The modification type ('username', 'email')
     * @param string|null $oldValue The current active credentials value
     * @param string $requestedValue The newly requested credentials value
     * @return int|false Last insert ID or false on failure
     */
    public function create($userId, $changeType, $oldValue, $requestedValue) {
        // Clear any existing pending requests of the same type/field for this user
        $sqlClear = "DELETE FROM `account_change_requests` 
                     WHERE `user_id` = :user_id 
                       AND `change_type` = :change_type 
                       AND `status` = 'pending'";
        $stmtClear = $this->db->prepare($sqlClear);
        $stmtClear->execute([
            'user_id'     => $userId,
            'change_type' => $changeType
        ]);

        $sql = "INSERT INTO `account_change_requests` (`user_id`, `change_type`, `old_value`, `requested_value`, `status`) 
                VALUES (:user_id, :change_type, :old_value, :requested_value, 'pending')";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([
            'user_id'         => $userId,
            'change_type'     => $changeType,
            'old_value'       => $oldValue,
            'requested_value' => $requestedValue
        ])) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Retrieves all account change requests for a user.
     * 
     * @param int $userId The users.id value
     * @return array List of change requests
     */
    public function getByUserId($userId) {
        $sql = "SELECT * FROM `account_change_requests` WHERE `user_id` = :user_id ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all pending account change requests in the system.
     * 
     * @return array List of pending requests joined with user details
     */
    public function getPendingRequests() {
        $sql = "SELECT acr.*, u.name as user_name, u.email as user_email 
                FROM `account_change_requests` acr
                JOIN `users` u ON acr.user_id = u.id
                WHERE acr.status = 'pending'
                ORDER BY acr.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a request record by ID.
     */
    public function getById($id) {
        $sql = "SELECT * FROM `account_change_requests` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atomically approves an account change request inside a database transaction.
     * Overwrites active name/username or email in the 'users' table.
     */
    public function approve($id, $adminId) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Get request details
            $req = $this->getById($id);
            if (!$req || $req['status'] !== 'pending') {
                throw new Exception("Request not found or not pending.");
            }

            $userId = $req['user_id'];
            $type = $req['change_type'];
            $val = $req['requested_value'];

            // 2. Overwrite the active user record depending on the change type
            if ($type === 'username') {
                $sqlUpdate = "UPDATE `users` SET `name` = :val WHERE `id` = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute(['val' => $val, 'user_id' => $userId]);
            } elseif ($type === 'email') {
                // Ensure email uniqueness check before approval
                $sqlCheck = "SELECT id FROM `users` WHERE `email` = :email AND `id` != :user_id LIMIT 1";
                $stmtCheck = $this->db->prepare($sqlCheck);
                $stmtCheck->execute(['email' => $val, 'user_id' => $userId]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Unique email constraint violation: Email is already occupied by another user.");
                }

                $sqlUpdate = "UPDATE `users` SET `email` = :val WHERE `id` = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute(['val' => $val, 'user_id' => $userId]);
            }

            // 3. Mark request as approved
            $sqlApprove = "UPDATE `account_change_requests` 
                           SET `status` = 'approved', `reviewed_by` = :reviewed_by, `reviewed_at` = CURRENT_TIMESTAMP 
                           WHERE `id` = :id";
            $stmtApprove = $this->db->prepare($sqlApprove);
            $stmtApprove->execute(['reviewed_by' => $adminId, 'id' => $id]);

            // 4. Log admin review
            $sqlReview = "INSERT INTO `admin_reviews` (`admin_id`, `request_type`, `request_id`, `action`) 
                          VALUES (:admin_id, 'account_change', :request_id, 'approved')";
            $stmtReview = $this->db->prepare($sqlReview);
            $stmtReview->execute([
                'admin_id'   => $adminId,
                'request_id' => $id
            ]);

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Atomically rejects an account change request.
     */
    public function reject($id, $reason, $adminId) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Mark request as rejected
            $sqlReject = "UPDATE `account_change_requests` 
                          SET `status` = 'rejected', 
                              `reviewed_by` = :reviewed_by, 
                              `reviewed_at` = CURRENT_TIMESTAMP, 
                              `rejection_reason` = :reason 
                          WHERE `id` = :id";
            $stmtReject = $this->db->prepare($sqlReject);
            $stmtReject->execute([
                'reviewed_by' => $adminId,
                'reason'      => $reason,
                'id'          => $id
            ]);

            // 2. Log admin review
            $sqlReview = "INSERT INTO `admin_reviews` (`admin_id`, `request_type`, `request_id`, `action`, `comments`) 
                          VALUES (:admin_id, 'account_change', :request_id, 'rejected', :comments)";
            $stmtReview = $this->db->prepare($sqlReview);
            $stmtReview->execute([
                'admin_id'   => $adminId,
                'request_id' => $id,
                'comments'   => $reason
            ]);

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
