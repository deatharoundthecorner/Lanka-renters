-- Adds Admin-owned verification state to guarantors created before migration 010
-- included these columns. "missing" remains a derived UI state; no row is made for it.

ALTER TABLE `customer_guarantors`
  ADD COLUMN IF NOT EXISTS `verification_status`
    ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `relationship`,
  ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT DEFAULT NULL AFTER `verification_status`;
