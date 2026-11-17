<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/VehicleInspection.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * OwnerInspectionController
 * Handles vehicle inspection logs for the Vehicle Owner portal.
 */
class OwnerInspectionController {
    private $inspectionModel;
    private $ownerModel;

    public function __construct() {
        $this->inspectionModel = new VehicleInspection();
        $this->ownerModel = new VehicleOwner();
    }

    public function getInspectionsData() {
        $auth = AuthHelper::getCurrentUser();
        if (!$auth || $auth['role'] !== 'owner') {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $owner = $this->ownerModel->findByUserId($auth['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Owner profile record not found.'];
        }

        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
        $inspections = $this->inspectionModel->getInspectionsByOwner($owner['id'], $statusFilter);
        $summary = $this->inspectionModel->getSummaryByOwner($owner['id']);
        $eligibleBookings = $this->inspectionModel->getEligibleConfirmedBookingsByOwner($owner['id']);
        $ongoingBookings  = $this->inspectionModel->getEligibleOngoingBookingsByOwner($owner['id']);

        return [
            'success'           => true,
            'inspections'       => $inspections,
            'summary'           => $summary,
            'filter'            => $statusFilter,
            'eligible_bookings' => $eligibleBookings,
            'ongoing_bookings'  => $ongoingBookings,
            'owner'             => $owner,
        ];
    }

    /**
     * Handles recording a pre-rental inspection from POST request data.
     * Validates owner role, CSRF token, and required field inputs.
     */
    public function recordPreRentalInspection(array $postData): array {
        $auth = AuthHelper::getCurrentUser();
        if (!$auth || ($auth['role'] ?? '') !== 'owner') {
            return ['success' => false, 'error' => 'Unauthorized access.'];
        }

        $csrfToken = $postData['csrf_token'] ?? '';
        if (!AuthHelper::validateCsrfToken($csrfToken)) {
            return ['success' => false, 'error' => 'Invalid or expired session security token. Please try again.'];
        }

        $bookingId = (int)($postData['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            return ['success' => false, 'error' => 'A valid confirmed booking must be selected.'];
        }

        $odometerStr = trim((string)($postData['odometer_reading'] ?? ''));
        if ($odometerStr === '' || !ctype_digit($odometerStr) || (int)$odometerStr < 0) {
            return ['success' => false, 'error' => 'A valid non-negative odometer reading is required.'];
        }
        $odometer = (int)$odometerStr;

        $fuelLevel = trim((string)($postData['fuel_level'] ?? ''));
        if ($fuelLevel === '' || strlen($fuelLevel) > 20) {
            return ['success' => false, 'error' => 'A valid fuel level is required (e.g., Full, 3/4, 1/2, 1/4).'];
        }

        $exteriorCondition = trim((string)($postData['exterior_condition'] ?? ''));
        if ($exteriorCondition === '') {
            return ['success' => false, 'error' => 'Exterior condition notes are required.'];
        }

        $interiorCondition = trim((string)($postData['interior_condition'] ?? ''));
        if ($interiorCondition === '') {
            return ['success' => false, 'error' => 'Interior condition notes are required.'];
        }

        $status = trim((string)($postData['status'] ?? ''));
        if (!in_array($status, ['pass', 'fail', 'needs_maintenance'], true)) {
            return ['success' => false, 'error' => 'Inspection status must be Pass, Needs Maintenance, or Fail.'];
        }

        $comments = isset($postData['comments']) ? trim((string)$postData['comments']) : null;

        $result = $this->inspectionModel->createPreRentalInspection(
            $bookingId,
            (int)$auth['id'],
            $odometer,
            $fuelLevel,
            $exteriorCondition,
            $interiorCondition,
            $status,
            $comments
        );

        if (!empty($result['success']) && !empty($_FILES['inspection_photos'])) {
            $uploaded = $this->processPhotoUploads((int)$result['inspection_id'], $_FILES['inspection_photos']);
            if ($uploaded !== []) {
                $result['message'] .= ' (' . count($uploaded) . ' photo(s) attached)';
            }
        }

        return $result;
    }

    /**
     * Handles recording a post-rental inspection from POST request data.
     * Validates owner role, CSRF token, and required field inputs.
     */
    public function recordPostRentalInspection(array $postData): array {
        $auth = AuthHelper::getCurrentUser();
        if (!$auth || ($auth['role'] ?? '') !== 'owner') {
            return ['success' => false, 'error' => 'Unauthorized access.'];
        }

        $owner = $this->ownerModel->findByUserId($auth['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Owner profile record not found.'];
        }

        $csrfToken = $postData['csrf_token'] ?? '';
        if (!AuthHelper::validateCsrfToken($csrfToken)) {
            return ['success' => false, 'error' => 'Invalid or expired session security token. Please try again.'];
        }

        $bookingId = (int)($postData['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            return ['success' => false, 'error' => 'A valid ongoing booking must be selected.'];
        }

        $odometerStr = trim((string)($postData['odometer_reading'] ?? ''));
        if ($odometerStr === '' || !ctype_digit($odometerStr) || (int)$odometerStr < 0) {
            return ['success' => false, 'error' => 'A valid non-negative odometer reading is required.'];
        }
        $odometer = (int)$odometerStr;

        $fuelLevel = trim((string)($postData['fuel_level'] ?? ''));
        if ($fuelLevel === '' || strlen($fuelLevel) > 20) {
            return ['success' => false, 'error' => 'A valid fuel level is required (e.g., Full, 3/4, 1/2, 1/4).'];
        }

        $exteriorCondition = trim((string)($postData['exterior_condition'] ?? ''));
        if ($exteriorCondition === '') {
            return ['success' => false, 'error' => 'Exterior condition notes are required.'];
        }

        $interiorCondition = trim((string)($postData['interior_condition'] ?? ''));
        if ($interiorCondition === '') {
            return ['success' => false, 'error' => 'Interior condition notes are required.'];
        }

        $status = trim((string)($postData['status'] ?? ''));
        if (!in_array($status, ['pass', 'fail', 'needs_maintenance'], true)) {
            return ['success' => false, 'error' => 'Inspection status must be Pass, Needs Maintenance, or Fail.'];
        }

        $comments = isset($postData['comments']) ? trim((string)$postData['comments']) : null;

        $result = $this->inspectionModel->createPostRentalInspection(
            $bookingId,
            (int)$auth['id'],
            $odometer,
            $fuelLevel,
            $exteriorCondition,
            $interiorCondition,
            $status,
            $comments
        );

        if (!empty($result['success']) && !empty($_FILES['inspection_photos'])) {
            $uploaded = $this->processPhotoUploads((int)$result['inspection_id'], $_FILES['inspection_photos']);
            if ($uploaded !== []) {
                $result['message'] .= ' (' . count($uploaded) . ' photo(s) attached)';
            }
        }

        return $result;
    }

    /**
     * Securely validates and uploads inspection photos for an inspection ID.
     */
    private function processPhotoUploads(int $inspectionId, array $files): array {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSizeBytes = 5 * 1024 * 1024; // 5MB

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/inspections/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploaded = [];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $size = (int)($files['size'][$i] ?? 0);
            if ($size <= 0 || $size > $maxSizeBytes) {
                continue;
            }

            $tmpPath = (string)($files['tmp_name'][$i] ?? '');
            if (!is_uploaded_file($tmpPath)) {
                continue;
            }

            // Verify MIME type using finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes, true)) {
                continue;
            }

            $origName = (string)($files['name'][$i] ?? '');
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $safeFileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = $uploadDir . $safeFileName;

            if (move_uploaded_file($tmpPath, $destination)) {
                $dbPath = 'uploads/inspections/' . $safeFileName;
                $caption = pathinfo($origName, PATHINFO_FILENAME);
                $caption = substr(preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $caption), 0, 100);
                $this->inspectionModel->addPhoto($inspectionId, $dbPath, $caption !== '' ? $caption : null);
                $uploaded[] = $dbPath;
            }
        }

        return $uploaded;
    }
}
