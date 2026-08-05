<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Driver.php';
require_once dirname(__DIR__) . '/models/DriverDocument.php';
require_once dirname(__DIR__) . '/models/DriverLeave.php';
require_once dirname(__DIR__) . '/models/DriverAvailability.php';
require_once dirname(__DIR__) . '/models/DriverOwnerLink.php';
require_once dirname(__DIR__) . '/models/DriverPayment.php';
require_once dirname(__DIR__) . '/models/Notification.php';

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

            // Fetch hybrid link stats and monthly earnings
            $linkModel = new DriverOwnerLink();
            $paymentModel = new DriverPayment();

            $stats['connected_owners'] = $linkModel->getLinkCountByDriver($driverId, 'accepted');
            $stats['pending_requests'] = $linkModel->getLinkCountByDriver($driverId, 'pending');
            $stats['monthly_earnings'] = $paymentModel->getMonthlyEarnings($driverId);

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
                if ($status === 'dropped_off') {
                    $db = Database::getInstance()->getConnection();
                    $sql = "SELECT b.booking_type, b.start_date, b.end_date, b.customer_id,
                                   v.price_per_day, v.price_with_driver_per_day
                            FROM `bookings` b
                            JOIN `vehicles` v ON b.vehicle_id = v.id
                            WHERE b.id = :booking_id LIMIT 1";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['booking_id' => $bookingId]);
                    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($booking && $booking['booking_type'] === 'with_driver') {
                        $daily_rental = (float)$booking['price_per_day'];
                        $daily_with_driver = $booking['price_with_driver_per_day'] !== null ? (float)$booking['price_with_driver_per_day'] : ($daily_rental + 3000.0);
                        $driver_rate = max(0.0, $daily_with_driver - $daily_rental);
                        
                        $days = max(1, (int)ceil((strtotime($booking['end_date']) - strtotime($booking['start_date'])) / 86400));
                        $paymentAmount = $driver_rate * $days;

                        $paymentModel = new DriverPayment();
                        $paymentModel->createPayment($driver['id'], $bookingId, $paymentAmount, 'pending');

                        // Notifications
                        $notificationModel = new Notification();
                        $msgDriver = "Payment generated: Rs. " . number_format($paymentAmount, 2) . " has been credited to your pending earnings for Booking #" . $bookingId;
                        $notificationModel->create($user['id'], "Payment Generated", $msgDriver);

                        $sqlCust = "SELECT user_id FROM `customers` WHERE id = :customer_id LIMIT 1";
                        $stmtCust = $db->prepare($sqlCust);
                        $stmtCust->execute(['customer_id' => $booking['customer_id']]);
                        $custUserId = $stmtCust->fetchColumn();
                        if ($custUserId) {
                            $msgCust = "Your booking #" . $bookingId . " has been marked as completed. Thank you for using Lanka Renters!";
                            $notificationModel->create($custUserId, "Trip Completed", $msgCust);
                        }
                    }
                }

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

    /**
     * Retrieves all pending owner connection requests for the driver.
     */
    public function viewOwnerRequests() {
        try {
            $driver = $this->getSecureDriver();
            $linkModel = new DriverOwnerLink();
            $requests = $linkModel->getLinksByDriver($driver['id'], 'pending');
            return [
                'success'  => true,
                'requests' => $requests
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Driver accepts an owner's connection request.
     */
    public function acceptOwnerRequest($linkId) {
        try {
            $driver = $this->getSecureDriver();
            $linkModel = new DriverOwnerLink();
            
            // Verify link ownership
            $link = $linkModel->findById($linkId);
            if (!$link || (int)$link['driver_id'] !== (int)$driver['id']) {
                return [
                    'success' => false,
                    'error'   => "Security Violation: Unauthorized connection request."
                ];
            }

            $success = $linkModel->updateStatus($linkId, 'accepted');
            if ($success) {
                // Notify Owner
                $notificationModel = new Notification();
                $db = Database::getInstance()->getConnection();
                $sqlOwner = "SELECT vo.user_id FROM `vehicle_owners` vo WHERE vo.id = :owner_id LIMIT 1";
                $stmt = $db->prepare($sqlOwner);
                $stmt->execute(['owner_id' => $link['owner_id']]);
                $ownerUserId = $stmt->fetchColumn();
                
                if ($ownerUserId) {
                    $msg = "Driver " . $driver['name'] . " has accepted your connection request.";
                    $notificationModel->create($ownerUserId, "Connection Request Accepted", $msg);
                }

                return [
                    'success' => true,
                    'message' => "Connection request accepted successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to accept connection request."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Driver rejects an owner's connection request.
     */
    public function rejectOwnerRequest($linkId) {
        try {
            $driver = $this->getSecureDriver();
            $linkModel = new DriverOwnerLink();
            
            // Verify link ownership
            $link = $linkModel->findById($linkId);
            if (!$link || (int)$link['driver_id'] !== (int)$driver['id']) {
                return [
                    'success' => false,
                    'error'   => "Security Violation: Unauthorized connection request."
                ];
            }

            $success = $linkModel->updateStatus($linkId, 'rejected');
            if ($success) {
                // Notify Owner
                $notificationModel = new Notification();
                $db = Database::getInstance()->getConnection();
                $sqlOwner = "SELECT vo.user_id FROM `vehicle_owners` vo WHERE vo.id = :owner_id LIMIT 1";
                $stmt = $db->prepare($sqlOwner);
                $stmt->execute(['owner_id' => $link['owner_id']]);
                $ownerUserId = $stmt->fetchColumn();
                
                if ($ownerUserId) {
                    $msg = "Driver " . $driver['name'] . " has rejected your connection request.";
                    $notificationModel->create($ownerUserId, "Connection Request Rejected", $msg);
                }

                return [
                    'success' => true,
                    'message' => "Connection request rejected successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to reject connection request."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
