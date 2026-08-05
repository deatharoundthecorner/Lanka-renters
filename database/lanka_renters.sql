-- Lanka Renters Database Schema
-- Database: lanka_renters

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `pickup_tracking`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_participants`;
DROP TABLE IF EXISTS `chat_rooms`;
DROP TABLE IF EXISTS `ratings_reviews`;
DROP TABLE IF EXISTS `replacement_requests`;
DROP TABLE IF EXISTS `incident_photos`;
DROP TABLE IF EXISTS `incidents`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `vehicle_inspections`;
DROP TABLE IF EXISTS `vehicle_assignments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `vehicle_documents`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `driver_leaves`;
DROP TABLE IF EXISTS `driver_owner_links`;
DROP TABLE IF EXISTS `driver_documents`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `vehicle_owners`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Central Authentication)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `role` ENUM('customer', 'owner', 'driver', 'admin') NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Customers Table
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `nic_number` VARCHAR(20) UNIQUE,
  `driving_license_number` VARCHAR(30) UNIQUE,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vehicle Owners Table
CREATE TABLE `vehicle_owners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `owner_type` ENUM('individual', 'company') NOT NULL DEFAULT 'individual',
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `bank_account_no` VARCHAR(50) DEFAULT NULL,
  `bank_branch` VARCHAR(100) DEFAULT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_owners_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Drivers Table
CREATE TABLE `drivers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `availability_status` ENUM('available', 'busy', 'off_duty') NOT NULL DEFAULT 'off_duty',
  `rating_avg` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_drivers_availability` (`availability_status`),
  CONSTRAINT `fk_drivers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Driver Documents Table
CREATE TABLE `driver_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `document_type` ENUM('nic', 'driving_license', 'police_report') NOT NULL,
  `document_number` VARCHAR(50) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejected_reason` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_driver_docs_lookup` (`driver_id`, `document_type`),
  CONSTRAINT `fk_driver_docs_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Driver Owner Links Table
CREATE TABLE `driver_owner_links` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uq_driver_owner` (`driver_id`, `owner_id`),
  INDEX `idx_driver_owner_status` (`owner_id`, `status`),
  CONSTRAINT `fk_owner_links_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_owner_links_owner` FOREIGN KEY (`owner_id`) REFERENCES `vehicle_owners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Driver Leaves Table
CREATE TABLE `driver_leaves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_driver_leaves_status` (`driver_id`, `status`),
  CONSTRAINT `fk_leaves_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leaves_admin` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Vehicles Table
CREATE TABLE `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `make` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `year` INT NOT NULL,
  `license_plate` VARCHAR(20) NOT NULL UNIQUE,
  `vehicle_type` ENUM('car', 'van', 'suv', 'lorry', 'motorbike') NOT NULL,
  `transmission` ENUM('manual', 'automatic') NOT NULL,
  `fuel_type` ENUM('petrol', 'diesel', 'hybrid', 'electric') NOT NULL,
  `seating_capacity` INT NOT NULL,
  `price_per_day` DECIMAL(10, 2) NOT NULL,
  `price_with_driver_per_day` DECIMAL(10, 2) DEFAULT NULL,
  `status` ENUM('available', 'rented', 'maintenance', 'unavailable') NOT NULL DEFAULT 'unavailable',
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_vehicles_status` (`status`),
  INDEX `idx_vehicles_verification` (`verification_status`),
  CONSTRAINT `fk_vehicles_owner` FOREIGN KEY (`owner_id`) REFERENCES `vehicle_owners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Vehicle Documents Table
CREATE TABLE `vehicle_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `document_type` ENUM('registration', 'insurance', 'emission_test', 'fitness_certificate') NOT NULL,
  `document_number` VARCHAR(50) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejected_reason` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_vehicle_docs_lookup` (`vehicle_id`, `document_type`),
  CONSTRAINT `fk_vehicle_docs_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bookings Table
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `booking_type` ENUM('self_drive', 'with_driver') NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `delivery_address` VARCHAR(255) DEFAULT NULL,
  `total_price` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending_payment', 'confirmed', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_payment',
  `pickup_status` ENUM('pending_pickup', 'dispatched', 'arrived', 'picked_up', 'dropped_off') NOT NULL DEFAULT 'pending_pickup',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_bookings_customer` (`customer_id`),
  INDEX `idx_bookings_vehicle` (`vehicle_id`),
  INDEX `idx_bookings_driver` (`driver_id`),
  INDEX `idx_bookings_status` (`status`),
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Vehicle Assignments Table (Long-term / Shift assignments)
CREATE TABLE `vehicle_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  INDEX `idx_assignments_driver` (`driver_id`),
  INDEX `idx_assignments_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_assignments_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Vehicle Inspections Table
CREATE TABLE `vehicle_inspections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `booking_id` INT DEFAULT NULL,
  `inspected_by` INT NOT NULL,
  `inspection_type` ENUM('routine', 'pre_rental', 'post_rental') NOT NULL,
  `odometer_reading` INT NOT NULL,
  `exterior_condition` TEXT NOT NULL,
  `interior_condition` TEXT NOT NULL,
  `fuel_level` VARCHAR(20) NOT NULL,
  `status` ENUM('pass', 'fail', 'needs_maintenance') NOT NULL DEFAULT 'pass',
  `comments` TEXT DEFAULT NULL,
  `inspection_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_inspections_vehicle` (`vehicle_id`),
  INDEX `idx_inspections_booking` (`booking_id`),
  CONSTRAINT `fk_inspections_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inspections_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inspections_user` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payments Table
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` ENUM('card', 'bank_transfer', 'cash') NOT NULL,
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_slip_path` VARCHAR(255) DEFAULT NULL,
  `transaction_reference` VARCHAR(100) DEFAULT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_payments_booking` (`booking_id`),
  INDEX `idx_payments_status` (`payment_status`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Incidents Table
CREATE TABLE `incidents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `reported_by` INT NOT NULL,
  `description` TEXT NOT NULL,
  `incident_date` DATETIME NOT NULL,
  `severity` ENUM('minor', 'moderate', 'major') NOT NULL,
  `status` ENUM('reported', 'investigating', 'resolved') NOT NULL DEFAULT 'reported',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_incidents_booking` (`booking_id`),
  INDEX `idx_incidents_status` (`status`),
  CONSTRAINT `fk_incidents_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_incidents_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Incident Photos Table
CREATE TABLE `incident_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `incident_id` INT NOT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_photos_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Replacement Requests Table
CREATE TABLE `replacement_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `incident_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `requested_by` INT NOT NULL,
  `original_vehicle_id` INT NOT NULL,
  `replacement_vehicle_id` INT DEFAULT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'dispatched', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_replacements_incident` (`incident_id`),
  INDEX `idx_replacements_booking` (`booking_id`),
  INDEX `idx_replacements_status` (`status`),
  CONSTRAINT `fk_replacements_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_replacements_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_replacements_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_replacements_orig_veh` FOREIGN KEY (`original_vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_replacements_rep_veh` FOREIGN KEY (`replacement_vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Ratings & Reviews Table (Only Customers can review vehicles/drivers)
CREATE TABLE `ratings_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `vehicle_id` INT DEFAULT NULL,
  `driver_rating` TINYINT UNSIGNED DEFAULT NULL CHECK (`driver_rating` BETWEEN 1 AND 5),
  `vehicle_rating` TINYINT UNSIGNED DEFAULT NULL CHECK (`vehicle_rating` BETWEEN 1 AND 5),
  `review_text` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reviews_customer` (`customer_id`),
  INDEX `idx_reviews_driver` (`driver_id`),
  INDEX `idx_reviews_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Chat Rooms Table
CREATE TABLE `chat_rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_chat_rooms_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Chat Participants Table
CREATE TABLE `chat_participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_room_user` (`room_id`, `user_id`),
  CONSTRAINT `fk_participants_room` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Chat Messages Table
CREATE TABLE `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message_text` TEXT NOT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_messages_room` (`room_id`),
  CONSTRAINT `fk_messages_room` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Pickup Tracking Table
CREATE TABLE `pickup_tracking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `status` ENUM('pending_pickup', 'dispatched', 'arrived', 'picked_up', 'dropped_off') NOT NULL,
  `updated_by` INT NOT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pickup_booking` (`booking_id`, `status`),
  CONSTRAINT `fk_pickup_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pickup_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Driver Vehicle Safety Checks Table
CREATE TABLE IF NOT EXISTS `driver_vehicle_checks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `booking_id` INT NULL,
  `brakes` BOOLEAN NOT NULL DEFAULT FALSE,
  `lights` BOOLEAN NOT NULL DEFAULT FALSE,
  `tires` BOOLEAN NOT NULL DEFAULT FALSE,
  `fuel` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vehicle_checks_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicle_checks_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicle_checks_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Driver Payments Table
CREATE TABLE `driver_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_driver_payments_driver` (`driver_id`),
  INDEX `idx_driver_payments_booking` (`booking_id`),
  CONSTRAINT `fk_driver_payments_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_driver_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
