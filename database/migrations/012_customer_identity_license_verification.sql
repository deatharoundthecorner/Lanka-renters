-- Customer identity and driving-licence verification workflow.
-- Preserve customers.verification_status for compatibility during rollout.
-- Document files and per-document status live in customer_verification_documents (migration 013);
-- these columns hold the Admin review audit trail used by admin/customer_verification.php.

ALTER TABLE `customers`
  ADD COLUMN `identity_verification_status` ENUM('pending', 'approved', 'rejected', 'resubmission_required') NOT NULL DEFAULT 'pending' AFTER `verification_status`,
  ADD COLUMN `driving_license_status` ENUM('not_submitted', 'pending', 'approved', 'rejected', 'resubmission_required') NOT NULL DEFAULT 'not_submitted' AFTER `identity_verification_status`,
  ADD COLUMN `has_driving_license` TINYINT(1) DEFAULT NULL AFTER `driving_license_status`,
  ADD COLUMN `identity_rejection_reason` TEXT DEFAULT NULL AFTER `has_driving_license`,
  ADD COLUMN `identity_reviewed_by` INT DEFAULT NULL AFTER `identity_rejection_reason`,
  ADD COLUMN `identity_reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `identity_reviewed_by`,
  ADD COLUMN `license_rejection_reason` TEXT DEFAULT NULL AFTER `identity_reviewed_at`,
  ADD COLUMN `license_reviewed_by` INT DEFAULT NULL AFTER `license_rejection_reason`,
  ADD COLUMN `license_reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `license_reviewed_by`,
  ADD COLUMN `terms_version` VARCHAR(50) DEFAULT NULL AFTER `license_reviewed_at`,
  ADD COLUMN `terms_accepted_at` TIMESTAMP NULL DEFAULT NULL AFTER `terms_version`,
  ADD CONSTRAINT `fk_customers_identity_reviewer` FOREIGN KEY (`identity_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_customers_license_reviewer` FOREIGN KEY (`license_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

UPDATE `customers`
SET `identity_verification_status` = CASE `verification_status`
    WHEN 'approved' THEN 'approved'
    WHEN 'rejected' THEN 'rejected'
    ELSE 'pending'
  END,
  `driving_license_status` = 'not_submitted',
  `has_driving_license` = NULL
WHERE `identity_verification_status` = 'pending'
  AND `driving_license_status` = 'not_submitted';
