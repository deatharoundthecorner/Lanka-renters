CREATE TABLE IF NOT EXISTS `vehicle_inspection_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inspection_id` INT NOT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(120) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vehicle_inspection_photos_inspection` (`inspection_id`),
  CONSTRAINT `fk_vehicle_inspection_photos_inspection`
    FOREIGN KEY (`inspection_id`) REFERENCES `vehicle_inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
