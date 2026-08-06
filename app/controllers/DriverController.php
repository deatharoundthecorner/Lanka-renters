<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Driver.php';
require_once dirname(__DIR__) . '/models/DriverDocument.php';
require_once dirname(__DIR__) . '/models/DriverLeave.php';
require_once dirname(__DIR__) . '/models/DriverAvailability.php';

/**
 * Lanka Renters - Driver Controller
 * Orchestrates driver operations such as dashboard data aggregation, document uploads,
 * availability toggling, leave requests, and booking pickup tracking.
 */
class DriverController {

    /**
     * Secures the endpoint by checking user session state and role membership.
     * Crucial: Always resolves the active driver profile using the session user's ID, 
     * protecting the backend from POST spoofing or ID tampering.
     * 
     * @return array The authenticated driver profile record (with id, user_id, name, etc.)
     * @throws Exception if unauthenticated, unauthorized, or profile doesn't exist
     */
    private function getSecureDriver() {
        // Enforce active session and role = driver
        AuthHelper::requireRole('driver');
        
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Session unauthorized.");
        }

        // Fetch driver profile using users.id from the session
        $driverModel = new Driver();
        $driver = $driverModel->findByUserId($user['id']);

        if (!$driver) {
            throw new Exception("Driver profile record not found inside database.");
        }

        return $driver;
    }

    /**
     * Returns consolidated driver dashboard data.
     * 
     * @return array Response array containing profile, stats, and assigned vehicles
     */
    public function dashboard() {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $driverModel = new Driver();
            $stats = $driverModel->getDashboardData($driverId);
            $vehicles = $driverModel->getAssignedVehicles($driverId);

            return [
                'success'           => true,
                'profile'           => $driver,
                'dashboard_stats'   => $stats,
                'assigned_vehicles' => $vehicles
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Uploads and registers a new document (NIC, license, police report).
     * 
     * @param array $data Input document data (document_type, document_number, expiry_date, file_path)
     * @return array Response array containing status and document ID
     */
    public function uploadDocument($data) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            // 1. Validate required fields
            if (empty($data['document_type']) || empty($data['file_path'])) {
                return [
                    'success' => false,
                    'error'   => "Document type and file upload are required."
                ];
            }

            // 2. Validate document type limit
            $allowedTypes = ['nic', 'driving_license', 'police_report'];
            if (!in_array($data['document_type'], $allowedTypes)) {
                return [
                    'success' => false,
                    'error'   => "Invalid document type selected."
                ];
            }

            // 3. Register document via model, securing driver_id from session
            $docModel = new DriverDocument();
            $docId = $docModel->create([
                'driver_id'       => $driverId,
                'document_type'   => $data['document_type'],
                'document_number' => $data['document_number'] ?? null,
                'expiry_date'     => $data['expiry_date'] ?? null,
                'file_path'       => $data['file_path']
            ]);

            if ($docId) {
                return [
                    'success'     => true,
                    'document_id' => $docId,
                    'message'     => "Document uploaded successfully and is pending verification."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to register document."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Returns a list of all documents uploaded by the authenticated driver.
     * 
     * @return array Response array containing documents
     */
    public function viewDocuments() {
        try {
            $driver = $this->getSecureDriver();
            
            $docModel = new DriverDocument();
            $documents = $docModel->getByDriverId($driver['id']);

            return [
                'success'   => true,
                'documents' => $documents
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Changes the driver's current availability status.
     * 
     * @param string $status Target status ('available', 'busy', 'off_duty')
     * @return array Response array containing status
     */
    public function updateAvailability($status) {
        try {
            $driver = $this->getSecureDriver();

            $availModel = new DriverAvailability();
            $result = $availModel->updateStatus($driver['id'], $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Availability status updated to " . $status . "."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update availability status."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Files a new leave request for the driver.
     * 
     * @param array $data Contains start_date, end_date, reason
     * @return array Response array containing status and leave request ID
     */
    public function requestLeave($data) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            // 1. Validate dates
            if (empty($data['start_date']) || empty($data['end_date'])) {
                return [
                    'success' => false,
                    'error'   => "Start date and end date are required."
                ];
            }

            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                return [
                    'success' => false,
                    'error'   => "Start date cannot fall after the end date."
                ];
            }

            // 2. Save leave request via model
            $leaveModel = new DriverLeave();
            $leaveId = $leaveModel->create([
                'driver_id'  => $driverId,
                'start_date' => $data['start_date'],
                'end_date'   => $data['end_date'],
                'reason'     => $data['reason'] ?? ''
            ]);

            if ($leaveId) {
                return [
                    'success'  => true,
                    'leave_id' => $leaveId,
                    'message'  => "Leave request submitted successfully and is pending review."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to submit leave request."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Returns the leave history log for the authenticated driver.
     * 
     * @return array Response array containing leave records
     */
    public function viewLeaveHistory() {
        try {
            $driver = $this->getSecureDriver();
            
            $leaveModel = new DriverLeave();
            $leaves = $leaveModel->getByDriverId($driver['id']);

            return [
                'success' => true,
                'leaves'  => $leaves
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Returns a list of vehicles currently assigned to the driver.
     * 
     * @return array Response array containing assigned vehicle records
     */
    public function viewAssignedVehicles() {
        try {
            $driver = $this->getSecureDriver();
            
            $driverModel = new Driver();
            $vehicles = $driverModel->getAssignedVehicles($driver['id']);

            return [
                'success'  => true,
                'vehicles' => $vehicles
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Updates the pickup status of a booking and logs it in tracking history.
     * Asserts that the requested booking is explicitly assigned to this driver.
     * 
     * @param int $bookingId Booking ID
     * @param string $status Target pickup status ('pending_pickup', 'dispatched', 'arrived', 'picked_up', 'dropped_off')
     * @param float|null $latitude GPS latitude (optional)
     * @param float|null $longitude GPS longitude (optional)
     * @return array Response array containing status
     */
    public function updatePickupStatus($bookingId, $status, $latitude = null, $longitude = null) {
        try {
            // Retrieve secure driver context (checks session, verifies role)
            $driver = $this->getSecureDriver();
            
            $user = AuthHelper::getCurrentUser();
            $userId = $user['id']; // users.id required for pickup_tracking.updated_by

            // Validate status ENUM
            $allowedStatuses = ['pending_pickup', 'dispatched', 'arrived', 'picked_up', 'dropped_off'];
            if (!in_array($status, $allowedStatuses)) {
                return [
                    'success' => false,
                    'error'   => "Invalid pickup status."
                ];
            }

            // Call Driver model which runs authorization validation and updates tables atomically
            $driverModel = new Driver();
            $result = $driverModel->addPickupTracking($bookingId, $userId, $status, $latitude, $longitude);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Pickup tracking status successfully updated to " . $status . "."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update pickup status."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Returns all bookings (trips) assigned to the driver,
     * categorized into active/ongoing and completed/cancelled histories.
     * 
     * @return array Response array containing active and completed trips
     */
    public function viewTrips() {
        try {
            $driver = $this->getSecureDriver();
            $driverModel = new Driver();
            $bookings = $driverModel->getBookings($driver['id']);

            $activeTrips = [];
            $completedTrips = [];

            foreach ($bookings as $booking) {
                // Booking statuses: 'pending_payment', 'confirmed', 'ongoing', 'completed', 'cancelled'
                if (in_array($booking['status'], ['completed', 'cancelled'])) {
                    $completedTrips[] = $booking;
                } else {
                    $activeTrips[] = $booking;
                }
            }

            return [
                'success'         => true,
                'active_trips'    => $activeTrips,
                'completed_trips' => $completedTrips
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
