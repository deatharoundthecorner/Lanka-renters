-- Lanka Renters Migration 006: Add Username column to Users, fix status enum, update change_type enum
-- Safe to re-run: ADD COLUMN will fail gracefully if already applied.
-- NOTE: If re-running, skip the ADD COLUMN line if 'username' column already exists.

ALTER TABLE `users`
ADD COLUMN `username` VARCHAR(100) DEFAULT NULL UNIQUE AFTER `name`;

-- Add 'pending' to users.status ENUM (needed for account status UI component)
ALTER TABLE `users`
MODIFY COLUMN `status` ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'active';

ALTER TABLE `account_change_requests`
MODIFY COLUMN `change_type` ENUM('display_name', 'username', 'email') NOT NULL;
