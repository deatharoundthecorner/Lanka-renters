-- Migration 014: Add pending_approval to bookings.status ENUM
-- The Owner module can approve / reject bookings in 'pending_approval'. Customer bookings are
-- created as 'pending_payment', so the default stays 'pending_payment'.
-- The list keeps 'disputed' so a database that already has it (demo export) loses no rows.

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
