-- Migration: 007_settlements.sql
-- Create settlements table for Lanka Renters platform commission and owner payouts

CREATE TABLE IF NOT EXISTS `settlements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `gross_amount` DECIMAL(10, 2) NOT NULL,
  `commission_amount` DECIMAL(10, 2) NOT NULL,
  `net_amount` DECIMAL(10, 2) NOT NULL,
  `settlement_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_settlements_owner` (`owner_id`),
  INDEX `idx_settlements_booking` (`booking_id`),
  INDEX `idx_settlements_status` (`status`),
  CONSTRAINT `fk_settlements_owner` FOREIGN KEY (`owner_id`) REFERENCES `vehicle_owners` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_settlements_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
