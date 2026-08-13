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

        return [
            'success'     => true,
            'inspections' => $inspections,
            'summary'     => $summary,
            'filter'      => $statusFilter
        ];
    }
}
