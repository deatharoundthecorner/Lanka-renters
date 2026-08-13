-- Lanka Renters Seed Data
-- All test accounts use password: password123
-- Hash was generated with: password_hash('password123', PASSWORD_DEFAULT)
-- Note: Regenerate hashes for production use.

-- Admin
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0771112222', 'admin', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Owner
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('City Rentals', 'owner@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0773334444', 'owner', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Driver
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('Kasun Silva', 'driver@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0775556666', 'driver', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Customer
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('Amala Perera', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0777778888', 'customer', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Role Profile Tables
-- Owner Profile
INSERT INTO `vehicle_owners` (`user_id`, `owner_type`, `verification_status`)
VALUES ((SELECT `id` FROM `users` WHERE `email` = 'owner@example.com'), 'individual', 'approved')
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;

-- Driver Profile
INSERT INTO `drivers` (`user_id`, `availability_status`, `rating_avg`)
VALUES ((SELECT `id` FROM `users` WHERE `email` = 'driver@example.com'), 'available', 5.00)
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;

-- Customer Profile
INSERT INTO `customers` (`user_id`, `verification_status`)
VALUES ((SELECT `id` FROM `users` WHERE `email` = 'customer@example.com'), 'approved')
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;
