<?php
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Notification Model
 * Manages notification tracking, querying, and read states.
 */
class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves all notifications for a specific user ID.
     * 
     * @param int $userId The users.id value
     * @return array Array of notification associative arrays
     */
    public function getByUserId($userId) {
        $sql = "SELECT * FROM `notifications` WHERE `user_id` = :user_id ORDER BY `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marks a specific notification as read.
     * 
     * @param int $notificationId The notification record ID
     * @param int $userId Security check to ensure owner is marking it read
     * @return bool True on success, false on failure
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE `notifications` SET `is_read` = TRUE WHERE `id` = :id AND `user_id` = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'      => $notificationId,
            'user_id' => $userId
        ]);
    }
}
