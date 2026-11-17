<?php

require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/ProfileChangeRequest.php';
require_once dirname(__DIR__) . '/models/AccountChangeRequest.php';
require_once dirname(__DIR__) . '/models/DriverDocument.php';

/**
 * Lanka Renters - AdminApprovalController
 * Coordinates administration approval centers and handles approval/rejections.
 */
class AdminApprovalController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Secures the admin session and returns the active admin details.
     */
    private function getSecureAdmin() {
        AuthHelper::startSession();
        AuthHelper::requireRole('admin');
        $admin = AuthHelper::getCurrentUser();
        if (!$admin) {
            throw new Exception("Unauthorized admin access.");
        }
        return $admin;
    }

    /**
     * Aggregates all pending approval queues for the central dashboard.
     */
    public function getPendingQueues() {
        try {
            $this->getSecureAdmin();

            $pcrModel = new ProfileChangeRequest();
            $acrModel = new AccountChangeRequest();

            // 1. Fetch pending profile change requests
            $profileChanges = $pcrModel->getPendingRequests();

            // 2. Fetch pending credentials change requests
            $accountChanges = $acrModel->getPendingRequests();

            // 3. Fetch pending driver documents
            $sqlDocs = "SELECT dd.*, u.name as user_name, u.email as user_email 
                        FROM `driver_documents` dd
                        JOIN `drivers` d ON dd.driver_id = d.id
                        JOIN `users` u ON d.user_id = u.id
                        WHERE dd.status = 'pending'
                        ORDER BY dd.uploaded_at ASC";
            $stmtDocs = $this->db->prepare($sqlDocs);
            $stmtDocs->execute();
            $documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'profile_changes' => $profileChanges,
                'account_changes' => $accountChanges,
                'documents' => $documents
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Approves a pending profile change request.
     */
    public function approveProfileChange($requestId) {
        try {
            $admin = $this->getSecureAdmin();
            $pcrModel = new ProfileChangeRequest();
            if ($pcrModel->approve($requestId, $admin['id'])) {
                return ['success' => true, 'message' => "Profile change request approved."];
            }
            return ['success' => false, 'error' => "Failed to approve profile change."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejects a pending profile change request.
     */
    public function rejectProfileChange($requestId, $reason) {
        try {
            $admin = $this->getSecureAdmin();
            if (empty($reason)) {
                return ['success' => false, 'error' => "A rejection reason is required."];
            }
            $pcrModel = new ProfileChangeRequest();
            if ($pcrModel->reject($requestId, $reason, $admin['id'])) {
                return ['success' => true, 'message' => "Profile change request rejected."];
            }
            return ['success' => false, 'error' => "Failed to reject profile change."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Approves a pending account change request.
     */
    public function approveAccountChange($requestId) {
        try {
            $admin = $this->getSecureAdmin();
            $acrModel = new AccountChangeRequest();
            if ($acrModel->approve($requestId, $admin['id'])) {
                return ['success' => true, 'message' => "Credentials change request approved."];
            }
            return ['success' => false, 'error' => "Failed to approve credentials change."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejects a pending account change request.
     */
    public function rejectAccountChange($requestId, $reason) {
        try {
            $admin = $this->getSecureAdmin();
            if (empty($reason)) {
                return ['success' => false, 'error' => "A rejection reason is required."];
            }
            $acrModel = new AccountChangeRequest();
            if ($acrModel->reject($requestId, $reason, $admin['id'])) {
                return ['success' => true, 'message' => "Credentials change request rejected."];
            }
            return ['success' => false, 'error' => "Failed to reject credentials change."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Approves a pending driver document version.
     */
    public function approveDocument($documentId) {
        try {
            $admin = $this->getSecureAdmin();
            $docModel = new DriverDocument();
            if ($docModel->approveDocument($documentId, $admin['id'])) {
                return ['success' => true, 'message' => "Driver document approved."];
            }
            return ['success' => false, 'error' => "Failed to approve document."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rejects a pending driver document version.
     */
    public function rejectDocument($documentId, $reason) {
        try {
            $admin = $this->getSecureAdmin();
            if (empty($reason)) {
                return ['success' => false, 'error' => "A rejection reason is required."];
            }
            $docModel = new DriverDocument();
            if ($docModel->rejectDocument($documentId, $reason, $admin['id'])) {
                return ['success' => true, 'message' => "Driver document rejected."];
            }
            return ['success' => false, 'error' => "Failed to reject document."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
