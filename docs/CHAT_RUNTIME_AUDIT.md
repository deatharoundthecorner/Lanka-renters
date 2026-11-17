# Lanka Renters - Chat Runtime Audit

Date: 2026-08-18
Scope: Read-only audit. No chat, UI, database, configuration, controller, model, helper, or schema files were modified.

## Executive Result

The current MariaDB runtime contains no booking rows, no chat rooms, no chat participants, and no chat messages. Therefore no legitimate booking-scoped conversation can currently be exercised.

The immediate interim-demo cause is **A. No eligible demo booking/data**. The chat code may be functional, but the current database cannot create or open a legitimate conversation without a booking. This audit does not classify that condition as a runtime code failure.

A separate static risk was found: the three role implementations do not consistently form the expected Customer + Owner + assigned Driver participant set. Enrollment depends on which role opens or initializes the conversation first.

## Required Summary

CUSTOMER CHAT PAGE: NOT TESTABLE AUTHENTICATED

OWNER CHAT PAGE: NOT TESTABLE AUTHENTICATED

DRIVER CHAT PAGE: NOT TESTABLE AUTHENTICATED

ELIGIBLE TEST BOOKING EXISTS: NO

ROOM CREATION: NOT TESTABLE

PARTICIPANT FORMATION: FAIL (static timing-dependent formation; no live room to exercise)

MESSAGE READ: NOT TESTABLE

MESSAGE SEND: NOT TESTABLE

CROSS-ROLE CHAT: NOT TESTABLE

AUTHORIZATION: PASS (static code review; live tamper test not executable without a room)

ADMIN BLANKET ACCESS: NO

ROOT CAUSE:

The live `lanka_renters` database has zero rows in `bookings`, so the Customer eligible-booking query returns zero rows and no role can legitimately initialize a booking chat. Consequently, `chat_rooms`, `chat_participants`, and `chat_messages` are also empty. The chat pages correctly require authentication and redirect unauthenticated requests to `login.php`; no route or database-connection error was observed.

Separately, participant formation is timing-dependent. Customer room initialization adds Customer plus assigned Driver, or Owner when no Driver is assigned. Driver room initialization adds Driver plus Customer. Owner page load creates/enrolls Owner rooms for Owner-owned bookings, but does not add the Customer or assigned Driver. No single current path guarantees all three expected participants.

FAILURE CLASS:

A. No eligible demo booking/data
C. Participant not being enrolled consistently across role initialization paths

FILES THAT WOULD REQUIRE A FIX:

No files should be fixed in this read-only audit. A later approved implementation would likely need review of:

- `app/models/CustomerPortal.php`
- `app/models/Chat.php`
- `app/controllers/ChatController.php`
- `app/controllers/OwnerChatController.php`
- `public/customer/chat/index.php`
- `public/owner/chat.php`
- `public/driver/messages.php`
- `public/driver/chat.php`

DATABASE SCHEMA CHANGE REQUIRED: NO

## 1. Current Chat File Inventory

### Customer

- Page: `public/customer/chat/index.php`
- Layout: `public/customer/components/layout/feature-start.php`
- Chat list/view markup: `public/customer/chat/index.php`
- Controller entry: `app/controllers/CustomerPortalController.php`, `chatPage()`
- Model entry: `app/models/CustomerPortal.php`
- CSRF helper: `app/helpers/CustomerCsrf.php`
- Browser behavior: `public/customer/assets/js/customer-workflows.js` only scrolls the message list; it does not create rooms or send messages.

Customer operations:

- `getChatRooms()` lists rooms only when the authenticated Customer is a participant and owns the booking.
- `getChatEligibleBookings()` allows `pending_payment`, `confirmed`, and `ongoing` bookings owned by the authenticated Customer.
- `ensureChatRoom()` locks and validates the Customer-owned eligible booking, creates or reuses the room, and inserts Customer plus assigned Driver or Owner.
- `findOwnedChatRoom()` requires both Customer booking ownership and participant membership.
- `getChatMessages()` repeats the room ownership/membership check before selecting messages.
- `sendChatMessage()` repeats the room check and eligible-status check before INSERT.

### Owner

- Page: `public/owner/chat.php`
- Controller: `app/controllers/OwnerChatController.php`
- Shared model: `app/models/Chat.php`
- Auth/session helper: `app/helpers/AuthHelper.php`
- Browser JavaScript: no chat-specific JavaScript found; the page uses normal PHP form POST.

Owner operations:

- `getChatData()` calls `ensureOwnerRoomsEnrolled()` on every GET.
- `ensureOwnerRoomsEnrolled()` derives bookings through Owner-owned vehicles, creates/reuses rooms, and enrolls the Owner.
- `getUserRooms()` lists rooms in which the authenticated Owner is a participant.
- Active room access requires the target room to be in that authenticated Owner's returned participant room list.
- `sendMessage()` requires CSRF, Owner role/profile, participant membership, and non-empty text.

### Driver

- List/start page: `public/driver/messages.php`
- Room page: `public/driver/chat.php`
- Controller: `app/controllers/ChatController.php`
- Shared model: `app/models/Chat.php`
- Auth/session helper: `app/helpers/AuthHelper.php`
- Browser JavaScript: no chat-specific JavaScript found; `public/driver/chat.php` only auto-scrolls the message box.

Driver operations:

- `messages.php` lists rooms through `ChatController::getDriverRooms()` and offers assigned bookings from `DriverController::viewTrips()`.
- `createBookingRoom()` validates the booking against the authenticated Driver profile, creates/reuses the room, and enrolls Driver plus Customer.
- `chat.php` uses `getRoomMessages()` and `getOtherParticipant()`, both of which require Driver participant membership.
- `sendChatMessage()` requires CSRF, Driver profile, participant membership, and non-empty text.

## 2. Live Database Verification

Connection was read through the project Database helper against the verified runtime:

- MariaDB: `10.4.32-MariaDB`
- Host/port: `127.0.0.1:3308`
- Database: `lanka_renters`

Live counts:

| Table | Rows |
| --- | ---: |
| `chat_rooms` | 0 |
| `chat_participants` | 0 |
| `chat_messages` | 0 |
| `bookings` | 0 |

### Table structure and relevant foreign keys

`chat_rooms`:

- `id INT` primary key
- `booking_id INT NULL`
- `created_at TIMESTAMP`
- `fk_chat_rooms_booking`: `booking_id -> bookings.id`, `ON DELETE SET NULL`

`chat_participants`:

- `id INT` primary key
- `room_id INT NOT NULL`
- `user_id INT NOT NULL`
- unique key on `(room_id, user_id)`
- `fk_participants_room`: `room_id -> chat_rooms.id`, `ON DELETE CASCADE`
- `fk_participants_user`: `user_id -> users.id`, `ON DELETE CASCADE`

`chat_messages`:

- `id INT` primary key
- `room_id INT NOT NULL`
- `sender_id INT NOT NULL`
- `message_text TEXT NOT NULL`
- `is_read TINYINT(1) NOT NULL DEFAULT 0`
- `sent_at TIMESTAMP`
- `fk_messages_room`: `room_id -> chat_rooms.id`, `ON DELETE CASCADE`
- `fk_messages_sender`: `sender_id -> users.id`, `ON DELETE RESTRICT`

The live schema is compatible with the current chat queries, including `is_read`, `sent_at`, and all referenced foreign keys. No schema mismatch was found.

## 3. Test Booking Determination

No booking exists in the live database, so there is no booking ID, Customer, Owner, assigned Driver, status, or vehicle that can legitimately support chat.

The live role/profile query did confirm one active profile for each role, but there is no booking relationship to connect them.

**CHAT CODE MAY BE FUNCTIONAL BUT CURRENT DEMO DATA CANNOT EXERCISE IT.**

## 4. Runtime Route Checks

Unauthenticated GET checks were performed read-only:

| Route | Result |
| --- | --- |
| `/customer/chat/index.php` | HTTP 302 to login; browser landed on Sign In |
| `/owner/chat.php` | HTTP 302 to login; browser landed on Sign In |
| `/driver/messages.php` | HTTP 302 to login; browser landed on Sign In |
| `/driver/chat.php?room_id=1` | HTTP 302 to login; browser landed on Sign In |

No authenticated Customer, Owner, or Driver session was available to the browser tool during this audit. Since the database has no eligible booking or room, creating a test session or mutating data would not produce a legitimate end-to-end chat test and would violate the read-only scope.

Therefore page conversation listing, valid room opening, participant resolution, message read, composer submission, and message send are all reported as **NOT TESTABLE**, rather than failed.

No chat-specific JavaScript error or server log entry was found. The observed route behavior is the expected authentication gate.

## 5. Cross-Role Consistency Review

No live room exists, so participant rows cannot be compared for one booking.

Static formation behavior:

- Customer start: Customer + assigned Driver, otherwise Customer + Owner.
- Driver start: Driver + Customer.
- Owner GET enrollment: Owner only for each Owner-owned booking room.

This means the expected Customer + Owner + assigned Driver set is not guaranteed. For example, a Driver-created room contains Driver and Customer until the Owner visits Chat; an Owner-created room contains Owner until Customer or Driver initialization adds another participant. This is timing-dependent and is classified as participant-enrollment risk, not a database schema failure.

No Admin enrollment path was found.

## 6. Authorization Review

Static authorization checks are present:

- Customer room list/read requires Customer-owned booking plus Customer participant membership.
- Customer start validates the booking against the authenticated Customer and eligible status.
- Driver room creation validates `booking_id` against the authenticated Driver's assigned `driver_id`.
- Driver room read/send requires Driver participant membership.
- Owner room list/send requires authenticated Owner participant membership; Owner room enrollment derives bookings from Owner-owned vehicles.
- Admin has no chat route or automatic enrollment path.

Changing arbitrary `room_id` or `booking_id` is rejected by these predicates in the applicable role paths. A live tamper request could not be executed because no room exists, so the PASS result is based on code inspection and the no-room runtime state.

The current generic `Chat::getUserRooms()` model method can serve any user ID that is already a participant, but there is no Admin page or Admin enrollment path that grants blanket access. Admin blanket private-chat access is therefore **NO** in the current application.

## 7. Final Classification

| Class | Finding |
| --- | --- |
| A | Confirmed: no eligible booking/demo data exists |
| B | Not testable live; room creation code paths exist |
| C | Confirmed static risk: role initialization does not guarantee all three participants |
| D | No evidence of wrong lookup in the current no-data state |
| E | No evidence that legitimate participant authorization is rejected |
| F | Not testable; no room/message INSERT can be performed legitimately |
| G | Not testable; no room/message SELECT can be performed legitimately |
| H | No chat-specific JS dependency causing failure found |
| I | No routing failure; auth redirects work as expected |
| J | Not found; schema matches current queries |
| K | Timing-dependent participant formation across role paths |

## Audit Stop Statement

This report is the complete read-only audit. No fix was applied. No Customer UI, Admin UI, database schema, database configuration, controller, model, helper, JavaScript, or Git state was changed.
