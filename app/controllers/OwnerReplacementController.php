<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/ReplacementRequest.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';
require_once dirname(__DIR__) . '/models/Vehicle.php';

/**
 * OwnerReplacementController
 * Manages vehicle replacement requests logged during incidents for the Owner portal.
 */
class OwnerReplacementController {
    private $replacementModel;
    private $ownerModel;
    private $vehicleModel;

    public function __construct() {
        $this->replacementModel = new ReplacementRequest();
        $this->ownerModel = new VehicleOwner();
        $this->vehicleModel = new Vehicle();
    }

    public function getReplacementData() {
        $auth = AuthHelper::getCurrentUser();
        if (!$auth || $auth['role'] !== 'owner') {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $owner = $this->ownerModel->findByUserId($auth['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Owner profile record not found.'];
        }

        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
        $requests = $this->replacementModel->getRequestsByOwner($owner['id'], $statusFilter);
        $summary = $this->replacementModel->getSummaryByOwner($owner['id']);
        $availableVehicles = $this->vehicleModel->getByOwnerId($owner['id']);

        return [
            'success'           => true,
            'requests'          => $requests,
            'summary'           => $summary,
            'availableVehicles' => $availableVehicles,
            'filter'            => $statusFilter
        ];
    }

    public function handleStatusUpdate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'error' => 'Invalid security token. Please refresh and try again.'];
        }

        $auth = AuthHelper::getCurrentUser();
        if (!$auth || $auth['role'] !== 'owner') {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $owner = $this->ownerModel->findByUserId($auth['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Owner profile record not found.'];
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $replacementVehicleId = !empty($_POST['replacement_vehicle_id']) ? (int)$_POST['replacement_vehicle_id'] : null;
        $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

        if ($requestId <= 0 || !in_array($action, ['approved', 'rejected', 'dispatched', 'delivered', 'cancelled'], true)) {
            return ['success' => false, 'error' => 'Invalid action parameters.'];
        }

        $success = $this->replacementModel->updateStatus($requestId, $owner['id'], $action, $replacementVehicleId, $remarks);

        if ($success) {
            return ['success' => true, 'message' => "Replacement request #{$requestId} has been updated to '" . ucfirst($action) . "'."];
        } else {
            return ['success' => false, 'error' => 'Failed to update replacement request or unauthorized.'];
        }
    }
}
