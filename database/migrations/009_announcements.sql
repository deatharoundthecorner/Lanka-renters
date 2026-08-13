-- Migration: 009_announcements.sql
-- Create announcements table for system-wide platform broadcasts

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `announcement_type` ENUM('maintenance', 'system_update', 'important_notice', 'payment_notice', 'booking_notice', 'emergency', 'general') NOT NULL DEFAULT 'general',
  `target_audience` ENUM('all', 'customers', 'owners', 'drivers') NOT NULL DEFAULT 'all',
  `priority` ENUM('normal', 'important', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `message` TEXT NOT NULL,
  `publish_date` DATE DEFAULT NULL,
  `publish_time` TIME DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('draft', 'scheduled', 'published', 'expired') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_announcements_status` (`status`),
  INDEX `idx_announcements_target` (`target_audience`),
  CONSTRAINT `fk_announcements_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
