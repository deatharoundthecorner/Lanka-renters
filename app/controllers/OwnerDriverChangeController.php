<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/DriverChangeRequest.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * Lanka Renters - Owner Driver Change Controller
 * Coordinates viewing, approving, and rejecting customer driver change requests
 * for vehicles owned by the authenticated vehicle owner.
 */
class OwnerDriverChangeController
{
    private DriverChangeRequest $requestModel;
    private VehicleOwner $ownerModel;

    public function __construct(
        ?DriverChangeRequest $requestModel = null,
        ?VehicleOwner $ownerModel = null
    ) {
        $this->requestModel = $requestModel ?? new DriverChangeRequest();
        $this->ownerModel   = $ownerModel ?? new VehicleOwner();
    }

    public function getData(): array
    {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Vehicle owner profile not found.'];
        }

        $ownerId = (int)$owner['id'];
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
        if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $statusFilter = null;
        }

        $requests = $this->requestModel->findByOwner($ownerId, $statusFilter);
        $summary  = $this->requestModel->getSummaryByOwner($ownerId);

        return [
            'success'  => true,
            'owner'    => $owner,
            'requests' => $requests,
            'summary'  => $summary,
            'filter'   => $statusFilter,
        ];
    }

    public function handleAction(array $post): ?array
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return null;
        }

        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        if (!AuthHelper::validateCsrfToken($post['csrf_token'] ?? '')) {
            return ['success' => false, 'error' => 'Security token invalid or expired. Please refresh and try again.'];
        }

        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            return ['success' => false, 'error' => 'Vehicle owner profile not found.'];
        }

        $ownerId        = (int)$owner['id'];
        $reviewerUserId = (int)$user['id'];
        $requestId      = (int)($post['request_id'] ?? 0);
        $action         = trim((string)($post['action'] ?? ''));
        $remarks        = trim((string)($post['remarks'] ?? ''));

        if ($requestId <= 0) {
            return ['success' => false, 'error' => 'Invalid driver change request ID.'];
        }

        try {
            if ($action === 'approve') {
                $this->requestModel->approve($requestId, $reviewerUserId, $ownerId, $remarks !== '' ? $remarks : null);
                return [
                    'success' => true,
                    'message' => "Driver change request #{$requestId} has been approved. The new driver has been assigned to the booking.",
                ];
            } elseif ($action === 'reject') {
                $this->requestModel->reject($requestId, $reviewerUserId, $ownerId, $remarks !== '' ? $remarks : null);
                return [
                    'success' => true,
                    'message' => "Driver change request #{$requestId} has been rejected. The current driver remains assigned.",
                ];
            } else {
                return ['success' => false, 'error' => 'Invalid action specified.'];
            }
        } catch (DriverChangeRequestException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('Owner driver change review error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process request. Please try again.'];
        }
    }
}
