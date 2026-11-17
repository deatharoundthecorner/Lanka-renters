-- Lanka Renters Migration 002: Support Driver Document Versioning and History
-- Alter driver_documents table

ALTER TABLE `driver_documents`
ADD COLUMN `version` INT NOT NULL DEFAULT 1,
ADD COLUMN `is_current` BOOLEAN NOT NULL DEFAULT TRUE,
ADD COLUMN `reviewed_by` INT DEFAULT NULL,
ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
CHANGE COLUMN `verification_status` `status` ENUM('pending', 'approved', 'rejected', 'superseded', 'expired') NOT NULL DEFAULT 'pending',
CHANGE COLUMN `rejected_reason` `rejection_reason` TEXT DEFAULT NULL,
ADD CONSTRAINT `fk_driver_docs_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
