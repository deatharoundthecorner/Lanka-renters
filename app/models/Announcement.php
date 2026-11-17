<?php

require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Announcement Model
 * CRUD for system broadcast notices.
 */
class Announcement {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Gets all announcements.
     */
    public function getAll(): array {
        $sql = "SELECT a.*, u.name as creator_name
                FROM `announcements` a
                LEFT JOIN `users` u ON a.created_by = u.id
                ORDER BY a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new announcement.
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO `announcements` (`title`, `announcement_type`, `target_audience`, `priority`, `message`, `publish_date`, `publish_time`, `expiry_date`, `status`, `created_by`)
                VALUES (:title, :type, :target, :priority, :msg, :pdate, :ptime, :edate, :status, :creator)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'title' => $data['title'],
            'type' => $data['announcement_type'] ?? 'general',
            'target' => $data['target_audience'] ?? 'all',
            'priority' => $data['priority'] ?? 'normal',
            'msg' => $data['message'],
            'pdate' => !empty($data['publish_date']) ? $data['publish_date'] : date('Y-m-d'),
            'ptime' => !empty($data['publish_time']) ? $data['publish_time'] : date('H:i:s'),
            'edate' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'status' => $data['status'] ?? 'published',
            'creator' => $data['created_by'] ?? null
        ]);
    }

    /**
     * Deletes an announcement.
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `announcements` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
