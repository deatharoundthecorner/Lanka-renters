<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Chat.php';
require_once dirname(__DIR__) . '/models/Driver.php';

/**
 * Lanka Renters - Chat Controller
 * Orchestrates driver communications and secures room participant checks.
 */
class ChatController {
    private $chatModel;
    private $driverModel;

    public function __construct() {
        $this->chatModel = new Chat();
        $this->driverModel = new Driver();
    }

    /**
     * Helper to get secure driver and user ID.
     */
    private function getSessionContext() {
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Unauthorized. Please log in.");
        }
        $driver = $this->driverModel->findByUserId($user['id']);
        if (!$driver) {
            throw new Exception("Driver profile record not found.");
        }
        return [
            'user_id'   => $user['id'],
            'driver_id' => $driver['id']
        ];
    }

    /**
     * Retrieves all chat rooms assigned to the driver.
     * 
     * @return array Rooms list
     */
    public function getDriverRooms() {
        try {
            $context = $this->getSessionContext();
            $rooms = $this->chatModel->getDriverRooms($context['user_id']);
            $filteredRooms = [];
            foreach ($rooms as $room) {
                if ($this->isChatConnectionAllowed($room['room_id'])) {
                    $filteredRooms[] = $room;
                }
            }
            return [
                'success' => true,
                'rooms'   => $filteredRooms
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves messages for a specific room after verifying participant access.
     * 
     * @param int $roomId Room ID
     * @return array Response payload containing messages
     */
    /**
     * Checks if the chat is allowed based on the driver-owner connection status.
     */
    private function isChatConnectionAllowed($roomId) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT cp.user_id, u.role 
                FROM `chat_participants` cp
                JOIN `users` u ON cp.user_id = u.id
                WHERE cp.room_id = :room_id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['room_id' => $roomId]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $driverUserId = null;
        $ownerUserId = null;
        foreach ($participants as $p) {
            if ($p['role'] === 'driver') {
                $driverUserId = $p['user_id'];
            } elseif ($p['role'] === 'owner') {
                $ownerUserId = $p['user_id'];
            }
        }

        if ($driverUserId !== null && $ownerUserId !== null) {
            $sqlD = "SELECT id FROM `drivers` WHERE `user_id` = :uid LIMIT 1";
            $stmtD = $db->prepare($sqlD);
            $stmtD->execute(['uid' => $driverUserId]);
            $driverId = $stmtD->fetchColumn();

            $sqlO = "SELECT id FROM `vehicle_owners` WHERE `user_id` = :uid LIMIT 1";
            $stmtO = $db->prepare($sqlO);
            $stmtO->execute(['uid' => $ownerUserId]);
            $ownerId = $stmtO->fetchColumn();

            if ($driverId && $ownerId) {
                $sqlLink = "SELECT `status` FROM `driver_owner_links` 
                            WHERE `driver_id` = :driver_id AND `owner_id` = :owner_id LIMIT 1";
                $stmtLink = $db->prepare($sqlLink);
                $stmtLink->execute([
                    'driver_id' => $driverId,
                    'owner_id'  => $ownerId
                ]);
                $status = $stmtLink->fetchColumn();

                if ($status !== 'accepted') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Retrieves messages for a specific room after verifying participant access.
     * 
     * @param int $roomId Room ID
     * @return array Response payload containing messages
     */
    public function getRoomMessages($roomId) {
        try {
            $context = $this->getSessionContext();
            if (!$this->chatModel->isParticipant($roomId, $context['user_id'])) {
                return [
                    'success' => false,
                    'error'   => "Access denied. You are not a participant in this chat room."
                ];
            }

            if (!$this->isChatConnectionAllowed($roomId)) {
                return [
                    'success' => false,
                    'error'   => "Chat blocked. Driver <-> Owner chat is only allowed when connection is accepted."
                ];
            }

            $messages = $this->chatModel->getMessages($roomId);
            return [
                'success'  => true,
                'messages' => $messages
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Sends a chat message in a specific room.
     * 
     * @param int $roomId Room ID
     * @param string $messageText Message text
     * @return array Success status
     */
    public function sendChatMessage($roomId, $messageText) {
        try {
            $messageText = trim($messageText);
            if (empty($messageText)) {
                return [
                    'success' => false,
                    'error'   => "Message text cannot be empty."
                ];
            }

            $context = $this->getSessionContext();
            if (!$this->chatModel->isParticipant($roomId, $context['user_id'])) {
                return [
                    'success' => false,
                    'error'   => "Access denied. You are not a participant in this chat room."
                ];
            }

            if (!$this->isChatConnectionAllowed($roomId)) {
                return [
                    'success' => false,
                    'error'   => "Chat blocked. Driver <-> Owner chat is only allowed when connection is accepted."
                ];
            }

            $success = $this->chatModel->sendMessage($roomId, $context['user_id'], $messageText);
            return [
                'success' => $success,
                'error'   => $success ? '' : "Failed to send message."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new chat room linked to an assigned booking and adds participants.
     * 
     * @param int $bookingId Booking ID
     * @return array Room ID and success status
     */
    public function createBookingRoom($bookingId) {
        try {
            $context = $this->getSessionContext();
            
            // Check if the booking is indeed assigned to the driver
            $sqlBooking = "SELECT b.id, b.customer_id, c.user_id as customer_user_id 
                           FROM `bookings` b 
                           JOIN `customers` c ON b.customer_id = c.id 
                           WHERE b.id = :booking_id AND b.driver_id = :driver_id LIMIT 1";
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sqlBooking);
            $stmt->execute([
                'booking_id' => $bookingId,
                'driver_id'  => $context['driver_id']
            ]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return [
                    'success' => false,
                    'error'   => "Booking not found or not assigned to you."
                ];
            }

            // Create or fetch the room
            $roomId = $this->chatModel->createRoom($bookingId);

            // Add the driver as participant
            $this->chatModel->addParticipant($roomId, $context['user_id']);

            // Add the customer as participant
            $this->chatModel->addParticipant($roomId, $booking['customer_user_id']);

            return [
                'success' => true,
                'room_id' => $roomId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
