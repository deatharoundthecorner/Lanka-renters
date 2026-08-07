-- Lanka Renters Customer demonstration data proposal
-- REVIEW ONLY - requires database-coordinator approval before execution.
-- Run only against an approved disposable/test database.
-- Expected order:
--   1. Current shared schema
--   2. Approved statements from customer_schema_proposal.sql
--   3. Coordinated fake prerequisite accounts/profiles
--   4. This script
--
-- This script intentionally does not create authentication, Customer, Owner,
-- Driver, or Admin identities. The team must coordinate fake records with:
--   customer.demo@lankarenters.test (active, approved Customer profile)
--   owner.demo@lankarenters.test    (active, approved Owner profile)
--   admin.demo@lankarenters.test    (optional active Admin verifier)
-- No password or credential is stored in this file.

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- Resolve coordinated prerequisite records through unique email values.
-- If required Customer or Owner records are absent, dependent inserts safely
-- affect zero rows.
-- ---------------------------------------------------------------------------
SET @demo_customer_user_id := (
  SELECT `id`
  FROM `users`
  WHERE `email` = 'customer.demo@lankarenters.test'
    AND `role` = 'customer'
    AND `status` = 'active'
  LIMIT 1
);

SET @demo_customer_id := (
  SELECT `id`
  FROM `customers`
  WHERE `user_id` = @demo_customer_user_id
    AND `verification_status` = 'approved'
  LIMIT 1
);

SET @demo_owner_user_id := (
  SELECT `id`
  FROM `users`
  WHERE `email` = 'owner.demo@lankarenters.test'
    AND `role` = 'owner'
    AND `status` = 'active'
  LIMIT 1
);

SET @demo_owner_id := (
  SELECT `id`
  FROM `vehicle_owners`
  WHERE `user_id` = @demo_owner_user_id
    AND `verification_status` = 'approved'
  LIMIT 1
);

SET @demo_admin_user_id := (
  SELECT `id`
  FROM `users`
  WHERE `email` = 'admin.demo@lankarenters.test'
    AND `role` = 'admin'
    AND `status` = 'active'
  LIMIT 1
);

-- ---------------------------------------------------------------------------
-- Owner-coordinated catalogue rows: five types/states across Sri Lanka.
-- Unique registration numbers make these inserts repeatable.
-- ---------------------------------------------------------------------------
INSERT INTO `vehicles` (
  `owner_id`, `district`, `pickup_location`, `make`, `model`, `year`,
  `license_plate`, `vehicle_type`, `transmission`, `fuel_type`,
  `seating_capacity`, `price_per_day`, `price_with_driver_per_day`,
  `status`, `verification_status`
)
SELECT
  @demo_owner_id, 'Colombo', 'Bambalapitiya, Colombo 04',
  'Toyota', 'Prius', 2020, 'CAA-2468', 'car', 'automatic', 'hybrid',
  5, 8000.00, 11500.00, 'available', 'approved'
WHERE @demo_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `vehicles` WHERE `license_plate` = 'CAA-2468');

INSERT INTO `vehicles` (
  `owner_id`, `district`, `pickup_location`, `make`, `model`, `year`,
  `license_plate`, `vehicle_type`, `transmission`, `fuel_type`,
  `seating_capacity`, `price_per_day`, `price_with_driver_per_day`,
  `status`, `verification_status`
)
SELECT
  @demo_owner_id, 'Kandy', 'Peradeniya Road, Kandy',
  'Suzuki', 'Wagon R', 2019, 'CAB-1357', 'car', 'automatic', 'petrol',
  4, 7500.00, 10500.00, 'available', 'approved'
WHERE @demo_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `vehicles` WHERE `license_plate` = 'CAB-1357');

INSERT INTO `vehicles` (
  `owner_id`, `district`, `pickup_location`, `make`, `model`, `year`,
  `license_plate`, `vehicle_type`, `transmission`, `fuel_type`,
  `seating_capacity`, `price_per_day`, `price_with_driver_per_day`,
  `status`, `verification_status`
)
SELECT
  @demo_owner_id, 'Galle', 'Wakwella Road, Galle',
  'Toyota', 'KDH', 2018, 'NC-4821', 'van', 'automatic', 'diesel',
  10, 15000.00, 19000.00, 'rented', 'approved'
WHERE @demo_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `vehicles` WHERE `license_plate` = 'NC-4821');

INSERT INTO `vehicles` (
  `owner_id`, `district`, `pickup_location`, `make`, `model`, `year`,
  `license_plate`, `vehicle_type`, `transmission`, `fuel_type`,
  `seating_capacity`, `price_per_day`, `price_with_driver_per_day`,
  `status`, `verification_status`
)
SELECT
  @demo_owner_id, 'Colombo', 'Battaramulla, Colombo',
  'Mitsubishi', 'Outlander', 2021, 'CAD-2026', 'suv', 'automatic', 'hybrid',
  7, 18000.00, 22500.00, 'maintenance', 'approved'
WHERE @demo_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `vehicles` WHERE `license_plate` = 'CAD-2026');

INSERT INTO `vehicles` (
  `owner_id`, `district`, `pickup_location`, `make`, `model`, `year`,
  `license_plate`, `vehicle_type`, `transmission`, `fuel_type`,
  `seating_capacity`, `price_per_day`, `price_with_driver_per_day`,
  `status`, `verification_status`
)
SELECT
  @demo_owner_id, 'Jaffna', 'Hospital Road, Jaffna',
  'Honda', 'Dio', 2022, 'BCT-9087', 'motorbike', 'automatic', 'petrol',
  2, 3500.00, NULL, 'unavailable', 'approved'
WHERE @demo_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `vehicles` WHERE `license_plate` = 'BCT-9087');

SET @demo_prius_id := (SELECT `id` FROM `vehicles` WHERE `license_plate` = 'CAA-2468' LIMIT 1);
SET @demo_wagon_id := (SELECT `id` FROM `vehicles` WHERE `license_plate` = 'CAB-1357' LIMIT 1);
SET @demo_van_id := (SELECT `id` FROM `vehicles` WHERE `license_plate` = 'NC-4821' LIMIT 1);
SET @demo_suv_id := (SELECT `id` FROM `vehicles` WHERE `license_plate` = 'CAD-2026' LIMIT 1);
SET @demo_bike_id := (SELECT `id` FROM `vehicles` WHERE `license_plate` = 'BCT-9087' LIMIT 1);

-- Public image paths assume later Customer-owned demo image assets.
INSERT INTO `vehicle_images` (`vehicle_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
SELECT @demo_prius_id, 'customer/assets/images/demo/toyota-prius.jpg',
       'Silver Toyota Prius', TRUE, 1
WHERE @demo_prius_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `vehicle_images`
    WHERE `vehicle_id` = @demo_prius_id
      AND `image_path` = 'customer/assets/images/demo/toyota-prius.jpg'
  );

INSERT INTO `vehicle_images` (`vehicle_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
SELECT @demo_wagon_id, 'customer/assets/images/demo/suzuki-wagon-r.jpg',
       'White Suzuki Wagon R', TRUE, 1
WHERE @demo_wagon_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `vehicle_images`
    WHERE `vehicle_id` = @demo_wagon_id
      AND `image_path` = 'customer/assets/images/demo/suzuki-wagon-r.jpg'
  );

INSERT INTO `vehicle_images` (`vehicle_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
SELECT @demo_van_id, 'customer/assets/images/demo/toyota-kdh.jpg',
       'White Toyota KDH van', TRUE, 1
WHERE @demo_van_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `vehicle_images`
    WHERE `vehicle_id` = @demo_van_id
      AND `image_path` = 'customer/assets/images/demo/toyota-kdh.jpg'
  );

INSERT INTO `vehicle_images` (`vehicle_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
SELECT @demo_suv_id, 'customer/assets/images/demo/mitsubishi-outlander.jpg',
       'Black Mitsubishi Outlander', TRUE, 1
WHERE @demo_suv_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `vehicle_images`
    WHERE `vehicle_id` = @demo_suv_id
      AND `image_path` = 'customer/assets/images/demo/mitsubishi-outlander.jpg'
  );

INSERT INTO `vehicle_images` (`vehicle_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
SELECT @demo_bike_id, 'customer/assets/images/demo/honda-dio.jpg',
       'Red Honda Dio motorbike', TRUE, 1
WHERE @demo_bike_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `vehicle_images`
    WHERE `vehicle_id` = @demo_bike_id
      AND `image_path` = 'customer/assets/images/demo/honda-dio.jpg'
  );

-- ---------------------------------------------------------------------------
-- Booking lifecycle records. Marker text makes reruns safe even though the
-- booking table has no external demo key.
-- ---------------------------------------------------------------------------
INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL -150 DAY), DATE_ADD(NOW(), INTERVAL -120 DAY),
  'Peradeniya Road, Kandy [DEMO-BKG-COMPLETED]', v.`price_per_day` * 30,
  'completed', 'dropped_off', NULL, NULL, NULL,
  DATE_ADD(NOW(), INTERVAL -160 DAY), DATE_ADD(NOW(), INTERVAL -120 DAY)
FROM `vehicles` v
WHERE v.`id` = @demo_wagon_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Peradeniya Road, Kandy [DEMO-BKG-COMPLETED]'
  );

INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL -90 DAY), DATE_ADD(NOW(), INTERVAL -60 DAY),
  'Galle Face, Colombo [DEMO-BKG-CANCELLED]', v.`price_per_day` * 30,
  'cancelled', 'pending_pickup', DATE_ADD(NOW(), INTERVAL -95 DAY),
  'Customer travel dates changed.', @demo_customer_user_id,
  DATE_ADD(NOW(), INTERVAL -100 DAY), DATE_ADD(NOW(), INTERVAL -95 DAY)
FROM `vehicles` v
WHERE v.`id` = @demo_prius_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Galle Face, Colombo [DEMO-BKG-CANCELLED]'
  );

INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL -7 DAY), DATE_ADD(NOW(), INTERVAL 21 DAY),
  'Unawatuna, Galle [DEMO-BKG-ONGOING]', v.`price_per_day` * 28,
  'ongoing', 'picked_up', NULL, NULL, NULL,
  DATE_ADD(NOW(), INTERVAL -14 DAY), NOW()
FROM `vehicles` v
WHERE v.`id` = @demo_van_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Unawatuna, Galle [DEMO-BKG-ONGOING]'
  );

INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 58 DAY),
  'Nugegoda, Colombo [DEMO-BKG-CONFIRMED]', v.`price_per_day` * 28,
  'confirmed', 'pending_pickup', NULL, NULL, NULL, NOW(), NOW()
FROM `vehicles` v
WHERE v.`id` = @demo_prius_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Nugegoda, Colombo [DEMO-BKG-CONFIRMED]'
  );

INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL 70 DAY), DATE_ADD(NOW(), INTERVAL 98 DAY),
  'Katugastota, Kandy [DEMO-BKG-PAYMENT]', v.`price_per_day` * 28,
  'pending_payment', 'pending_pickup', NULL, NULL, NULL, NOW(), NOW()
FROM `vehicles` v
WHERE v.`id` = @demo_wagon_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Katugastota, Kandy [DEMO-BKG-PAYMENT]'
  );

INSERT INTO `bookings` (
  `customer_id`, `vehicle_id`, `driver_id`, `booking_type`, `start_date`,
  `end_date`, `delivery_address`, `total_price`, `status`, `pickup_status`,
  `cancelled_at`, `cancellation_reason`, `cancelled_by`, `created_at`, `updated_at`
)
SELECT
  @demo_customer_id, v.`id`, NULL, 'self_drive',
  DATE_ADD(NOW(), INTERVAL 110 DAY), DATE_ADD(NOW(), INTERVAL 138 DAY),
  'Kelaniya, Gampaha [DEMO-BKG-APPROVAL]', v.`price_per_day` * 28,
  'pending_approval', 'pending_pickup', NULL, NULL, NULL, NOW(), NOW()
FROM `vehicles` v
WHERE v.`id` = @demo_prius_id
  AND @demo_customer_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `bookings`
    WHERE `customer_id` = @demo_customer_id
      AND `delivery_address` = 'Kelaniya, Gampaha [DEMO-BKG-APPROVAL]'
  );

SET @demo_completed_booking_id := (
  SELECT `id` FROM `bookings`
  WHERE `customer_id` = @demo_customer_id
    AND `delivery_address` = 'Peradeniya Road, Kandy [DEMO-BKG-COMPLETED]'
  LIMIT 1
);
SET @demo_cancelled_booking_id := (
  SELECT `id` FROM `bookings`
  WHERE `customer_id` = @demo_customer_id
    AND `delivery_address` = 'Galle Face, Colombo [DEMO-BKG-CANCELLED]'
  LIMIT 1
);
SET @demo_ongoing_booking_id := (
  SELECT `id` FROM `bookings`
  WHERE `customer_id` = @demo_customer_id
    AND `delivery_address` = 'Unawatuna, Galle [DEMO-BKG-ONGOING]'
  LIMIT 1
);
SET @demo_confirmed_booking_id := (
  SELECT `id` FROM `bookings`
  WHERE `customer_id` = @demo_customer_id
    AND `delivery_address` = 'Nugegoda, Colombo [DEMO-BKG-CONFIRMED]'
  LIMIT 1
);

-- ---------------------------------------------------------------------------
-- Payment and invoice states used by later demonstration pages.
-- Admin verification fields remain null when the optional fake Admin is absent.
-- ---------------------------------------------------------------------------
INSERT INTO `payments` (
  `booking_id`, `amount`, `payment_method`, `payment_status`,
  `transaction_reference`, `verified_by`, `verified_at`, `failure_reason`, `paid_at`
)
SELECT b.`id`, b.`total_price`, 'bank_transfer', 'completed',
       'DEMO-PAY-COMPLETED-001', @demo_admin_user_id,
       DATE_ADD(b.`start_date`, INTERVAL -5 DAY), NULL,
       DATE_ADD(b.`start_date`, INTERVAL -6 DAY)
FROM `bookings` b
WHERE b.`id` = @demo_completed_booking_id
  AND NOT EXISTS (
    SELECT 1 FROM `payments`
    WHERE `transaction_reference` = 'DEMO-PAY-COMPLETED-001'
  );

INSERT INTO `payments` (
  `booking_id`, `amount`, `payment_method`, `payment_status`,
  `transaction_reference`, `verified_by`, `verified_at`, `failure_reason`, `paid_at`
)
SELECT b.`id`, b.`total_price`, 'card', 'completed',
       'DEMO-PAY-ONGOING-001', @demo_admin_user_id,
       DATE_ADD(b.`start_date`, INTERVAL -3 DAY), NULL,
       DATE_ADD(b.`start_date`, INTERVAL -4 DAY)
FROM `bookings` b
WHERE b.`id` = @demo_ongoing_booking_id
  AND NOT EXISTS (
    SELECT 1 FROM `payments`
    WHERE `transaction_reference` = 'DEMO-PAY-ONGOING-001'
  );

INSERT INTO `payments` (
  `booking_id`, `amount`, `payment_method`, `payment_status`,
  `transaction_reference`, `verified_by`, `verified_at`, `failure_reason`, `paid_at`
)
SELECT b.`id`, b.`total_price`, 'bank_transfer', 'pending',
       'DEMO-PAY-PENDING-001', NULL, NULL, NULL, NULL
FROM `bookings` b
WHERE b.`id` = @demo_confirmed_booking_id
  AND NOT EXISTS (
    SELECT 1 FROM `payments`
    WHERE `transaction_reference` = 'DEMO-PAY-PENDING-001'
  );

INSERT INTO `payments` (
  `booking_id`, `amount`, `payment_method`, `payment_status`,
  `transaction_reference`, `verified_by`, `verified_at`, `failure_reason`, `paid_at`
)
SELECT b.`id`, b.`total_price`, 'card', 'refunded',
       'DEMO-PAY-REFUNDED-001', @demo_admin_user_id,
       b.`cancelled_at`, NULL, DATE_ADD(b.`start_date`, INTERVAL -10 DAY)
FROM `bookings` b
WHERE b.`id` = @demo_cancelled_booking_id
  AND NOT EXISTS (
    SELECT 1 FROM `payments`
    WHERE `transaction_reference` = 'DEMO-PAY-REFUNDED-001'
  );

INSERT INTO `invoices` (
  `invoice_number`, `booking_id`, `customer_id`, `payment_id`, `rental_fee`,
  `driver_fee`, `additional_charges`, `discount`, `tax`, `total_amount`,
  `invoice_status`
)
SELECT 'INV-DEMO-0001', b.`id`, b.`customer_id`, p.`id`, b.`total_price`,
       0.00, 0.00, 0.00, 0.00, b.`total_price`, 'paid'
FROM `bookings` b
JOIN `payments` p ON p.`booking_id` = b.`id`
WHERE b.`id` = @demo_completed_booking_id
  AND p.`transaction_reference` = 'DEMO-PAY-COMPLETED-001'
  AND NOT EXISTS (SELECT 1 FROM `invoices` WHERE `invoice_number` = 'INV-DEMO-0001');

INSERT INTO `invoices` (
  `invoice_number`, `booking_id`, `customer_id`, `payment_id`, `rental_fee`,
  `driver_fee`, `additional_charges`, `discount`, `tax`, `total_amount`,
  `invoice_status`
)
SELECT 'INV-DEMO-0002', b.`id`, b.`customer_id`, p.`id`, b.`total_price`,
       0.00, 0.00, 0.00, 0.00, b.`total_price`, 'paid'
FROM `bookings` b
JOIN `payments` p ON p.`booking_id` = b.`id`
WHERE b.`id` = @demo_ongoing_booking_id
  AND p.`transaction_reference` = 'DEMO-PAY-ONGOING-001'
  AND NOT EXISTS (SELECT 1 FROM `invoices` WHERE `invoice_number` = 'INV-DEMO-0002');

INSERT INTO `invoices` (
  `invoice_number`, `booking_id`, `customer_id`, `payment_id`, `rental_fee`,
  `driver_fee`, `additional_charges`, `discount`, `tax`, `total_amount`,
  `invoice_status`
)
SELECT 'INV-DEMO-0003', b.`id`, b.`customer_id`, p.`id`, b.`total_price`,
       0.00, 0.00, 0.00, 0.00, b.`total_price`, 'pending'
FROM `bookings` b
JOIN `payments` p ON p.`booking_id` = b.`id`
WHERE b.`id` = @demo_confirmed_booking_id
  AND p.`transaction_reference` = 'DEMO-PAY-PENDING-001'
  AND NOT EXISTS (SELECT 1 FROM `invoices` WHERE `invoice_number` = 'INV-DEMO-0003');

INSERT INTO `invoices` (
  `invoice_number`, `booking_id`, `customer_id`, `payment_id`, `rental_fee`,
  `driver_fee`, `additional_charges`, `discount`, `tax`, `total_amount`,
  `invoice_status`
)
SELECT 'INV-DEMO-0004', b.`id`, b.`customer_id`, p.`id`, b.`total_price`,
       0.00, 0.00, 0.00, 0.00, b.`total_price`, 'cancelled'
FROM `bookings` b
JOIN `payments` p ON p.`booking_id` = b.`id`
WHERE b.`id` = @demo_cancelled_booking_id
  AND p.`transaction_reference` = 'DEMO-PAY-REFUNDED-001'
  AND NOT EXISTS (SELECT 1 FROM `invoices` WHERE `invoice_number` = 'INV-DEMO-0004');

-- ---------------------------------------------------------------------------
-- Review and incident examples tied to eligible owned bookings.
-- ---------------------------------------------------------------------------
INSERT INTO `ratings_reviews` (
  `booking_id`, `customer_id`, `driver_id`, `vehicle_id`,
  `driver_rating`, `vehicle_rating`, `review_text`
)
SELECT b.`id`, b.`customer_id`, b.`driver_id`, b.`vehicle_id`,
       NULL, 5, 'Clean vehicle and a smooth pickup experience. [DEMO-REVIEW]'
FROM `bookings` b
WHERE b.`id` = @demo_completed_booking_id
  AND b.`status` = 'completed'
  AND NOT EXISTS (
    SELECT 1 FROM `ratings_reviews` r
    WHERE r.`booking_id` = b.`id` AND r.`customer_id` = b.`customer_id`
  );

INSERT INTO `incidents` (
  `booking_id`, `reported_by`, `description`, `incident_date`, `severity`, `status`
)
SELECT b.`id`, @demo_customer_user_id,
       'Small windscreen chip noticed near Galle. [DEMO-INCIDENT]',
       DATE_ADD(NOW(), INTERVAL -1 DAY), 'minor', 'reported'
FROM `bookings` b
WHERE b.`id` = @demo_ongoing_booking_id
  AND b.`status` = 'ongoing'
  AND @demo_customer_user_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `incidents` i
    WHERE i.`booking_id` = b.`id`
      AND i.`description` = 'Small windscreen chip noticed near Galle. [DEMO-INCIDENT]'
  );

-- ---------------------------------------------------------------------------
-- Typed Customer notifications.
-- ---------------------------------------------------------------------------
INSERT INTO `notifications` (`user_id`, `title`, `message`, `notification_type`, `related_id`)
SELECT @demo_customer_user_id, 'Upcoming booking confirmed',
       'Your Toyota Prius booking is confirmed for the upcoming rental period.',
       'booking', @demo_confirmed_booking_id
WHERE @demo_customer_user_id IS NOT NULL
  AND @demo_confirmed_booking_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `notifications`
    WHERE `user_id` = @demo_customer_user_id
      AND `title` = 'Upcoming booking confirmed'
      AND `related_id` = @demo_confirmed_booking_id
  );

INSERT INTO `notifications` (`user_id`, `title`, `message`, `notification_type`, `related_id`)
SELECT @demo_customer_user_id, 'Payment awaiting verification',
       'Your submitted payment is waiting for Admin verification.',
       'payment', @demo_confirmed_booking_id
WHERE @demo_customer_user_id IS NOT NULL
  AND @demo_confirmed_booking_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `notifications`
    WHERE `user_id` = @demo_customer_user_id
      AND `title` = 'Payment awaiting verification'
      AND `related_id` = @demo_confirmed_booking_id
  );

INSERT INTO `notifications` (`user_id`, `title`, `message`, `notification_type`, `related_id`)
SELECT @demo_customer_user_id, 'Active rental reminder',
       'Your current van rental is active. Keep the return checklist ready.',
       'booking', @demo_ongoing_booking_id
WHERE @demo_customer_user_id IS NOT NULL
  AND @demo_ongoing_booking_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `notifications`
    WHERE `user_id` = @demo_customer_user_id
      AND `title` = 'Active rental reminder'
      AND `related_id` = @demo_ongoing_booking_id
  );

INSERT INTO `notifications` (`user_id`, `title`, `message`, `notification_type`, `related_id`)
SELECT @demo_customer_user_id, 'Review received',
       'Thank you for reviewing your completed Kandy rental.',
       'review', @demo_completed_booking_id
WHERE @demo_customer_user_id IS NOT NULL
  AND @demo_completed_booking_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `notifications`
    WHERE `user_id` = @demo_customer_user_id
      AND `title` = 'Review received'
      AND `related_id` = @demo_completed_booking_id
  );

-- ---------------------------------------------------------------------------
-- One booking conversation between the coordinated fake Customer and Owner.
-- ---------------------------------------------------------------------------
INSERT INTO `chat_rooms` (`booking_id`)
SELECT @demo_confirmed_booking_id
WHERE @demo_confirmed_booking_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `chat_rooms` WHERE `booking_id` = @demo_confirmed_booking_id
  );

SET @demo_chat_room_id := (
  SELECT `id` FROM `chat_rooms`
  WHERE `booking_id` = @demo_confirmed_booking_id
  ORDER BY `id`
  LIMIT 1
);

INSERT INTO `chat_participants` (`room_id`, `user_id`)
SELECT @demo_chat_room_id, @demo_customer_user_id
WHERE @demo_chat_room_id IS NOT NULL
  AND @demo_customer_user_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `chat_participants`
    WHERE `room_id` = @demo_chat_room_id AND `user_id` = @demo_customer_user_id
  );

INSERT INTO `chat_participants` (`room_id`, `user_id`)
SELECT @demo_chat_room_id, @demo_owner_user_id
WHERE @demo_chat_room_id IS NOT NULL
  AND @demo_owner_user_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `chat_participants`
    WHERE `room_id` = @demo_chat_room_id AND `user_id` = @demo_owner_user_id
  );

INSERT INTO `chat_messages` (`room_id`, `sender_id`, `message_text`, `is_read`)
SELECT @demo_chat_room_id, @demo_customer_user_id,
       'Could you confirm the Colombo pickup instructions? [DEMO-CHAT-1]', TRUE
WHERE @demo_chat_room_id IS NOT NULL
  AND @demo_customer_user_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `chat_messages`
    WHERE `room_id` = @demo_chat_room_id
      AND `message_text` = 'Could you confirm the Colombo pickup instructions? [DEMO-CHAT-1]'
  );

INSERT INTO `chat_messages` (`room_id`, `sender_id`, `message_text`, `is_read`)
SELECT @demo_chat_room_id, @demo_owner_user_id,
       'Yes. Please arrive at the listed pickup point fifteen minutes early. [DEMO-CHAT-2]', FALSE
WHERE @demo_chat_room_id IS NOT NULL
  AND @demo_owner_user_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `chat_messages`
    WHERE `room_id` = @demo_chat_room_id
      AND `message_text` = 'Yes. Please arrive at the listed pickup point fifteen minutes early. [DEMO-CHAT-2]'
  );

COMMIT;

-- ---------------------------------------------------------------------------
-- Read-only verification queries. Expected maximum additions after one run:
-- 5 vehicles, 5 images, 6 bookings, 4 payments, 4 invoices, 1 review,
-- 1 incident, 4 notifications, 1 chat room, 2 participants and 2 messages.
-- A second run should leave these counts unchanged.
-- ---------------------------------------------------------------------------
SELECT `license_plate`, `vehicle_type`, `district`, `status`, `price_per_day`
FROM `vehicles`
WHERE `license_plate` IN ('CAA-2468', 'CAB-1357', 'NC-4821', 'CAD-2026', 'BCT-9087')
ORDER BY `license_plate`;

SELECT `status`, COUNT(*) AS `demo_booking_count`
FROM `bookings`
WHERE `customer_id` = @demo_customer_id
  AND `delivery_address` LIKE '%[DEMO-BKG-%'
GROUP BY `status`
ORDER BY `status`;

SELECT p.`payment_status`, COUNT(*) AS `demo_payment_count`
FROM `payments` p
WHERE p.`transaction_reference` LIKE 'DEMO-PAY-%'
GROUP BY p.`payment_status`
ORDER BY p.`payment_status`;

SELECT COUNT(*) AS `broken_demo_booking_relationships`
FROM `bookings` b
LEFT JOIN `customers` c ON c.`id` = b.`customer_id`
LEFT JOIN `vehicles` v ON v.`id` = b.`vehicle_id`
WHERE b.`delivery_address` LIKE '%[DEMO-BKG-%'
  AND (c.`id` IS NULL OR v.`id` IS NULL);
