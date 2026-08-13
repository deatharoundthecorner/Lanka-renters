<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Chat/Messaging Model
 * Manages chat rooms, participants, and messaging records.
 */
class Chat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Checks if a chat room already exists for a specific booking.
     * If yes, returns it. If no, creates it and returns the new ID.
     * 
     * @param int $bookingId Booking ID
     * @return int Room ID
     */
    public function createRoom($bookingId) {
        // Check if room exists
        $sqlCheck = "SELECT `id` FROM `chat_rooms` WHERE `booking_id` = :booking_id LIMIT 1";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute(['booking_id' => $bookingId]);
        $roomId = $stmt->fetchColumn();

        if ($roomId) {
            return (int)$roomId;
        }

        // Create new room
        $sqlInsert = "INSERT INTO `chat_rooms` (`booking_id`) VALUES (:booking_id)";
        $stmt = $this->db->prepare($sqlInsert);
        $stmt->execute(['booking_id' => $bookingId]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Adds a user participant to a chat room if they are not already enrolled.
     * 
     * @param int $roomId Room ID
     * @param int $userId User ID (users.id)
     * @return bool True on success
     */
    public function addParticipant($roomId, $userId) {
        // Check if participant already added
        $sqlCheck = "SELECT COUNT(*) FROM `chat_participants` WHERE `room_id` = :room_id AND `user_id` = :user_id";
        $stmt = $this->db->prepare($sqlCheck);
        $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $userId
        ]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // Add participant
        $sqlInsert = "INSERT INTO `chat_participants` (`room_id`, `user_id`) VALUES (:room_id, :user_id)";
        $stmt = $this->db->prepare($sqlInsert);
        return $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $userId
        ]);
    }

    /**
     * Sends a chat message.
     * 
     * @param int $roomId Room ID
     * @param int $senderId Sender's User ID (users.id)
     * @param string $messageText Message text
     * @return bool True on success
     */
    public function sendMessage($roomId, $senderId, $messageText) {
        $sql = "INSERT INTO `chat_messages` (`room_id`, `sender_id`, `message_text`) VALUES (:room_id, :sender_id, :message_text)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'room_id'      => $roomId,
            'sender_id'    => $senderId,
            'message_text' => $messageText
        ]);
    }

    /**
     * Retrieves all chat transcript messages for a specific room.
     * 
     * @param int $roomId Room ID
     * @return array Array of message records
     */
    public function getMessages($roomId) {
        $sql = "SELECT cm.*, u.name as sender_name, u.role as sender_role 
                FROM `chat_messages` cm
                JOIN `users` u ON cm.sender_id = u.id
                WHERE cm.`room_id` = :room_id
                ORDER BY cm.`sent_at` ASC, cm.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Checks if a user is a participant in a chat room.
     * 
     * @param int $roomId Room ID
     * @param int $userId User ID (users.id)
     * @return bool True if participant, false otherwise
     */
    public function isParticipant($roomId, $userId) {
        $sql = "SELECT COUNT(*) FROM `chat_participants` WHERE `room_id` = :room_id AND `user_id` = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'room_id' => $roomId,
            'user_id' => $userId
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Retrieves all chat rooms that a user participates in.
     * 
     * @param int $userId User ID (users.id)
     * @return array Array of room details
     */
    public function getDriverRooms($userId) {
        return $this->getUserRooms($userId);
    }

    /**
     * Retrieves all chat rooms for any user (Owner, Driver, Customer, Admin).
     *
     * @param int $userId User ID (users.id)
     * @return array
     */
    public function getUserRooms($userId) {
        $sql = "SELECT cr.id as room_id, cr.booking_id,
                       b.vehicle_id, v.make, v.model, v.license_plate,
                       (SELECT u.name FROM chat_participants cp2 
                        JOIN users u ON cp2.user_id = u.id 
                        WHERE cp2.room_id = cr.id AND cp2.user_id != :user_id1 LIMIT 1) as other_participant_name,
                       (SELECT u.role FROM chat_participants cp2 
                        JOIN users u ON cp2.user_id = u.id 
                        WHERE cp2.room_id = cr.id AND cp2.user_id != :user_id2 LIMIT 1) as other_participant_role,
                       cm.message_text as last_message, cm.sent_at as last_message_time,
                        (SELECT COUNT(*) FROM chat_messages cm_unread
                         WHERE cm_unread.room_id = cr.id AND cm_unread.sender_id != :user_id3 AND cm_unread.is_read = 0) as unread_count
                FROM chat_rooms cr
                JOIN chat_participants cp ON cr.id = cp.room_id
                LEFT JOIN bookings b ON cr.booking_id = b.id
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN chat_messages cm ON cm.id = (
                    SELECT id FROM chat_messages
                    WHERE room_id = cr.id
                    ORDER BY sent_at DESC, id DESC LIMIT 1
                )
                WHERE cp.user_id = :user_id4
                ORDER BY COALESCE(cm.sent_at, cr.created_at) DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id1' => (int)$userId,
            'user_id2' => (int)$userId,
            'user_id3' => (int)$userId,
            'user_id4' => (int)$userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marks all unread messages in a room as read for a given user.
     *
     * @param int $roomId
     * @param int $userId
     * @return bool
     */
    public function markMessagesAsRead($roomId, $userId) {
        $sql = "UPDATE chat_messages SET is_read = 1 WHERE room_id = :room_id AND sender_id != :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'room_id' => (int)$roomId,
            'user_id' => (int)$userId
        ]);
    }

    /**
     * Ensures an Owner is enrolled as a participant in all chat rooms associated with bookings of their vehicles.
     *
     * @param int $ownerId
     * @param int $ownerUserId
     * @return void
     */
    public function ensureOwnerRoomsEnrolled($ownerId, $ownerUserId) {
        $sql = "SELECT b.id as booking_id
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE v.owner_id = :owner_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['owner_id' => (int)$ownerId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($bookings as $bId) {
            $roomId = $this->createRoom((int)$bId);
            if ($roomId > 0) {
                $this->addParticipant($roomId, (int)$ownerUserId);
            }
        }
    }
}
