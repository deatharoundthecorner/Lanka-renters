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
     * Returns only the newest notifications needed by the shared header dropdown.
     */
    public function getRecentByUserId($userId, $limit = 5) {
        $limit = max(1, min(10, (int)$limit));
        $sql = "SELECT `id`, `title`, `message`, `notification_type`, `related_id`, `is_read`, `created_at`
                FROM `notifications`
                WHERE `user_id` = :user_id
                ORDER BY `created_at` DESC, `id` DESC
                LIMIT :notification_limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':notification_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
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
        $executed = $stmt->execute([
            'id'      => $notificationId,
            'user_id' => $userId
        ]);
        return $executed && $stmt->rowCount() > 0;
    }

    /**
     * Marks every unread notification owned by a user as read.
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE `notifications` SET `is_read` = TRUE
                WHERE `user_id` = :user_id AND `is_read` = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->rowCount();
    }



    /**
     * Creates a new notification for a user.
     * 
     * @param int $userId The users.id value
     * @param string $title The notification title
     * @param string $message The notification body text
     * @return bool True on success, false on failure
     */
    public function create($userId, $title, $message, $notificationType = 'system', $relatedId = null) {
        $allowedTypes = ['booking', 'payment', 'incident', 'review', 'chat', 'system'];
        if (!in_array($notificationType, $allowedTypes, true)) {
            $notificationType = 'system';
        }
        $sql = "INSERT INTO `notifications`
                    (`user_id`, `title`, `message`, `notification_type`, `related_id`, `is_read`)
                VALUES
                    (:user_id, :title, :message, :notification_type, :related_id, FALSE)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'notification_type' => $notificationType,
            'related_id' => $relatedId
        ]);
    }

    /**
     * Returns the count of unread notifications for a user.
     * 
     * @param int $userId The users.id value
     * @return int Unread count
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = :user_id AND `is_read` = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
