-- Customer-managed identity evidence. Files are stored outside public/ and are
-- always reached through the authenticated Customer record.

CREATE TABLE IF NOT EXISTS `customer_verification_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `guarantor_id` INT DEFAULT NULL,
  `document_type` ENUM('nic', 'driving_license', 'guarantor_nic') NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_customer_verification_document_type` (`customer_id`, `document_type`),
  KEY `idx_customer_verification_document_status` (`verification_status`),
  CONSTRAINT `fk_customer_verification_documents_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_verification_documents_guarantor`
    FOREIGN KEY (`guarantor_id`) REFERENCES `customer_guarantors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
