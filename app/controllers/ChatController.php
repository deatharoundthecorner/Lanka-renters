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
            return [
                'success' => true,
                'rooms'   => $rooms
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
    public function getRoomMessages($roomId) {
        try {
            $context = $this->getSessionContext();
            if (!$this->chatModel->isParticipant($roomId, $context['user_id'])) {
                return [
                    'success' => false,
                    'error'   => "Access denied. You are not a participant in this chat room."
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

    /**
     * Retrieves the other participant's details in a chat room.
     * 
     * @param int $roomId Room ID
     * @return array Participant details or error
     */
    public function getOtherParticipant($roomId) {
        try {
            $context = $this->getSessionContext();
            if (!$this->chatModel->isParticipant($roomId, $context['user_id'])) {
                return [
                    'success' => false,
                    'error'   => "Access denied."
                ];
            }

            $db = Database::getInstance()->getConnection();
            $sql = "SELECT u.name, u.role, u.id as user_id 
                    FROM `chat_participants` cp 
                    JOIN `users` u ON cp.user_id = u.id 
                    WHERE cp.room_id = :room_id AND cp.user_id != :user_id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'room_id' => $roomId,
                'user_id' => $context['user_id']
            ]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($participant) {
                return [
                    'success'     => true,
                    'participant' => $participant
                ];
            }

            return [
                'success' => false,
                'error'   => "No other participant found."
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
