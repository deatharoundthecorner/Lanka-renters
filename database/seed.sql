-- Lanka Renters Seed Data

-- Insert default users with password hashes (password is 'password123' for all)
-- Admin
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('System Admin', 'admin@example.com', '$2y$10$wT/YmU9Dk5tD9sV6X0s.Qe3.PZ.nL.G6ZqY3U1JtO1s2O3v4Q6V.G', '0771112222', 'admin', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Owner
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('City Rentals', 'owner@example.com', '$2y$10$wT/YmU9Dk5tD9sV6X0s.Qe3.PZ.nL.G6ZqY3U1JtO1s2O3v4Q6V.G', '0773334444', 'owner', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Driver
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('Kasun Silva', 'driver@example.com', '$2y$10$wT/YmU9Dk5tD9sV6X0s.Qe3.PZ.nL.G6ZqY3U1JtO1s2O3v4Q6V.G', '0775556666', 'driver', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Customer
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
('Amala Perera', 'customer@example.com', '$2y$10$wT/YmU9Dk5tD9sV6X0s.Qe3.PZ.nL.G6ZqY3U1JtO1s2O3v4Q6V.G', '0777778888', 'customer', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;
