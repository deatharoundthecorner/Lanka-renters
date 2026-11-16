-- Issue #4: enforce one settlement per booking.
-- The ALTER naturally fails if existing duplicate booking_id values remain.
SET @settlement_booking_unique = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'settlements'
      AND index_name = 'uq_settlements_booking'
);

SET @settlement_booking_sql = IF(
    @settlement_booking_unique = 0,
    'ALTER TABLE `settlements` ADD UNIQUE KEY `uq_settlements_booking` (`booking_id`)',
    'SELECT 1'
);

PREPARE settlement_booking_statement FROM @settlement_booking_sql;
EXECUTE settlement_booking_statement;
DEALLOCATE PREPARE settlement_booking_statement;