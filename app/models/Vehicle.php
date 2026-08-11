<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Vehicle Model
 * Handles database persistence for the 'vehicles' and 'vehicle_documents' tables.
 * Enforces strict owner ownership matching on all state mutations.
 */
class Vehicle {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new vehicle record under the specified owner.
     *
     * @param array $data Vehicle properties
     * @return int|false Returns last insert ID on success or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO `vehicles` (
                    `owner_id`, `make`, `model`, `year`, `license_plate`, 
                    `vehicle_type`, `transmission`, `fuel_type`, `seating_capacity`, 
                    `price_per_day`, `price_with_driver_per_day`, `status`, `verification_status`
                ) VALUES (
                    :owner_id, :make, :model, :year, :license_plate, 
                    :vehicle_type, :transmission, :fuel_type, :seating_capacity, 
                    :price_per_day, :price_with_driver_per_day, :status, :verification_status
                )";

        $stmt = $this->db->prepare($sql);

        $priceWithDriver = (!empty($data['price_with_driver_per_day']) && is_numeric($data['price_with_driver_per_day']))
            ? (float)$data['price_with_driver_per_day']
            : null;

        $params = [
            'owner_id'                  => (int)$data['owner_id'],
            'make'                      => trim($data['make']),
            'model'                     => trim($data['model']),
            'year'                      => (int)$data['year'],
            'license_plate'             => strtoupper(trim($data['license_plate'])),
            'vehicle_type'              => $data['vehicle_type'],
            'transmission'              => $data['transmission'],
            'fuel_type'                 => $data['fuel_type'],
            'seating_capacity'          => (int)$data['seating_capacity'],
            'price_per_day'             => (float)$data['price_per_day'],
            'price_with_driver_per_day' => $priceWithDriver,
            'status'                    => $data['status'] ?? 'unavailable',
            'verification_status'       => $data['verification_status'] ?? 'pending'
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Retrieves all vehicles belonging to a specific owner.
     *
     * @param int $ownerId
     * @return array
     */
    public function getByOwnerId($ownerId) {
        $sql = "SELECT * FROM `vehicles` WHERE `owner_id` = :owner_id ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner_id' => (int)$ownerId]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single vehicle record by ID.
     * Optionally enforces owner ID verification.
     *
     * @param int $id
     * @param int|null $ownerId
     * @return array|false
     */
    public function getById($id, $ownerId = null) {
        if ($ownerId !== null) {
            $sql = "SELECT * FROM `vehicles` WHERE `id` = :id AND `owner_id` = :owner_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => (int)$id, 'owner_id' => (int)$ownerId]);
        } else {
            $sql = "SELECT * FROM `vehicles` WHERE `id` = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => (int)$id]);
        }
        return $stmt->fetch();
    }

    /**
     * Updates an existing vehicle record owned by the specified owner.
     *
     * @param int $id
     * @param int $ownerId
     * @param array $data
     * @return bool
     */
    public function update($id, $ownerId, $data) {
        $priceWithDriver = (!empty($data['price_with_driver_per_day']) && is_numeric($data['price_with_driver_per_day']))
            ? (float)$data['price_with_driver_per_day']
            : null;

        $sql = "UPDATE `vehicles` 
                SET `make` = :make,
                    `model` = :model,
                    `year` = :year,
                    `license_plate` = :license_plate,
                    `vehicle_type` = :vehicle_type,
                    `transmission` = :transmission,
                    `fuel_type` = :fuel_type,
                    `seating_capacity` = :seating_capacity,
                    `price_per_day` = :price_per_day,
                    `price_with_driver_per_day` = :price_with_driver_per_day
                WHERE `id` = :id AND `owner_id` = :owner_id";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'make'                      => trim($data['make']),
            'model'                     => trim($data['model']),
            'year'                      => (int)$data['year'],
            'license_plate'             => strtoupper(trim($data['license_plate'])),
            'vehicle_type'              => $data['vehicle_type'],
            'transmission'              => $data['transmission'],
            'fuel_type'                 => $data['fuel_type'],
            'seating_capacity'          => (int)$data['seating_capacity'],
            'price_per_day'             => (float)$data['price_per_day'],
            'price_with_driver_per_day' => $priceWithDriver,
            'id'                        => (int)$id,
            'owner_id'                  => (int)$ownerId
        ]);

        return $success && ($stmt->rowCount() > 0);
    }

    /**
     * Safely updates a vehicle status.
     * Allowed statuses for vehicle owners: 'available', 'maintenance', 'unavailable'.
     *
     * @param int $id
     * @param int $ownerId
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $ownerId, $status) {
        $allowedStatuses = ['available', 'maintenance', 'unavailable'];
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        $sql = "UPDATE `vehicles` SET `status` = :status WHERE `id` = :id AND `owner_id` = :owner_id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'status'   => $status,
            'id'       => (int)$id,
            'owner_id' => (int)$ownerId
        ]);

        return $success && ($stmt->rowCount() > 0);
    }

    /**
     * Safely deactivates a vehicle by setting its status to 'unavailable'.
     *
     * @param int $id
     * @param int $ownerId
     * @return bool
     */
    public function deactivate($id, $ownerId) {
        return $this->updateStatus($id, $ownerId, 'unavailable');
    }

    /**
     * Checks if a license plate already exists in the database.
     * Option to exclude a specific vehicle ID (for updates).
     *
     * @param string $licensePlate
     * @param int|null $excludeId
     * @return bool True if duplicate exists, false otherwise
     */
    public function licensePlateExists($licensePlate, $excludeId = null) {
        $plate = strtoupper(trim($licensePlate));
        if ($excludeId !== null) {
            $sql = "SELECT COUNT(*) as `cnt` FROM `vehicles` WHERE `license_plate` = :plate AND `id` != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['plate' => $plate, 'exclude_id' => (int)$excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as `cnt` FROM `vehicles` WHERE `license_plate` = :plate";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['plate' => $plate]);
        }
        $row = $stmt->fetch();
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    /**
     * Retrieves documents associated with a vehicle.
     *
     * @param int $vehicleId
     * @return array
     */
    public function getDocuments($vehicleId) {
        $sql = "SELECT * FROM `vehicle_documents` WHERE `vehicle_id` = :vehicle_id ORDER BY `uploaded_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vehicle_id' => (int)$vehicleId]);
        return $stmt->fetchAll();
    }

    /**
     * Attaches a document to a vehicle record in `vehicle_documents`.
     *
     * @param int $vehicleId
     * @param string $docType
     * @param string $filePath
     * @param string|null $docNumber
     * @param string|null $expiryDate
     * @return int|false
     */
    public function addDocument($vehicleId, $docType, $filePath, $docNumber = null, $expiryDate = null) {
        $allowedTypes = ['registration', 'insurance', 'emission_test', 'fitness_certificate'];
        if (!in_array($docType, $allowedTypes, true)) {
            return false;
        }

        $sql = "INSERT INTO `vehicle_documents` (
                    `vehicle_id`, `document_type`, `document_number`, `expiry_date`, `file_path`, `verification_status`
                ) VALUES (
                    :vehicle_id, :document_type, :document_number, :expiry_date, :file_path, 'pending'
                )";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'vehicle_id'      => (int)$vehicleId,
            'document_type'   => $docType,
            'document_number' => $docNumber ? trim($docNumber) : null,
            'expiry_date'     => $expiryDate ? trim($expiryDate) : null,
            'file_path'       => $filePath
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Checks whether a vehicle currently has an active/ongoing booking.
     *
     * @param int $vehicleId
     * @return bool
     */
    public function hasActiveBookings($vehicleId) {
        $sql = "SELECT COUNT(*) as `cnt` FROM `bookings` WHERE `vehicle_id` = :vehicle_id AND `status` IN ('confirmed', 'ongoing')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vehicle_id' => (int)$vehicleId]);
        $row = $stmt->fetch();
        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    /**
     * Retrieves all approved and active vehicles for public browsing.
     *
     * @return array
     */
    public function getPublicVehicles() {
        $sql = "SELECT v.*, u.name as owner_name, u.phone as owner_phone 
                FROM `vehicles` v
                JOIN `vehicle_owners` o ON v.owner_id = o.id
                JOIN `users` u ON o.user_id = u.id
                WHERE v.status = 'available' AND v.verification_status = 'approved'
                ORDER BY v.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a single public vehicle by ID.
     *
     * @param int $id
     * @return array|false
     */
    public function getPublicVehicleById($id) {
        $sql = "SELECT v.*, u.name as owner_name, u.phone as owner_phone, u.email as owner_email
                FROM `vehicles` v
                JOIN `vehicle_owners` o ON v.owner_id = o.id
                JOIN `users` u ON o.user_id = u.id
                WHERE v.id = :id AND v.status = 'available' AND v.verification_status = 'approved'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }
}
