-- Lanka Renters - MySQL Database Schema & Seed Data
CREATE DATABASE IF NOT EXISTS `lanka_renters` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lanka_renters`;

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'Super Admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Default Admin: admin@lankarenters.lk / admin123
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@lankarenters.lk', '$2y$10$8K1p/a0dL1LXMIg.25Y70O3Z/Uo3S.8XvR9k1B.zN1W.eW8/4cT3i', 'Super Admin')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Users (Customers) Table
CREATE TABLE IF NOT EXISTS `users` (
    `customer_id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `nic_doc` VARCHAR(255) DEFAULT 'nic_sample.pdf',
    `license_doc` VARCHAR(255) DEFAULT 'license_sample.pdf',
    `status` ENUM('Pending', 'Approved', 'Rejected', 'Suspended') DEFAULT 'Pending',
    `registered_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Users Data
INSERT INTO `users` (`customer_id`, `name`, `email`, `phone`, `district`, `status`, `registered_date`) VALUES
('CUS-001', 'Kasun Perera', 'kasun.perera@gmail.com', '071 234 5678', 'Colombo', 'Pending', '2026-08-01'),
('CUS-002', 'Nimal Silva', 'nimal.silva@yahoo.com', '077 456 7890', 'Kandy', 'Pending', '2026-08-02'),
('CUS-003', 'Tharindu Fernando', 'tharindu.f@hotmail.com', '076 321 4567', 'Galle', 'Pending', '2026-08-03'),
('CUS-004', 'Chamara Jayasinghe', 'chamara.j@gmail.com', '075 654 3210', 'Gampaha', 'Approved', '2026-07-15'),
('CUS-005', 'Sanduni Perera', 'sanduni.p@gmail.com', '071 987 6543', 'Kurunegala', 'Approved', '2026-07-18'),
('CUS-006', 'Dilshan Kumara', 'dilshan.k@outlook.com', '077 123 9876', 'Jaffna', 'Approved', '2026-07-20'),
('CUS-007', 'Kamal Fernando', 'kamal.f@gmail.com', '076 888 7777', 'Matara', 'Approved', '2026-07-22'),
('CUS-008', 'Sahan Perera', 'sahan.perera@gmail.com', '071 444 3333', 'Badulla', 'Approved', '2026-07-25')
ON DUPLICATE KEY UPDATE `customer_id`=`customer_id`;

-- Vehicle Owners Table
CREATE TABLE IF NOT EXISTS `vehicle_owners` (
    `owner_id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `vehicle_type` VARCHAR(50) NOT NULL,
    `model_name` VARCHAR(100) NOT NULL,
    `nic_doc` VARCHAR(255) DEFAULT 'nic_owner.pdf',
    `vehicle_doc` VARCHAR(255) DEFAULT 'vehicle_reg.pdf',
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Owners Data
INSERT INTO `vehicle_owners` (`owner_id`, `name`, `phone`, `district`, `vehicle_type`, `model_name`, `status`) VALUES
('OWN-001', 'Sunil Wickramasinghe', '071 555 1234', 'Colombo', 'Car (4 Seater)', 'Toyota Prius 2021', 'Pending'),
('OWN-002', 'Dhanushka Ratnayake', '077 333 4444', 'Gampaha', 'SUV (6 Seater)', 'Honda Vezel 2020', 'Pending'),
('OWN-003', 'Rohan Jayawardena', '076 222 1111', 'Kandy', 'Van (8 Seater)', 'Toyota KDH 2019', 'Approved'),
('OWN-004', 'Mahesh Samarawickrama', '075 999 8888', 'Galle', 'Minivan (6 Seater)', 'Suzuki Wagon R 2022', 'Approved'),
('OWN-005', 'Nimal Perera', '071 777 6666', 'Kalutara', 'Car (4 Seater)', 'Toyota Aqua 2018', 'Approved')
ON DUPLICATE KEY UPDATE `owner_id`=`owner_id`;

-- Drivers Table
CREATE TABLE IF NOT EXISTS `drivers` (
    `driver_id` VARCHAR(20) PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `contact` VARCHAR(20) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `owner_name` VARCHAR(100) NOT NULL,
    `nic_doc` VARCHAR(255) DEFAULT 'driver_nic.pdf',
    `vehicle_doc` VARCHAR(255) DEFAULT 'driver_license.pdf',
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Drivers Data
INSERT INTO `drivers` (`driver_id`, `name`, `contact`, `district`, `owner_name`, `status`) VALUES
('DRV-001', 'Kamal Silva', '077 888 1234', 'Colombo', 'Sunil Wickramasinghe', 'Pending'),
('DRV-002', 'Nuwan Bandara', '071 999 4321', 'Kandy', 'Rohan Jayawardena', 'Pending'),
('DRV-003', 'Roshan Ranasinghe', '076 555 4433', 'Gampaha', 'Dhanushka Ratnayake', 'Approved'),
('DRV-004', 'Asanka Gunawardena', '075 111 2233', 'Galle', 'Mahesh Samarawickrama', 'Approved'),
('DRV-005', 'Priyantha Cooray', '077 444 5566', 'Kalutara', 'Nimal Perera', 'Approved')
ON DUPLICATE KEY UPDATE `driver_id`=`driver_id`;

-- Vehicles Table
CREATE TABLE IF NOT EXISTS `vehicles` (
    `vehicle_id` VARCHAR(20) PRIMARY KEY,
    `vehicle_model` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `owner_name` VARCHAR(100) NOT NULL,
    `vehicle_doc` VARCHAR(255) DEFAULT 'vehicle_book.pdf',
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Vehicles Data
INSERT INTO `vehicles` (`vehicle_id`, `vehicle_model`, `type`, `district`, `owner_name`, `status`) VALUES
('VEH-001', 'Toyota Prius 2021', 'Car (4 Seater)', 'Colombo', 'Sunil Wickramasinghe', 'Pending'),
('VEH-002', 'Honda Vezel 2020', 'SUV (6 Seater)', 'Gampaha', 'Dhanushka Ratnayake', 'Pending'),
('VEH-003', 'Toyota KDH Super GL', 'Van (8 Seater)', 'Kandy', 'Rohan Jayawardena', 'Approved'),
('VEH-004', 'Suzuki Wagon R FX', 'Minivan (6 Seater)', 'Galle', 'Mahesh Samarawickrama', 'Approved'),
('VEH-005', 'Toyota Aqua S Grade', 'Car (4 Seater)', 'Kalutara', 'Nimal Perera', 'Approved')
ON DUPLICATE KEY UPDATE `vehicle_id`=`vehicle_id`;

-- Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `booking_id` VARCHAR(20) PRIMARY KEY,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_id` VARCHAR(20) NOT NULL,
    `vehicle_name` VARCHAR(100) NOT NULL,
    `vehicle_id` VARCHAR(20) NOT NULL,
    `driver_name` VARCHAR(100) NOT NULL,
    `driver_id` VARCHAR(20) NOT NULL,
    `payment_amount` DECIMAL(10,2) NOT NULL,
    `payment_id` VARCHAR(20) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `vehicle_type` VARCHAR(50) NOT NULL,
    `booking_date` DATE NOT NULL,
    `status` ENUM('Pending', 'Active', 'Completed', 'Cancelled') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Bookings Data
INSERT INTO `bookings` (`booking_id`, `customer_name`, `customer_id`, `vehicle_name`, `vehicle_id`, `driver_name`, `driver_id`, `payment_amount`, `payment_id`, `district`, `vehicle_type`, `booking_date`, `status`) VALUES
('BKG-101', 'John Perera', 'CUS-001', 'Toyota Prius', 'VEH-023', 'Kamal Silva', 'DRV-012', 45000.00, 'PAY-104', 'Colombo', 'Car (4 Seater)', '2026-08-08', 'Pending'),
('BKG-102', 'Chamara Jayasinghe', 'CUS-004', 'Honda Vezel', 'VEH-002', 'Roshan Ranasinghe', 'DRV-003', 75000.00, 'PAY-105', 'Gampaha', 'SUV (6 Seater)', '2026-08-07', 'Active'),
('BKG-103', 'Sanduni Perera', 'CUS-005', 'Toyota KDH Super GL', 'VEH-003', 'Nuwan Bandara', 'DRV-002', 120000.00, 'PAY-106', 'Kandy', 'Van (8 Seater)', '2026-08-05', 'Completed'),
('BKG-104', 'Dilshan Kumara', 'CUS-006', 'Suzuki Wagon R', 'VEH-004', 'Asanka Gunawardena', 'DRV-004', 35000.00, 'PAY-107', 'Jaffna', 'Minivan (6 Seater)', '2026-08-02', 'Completed')
ON DUPLICATE KEY UPDATE `booking_id`=`booking_id`;

-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `payment_id` VARCHAR(20) PRIMARY KEY,
    `booking_id` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_type` ENUM('Full Payment', 'Rental Payment') NOT NULL,
    `payment_doc` VARCHAR(255) DEFAULT 'bank_slip.pdf',
    `payment_date` DATE NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Payments Data
INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_type`, `payment_date`, `status`) VALUES
('PAY-104', 'BKG-101', 45000.00, 'Full Payment', '2026-08-08', 'Pending'),
('PAY-105', 'BKG-102', 75000.00, 'Rental Payment', '2026-08-07', 'Approved'),
('PAY-106', 'BKG-103', 120000.00, 'Full Payment', '2026-08-05', 'Approved'),
('PAY-107', 'BKG-104', 35000.00, 'Rental Payment', '2026-08-02', 'Approved')
ON DUPLICATE KEY UPDATE `payment_id`=`payment_id`;

-- Incidents Table
CREATE TABLE IF NOT EXISTS `incidents` (
    `incident_id` VARCHAR(20) PRIMARY KEY,
    `incident_type` ENUM('Accident', 'Vehicle Damage', 'Driver Issue', 'Customer Issue', 'Late Return', 'Other') NOT NULL,
    `customer_id` VARCHAR(20) NOT NULL,
    `vehicle_name` VARCHAR(100) NOT NULL,
    `booking_id` VARCHAR(20) NOT NULL,
    `owner_name` VARCHAR(100) NOT NULL,
    `reported_time` VARCHAR(50) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `evidence_doc` VARCHAR(255) DEFAULT 'incident_photo.jpg',
    `status` ENUM('Pending', 'Active', 'Resolved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Incidents Data
INSERT INTO `incidents` (`incident_id`, `incident_type`, `customer_id`, `vehicle_name`, `booking_id`, `owner_name`, `reported_time`, `district`, `status`) VALUES
('INC-001', 'Accident', 'CUS-102', 'Toyota Prius', 'BKG-203', 'Nimal Perera', '08 Aug 2026, 10:30 AM', 'Colombo', 'Pending'),
('INC-002', 'Vehicle Damage', 'CUS-005', 'Honda Vezel', 'BKG-102', 'Dhanushka Ratnayake', '07 Aug 2026, 04:15 PM', 'Gampaha', 'Pending'),
('INC-003', 'Driver Issue', 'CUS-006', 'Toyota KDH', 'BKG-103', 'Rohan Jayawardena', '06 Aug 2026, 08:45 AM', 'Kandy', 'Active')
ON DUPLICATE KEY UPDATE `incident_id`=`incident_id`;

-- Replacement Driver Requests Table
CREATE TABLE IF NOT EXISTS `replacement_requests` (
    `request_id` VARCHAR(20) PRIMARY KEY,
    `booking_id` VARCHAR(20) NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_id` VARCHAR(20) NOT NULL,
    `current_driver_name` VARCHAR(100) NOT NULL,
    `current_driver_id` VARCHAR(20) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Replacement Requests Data
INSERT INTO `replacement_requests` (`request_id`, `booking_id`, `customer_name`, `customer_id`, `current_driver_name`, `current_driver_id`, `reason`, `status`) VALUES
('REP-001', 'BKG-101', 'John Perera', 'CUS-001', 'Kamal Silva', 'DRV-012', 'Medical Emergency of Driver', 'Pending'),
('REP-002', 'BKG-102', 'Chamara Jayasinghe', 'CUS-004', 'Roshan Ranasinghe', 'DRV-003', 'Vehicle Owner Requested Driver Change', 'Approved')
ON DUPLICATE KEY UPDATE `request_id`=`request_id`;

-- Settlements Table
CREATE TABLE IF NOT EXISTS `settlements` (
    `settlement_id` VARCHAR(20) PRIMARY KEY,
    `owner_name` VARCHAR(100) NOT NULL,
    `booking_id` VARCHAR(20) NOT NULL,
    `gross_amount` DECIMAL(10,2) NOT NULL,
    `commission_amount` DECIMAL(10,2) NOT NULL,
    `net_amount` DECIMAL(10,2) NOT NULL,
    `settlement_date` DATE NOT NULL,
    `status` ENUM('Completed', 'Pending') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Settlements Data
INSERT INTO `settlements` (`settlement_id`, `owner_name`, `booking_id`, `gross_amount`, `commission_amount`, `net_amount`, `settlement_date`, `status`) VALUES
('SET-001', 'Sunil Wickramasinghe', 'BKG-101', 50000.00, 5000.00, 45000.00, '2026-08-08', 'Pending'),
('SET-002', 'Rohan Jayawardena', 'BKG-103', 120000.00, 12000.00, 108000.00, '2026-08-06', 'Completed'),
('SET-003', 'Mahesh Samarawickrama', 'BKG-104', 35000.00, 3500.00, 31500.00, '2026-08-03', 'Completed')
ON DUPLICATE KEY UPDATE `settlement_id`=`settlement_id`;

-- Email Logs Table
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email_id` VARCHAR(20) NOT NULL,
    `recipient_type` ENUM('Customer', 'Driver', 'Owner') NOT NULL,
    `recipient_name` VARCHAR(100) NOT NULL,
    `recipient_id` VARCHAR(20) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `booking_id` VARCHAR(20) DEFAULT 'N/A',
    `status` ENUM('Sent', 'Failed') NOT NULL,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Email Logs Data
INSERT INTO `email_logs` (`email_id`, `recipient_type`, `recipient_name`, `recipient_id`, `subject`, `booking_id`, `status`) VALUES
('EML-101', 'Customer', 'John Perera', 'CUS-001', 'Booking Confirmation - BKG-101', 'BKG-101', 'Sent'),
('EML-102', 'Owner', 'Sunil Wickramasinghe', 'OWN-001', 'New Rental Request Assigned', 'BKG-101', 'Sent'),
('EML-103', 'Driver', 'Kamal Silva', 'DRV-012', 'Trip Schedule Update - BKG-101', 'BKG-101', 'Failed'),
('EML-104', 'Customer', 'Chamara Jayasinghe', 'CUS-004', 'Payment Received Receipt', 'BKG-102', 'Sent'),
('EML-105', 'Driver', 'Nuwan Bandara', 'DRV-002', 'Replacement Driver Request Approved', 'BKG-101', 'Failed'),
('EML-106', 'Customer', 'Kasun Perera', 'CUS-001', 'Customer Registration Approved', 'N/A', 'Sent')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Announcements Table
CREATE TABLE IF NOT EXISTS `announcements` (
    `announcement_id` VARCHAR(20) PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `announcement_type` ENUM('Maintenance', 'System Update', 'Important Notice', 'Payment Notice', 'Booking Notice', 'Emergency', 'General') NOT NULL,
    `target_audience` ENUM('All Users', 'Customers', 'Vehicle Owners', 'Drivers') NOT NULL,
    `priority` ENUM('Normal', 'Important', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    `message` TEXT NOT NULL,
    `publish_date` DATE NOT NULL,
    `publish_time` TIME NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `status` ENUM('Draft', 'Scheduled', 'Published', 'Expired') NOT NULL DEFAULT 'Draft',
    `created_by` VARCHAR(100) DEFAULT 'Admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed Announcements Data
INSERT INTO `announcements` (`announcement_id`, `title`, `announcement_type`, `target_audience`, `priority`, `message`, `publish_date`, `publish_time`, `expiry_date`, `status`, `created_by`) VALUES
('ANN-001', 'Scheduled System Maintenance', 'Maintenance', 'All Users', 'High', 'Lanka Renters will be temporarily unavailable on Sunday from 10:00 PM to 12:00 AM due to scheduled database maintenance.', '2026-08-10', '22:00:00', '2026-08-11', 'Published', 'Admin'),
('ANN-002', 'Payment Gateway Integration Upgrade', 'Payment Notice', 'Customers', 'Important', 'We have added new online banking options for faster payment verification on rental bookings.', '2026-08-09', '09:00:00', '2026-08-25', 'Published', 'Admin'),
('ANN-003', 'Owner Commission Payout Guidelines', 'Important Notice', 'Vehicle Owners', 'Normal', 'Settlement calculations are processed every Monday. Please ensure bank details are up to date.', '2026-08-12', '08:00:00', '2026-08-30', 'Scheduled', 'Admin'),
('ANN-004', 'Driver Verification Policy Update', 'System Update', 'Drivers', 'Normal', 'All drivers must submit updated commercial driving license copies before the end of the month.', '2026-08-08', '14:30:00', NULL, 'Draft', 'Admin')
ON DUPLICATE KEY UPDATE `announcement_id`=`announcement_id`;
