-- Customer-confirmed pickup and rental start are recorded alongside the
-- existing driver pickup stages, preserving a complete handover history.
ALTER TABLE `bookings`
  MODIFY COLUMN `pickup_status` ENUM('pending_pickup', 'dispatched', 'arrived', 'picked_up', 'customer_confirmed_pickup', 'rental_started', 'dropped_off')
  NOT NULL DEFAULT 'pending_pickup';

ALTER TABLE `pickup_tracking`
  MODIFY COLUMN `status` ENUM('pending_pickup', 'dispatched', 'arrived', 'picked_up', 'customer_confirmed_pickup', 'rental_started', 'dropped_off')
  NOT NULL;
