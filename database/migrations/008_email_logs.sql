-- Migration: 008_email_logs.sql
-- Create email_logs table for system notifications audit trail

CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient_type` ENUM('customer', 'driver', 'owner', 'all') NOT NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `recipient_email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `booking_id` INT DEFAULT NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  `failure_reason` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email_logs_status` (`status`),
  INDEX `idx_email_logs_recipient` (`recipient_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
