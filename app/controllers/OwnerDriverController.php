<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/Database.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';
require_once dirname(__DIR__) . '/models/DriverOwnerLink.php';
require_once dirname(__DIR__) . '/models/Driver.php';

/**
 * Lanka Renters - Owner Driver Controller
 * Manages driver connections and requests for the authenticated vehicle owner.
 * All queries enforce ownership via session-resolved owner_id — never from client input.
 */
class OwnerDriverController {
    private $db;
    private $ownerModel;
    private $linkModel;
    private $driverModel;

    public function __construct() {
        $this->db          = Database::getInstance()->getConnection();
        $this->ownerModel  = new VehicleOwner();
        $this->linkModel   = new DriverOwnerLink();
        $this->driverModel = new Driver();
    }

    /**
     * Resolves authenticated owner profile from session.
     */
    private function getAuthenticatedOwner(): array {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception('Unauthorized access.');
        }
        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            throw new Exception('Vehicle owner profile not found.');
        }
        return ['user' => $user, 'owner' => $owner];
    }

    /**
     * Fetches all accepted drivers linked to the authenticated owner,
     * all pending outbound requests, and all available verified drivers for discovery.
     */
    public function getDriverData(): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            // Accepted drivers
            $acceptedDrivers = $this->linkModel->getAcceptedDriversByOwner($ownerId);

            // Pending outbound requests
            $stmtPending = $this->db->prepare("
                SELECT dol.id AS link_id, dol.created_at,
                       d.rating_avg AS rating,
                       u.name, u.email, u.phone
                FROM driver_owner_links dol
                JOIN drivers d ON dol.driver_id = d.id
                JOIN users   u ON d.user_id     = u.id
                WHERE dol.owner_id = :owner_id AND dol.status = 'pending'
                ORDER BY dol.created_at DESC
            ");
            $stmtPending->execute(['owner_id' => $ownerId]);
            $pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

            // Available verified drivers (for discovery/request form)
            $availableDrivers = $this->driverModel->getAvailableVerifiedDrivers();

            return [
                'success'          => true,
                'owner'            => $auth['owner'],
                'accepted_drivers' => $acceptedDrivers,
                'pending_requests' => $pendingRequests,
                'available_drivers' => $availableDrivers,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'accepted_drivers'  => [],
                'pending_requests'  => [],
                'available_drivers' => [],
            ];
        }
    }

    /**
     * Sends a connection request from the authenticated owner to a driver.
     * Validates CSRF, checks driver eligibility, and prevents duplicate requests.
     *
     * @param array $postData POST input containing driver_id and csrf_token
     */
    public function requestDriverConnection(array $postData): array {
        try {
            $auth    = $this->getAuthenticatedOwner();
            $ownerId = $auth['owner']['id'];

            // 1. CSRF
            if (!AuthHelper::validateCsrfToken($postData['csrf_token'] ?? '')) {
                return ['success' => false, 'error' => 'CSRF security verification failed.'];
            }

            $driverId = (int)($postData['driver_id'] ?? 0);
            if ($driverId <= 0) {
                return ['success' => false, 'error' => 'Invalid driver selected.'];
            }

            // 2. Validate the driver is eligible (verified + available)
            $availableDrivers = $this->driverModel->getAvailableVerifiedDrivers();
            $isEligible       = false;
            foreach ($availableDrivers as $d) {
                if ((int)$d['id'] === $driverId) {
                    $isEligible = true;
                    break;
                }
            }
            if (!$isEligible) {
                return ['success' => false, 'error' => 'Selected driver is not eligible or verified.'];
            }

            // 3. Check for existing link
            $stmt = $this->db->prepare("SELECT status FROM driver_owner_links WHERE driver_id = :did AND owner_id = :oid LIMIT 1");
            $stmt->execute(['did' => $driverId, 'oid' => $ownerId]);
            $existing = $stmt->fetchColumn();

            if ($existing === 'accepted') {
                return ['success' => false, 'error' => 'You are already connected with this driver.'];
            }
            if ($existing === 'pending') {
                return ['success' => false, 'error' => 'A connection request is already pending with this driver.'];
            }

            // 4. Create the link
            $this->linkModel->requestLink($driverId, $ownerId);
            return ['success' => true, 'message' => 'Connection request sent successfully.'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Request failed: ' . $e->getMessage()];
        }
    }
}
