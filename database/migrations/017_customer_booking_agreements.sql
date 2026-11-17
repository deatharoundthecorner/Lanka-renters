-- Immutable proof that a Customer accepted the booking terms before a booking
-- request was created. The one-to-one key prevents multiple agreement records
-- from being attached to the same booking.

CREATE TABLE IF NOT EXISTS `customer_booking_agreements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `agreement_version` VARCHAR(100) NOT NULL,
  `accepted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_customer_booking_agreements_booking` (`booking_id`),
  KEY `idx_customer_booking_agreements_customer` (`customer_id`),
  CONSTRAINT `fk_customer_booking_agreements_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_booking_agreements_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
