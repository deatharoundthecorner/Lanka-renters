<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';
require_once dirname(__DIR__) . '/models/Driver.php';
require_once dirname(__DIR__) . '/models/DriverOwnerLink.php';
require_once dirname(__DIR__) . '/models/Notification.php';
require_once dirname(__DIR__) . '/models/User.php';

/**
 * Lanka Renters - Owner Controller
 * Handles vehicle owner actions: assigning drivers and viewing dashboard.
 */
class OwnerController {
    private $ownerModel;
    private $driverModel;
    private $linkModel;
    private $notificationModel;

    public function __construct() {
        $this->ownerModel = new VehicleOwner();
        $this->driverModel = new Driver();
        $this->linkModel = new DriverOwnerLink();
        $this->notificationModel = new Notification();
    }

    /**
     * Helper to authenticate owner role and retrieve owner record.
     */
    private function getSecureOwner() {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Session unauthorized.");
        }

        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            throw new Exception("Vehicle Owner profile not found.");
        }

        // Add user name to owner profile
        $owner['name'] = $user['name'];
        $owner['user_id'] = $user['id'];
        return $owner;
    }

    /**
     * Lists all available verified drivers.
     */
    public function viewAvailableDrivers() {
        try {
            $owner = $this->getSecureOwner();
            $drivers = $this->driverModel->getAvailableVerifiedDrivers();
            return [
                'success' => true,
                'drivers' => $drivers,
                'owner'   => $owner
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Assigns/requests a connection with a driver.
     * Enforces security: only available verified drivers can be assigned.
     */
    public function assignDriver($driverId) {
        try {
            $owner = $this->getSecureOwner();

            // 1. Security Check: Verify driver is indeed verified and available
            $verifiedDrivers = $this->driverModel->getAvailableVerifiedDrivers();
            $isVerifiedAvailable = false;
            foreach ($verifiedDrivers as $vd) {
                if ((int)$vd['id'] === (int)$driverId) {
                    $isVerifiedAvailable = true;
                    break;
                }
            }

            if (!$isVerifiedAvailable) {
                return [
                    'success' => false,
                    'error'   => "Security Violation: You can only request links with verified, available drivers."
                ];
            }

            // 2. Insert/update link
            $success = $this->linkModel->requestLink($driverId, $owner['id']);
            if (!$success) {
                return [
                    'success' => false,
                    'error'   => "Failed to create driver connection request."
                ];
            }

            // 3. Find driver user_id to send notification
            $driver = $this->driverModel->findById($driverId);
            if ($driver) {
                $driverUserId = $driver['user_id'];
                $msg = "You have received a driver connection request from Owner " . $owner['name'];
                $this->notificationModel->create($driverUserId, "Driver Connection Request", $msg);
            }

            return [
                'success' => true,
                'message' => "Connection request sent to driver successfully."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
