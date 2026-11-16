-- Issue #2: enforce one driver payout per booking.
-- The ALTER naturally fails if existing duplicate booking_id values remain.
SET @driver_payment_booking_unique = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'driver_payments'
      AND index_name = 'uq_driver_payments_booking'
);

SET @driver_payment_booking_sql = IF(
    @driver_payment_booking_unique = 0,
    'ALTER TABLE `driver_payments` ADD UNIQUE KEY `uq_driver_payments_booking` (`booking_id`)',
    'SELECT 1'
);

PREPARE driver_payment_booking_statement FROM @driver_payment_booking_sql;
EXECUTE driver_payment_booking_statement;
DEALLOCATE PREPARE driver_payment_booking_statement;