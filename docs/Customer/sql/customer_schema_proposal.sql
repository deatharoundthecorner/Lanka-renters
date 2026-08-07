-- Lanka Renters Customer schema proposal
-- REVIEW ONLY - requires database-coordinator approval before execution.
-- Target: a disposable/test copy of the current lanka_renters schema.
-- This proposal is additive and does not create duplicate core entity tables.

-- ---------------------------------------------------------------------------
-- REQUIRED 1: public vehicle location data for catalogue filtering.
-- Owner remains responsible for maintaining these fields.
-- ---------------------------------------------------------------------------
ALTER TABLE `vehicles`
  ADD COLUMN `district` VARCHAR(50) NULL AFTER `owner_id`,
  ADD COLUMN `pickup_location` VARCHAR(150) NULL AFTER `district`;

-- ---------------------------------------------------------------------------
-- REQUIRED 2: public catalogue images.
-- vehicle_documents stores private compliance files and must not be reused.
-- ---------------------------------------------------------------------------
CREATE TABLE `vehicle_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(150) DEFAULT NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT FALSE,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_vehicle_image_path` (`vehicle_id`, `image_path`),
  INDEX `idx_vehicle_images_primary` (`vehicle_id`, `is_primary`, `sort_order`),
  CONSTRAINT `fk_vehicle_images_vehicle`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- REQUIRED 3: owner decision states and auditable Customer cancellation.
-- Existing values are retained for compatibility.
-- ---------------------------------------------------------------------------
ALTER TABLE `bookings`
  MODIFY COLUMN `status`
    ENUM(
      'pending_approval',
      'pending_payment',
      'confirmed',
      'ongoing',
      'completed',
      'cancelled',
      'rejected'
    ) NOT NULL DEFAULT 'pending_approval',
  ADD COLUMN `cancelled_at` DATETIME NULL AFTER `pickup_status`,
  ADD COLUMN `cancellation_reason` VARCHAR(255) NULL AFTER `cancelled_at`,
  ADD COLUMN `cancelled_by` INT NULL AFTER `cancellation_reason`,
  ADD INDEX `idx_bookings_cancelled_by` (`cancelled_by`),
  ADD CONSTRAINT `fk_bookings_cancelled_by`
    FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ---------------------------------------------------------------------------
-- RECOMMENDED 4: query indexes for catalogue, ownership and overlap checks.
-- These indexes improve lookups but application transactions still enforce
-- booking non-overlap.
-- ---------------------------------------------------------------------------
ALTER TABLE `vehicles`
  ADD INDEX `idx_vehicles_catalogue`
    (`verification_status`, `status`, `district`, `vehicle_type`);

ALTER TABLE `bookings`
  ADD INDEX `idx_bookings_customer_status_date`
    (`customer_id`, `status`, `start_date`),
  ADD INDEX `idx_bookings_vehicle_period`
    (`vehicle_id`, `status`, `start_date`, `end_date`),
  ADD INDEX `idx_bookings_driver_period`
    (`driver_id`, `status`, `start_date`, `end_date`);

-- ---------------------------------------------------------------------------
-- OPTIONAL 5: one review per Customer booking.
-- Coordinator must verify that no duplicates exist before adding this key.
-- ---------------------------------------------------------------------------
ALTER TABLE `ratings_reviews`
  ADD UNIQUE KEY `uq_reviews_booking_customer` (`booking_id`, `customer_id`);
