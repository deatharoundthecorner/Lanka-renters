<?php
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/Chat.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';

/**
 * OwnerChatController
 * Orchestrates Owner chat messaging and room authorization.
 */
class OwnerChatController {
    private $chatModel;
    private $ownerModel;

    public function __construct() {
        $this->chatModel = new Chat();
        $this->ownerModel = new VehicleOwner();
    }

    private function getSessionContext() {
        $user = AuthHelper::getCurrentUser();
        if (!$user || $user['role'] !== 'owner') {
            throw new Exception("Unauthorized. Owner login required.");
        }
        $owner = $this->ownerModel->findByUserId($user['id']);
        if (!$owner) {
            throw new Exception("Owner profile record not found.");
        }
        return [
            'user_id'  => (int)$user['id'],
            'owner_id' => (int)$owner['id'],
            'name'     => $user['name']
        ];
    }

    public function getChatData($activeRoomId = null) {
        try {
            $context = $this->getSessionContext();

            // Auto-enroll owner into chat rooms for their vehicle bookings
            $this->chatModel->ensureOwnerRoomsEnrolled($context['owner_id'], $context['user_id']);

            $rooms = $this->chatModel->getUserRooms($context['user_id']);

            $activeRoom = null;
            $messages = [];

            if (!empty($rooms)) {
                $targetRoomId = $activeRoomId ? (int)$activeRoomId : (int)$rooms[0]['room_id'];
                
                // Verify owner is a participant in target room
                if ($this->chatModel->isParticipant($targetRoomId, $context['user_id'])) {
                    foreach ($rooms as $r) {
                        if ((int)$r['room_id'] === $targetRoomId) {
                            $activeRoom = $r;
                            break;
                        }
                    }
                    if ($activeRoom) {
                        $messages = $this->chatModel->getMessages($targetRoomId);
                        $this->chatModel->markMessagesAsRead($targetRoomId, $context['user_id']);
                    }
                }
            }

            return [
                'success'    => true,
                'rooms'      => $rooms,
                'activeRoom' => $activeRoom,
                'messages'   => $messages,
                'userId'     => $context['user_id']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    public function sendMessage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'error' => 'Invalid security token. Please refresh and try again.'];
        }

        try {
            $context = $this->getSessionContext();
            $roomId = (int)($_POST['room_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');

            if ($roomId <= 0 || empty($messageText)) {
                return ['success' => false, 'error' => 'Message text cannot be empty.'];
            }

            if (!$this->chatModel->isParticipant($roomId, $context['user_id'])) {
                return ['success' => false, 'error' => 'Access denied. You are not a participant in this chat.'];
            }

            $sent = $this->chatModel->sendMessage($roomId, $context['user_id'], $messageText);
            return [
                'success' => $sent,
                'error'   => $sent ? '' : 'Failed to send message.'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
