<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - DriverDocument Model
 * Manages database operations on the 'driver_documents' table, restricting file types,
 * handling versioning, current flag state settings, and transaction-driven approvals/rejections.
 */
class DriverDocument {
    // Database connection instance
    private $db;

    // Allowed document type keys
    private const ALLOWED_TYPES = ['nic', 'driving_license', 'police_report'];

    /**
     * Model constructor.
     * Initializes connection utilizing the Singleton Database helper.
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registers a new driver document record inside the database.
     * Default validation state is set to 'pending' and determines the next version number.
     * 
     * @param array $data Contains driver_id, document_type, document_number, expiry_date, file_path
     * @return int|false The document record ID or false on failure
     * @throws InvalidArgumentException if the document type is invalid
     */
    public function create($data) {
        if (!in_array($data['document_type'], self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Invalid document type: " . $data['document_type']);
        }

        // Determine the next version for this document type
        $sqlVer = "SELECT MAX(`version`) FROM `driver_documents` 
                   WHERE `driver_id` = :driver_id AND `document_type` = :document_type";
        $stmtVer = $this->db->prepare($sqlVer);
        $stmtVer->execute([
            'driver_id'     => $data['driver_id'],
            'document_type' => $data['document_type']
        ]);
        $maxVer = (int)$stmtVer->fetchColumn();
        $nextVer = $maxVer > 0 ? $maxVer + 1 : 1;

        $sql = "INSERT INTO `driver_documents` (`driver_id`, `document_type`, `document_number`, `expiry_date`, `file_path`, `version`, `status`, `is_current`) 
                VALUES (:driver_id, :document_type, :document_number, :expiry_date, :file_path, :version, 'pending', FALSE)";
        
        $stmt = $this->db->prepare($sql);
        
        $params = [
            'driver_id'       => $data['driver_id'],
            'document_type'   => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'expiry_date'     => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'file_path'       => $data['file_path'],
            'version'         => $nextVer
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Returns all uploaded document records for a specific driver.
     * 
     * @param int $driverId The driver primary ID
     * @return array Array of matching document records
     */
    public function getByDriverId($driverId) {
        $sql = "SELECT * FROM `driver_documents` WHERE `driver_id` = :driver_id ORDER BY `version` DESC, `uploaded_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns a specific document type for a driver (gets the active/current approved document,
     * or the latest uploaded version if no approved one exists).
     * 
     * @param int $driverId The driver primary ID
     * @param string $type The document type key ('nic', 'driving_license', 'police_report')
     * @return array|false The document record or false if not found
     * @throws InvalidArgumentException if the document type is invalid
     */
    public function getByType($driverId, $type) {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Invalid document type: " . $type);
        }

        // 1. Try to fetch the active approved document
        $sql = "SELECT * FROM `driver_documents` 
                WHERE `driver_id` = :driver_id 
                  AND `document_type` = :document_type 
                  AND `status` = 'approved' 
                  AND `is_current` = TRUE 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id'     => $driverId,
            'document_type' => $type
        ]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            return $doc;
        }

        // 2. Fall back to the latest uploaded version (even if pending/rejected)
        $sql = "SELECT * FROM `driver_documents` 
                WHERE `driver_id` = :driver_id 
                  AND `document_type` = :document_type 
                ORDER BY `version` DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id'     => $driverId,
            'document_type' => $type
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns a document record by its primary key ID.
     * 
     * @param int $id The document ID
     * @return array|false The document record or false
     */
    public function getById($id) {
        $sql = "SELECT * FROM `driver_documents` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atomically approves a pending document using database transactions.
     * Marks previous approved document as superseded and updates current flags.
     * 
     * @param int $id The document ID
     * @param int $adminId The admin reviewer ID
     * @return bool True on success
     * @throws Exception on failures
     */
    public function approveDocument($id, $adminId) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Get the pending document details
            $doc = $this->getById($id);
            if (!$doc || $doc['status'] !== 'pending') {
                throw new Exception("Document not found or not in pending state.");
            }

            // 2. Mark previous approved document of this type as superseded and is_current = FALSE
            $sqlSupersede = "UPDATE `driver_documents` 
                             SET `status` = 'superseded', `is_current` = FALSE 
                             WHERE `driver_id` = :driver_id 
                               AND `document_type` = :document_type 
                               AND `status` = 'approved'";
            $stmtSupersede = $this->db->prepare($sqlSupersede);
            $stmtSupersede->execute([
                'driver_id'     => $doc['driver_id'],
                'document_type' => $doc['document_type']
            ]);

            // 3. Mark the new document version as approved and is_current = TRUE
            $sqlApprove = "UPDATE `driver_documents` 
                           SET `status` = 'approved', 
                               `is_current` = TRUE, 
                               `reviewed_by` = :reviewed_by, 
                               `reviewed_at` = CURRENT_TIMESTAMP 
                           WHERE `id` = :id";
            $stmtApprove = $this->db->prepare($sqlApprove);
            $stmtApprove->execute([
                'reviewed_by' => $adminId,
                'id'          => $id
            ]);

            // 4. Record admin review log
            $sqlReview = "INSERT INTO `admin_reviews` (`admin_id`, `request_type`, `request_id`, `action`) 
                          VALUES (:admin_id, 'document_replacement', :request_id, 'approved')";
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
     * Atomically rejects a pending document.
     * 
     * @param int $id The document ID
     * @param string $reason Rejection reason comments
     * @param int $adminId The admin reviewer ID
     * @return bool True on success
     * @throws Exception on failures
     */
    public function rejectDocument($id, $reason, $adminId) {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // 1. Get the pending document details
            $doc = $this->getById($id);
            if (!$doc || $doc['status'] !== 'pending') {
                throw new Exception("Document not found or not in pending state.");
            }

            // 2. Mark this document as rejected and is_current = FALSE
            $sqlReject = "UPDATE `driver_documents` 
                          SET `status` = 'rejected', 
                              `is_current` = FALSE, 
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

            // 3. Record admin review log
            $sqlReview = "INSERT INTO `admin_reviews` (`admin_id`, `request_type`, `request_id`, `action`, `comments`) 
                          VALUES (:admin_id, 'document_replacement', :request_id, 'rejected', :comments)";
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

    /**
     * Deletes a document from the system.
     * Security Constraint: Only permits deleting documents that are still in a 'pending' verification state.
     * 
     * @param int $id The document record primary key ID
     * @return bool True if a pending record was deleted, false otherwise
     */
    public function delete($id) {
        $sql = "DELETE FROM `driver_documents` WHERE `id` = :id AND `status` = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
