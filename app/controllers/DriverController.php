<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Driver.php';
require_once dirname(__DIR__) . '/models/DriverDocument.php';
require_once dirname(__DIR__) . '/models/DriverLeave.php';
require_once dirname(__DIR__) . '/models/DriverAvailability.php';
require_once dirname(__DIR__) . '/models/DriverOwnerLink.php';
require_once dirname(__DIR__) . '/models/DriverPayment.php';
require_once dirname(__DIR__) . '/models/Notification.php';
require_once dirname(__DIR__) . '/models/DriverIncident.php';
require_once dirname(__DIR__) . '/models/VehicleSafetyCheck.php';
require_once dirname(__DIR__) . '/models/DriverPerformance.php';

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
     * Edits an existing document.
     * 
     * @param int $documentId The document ID
     * @param array $data Input document data (document_number, expiry_date, and optional file_path)
     * @return array Response array containing status
     */
    public function editDocument($documentId, $data) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $docModel = new DriverDocument();
            $document = $docModel->getById($documentId);

            if (!$document || $document['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Document not found or unauthorized."
                ];
            }

            // Perform edit updates via model
            $result = $docModel->update($documentId, $data);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Document updated successfully and is pending verification."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update document."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes an existing document (if pending).
     * 
     * @param int $documentId The document ID
     * @return array Response array containing status
     */
    public function deleteDocument($documentId) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $docModel = new DriverDocument();
            $document = $docModel->getById($documentId);

            if (!$document || $document['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Document not found or unauthorized."
                ];
            }

            if ($document['verification_status'] !== 'pending') {
                return [
                    'success' => false,
                    'error'   => "Only pending documents can be deleted."
                ];
            }

            // Retrieve file path to remove it physically
            $filePath = $document['file_path'];

            $result = $docModel->delete($documentId);

            if ($result) {
                // Delete physical file
                $fullPath = dirname(dirname(__DIR__)) . '/public/' . $filePath;
                if (!empty($filePath) && file_exists($fullPath)) {
                    unlink($fullPath);
                }
                return [
                    'success' => true,
                    'message' => "Document deleted successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to delete document."
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
     * Edits an existing pending leave request.
     * 
     * @param int $leaveId The leave ID
     * @param array $data Input leave data (start_date, end_date, reason)
     * @return array Response array containing status
     */
    public function editLeave($leaveId, $data) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $leaveModel = new DriverLeave();
            $leave = $leaveModel->getById($leaveId);

            if (!$leave || $leave['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Leave request not found or unauthorized."
                ];
            }

            if ($leave['status'] !== 'pending') {
                return [
                    'success' => false,
                    'error'   => "Only pending leave requests can be edited."
                ];
            }

            // Validate fields
            if (empty($data['start_date']) || empty($data['end_date'])) {
                return [
                    'success' => false,
                    'error'   => "Start date and end date are required."
                ];
            }

            if (empty($data['reason']) || trim($data['reason']) === '') {
                return [
                    'success' => false,
                    'error'   => "Leave reason cannot be empty."
                ];
            }

            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                return [
                    'success' => false,
                    'error'   => "Start date cannot fall after the end date."
                ];
            }

            $result = $leaveModel->update($leaveId, $data);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Leave request updated successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update leave request."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Cancels an existing pending leave request.
     * 
     * @param int $leaveId The leave ID
     * @return array Response array containing status
     */
    public function cancelLeave($leaveId) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $leaveModel = new DriverLeave();
            $leave = $leaveModel->getById($leaveId);

            if (!$leave || $leave['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Leave request not found or unauthorized."
                ];
            }

            if ($leave['status'] !== 'pending') {
                return [
                    'success' => false,
                    'error'   => "Only pending leave requests can be cancelled."
                ];
            }

            $result = $leaveModel->delete($leaveId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Leave request cancelled successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to cancel leave request."
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
    public function updatePickupStatus($bookingId, $status, $driverNote = null) {
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
            $result = $driverModel->addPickupTracking($bookingId, $userId, $status, $driverNote);

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

    /**
     * Submits a vehicle safety checklist.
     * 
     * @param array $data Contains booking_id, brakes, lights, tires, fuel
     * @return array Response array containing status
     */
    public function submitSafetyCheck($data) {
        try {
            $driver = $this->getSecureDriver();
            $bookingId = (int)($data['booking_id'] ?? 0);
            
            $brakes = isset($data['brakes']) ? 1 : 0;
            $lights = isset($data['lights']) ? 1 : 0;
            $tires = isset($data['tires']) ? 1 : 0;
            $fuel = isset($data['fuel']) ? 1 : 0;

            // Fetch booking details to verify it exists and is assigned
            $db = Database::getInstance()->getConnection();
            $sqlBooking = "SELECT vehicle_id FROM bookings WHERE id = :booking_id AND driver_id = :driver_id LIMIT 1";
            $stmt = $db->prepare($sqlBooking);
            $stmt->execute([
                'booking_id' => $bookingId,
                'driver_id'  => $driver['id']
            ]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return [
                    'success' => false,
                    'error'   => "Booking not found or not assigned to you."
                ];
            }

            $safetyCheckModel = new VehicleSafetyCheck();
            
            // Check duplicate check
            $existing = $safetyCheckModel->getByBooking($bookingId);
            if ($existing) {
                return [
                    'success' => false,
                    'error'   => "Safety check has already been recorded for this booking."
                ];
            }

            // Save safety check
            $checkId = $safetyCheckModel->create([
                'driver_id'  => $driver['id'],
                'vehicle_id' => $booking['vehicle_id'],
                'booking_id' => $bookingId,
                'brakes'     => $brakes,
                'lights'     => $lights,
                'tires'      => $tires,
                'fuel'       => $fuel
            ]);

            if ($checkId) {
                return [
                    'success' => true,
                    'message' => "Vehicle safety checks logged successfully!"
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to log vehicle safety check."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Edits an existing pending vehicle safety check.
     * 
     * @param int $checkId The safety check ID
     * @param array $data Contains brakes, lights, tires, fuel
     * @return array Response array containing status
     */
    public function editSafetyCheck($checkId, $data) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $safetyCheckModel = new VehicleSafetyCheck();
            $check = $safetyCheckModel->getById($checkId);

            if (!$check || $check['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Safety check not found or unauthorized."
                ];
            }

            // Verify if booking is still in pending_pickup status
            $db = Database::getInstance()->getConnection();
            $sqlBooking = "SELECT pickup_status FROM bookings WHERE id = :booking_id LIMIT 1";
            $stmt = $db->prepare($sqlBooking);
            $stmt->execute(['booking_id' => $check['booking_id']]);
            $pickupStatus = $stmt->fetchColumn();

            if ($pickupStatus !== 'pending_pickup') {
                return [
                    'success' => false,
                    'error'   => "This safety check cannot be edited as the trip has already started."
                ];
            }

            $brakes = isset($data['brakes']) ? 1 : 0;
            $lights = isset($data['lights']) ? 1 : 0;
            $tires = isset($data['tires']) ? 1 : 0;
            $fuel = isset($data['fuel']) ? 1 : 0;

            $result = $safetyCheckModel->update($checkId, [
                'brakes' => $brakes,
                'lights' => $lights,
                'tires'  => $tires,
                'fuel'   => $fuel
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Vehicle safety check updated successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update safety check."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes a pending vehicle safety check.
     * 
     * @param int $checkId The safety check ID
     * @return array Response array containing status
     */
    public function deleteSafetyCheck($checkId) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $safetyCheckModel = new VehicleSafetyCheck();
            $check = $safetyCheckModel->getById($checkId);

            if (!$check || $check['driver_id'] !== $driverId) {
                return [
                    'success' => false,
                    'error'   => "Safety check not found or unauthorized."
                ];
            }

            // Verify if booking is still in pending_pickup status
            $db = Database::getInstance()->getConnection();
            $sqlBooking = "SELECT pickup_status FROM bookings WHERE id = :booking_id LIMIT 1";
            $stmt = $db->prepare($sqlBooking);
            $stmt->execute(['booking_id' => $check['booking_id']]);
            $pickupStatus = $stmt->fetchColumn();

            if ($pickupStatus !== 'pending_pickup') {
                return [
                    'success' => false,
                    'error'   => "This safety check cannot be deleted as the trip has already started."
                ];
            }

            $result = $safetyCheckModel->delete($checkId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Safety check record deleted successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to delete safety check."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Lists all safety checks recorded by the driver.
     * 
     * @return array Response array containing safety check logs
     */
    public function viewSafetyChecks() {
        try {
            $driver = $this->getSecureDriver();
            $safetyCheckModel = new VehicleSafetyCheck();
            $checks = $safetyCheckModel->getByDriverId($driver['id']);
            return [
                'success' => true,
                'checks'  => $checks
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }


    /**
     * Reports an incident and handles optional file upload with security constraints.
     * 
     * @param array $data Contains booking_id, description, incident_date, severity
     * @param array|null $file Contains files metadata of $_FILES['incident_photo']
     * @return array Response array containing status
     */
    public function reportIncident($data, $file = null) {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $bookingId = (int)($data['booking_id'] ?? 0);
            $description = trim($data['description'] ?? '');
            $severity = $data['severity'] ?? 'minor';
            $incidentDate = $data['incident_date'] ?? '';

            $incidentModel = new DriverIncident();

            // Security validation: booking must be assigned to driver
            if (!$incidentModel->isBookingAssignedToDriver($bookingId, $driverId)) {
                return [
                    'success' => false,
                    'error'   => "Unauthorized. You can only report incidents for bookings assigned to you."
                ];
            }

            if (empty($description) || empty($incidentDate)) {
                return [
                    'success' => false,
                    'error'   => "Description and Incident Date are required fields."
                ];
            }

            // Create incident
            $incidentId = $incidentModel->create([
                'booking_id'    => $bookingId,
                'reported_by'   => $driver['user_id'], // FK to users.id
                'description'   => $description,
                'incident_date' => $incidentDate,
                'severity'      => $severity
            ]);

            if (!$incidentId) {
                return [
                    'success' => false,
                    'error'   => "Failed to submit incident report."
                ];
            }

            // Photo Upload
            if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $file['tmp_name'];
                $fileName = $file['name'];
                $fileSize = $file['size'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Security Constraint 1: Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    return [
                        'success' => true,
                        'message' => "Incident successfully reported (ID: #" . $incidentId . "), but file size exceeded 5MB limit and was rejected."
                    ];
                }

                // Security Constraint 2: Validate file extensions and MIME type
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    return [
                        'success' => true,
                        'message' => "Incident successfully reported (ID: #" . $incidentId . "), but file extension is not allowed and attachment was rejected."
                    ];
                }

                // Verify actual MIME type (requires finfo)
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    
                    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        return [
                            'success' => true,
                            'message' => "Incident successfully reported (ID: #" . $incidentId . "), but file content type verification failed and attachment was rejected."
                        ];
                    }
                }

                $uploadBaseDir = dirname(dirname(__DIR__)) . '/public/uploads/incidents/';
                if (!is_dir($uploadBaseDir)) {
                    mkdir($uploadBaseDir, 0755, true);
                }
                
                $newFileName = 'incident_' . $incidentId . '_' . time() . '.' . $fileExtension;
                $destPath = $uploadBaseDir . $newFileName;
                
                $dbFilePath = 'uploads/incidents/' . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $incidentModel->addPhoto($incidentId, $dbFilePath);
                }
            }

            return [
                'success' => true,
                'message' => "Incident successfully reported. ID: #" . $incidentId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves the driver's performance metrics and customer reviews list.
     * 
     * @return array Response array containing stats and reviews
     */
    public function viewPerformance() {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $performanceModel = new DriverPerformance();

            $stats = $performanceModel->getStatistics($driverId);
            $reviews = $performanceModel->getRatingSummary($driverId);

            return [
                'success' => true,
                'stats'   => $stats,
                'reviews' => $reviews
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves the detailed split of earnings and complete transaction list.
     * 
     * @return array Response array containing earnings stats and logs
     */
    public function viewEarningsDetail() {
        try {
            $driver = $this->getSecureDriver();
            $driverId = $driver['id'];

            $paymentModel = new DriverPayment();
            $summary = $paymentModel->getEarningsSummary($driverId);
            $split = $paymentModel->getEarningsSplit($driverId);
            $history = $paymentModel->getPaymentHistory($driverId);

            return [
                'success' => true,
                'summary' => $summary,
                'split'   => $split,
                'history' => $history
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Updates the driver's profile details.
     * 
     * @param array $data Input profile fields (phone, address, emergency_contact)
     * @return array Response array containing status
     */
    public function updateProfile($data) {
        try {
            $driver = $this->getSecureDriver();
            
            $phone = trim($data['phone'] ?? '');
            $address = trim($data['address'] ?? '');
            $emergencyContact = trim($data['emergency_contact'] ?? '');

            // Validate phone
            if (empty($phone)) {
                return [
                    'success' => false,
                    'error'   => "Phone number is required."
                ];
            }

            $driverModel = new Driver();
            $result = $driverModel->updateProfile($driver['id'], $driver['user_id'], [
                'phone'             => $phone,
                'address'           => $address !== '' ? $address : null,
                'emergency_contact' => $emergencyContact !== '' ? $emergencyContact : null
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Profile updated successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to update profile."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Deactivates the driver's profile (soft-delete).
     * Sets user status to 'inactive' and signs them out.
     * 
     * @return array Response array containing status
     */
    public function deactivateProfile() {
        try {
            $driver = $this->getSecureDriver();
            
            $driverModel = new Driver();
            $result = $driverModel->deactivate($driver['user_id']);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Profile deactivated successfully."
                ];
            }

            return [
                'success' => false,
                'error'   => "Failed to deactivate profile."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
