-- Lanka Renters Migration 005: Create Admin Reviews Table

CREATE TABLE IF NOT EXISTS `admin_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `request_type` ENUM('profile_change', 'account_change', 'document_replacement', 'police_report') NOT NULL,
  `request_id` INT NOT NULL,
  `action` ENUM('approved', 'rejected') NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_admin_reviews_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
