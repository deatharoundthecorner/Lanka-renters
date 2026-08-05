<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Driver.php';
require_once dirname(__DIR__) . '/models/DriverOwnerLink.php';
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Customer Controller
 * Coordinates customer driver selection and security checks for bookings.
 */
class CustomerController {
    private $driverModel;
    private $linkModel;

    public function __construct() {
        $this->driverModel = new Driver();
        $this->linkModel = new DriverOwnerLink();
    }

    /**
     * Secures that active user has role customer.
     */
    private function getSecureCustomer() {
        AuthHelper::requireRole('customer');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Session unauthorized.");
        }
        return $user;
    }

    /**
     * Retrieves list of eligible drivers for a customer booking.
     * Only lists drivers accepted by the selected vehicle's owner.
     * Enforces driver availability status and scheduling overlaps.
     */
    public function getEligibleDriversForVehicle($vehicleId, $startDate, $endDate) {
        try {
            $customer = $this->getSecureCustomer();
            $db = Database::getInstance()->getConnection();

            // 1. Find the vehicle owner
            $sqlOwner = "SELECT `owner_id` FROM `vehicles` WHERE `id` = :vehicle_id LIMIT 1";
            $stmtOwner = $db->prepare($sqlOwner);
            $stmtOwner->execute(['vehicle_id' => $vehicleId]);
            $ownerId = $stmtOwner->fetchColumn();

            if (!$ownerId) {
                return [
                    'success' => false,
                    'error'   => "Vehicle not found."
                ];
            }

            // 2. Fetch accepted drivers for this owner
            $drivers = $this->linkModel->getAcceptedDriversByOwner($ownerId);

            $eligibleDrivers = [];
            foreach ($drivers as $d) {
                // 3. Check completed trips count
                $sqlTrips = "SELECT COUNT(*) FROM `bookings` WHERE `driver_id` = :driver_id AND `status` = 'completed'";
                $stmtTrips = $db->prepare($sqlTrips);
                $stmtTrips->execute(['driver_id' => $d['driver_id']]);
                $completedTrips = (int)$stmtTrips->fetchColumn();

                // 4. Driver Availability check 1: availability_status = 'available'
                $isAvailable = ($d['availability_status'] === 'available');

                // 5. Driver Availability check 2: no booking conflicts
                $hasConflict = $this->driverModel->hasBookingConflict($d['driver_id'], $startDate, $endDate);

                // Add eligibility properties
                $d['completed_trips'] = $completedTrips;
                $d['is_available_status'] = $isAvailable;
                $d['has_schedule_conflict'] = $hasConflict;

                // Add only if status is available and no conflict
                if ($isAvailable && !$hasConflict) {
                    $eligibleDrivers[] = $d;
                }
            }

            return [
                'success' => true,
                'drivers' => $eligibleDrivers,
                'owner_id' => $ownerId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Validates and processes the booking driver assignment.
     * Enforces the security requirement: customer cannot choose drivers from other owners.
     */
    public function selectDriverForBooking($vehicleId, $driverId, $startDate, $endDate) {
        try {
            $customer = $this->getSecureCustomer();
            $db = Database::getInstance()->getConnection();

            // 1. Find vehicle owner
            $sqlOwner = "SELECT `owner_id` FROM `vehicles` WHERE `id` = :vehicle_id LIMIT 1";
            $stmtOwner = $db->prepare($sqlOwner);
            $stmtOwner->execute(['vehicle_id' => $vehicleId]);
            $ownerId = $stmtOwner->fetchColumn();

            if (!$ownerId) {
                return [
                    'success' => false,
                    'error'   => "Vehicle not found."
                ];
            }

            // 2. Validate driver-owner accepted link exists (Security Requirement)
            $sqlLink = "SELECT COUNT(*) FROM `driver_owner_links` 
                        WHERE `driver_id` = :driver_id AND `owner_id` = :owner_id AND `status` = 'accepted'";
            $stmtLink = $db->prepare($sqlLink);
            $stmtLink->execute([
                'driver_id' => $driverId,
                'owner_id'  => $ownerId
            ]);
            $linkExists = ((int)$stmtLink->fetchColumn() > 0);

            if (!$linkExists) {
                return [
                    'success' => false,
                    'error'   => "Security Violation: Selected driver is not linked to this vehicle's owner."
                ];
            }

            // 3. Double-check availability and scheduling conflicts
            $driver = $this->driverModel->findById($driverId);
            if ($driver['availability_status'] !== 'available') {
                return [
                    'success' => false,
                    'error'   => "Driver is currently busy or off-duty."
                ];
            }

            if ($this->driverModel->hasBookingConflict($driverId, $startDate, $endDate)) {
                return [
                    'success' => false,
                    'error'   => "Assignment Rejected: Driver has a conflicting booking time."
                ];
            }

            return [
                'success' => true,
                'message' => "Driver is eligible and selected."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
