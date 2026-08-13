<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Vehicle.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * Lanka Renters - Vehicle Controller
 * Manages Owner vehicle creation, listing, updates, status changes, 
 * deactivations, and secure document uploads.
 */
class VehicleController {
    private $vehicleModel;
    private $ownerModel;

    public function __construct() {
        $this->vehicleModel = new Vehicle();
        $this->ownerModel = new VehicleOwner();
    }

    /**
     * Resolves the authenticated Vehicle Owner profile from the session.
     * Enforces strict role-based access control.
     *
     * @return array Returns array with 'user' and 'owner' data or throws Exception
     */
    private function getAuthenticatedOwner() {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Unauthorized session access.");
        }

        $ownerProfile = $this->ownerModel->findByUserId($user['id']);
        if (!$ownerProfile) {
            throw new Exception("Vehicle Owner profile not found for this account.");
        }

        return [
            'user'  => $user,
            'owner' => $ownerProfile
        ];
    }

    /**
     * Fetches all vehicles listed under the current authenticated owner.
     *
     * @return array
     */
    public function getOwnerVehicles() {
        try {
            $auth = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];
            $vehicles = $this->vehicleModel->getByOwnerId($ownerId);

            // Attach document records to each vehicle
            foreach ($vehicles as &$v) {
                $v['documents'] = $this->vehicleModel->getDocuments($v['id']);
            }

            return [
                'success'  => true,
                'vehicles' => $vehicles,
                'owner'    => $auth['owner'],
                'user'     => $auth['user']
            ];
        } catch (Exception $e) {
            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'vehicles' => []
            ];
        }
    }

    /**
     * Creates a new vehicle record with server-side validation and file uploads.
     *
     * @param array $data Input POST data
     * @param array $files Uploaded FILES array
     * @return array Status array
     */
    public function createVehicle($data, $files = []) {
        try {
            $auth = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            // 1. CSRF Validation
            if (!AuthHelper::validateCsrfToken($data['csrf_token'] ?? '')) {
                return ['success' => false, 'error' => 'Security verification failed (Invalid CSRF token).'];
            }

            // 2. Server-side Field Validation
            $validation = $this->validateVehicleInput($data);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // 3. Unique License Plate Check
            $licensePlate = strtoupper(trim($data['license_plate']));
            if ($this->vehicleModel->licensePlateExists($licensePlate)) {
                return ['success' => false, 'error' => "A vehicle with license plate '{$licensePlate}' is already registered in the system."];
            }

            // 4. Handle Main Image Upload if provided
            $mainImageFile = $files['vehicle_image'] ?? ($files['main_image'] ?? null);
            $mainImageResult = $this->handleMainImageUpload($mainImageFile);
            if (!$mainImageResult['success']) {
                return ['success' => false, 'error' => $mainImageResult['error']];
            }

            // 5. Construct DB Insert payload
            $vehiclePayload = [
                'owner_id'                  => $ownerId,
                'make'                      => trim($data['make']),
                'model'                     => trim($data['model']),
                'year'                      => (int)$data['year'],
                'license_plate'             => $licensePlate,
                'vehicle_type'              => $data['vehicle_type'],
                'transmission'              => $data['transmission'],
                'fuel_type'                 => $data['fuel_type'],
                'seating_capacity'          => (int)$data['seating_capacity'],
                'price_per_day'             => (float)$data['price_per_day'],
                'price_with_driver_per_day' => !empty($data['price_with_driver_per_day']) ? (float)$data['price_with_driver_per_day'] : null,
                'image_url'                 => $mainImageResult['image_url'],
                'status'                    => 'unavailable', // Initial status before verification/activation
                'verification_status'       => 'pending'
            ];

            $vehicleId = $this->vehicleModel->create($vehiclePayload);
            if (!$vehicleId) {
                return ['success' => false, 'error' => 'Failed to save vehicle details to database.'];
            }

            // 6. Handle Document Uploads if provided
            $docTypes = [
                'document_registration' => 'registration',
                'document_insurance'    => 'insurance',
                'document_emission'     => 'emission_test',
                'document_fitness'      => 'fitness_certificate'
            ];

            $uploadedCount = 0;
            foreach ($docTypes as $fileInputKey => $docType) {
                if (isset($files[$fileInputKey]) && $files[$fileInputKey]['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = $this->handleDocumentUpload($files[$fileInputKey], $docType, $vehicleId);
                    if ($uploadResult['success']) {
                        $this->vehicleModel->addDocument(
                            $vehicleId,
                            $docType,
                            $uploadResult['file_path'],
                            $data[$fileInputKey . '_number'] ?? null,
                            $data[$fileInputKey . '_expiry'] ?? null
                        );
                        $uploadedCount++;
                    }
                }
            }

            $msg = "Vehicle successfully listed and submitted for admin verification!";
            if ($uploadedCount > 0) {
                $msg .= " ({$uploadedCount} verification documents attached).";
            }

            return ['success' => true, 'message' => $msg, 'vehicle_id' => $vehicleId];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Vehicle creation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Updates vehicle details with ownership protection (IDOR check).
     *
     * @param int $vehicleId
     * @param array $data Input POST data
     * @param array $files Uploaded FILES array
     * @return array
     */
    public function updateVehicle($vehicleId, $data, $files = []) {
        try {
            $auth = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];
            $vehicleId = (int)$vehicleId;

            // 1. CSRF Validation
            if (!AuthHelper::validateCsrfToken($data['csrf_token'] ?? '')) {
                return ['success' => false, 'error' => 'Security verification failed (Invalid CSRF token).'];
            }

            // 2. IDOR Ownership Verification
            $existingVehicle = $this->vehicleModel->getById($vehicleId, $ownerId);
            if (!$existingVehicle) {
                return ['success' => false, 'error' => 'Vehicle not found or you do not have permission to modify it.'];
            }

            // 3. Field Validation
            $validation = $this->validateVehicleInput($data);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // 4. License Plate Uniqueness check excluding current vehicle ID
            $licensePlate = strtoupper(trim($data['license_plate']));
            if ($this->vehicleModel->licensePlateExists($licensePlate, $vehicleId)) {
                return ['success' => false, 'error' => "A vehicle with license plate '{$licensePlate}' is already registered."];
            }

            // 5. Handle Main Image Upload if a new file was uploaded
            $mainImageFile = $files['vehicle_image'] ?? ($files['main_image'] ?? null);
            $mainImageResult = $this->handleMainImageUpload($mainImageFile);
            if (!$mainImageResult['success']) {
                return ['success' => false, 'error' => $mainImageResult['error']];
            }

            // 6. Perform Update
            $updatePayload = [
                'make'                      => trim($data['make']),
                'model'                     => trim($data['model']),
                'year'                      => (int)$data['year'],
                'license_plate'             => $licensePlate,
                'vehicle_type'              => $data['vehicle_type'],
                'transmission'              => $data['transmission'],
                'fuel_type'                 => $data['fuel_type'],
                'seating_capacity'          => (int)$data['seating_capacity'],
                'price_per_day'             => (float)$data['price_per_day'],
                'price_with_driver_per_day' => !empty($data['price_with_driver_per_day']) ? (float)$data['price_with_driver_per_day'] : null
            ];

            if ($mainImageResult['image_url'] !== null) {
                $updatePayload['image_url'] = $mainImageResult['image_url'];

                // Delete old image file if exists
                if (!empty($existingVehicle['image_url'])) {
                    $oldPath = dirname(dirname(__DIR__)) . '/public/' . ltrim($existingVehicle['image_url'], '/');
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            $updated = $this->vehicleModel->update($vehicleId, $ownerId, $updatePayload);
            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update vehicle record.'];
            }

            return ['success' => true, 'message' => 'Vehicle details updated successfully!'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Toggles a vehicle's availability status.
     * Allowed owner statuses: 'available', 'maintenance', 'unavailable'.
     *
     * @param int $vehicleId
     * @param string $newStatus
     * @param string $csrfToken
     * @return array
     */
    public function updateStatus($vehicleId, $newStatus, $csrfToken) {
        try {
            $auth = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];
            $vehicleId = (int)$vehicleId;

            if (!AuthHelper::validateCsrfToken($csrfToken)) {
                return ['success' => false, 'error' => 'Security verification failed (Invalid CSRF token).'];
            }

            // Prevent setting status to 'rented' manually
            if ($newStatus === 'rented') {
                return ['success' => false, 'error' => "The 'rented' status is managed automatically by the booking system."];
            }

            $allowedStatuses = ['available', 'maintenance', 'unavailable'];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                return ['success' => false, 'error' => 'Invalid vehicle status selected.'];
            }

            $existing = $this->vehicleModel->getById($vehicleId, $ownerId);
            if (!$existing) {
                return ['success' => false, 'error' => 'Vehicle not found or permission denied.'];
            }

            $updated = $this->vehicleModel->updateStatus($vehicleId, $ownerId, $newStatus);
            if (!$updated) {
                return ['success' => false, 'error' => 'Failed to update vehicle status.'];
            }

            return ['success' => true, 'message' => 'Vehicle status changed to ' . ucfirst($newStatus) . '.'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Status update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Safely deactivates a vehicle (sets status = 'unavailable').
     * Checks active bookings to avoid breaking active rental contracts.
     *
     * @param int $vehicleId
     * @param string $csrfToken
     * @return array
     */
    public function deactivateVehicle($vehicleId, $csrfToken) {
        try {
            $auth = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];
            $vehicleId = (int)$vehicleId;

            if (!AuthHelper::validateCsrfToken($csrfToken)) {
                return ['success' => false, 'error' => 'Security verification failed (Invalid CSRF token).'];
            }

            $existing = $this->vehicleModel->getById($vehicleId, $ownerId);
            if (!$existing) {
                return ['success' => false, 'error' => 'Vehicle not found or permission denied.'];
            }

            if ($this->vehicleModel->hasActiveBookings($vehicleId)) {
                return ['success' => false, 'error' => 'Cannot deactivate vehicle because it currently has active/ongoing bookings.'];
            }

            $deactivated = $this->vehicleModel->deactivate($vehicleId, $ownerId);
            if (!$deactivated) {
                return ['success' => false, 'error' => 'Failed to deactivate vehicle.'];
            }

            return ['success' => true, 'message' => 'Vehicle has been deactivated safely.'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Deactivation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validates input values for vehicle creation and modification.
     *
     * @param array $data
     * @return array
     */
    private function validateVehicleInput($data) {
        $make = trim($data['make'] ?? '');
        $model = trim($data['model'] ?? '');
        $year = (int)($data['year'] ?? 0);
        $licensePlate = trim($data['license_plate'] ?? '');
        $vehicleType = $data['vehicle_type'] ?? '';
        $transmission = $data['transmission'] ?? '';
        $fuelType = $data['fuel_type'] ?? '';
        $seatingCapacity = (int)($data['seating_capacity'] ?? 0);
        $pricePerDay = (float)($data['price_per_day'] ?? 0);

        if (empty($make)) return ['valid' => false, 'error' => 'Vehicle Make is required (e.g. Toyota).'];
        if (empty($model)) return ['valid' => false, 'error' => 'Vehicle Model is required (e.g. Aqua).'];
        if ($year < 1990 || $year > ((int)date('Y') + 1)) return ['valid' => false, 'error' => 'Please provide a valid manufacturing year (1990 - ' . ((int)date('Y') + 1) . ').'];
        if (empty($licensePlate)) return ['valid' => false, 'error' => 'License plate number is required.'];

        $validTypes = ['car', 'van', 'suv', 'lorry', 'motorbike'];
        if (!in_array($vehicleType, $validTypes, true)) return ['valid' => false, 'error' => 'Invalid vehicle type selected.'];

        $validTransmissions = ['manual', 'automatic'];
        if (!in_array($transmission, $validTransmissions, true)) return ['valid' => false, 'error' => 'Invalid transmission type selected.'];

        $validFuelTypes = ['petrol', 'diesel', 'hybrid', 'electric'];
        if (!in_array($fuelType, $validFuelTypes, true)) return ['valid' => false, 'error' => 'Invalid fuel type selected.'];

        if ($seatingCapacity <= 0) return ['valid' => false, 'error' => 'Seating capacity must be at least 1.'];
        if ($pricePerDay <= 0) return ['valid' => false, 'error' => 'Price per day must be greater than 0 LKR.'];

        if (!empty($data['price_with_driver_per_day']) && (float)$data['price_with_driver_per_day'] <= 0) {
            return ['valid' => false, 'error' => 'Price with driver per day must be a positive number if provided.'];
        }

        return ['valid' => true];
    }

    /**
     * Handles document file uploads securely.
     *
     * @param array $file Single file upload element from $_FILES
     * @param string $docType Document category name
     * @param int $vehicleId Associated vehicle ID
     * @return array
     */
    private function handleDocumentUpload($file, $docType, $vehicleId) {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSizeBytes = 5 * 1024 * 1024; // 5 MB

        $originalName = basename($file['name']);
        $fileSize = $file['size'];
        $tmpName = $file['tmp_name'];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'error' => "Invalid file extension '.{$ext}'. Allowed: pdf, jpg, jpeg, png."];
        }

        if ($fileSize > $maxSizeBytes) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed limit of 5MB.'];
        }

        // Verify MIME type if finfo is available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/pjpeg'];
            if (!in_array($mime, $allowedMimes, true)) {
                return ['success' => false, 'error' => 'Invalid file MIME type. Uploaded file is not a valid document or image.'];
            }
        }

        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/vehicle_documents/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate sanitized, cryptographically random filename
        $safeFilename = 'veh_' . $vehicleId . '_' . $docType . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $uploadDir . $safeFilename;
        $dbPath = 'uploads/vehicle_documents/' . $safeFilename;

        if (move_uploaded_file($tmpName, $targetPath)) {
            return ['success' => true, 'file_path' => $dbPath];
        }

        return ['success' => false, 'error' => 'Failed to save uploaded document to server.'];
    }

    /**
     * Handles Main Vehicle Image upload.
     * Allowed formats: .jpg, .jpeg, .png, .webp. Max size: 5MB.
     * Saves to 'public/assets/uploads/vehicles/'. Creates directory if missing.
     *
     * @param array|null $file Single element from $_FILES
     * @return array ['success' => bool, 'image_url' => string|null, 'error' => string|null]
     */
    private function handleMainImageUpload($file) {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'image_url' => null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Vehicle image upload error (code ' . $file['error'] . ').'];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSizeBytes = 5 * 1024 * 1024; // 5 MB

        $originalName = basename($file['name']);
        $fileSize     = $file['size'];
        $tmpName      = $file['tmp_name'];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'error' => "Invalid image extension '.{$ext}'. Allowed formats: .jpg, .jpeg, .png, .webp."];
        }

        if ($fileSize > $maxSizeBytes) {
            return ['success' => false, 'error' => 'Vehicle image size exceeds maximum allowed limit of 5MB.'];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/pjpeg'];
            if (!in_array($mime, $allowedMimes, true)) {
                return ['success' => false, 'error' => 'Invalid file type. Please upload a valid JPG, PNG, or WebP image.'];
            }
        }

        $uploadDir = dirname(dirname(__DIR__)) . '/public/assets/uploads/vehicles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeFilename = 'veh_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath   = $uploadDir . $safeFilename;
        $dbPath       = 'assets/uploads/vehicles/' . $safeFilename;

        if (move_uploaded_file($tmpName, $targetPath)) {
            return ['success' => true, 'image_url' => $dbPath];
        }

        return ['success' => false, 'error' => 'Failed to save vehicle image to server.'];
    }
}
