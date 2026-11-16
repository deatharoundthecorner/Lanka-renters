ALTER TABLE `incidents`
  ADD COLUMN IF NOT EXISTS `incident_type` VARCHAR(40) NOT NULL DEFAULT 'other' AFTER `reported_by`,
  ADD COLUMN IF NOT EXISTS `district` VARCHAR(40) DEFAULT NULL AFTER `incident_date`,
  ADD COLUMN IF NOT EXISTS `vehicle_body_category` VARCHAR(30) DEFAULT NULL AFTER `district`,
  ADD COLUMN IF NOT EXISTS `google_maps_url` VARCHAR(2048) DEFAULT NULL AFTER `vehicle_body_category`,
  ADD COLUMN IF NOT EXISTS `police_report_path` VARCHAR(255) DEFAULT NULL AFTER `google_maps_url`;
ALTER TABLE `incidents`
  ADD COLUMN IF NOT EXISTS `resolution_preference` ENUM('replacement', 'refund') DEFAULT NULL AFTER `police_report_path`;
