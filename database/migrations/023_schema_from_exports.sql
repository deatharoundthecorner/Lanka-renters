-- Migration 023: Schema that existed only in the SQL exports
-- database/lanka_renters_demo.sql (identical structure in both source systems) contained a table,
-- columns and a foreign key that no migration created, so a migrations-only install was incomplete.
-- Definitions are copied unchanged from that export. Every statement is IF NOT EXISTS, so this is a
-- no-op on a database that was imported from the export.

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `payment_id` INT DEFAULT NULL,
  `rental_fee` DECIMAL(10,2) NOT NULL,
  `driver_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `additional_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `invoice_status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_invoice_booking` (`booking_id`),
  KEY `idx_invoice_customer` (`customer_id`),
  KEY `idx_invoice_status` (`invoice_status`),
  KEY `fk_invoice_payment` (`payment_id`),
  CONSTRAINT `fk_invoice_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `fk_invoice_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_invoice_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `district` VARCHAR(100) DEFAULT NULL AFTER `driving_license_number`,
  ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) DEFAULT NULL AFTER `district`;

ALTER TABLE `vehicles`
  ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(255) DEFAULT NULL AFTER `price_with_driver_per_day`;

ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `verified_by` INT DEFAULT NULL AFTER `transaction_reference`,
  ADD COLUMN IF NOT EXISTS `verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `verified_by`,
  ADD COLUMN IF NOT EXISTS `failure_reason` TEXT DEFAULT NULL AFTER `verified_at`,
  ADD CONSTRAINT `fk_payments_verified_by`
    FOREIGN KEY IF NOT EXISTS (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifications`
  ADD COLUMN IF NOT EXISTS `notification_type` ENUM('booking', 'payment', 'incident', 'review', 'chat', 'system') DEFAULT 'system' AFTER `created_at`,
  ADD COLUMN IF NOT EXISTS `related_id` INT DEFAULT NULL AFTER `notification_type`;

ALTER TABLE `chat_messages`
  ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `message_text`;
