-- Customer-facing rental details. These nullable fields are additive so existing
-- Owner workflows and existing vehicle records remain valid without a value.

ALTER TABLE `vehicles`
  ADD COLUMN IF NOT EXISTS `location` VARCHAR(120) DEFAULT NULL AFTER `seating_capacity`,
  ADD COLUMN IF NOT EXISTS `air_conditioning` TINYINT(1) DEFAULT NULL AFTER `location`,
  ADD COLUMN IF NOT EXISTS `monthly_rental_price` DECIMAL(10,2) DEFAULT NULL AFTER `price_per_day`,
  ADD COLUMN IF NOT EXISTS `refundable_key_money` DECIMAL(10,2) DEFAULT NULL AFTER `monthly_rental_price`,
  ADD COLUMN IF NOT EXISTS `mileage_limit_km` INT DEFAULT NULL AFTER `refundable_key_money`,
  ADD COLUMN IF NOT EXISTS `mileage_is_unlimited` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mileage_limit_km`,
  ADD COLUMN IF NOT EXISTS `additional_mileage_charge_per_km` DECIMAL(10,2) DEFAULT NULL AFTER `mileage_is_unlimited`;
