-- One customer-managed guarantor per Customer profile.
-- This migration is standalone and does not alter demo data or existing profiles.

CREATE TABLE IF NOT EXISTS `customer_guarantors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `nic` VARCHAR(12) NOT NULL,
  `phone` VARCHAR(10) NOT NULL,
  `relationship` VARCHAR(20) NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_customer_guarantors_customer` (`customer_id`),
  UNIQUE KEY `uq_customer_guarantors_nic` (`nic`),
  CONSTRAINT `fk_customer_guarantors_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
