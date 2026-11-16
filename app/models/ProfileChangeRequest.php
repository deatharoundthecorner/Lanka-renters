<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - ProfileChangeRequest Model
 * Manages pending profile modification requests for users and drivers.
 */
class ProfileChangeRequest {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new pending profile change request.
     * 
     * @param int $userId The users.id value
     * @param string $fieldName The column field ('phone', 'address', 'emergency_contact')
     * @param string|null $oldValue The current active value
     * @param string|null $requestedValue The newly requested value
     * @param string|null $reason Optional submission comments
     * @return int|false Last insert ID or false on failure
     */
    public function create($userId, $fieldName, $oldValue, $requestedValue, $reason = null) {
        // Clear any existing pending requests of the same type/field for this user
        $sqlClear = "DELETE FROM `profile_change_requests` 
                     WHERE `user_id` = :user_id 
                       AND `field_name` = :field_name 
                       AND `status` = 'pending'";
        $stmtClear = $this->db->prepare($sqlClear);
        $stmtClear->execute([
            'user_id'    => $userId,
            'field_name' => $fieldName
        ]);

        $sql = "INSERT INTO `profile_change_requests` (`user_id`, `field_name`, `old_value`, `requested_value`, `status`, `reason`) 
                VALUES (:user_id, :field_name, :old_value, :requested_value, 'pending', :reason)";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([
            'user_id'         => $userId,
            'field_name'      => $fieldName,
            'old_value'       => $oldValue,
            'requested_value' => $requestedValue,
            'reason'          => $reason
        ])) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Retrieves all profile change requests for a user.
     * 
     * @param int $userId The users.id value
     * @return array List of change requests
     */
    public function getByUserId($userId) {
        $sql = "SELECT * FROM `profile_change_requests` WHERE `user_id` = :user_id ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all pending profile change requests in the system.
     * 
     * @return array List of pending requests joined with user details
     */
    public function getPendingRequests() {
        $sql = "SELECT pcr.*, u.name as user_name, u.email as user_email 
                FROM `profile_change_requests` pcr
                JOIN `users` u ON pcr.user_id = u.id
                WHERE pcr.status = 'pending'
                ORDER BY pcr.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a request record by ID.
     */
    public function getById($id) {
        $sql = "SELECT * FROM `profile_change_requests` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atomically approves a profile change request inside a database transaction.
     * Overwrites active phone in 'users' or address/emergency_contact in 'drivers'.
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
            $field = $req['field_name'];
            $val = $req['requested_value'];

            // 2. Overwrite the active record depending on the field
            if ($field === 'phone') {
                $sqlUpdate = "UPDATE `users` SET `phone` = :val WHERE `id` = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute(['val' => $val, 'user_id' => $userId]);
            } elseif ($field === 'address') {
                $sqlUpdate = "UPDATE `drivers` SET `address` = :val WHERE `user_id` = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute(['val' => $val, 'user_id' => $userId]);
            } elseif ($field === 'emergency_contact') {
                $sqlUpdate = "UPDATE `drivers` SET `emergency_contact` = :val WHERE `user_id` = :user_id";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute(['val' => $val, 'user_id' => $userId]);
            }

            // 3. Mark request as approved
            $sqlApprove = "UPDATE `profile_change_requests` 
                           SET `status` = 'approved', `reviewed_by` = :reviewed_by, `reviewed_at` = CURRENT_TIMESTAMP 
                           WHERE `id` = :id";
            $stmtApprove = $this->db->prepare($sqlApprove);
            $stmtApprove->execute(['reviewed_by' => $adminId, 'id' => $id]);

            // 4. Log admin review
            $sqlReview = "INSERT INTO `admin_reviews` (`admin_id`, `request_type`, `request_id`, `action`) 
                          VALUES (:admin_id, 'profile_change', :request_id, 'approved')";
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
     * Atomically rejects a profile change request.
     */
    public function reject($id, $reason, $adminId) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Mark request as rejected
            $sqlReject = "UPDATE `profile_change_requests` 
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
                          VALUES (:admin_id, 'profile_change', :request_id, 'rejected', :comments)";
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
