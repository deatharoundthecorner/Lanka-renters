-- Migration 022: Admin review details for Customer guarantors
-- customer_guarantors (migrations 010 / 011) stays the Customer-owned table. This adds the optional
-- contact fields and reviewer audit columns used by admin/guarantors.php. Existing rows keep NULLs.

ALTER TABLE `customer_guarantors`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `address` TEXT DEFAULT NULL AFTER `relationship`,
  ADD COLUMN IF NOT EXISTS `reviewed_by` INT DEFAULT NULL AFTER `rejection_reason`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `reviewed_by`,
  ADD KEY IF NOT EXISTS `idx_customer_guarantors_status` (`verification_status`),
  ADD CONSTRAINT `fk_customer_guarantors_reviewer`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
