-- Migration 019: Incident dispute resolution
-- Approved incidents hold the active booking ('disputed') until staff issue a refund or
-- the Customer accepts a verified replacement vehicle.
ALTER TABLE `bookings`
  MODIFY COLUMN `status` ENUM(
    'pending_approval',
    'pending_payment',
    'confirmed',
    'ongoing',
    'disputed',
    'completed',
    'cancelled'
  ) NOT NULL DEFAULT 'pending_payment';
