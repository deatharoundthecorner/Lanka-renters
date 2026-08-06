<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - DriverDocument Model
 * Manages database operations on the 'driver_documents' table, restricting file types,
 * handling admin verification state updates, and protecting non-pending deletions.
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
     * Default validation state is set to 'pending'.
     * 
     * @param array $data Contains driver_id, document_type, document_number, expiry_date, file_path
     * @return int|false The document record ID or false on failure
     * @throws InvalidArgumentException if the document type is invalid
     */
    public function create($data) {
        if (!in_array($data['document_type'], self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Invalid document type: " . $data['document_type']);
        }

        $sql = "INSERT INTO `driver_documents` (`driver_id`, `document_type`, `document_number`, `expiry_date`, `file_path`, `verification_status`) 
                VALUES (:driver_id, :document_type, :document_number, :expiry_date, :file_path, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        
        $params = [
            'driver_id'       => $data['driver_id'],
            'document_type'   => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'expiry_date'     => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'file_path'       => $data['file_path']
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
        $sql = "SELECT * FROM `driver_documents` WHERE `driver_id` = :driver_id ORDER BY `uploaded_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns a specific document type for a driver (e.g. their active NIC record).
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

        $sql = "SELECT * FROM `driver_documents` 
                WHERE `driver_id` = :driver_id 
                  AND `document_type` = :document_type 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'driver_id'     => $driverId,
            'document_type' => $type
        ]);
        return $stmt->fetch();
    }

    /**
     * Updates the admin verification status and rejection rationale for a document.
     * 
     * @param int $id The document record primary key ID
     * @param string $status The verification status ('pending', 'approved', 'rejected')
     * @param string|null $reason Detailed rejection rationale
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException if the status value is invalid
     */
    public function updateVerificationStatus($id, $status, $reason = null) {
        $allowedStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid verification status: " . $status);
        }

        $sql = "UPDATE `driver_documents` 
                SET `verification_status` = :status, `rejected_reason` = :reason 
                WHERE `id` = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'reason' => $reason,
            'id'     => $id
        ]);
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
        return $stmt->fetch();
    }

    /**
     * Updates an existing driver document record.
     * Sets verification status back to 'pending' and clears rejected_reason.
     * 
     * @param int $id The document primary key ID
     * @param array $data Contains document_number, expiry_date, and optionally file_path
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $fields = [
            "`document_number` = :document_number",
            "`expiry_date` = :expiry_date",
            "`verification_status` = 'pending'",
            "`rejected_reason` = NULL"
        ];
        $params = [
            'id'              => $id,
            'document_number' => $data['document_number'] ?? null,
            'expiry_date'     => !empty($data['expiry_date']) ? $data['expiry_date'] : null
        ];

        if (isset($data['file_path'])) {
            $fields[] = "`file_path` = :file_path";
            $params['file_path'] = $data['file_path'];
        }

        $sql = "UPDATE `driver_documents` SET " . implode(", ", $fields) . " WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deletes a document from the system.
     * Security Constraint: Only permits deleting documents that are still in a 'pending' verification state.
     * 
     * @param int $id The document record primary key ID
     * @return bool True if a pending record was deleted, false otherwise
     */
    public function delete($id) {
        $sql = "DELETE FROM `driver_documents` WHERE `id` = :id AND `verification_status` = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        // Return true if at least one row was affected (deleted)
        return $stmt->rowCount() > 0;
    }
}
