-- Chat: enforce one booking-scoped room.
-- The ALTER naturally fails if existing duplicate booking_id values remain.
SET @chat_room_booking_unique = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'chat_rooms'
      AND index_name = 'uq_chat_rooms_booking'
);

SET @chat_room_booking_sql = IF(
    @chat_room_booking_unique = 0,
    'ALTER TABLE `chat_rooms` ADD UNIQUE KEY `uq_chat_rooms_booking` (`booking_id`)',
    'SELECT 1'
);

PREPARE chat_room_booking_statement FROM @chat_room_booking_sql;
EXECUTE chat_room_booking_statement;
DEALLOCATE PREPARE chat_room_booking_statement;