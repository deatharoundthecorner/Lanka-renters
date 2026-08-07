# Customer Module Database Contract

## Purpose and status

This document defines the database contract that later Customer features must
follow. It is based first on the live `Customer` worktree schema, then on a
read-only comparison with `origin/develop`, the team database guide, existing
Customer models, and the available Member 2 context.

This is a review document. It does not authorize changes to
`database/lanka_renters.sql` or execution of the SQL proposals under
`docs/Customer/sql/`.

## Reference findings

- The live schema contains one shared `users` table and role profile tables.
- The team database guide assigns Customer responsibility for customer-facing
  use of `customers`, `bookings`, `payments`, `ratings_reviews`, notifications,
  and booking chat. Vehicles remain Owner-owned and are read by Customer.
- The specifically named `LANKA_RENTERS_MASTER_CONTEXT.md` was not present.
  `Lanka_Renters_Member_2_Chat_Context.md` was available as a lower-priority
  reference and was not allowed to override the live schema.
- The ZIP archives were inspected only for filenames. No archived code or SQL
  was copied into the live repository.

## Important identifier rule

The authentication session stores `users.id`. Booking and review ownership use
`customers.id`. Customer code must resolve the profile using:

```sql
SELECT id FROM customers WHERE user_id = :session_user_id
```

The `customer_id` or `user_id` supplied by a URL, form, JSON body, or hidden
field is never identity proof.

Notifications and `incidents.reported_by` use `users.id`, while
`bookings.customer_id`, `ratings_reviews.customer_id`, and
`invoices.customer_id` use `customers.id`. These two identifiers must not be
interchanged.

## Existing schema audit

| Requirement | Existing table and key | Existing columns and foreign keys | Missing or inconsistent parts | Owner and Customer use | Safe to use now? |
| --- | --- | --- | --- | --- | --- |
| Authentication user | `users`, PK `id INT` | `name`, unique `email`, `password_hash`, `phone`, role/status enums, timestamps | No Customer-specific gap in this phase | Authentication/shared. Customer reads its session user only | Yes, through existing authentication |
| Customer profile | `customers`, PK `id INT` | Unique `user_id INT` -> `users.id` with cascade; NIC, driving licence, verification enum, timestamps | No address/profile-image fields; not required by the Phase 2 contract | Customer profile data; Admin owns verification decisions | Yes; Customer must not write `verification_status` |
| Vehicle owner | `vehicle_owners`, PK `id INT` | Unique `user_id INT` -> `users.id`; owner type, bank data, verification enum | No Customer write requirement | Owner-owned; Customer may read public owner identity only | Read-only after Owner coordination |
| Vehicle catalogue | `vehicles`, PK `id INT` | `owner_id` -> `vehicle_owners.id`; make/model/year/plate/type/transmission/fuel/seats, two prices, status and verification enums, timestamps | No district or pickup-location fields; no catalogue composite index | Owner writes; Customer reads approved catalogue rows | Partly |
| Vehicle images | None | `vehicle_documents.file_path` is a private document path and must not be used as a public image | A dedicated public image table is missing | Owner writes images; Customer reads them | No, proposal required |
| Vehicle availability | `vehicles` plus `bookings` | `vehicles.status`; booking vehicle, dates and lifecycle status | No date-range table or exclusion constraint; only single-column booking indexes | Owner controls operational status; Customer calculates scheduled availability | Yes in application transactions; indexes recommended |
| Bookings | `bookings`, PK `id INT` | Customer/vehicle required, driver optional; type, dates, delivery address, total, booking/pickup status and timestamps; FKs use `RESTRICT`/`SET NULL` | No owner-review state; no rejection state; no cancellation actor/time/reason; no composite overlap indexes | Customer creates/reads/updates/cancels owned rows; Owner confirms; Driver/Admin read operational rows | Partly; proposal required before full workflow |
| Payments | `payments`, PK `id INT` | `booking_id` -> bookings with restrict; amount, method/status enums, slip/reference, verification fields, payment timestamps | Current verification fields are absent from `origin/develop` and require baseline coordination | Customer submits/displays; Admin alone verifies | Yes only if current schema additions are approved |
| Invoices | `invoices`, PK `id INT` | Unique invoice number; booking/customer/payment FKs; fee breakdown, status, timestamps | Entire table is absent from `origin/develop` | Customer reads; payment/admin workflow generates or updates | Yes only if retained by coordinator |
| Reviews | `ratings_reviews`, PK `id INT` | Booking/customer required; optional driver/vehicle, 1-5 checks, text and created timestamp | No unique booking/customer rule; no explicit booking index; no update timestamp | Customer writes once for an owned completed booking | Yes with application checks; uniqueness recommended |
| Incidents | `incidents`, PK `id INT` | Booking and reporter required; date, severity/status enums and timestamps | Ownership is indirect; Customer must join booking/customer before insert/read | Customer reports owned active booking; Admin owns investigation status | Yes with ownership query |
| Notifications | `notifications`, PK `id INT` | `user_id` -> users with cascade; title/message/read/created; current schema also has type and related ID | Type and related ID are absent from `origin/develop` | Shared producers; Customer reads and marks only its own rows | Yes only if current additions are approved |
| Booking chat | `chat_rooms`, `chat_participants`, `chat_messages` | Room may reference booking; participants reference users; messages reference room/sender | `chat_rooms.booking_id` is not unique; message `is_read` is absent from `origin/develop` | Shared by booking participants; Customer accesses rooms only through participation | Yes with participant and booking ownership checks |

All relevant primary and foreign-key columns are `INT`, so no incompatible FK
types were found.

## 1. Customer identity contract

### Table responsibilities

- `users`: authentication identity, role, account status, name, email and phone.
- `customers`: Customer profile ID, verification state, NIC and driving licence.

### Required rules

- Require an authenticated `users.role = 'customer'` session.
- Resolve exactly one `customers` row through the unique `customers.user_id`.
- Require `users.status = 'active'` and
  `customers.verification_status = 'approved'` before booking creation.
- Profile updates may change only explicitly allowed Customer fields.
- Verification status is Admin-owned and never accepted from Customer input.

## 2. Vehicle catalogue contract

Customer catalogue queries read `vehicles` joined to `vehicle_owners` and may
read aggregate review values. A bookable catalogue row must have:

- `vehicles.verification_status = 'approved'`
- `vehicles.status = 'available'`
- An approved owner profile
- A positive server-read daily price

Customer never writes `vehicles.owner_id`, vehicle status, verification status,
or price. Location filters require the proposed `district` and
`pickup_location` columns.

## 3. Vehicle image contract

`vehicle_documents.file_path` may contain private registration or insurance
documents. It must not be exposed as a catalogue image.

The proposed `vehicle_images` table stores relative public image paths,
alternative text, primary-image choice, order and creation time. Owner owns
image writes. Customer reads images for approved vehicles only. If a vehicle
has no image, the application must use a Customer-owned static fallback asset.

## 4. Vehicle availability contract

No second availability table is required. Availability is computed from:

1. Operational eligibility in `vehicles.status` and
   `vehicles.verification_status`.
2. Absence of an overlapping booking for the requested interval.

With current statuses, blocking bookings are `pending_payment`, `confirmed`,
and `ongoing`. If owner approval is adopted, `pending_approval` also blocks the
vehicle while the request is being decided. `completed`, `cancelled`, and
`rejected` do not block future dates.

The overlap predicate is:

```sql
existing.start_date < :requested_end
AND existing.end_date > :requested_start
```

The later create/update operation must repeat the check inside a transaction.
A normal index improves the query but cannot itself guarantee non-overlap.

## 5. Customer booking contract

### Existing required fields

| Column | Type and rule |
| --- | --- |
| `id` | `INT`, database-generated primary key |
| `customer_id` | Required `customers.id`, derived from session |
| `vehicle_id` | Required approved `vehicles.id`, re-read by server |
| `driver_id` | Optional `drivers.id`; null for self-drive |
| `booking_type` | `self_drive` or `with_driver` |
| `start_date`, `end_date` | Required datetimes; end strictly later |
| `delivery_address` | Optional server-validated address |
| `total_price` | Required server-calculated decimal snapshot |
| `status` | Lifecycle enum; Customer never submits arbitrary status |
| `pickup_status` | Driver operational status; Customer read-only |
| `created_at`, `updated_at` | Database-managed audit timestamps |

### Proposed missing fields

- `cancelled_at DATETIME NULL`
- `cancellation_reason VARCHAR(255) NULL`
- `cancelled_by INT NULL` -> `users.id`

## 6. Booking cancellation contract

Cancellation is an `UPDATE`, never a physical `DELETE`:

```text
status = cancelled
cancelled_at = current database time
cancelled_by = authenticated users.id
cancellation_reason = validated Customer reason
```

Only an owned future booking in `pending_approval`, `pending_payment`, or
`confirmed` may be Customer-cancelled. Payment/refund work, when necessary, is
a separate coordinated transaction. `ongoing`, `completed`, `cancelled`, and
`rejected` rows are immutable to the Customer.

The `RESTRICT` foreign keys from payments, incidents and reviews already make
many booking rows impossible to delete. Application code must still never issue
a normal booking `DELETE`.

## 7. Price calculation inputs

The browser submits only the selected vehicle, booking type and requested
dates. The server reads:

- `vehicles.price_per_day` for self-drive
- `vehicles.price_with_driver_per_day` for with-driver
- Current approved vehicle and owner state
- The chargeable duration calculated from `start_date` and `end_date`

`bookings.total_price` is a snapshot, not trusted input. The available Member 2
context mentions a provisional 28-day minimum and six-month maximum. Those are
application validation rules requiring product/team confirmation; they do not
require new columns.

## 8. Payment and invoice display contract

- Read payments only through an owned booking.
- Customer may submit a method, proof path and transaction reference in a later
  phase, but not `payment_status`, `verified_by`, `verified_at` or failure reason.
- Only Admin verifies or rejects a payment.
- Payment values are `pending`, `completed`, `failed`, and `refunded`.
- Invoice values are `pending`, `paid`, and `cancelled`.
- Invoice totals must be derived server-side and checked against the booking and
  payment; Customer does not submit fee totals.

## 9. Review, incident, notification and chat contracts

### Reviews

- Require an owned `bookings.status = 'completed'` row.
- `customer_id`, driver and vehicle come from that booking, not the form.
- Ratings are nullable individually but, when present, must be 1-5.
- Limit one review row per booking/customer after coordinator approval.

### Incidents

- Require an owned `bookings.status = 'ongoing'` row.
- `reported_by` is authenticated `users.id`.
- Customer creates the report; Admin controls investigation/resolution state.

### Notifications

- Query by authenticated `users.id`.
- Mark-read updates include both notification ID and session user ID.
- `related_id` is contextual, not a foreign key; code must interpret it only
  together with `notification_type` and recheck ownership.

### Chat

- Require the session user to exist in `chat_participants` for the room.
- For booking rooms, also verify the room booking belongs to the Customer.
- `sender_id` always comes from the session.

## Booking status and business rules

### Existing status mapping

| Existing value | Meaning |
| --- | --- |
| `pending_payment` | Owner-approved booking awaiting payment/admin verification |
| `confirmed` | Booking accepted for fulfilment |
| `ongoing` | Active rental (`active` in business wording) |
| `completed` | Rental finished and eligible for review |
| `cancelled` | Soft-cancelled history row |

The existing default immediately creates `pending_payment`, so it cannot record
the documented owner-approval step. The minimal proposal adds
`pending_approval` and `rejected` without renaming existing values.

### Proposed lifecycle

```text
pending_approval -> rejected
pending_approval -> pending_payment -> confirmed -> ongoing -> completed
pending_approval | pending_payment | confirmed -> cancelled
```

### Required application rules

1. Resolve Customer ID only from the authenticated session.
2. Reject unverified/inactive customers.
3. Reject unavailable, unapproved, or unpriced vehicles and unapproved owners.
4. Require end date/time later than start date/time.
5. Prevent overlaps for blocking booking states.
6. Calculate all prices on the server.
7. Ignore browser-submitted Customer, owner, vehicle-state, status and price data.
8. Scope every read/detail query to the session Customer.
9. Allow rental-term updates only for owned future `pending_approval` or
   `pending_payment` rows; revalidate availability and price.
10. Do not edit active, completed, cancelled or rejected bookings.
11. Preserve history through cancellation; never permanently delete bookings.
12. Record cancellation time, actor and reason after schema approval.
13. Use transactions for overlap checking, booking writes, invoice creation and
    related multi-step changes.

## Integrity and deletion audit

- No incompatible FK types were found; relevant keys are all `INT`.
- `bookings` references Customer and vehicle with `ON DELETE RESTRICT`, which
  supports history preservation.
- Driver deletion sets `bookings.driver_id` to null, preserving the booking.
- Deleting a user cascades to its Customer profile, but existing restricted
  bookings should block that cascade. Account suspension is safer than deletion.
- Reviews cascade when a Customer profile is deleted. This is a shared retention
  decision and should not be changed by Customer alone.
- `chat_rooms.booking_id` uses `SET NULL`, so a physically deleted booking would
  orphan the booking context. This reinforces the no-delete rule.
- `pickup_tracking` cascades with booking deletion. Again, normal bookings must
  not be physically deleted.
- `vehicle_inspections` and `driver_vehicle_checks` overlap conceptually.
  Customer should read `vehicle_inspections`; Driver owns safety checks.
- `vehicles.status` is operational availability; booking date overlap is
  scheduled availability. They are related but not duplicate tables.

## Database Coordinator Actions Required

### 1. Reconcile the live Customer schema with `origin/develop` - required

- **Exact objects:** `invoices`; `payments.verified_by`, `verified_at`,
  `failure_reason`; `chat_messages.is_read`; `notifications.notification_type`,
  `related_id`.
- **Reason:** These exist in the live Customer schema but not in the current
  integration baseline.
- **Customer dependency:** Payment/invoice display, Admin payment verification,
  typed notifications and unread chat state.
- **Other effects:** Admin payment workflow and shared notification/chat code.
- **Safe order:** Review existing data, approve each addition, apply it to the
  integration schema, then update consuming models.
- **Verification:** Query `information_schema.tables` and
  `information_schema.columns` for the listed objects.

### 2. Add owner-approval booking states - required for documented flow

- **Exact object:** `bookings.status` enum; add `pending_approval`, `rejected`,
  and make `pending_approval` the default.
- **Reason:** The current default skips owner approval.
- **Customer dependency:** Booking creation and honest status display.
- **Other effects:** Owner must approve/reject; Admin must block payment before
  `pending_payment`; Driver should not receive unconfirmed work.
- **Safe order:** Inventory status values, extend the enum, deploy Owner and
  Customer transition checks, then use the new default.
- **Verification:** `SHOW COLUMNS FROM bookings LIKE 'status';`

### 3. Add cancellation audit fields - required for auditable cancellation

- **Exact objects:** `bookings.cancelled_at`, `cancellation_reason`,
  `cancelled_by`, index and FK to `users.id` with `ON DELETE SET NULL`.
- **Reason:** `updated_at` alone cannot explain who cancelled or why.
- **Customer dependency:** Safe cancellation and booking-history details.
- **Other effects:** Admin/Owner reports may display cancellation data.
- **Safe order:** Add nullable columns, add index/FK, deploy cancellation code.
- **Verification:** `SHOW CREATE TABLE bookings;`

### 4. Add vehicle location and public images - required for catalogue prototype

- **Exact objects:** `vehicles.district`, `vehicles.pickup_location`, and new
  `vehicle_images` table from the proposal.
- **Reason:** The current schema cannot represent public catalogue images or
  location filters without misusing private documents.
- **Customer dependency:** Vehicle cards, details, location filters and gallery.
- **Other effects:** Owner must maintain these values; Admin may moderate them.
- **Safe order:** Add nullable location columns, create image table, let Owner
  populate data, then enable Customer filters/gallery.
- **Verification:** `SHOW CREATE TABLE vehicles;` and
  `SHOW CREATE TABLE vehicle_images;`

### 5. Add booking and catalogue indexes - recommended

- **Exact objects:** `idx_bookings_vehicle_period`,
  `idx_bookings_driver_period`, `idx_bookings_customer_status_date`, and
  `idx_vehicles_catalogue`.
- **Reason:** Current single-column indexes are weak for availability, history
  and catalogue filtering.
- **Customer dependency:** Vehicle availability and booking pages.
- **Other effects:** Faster reads; small additional write/storage cost.
- **Safe order:** Check for existing names, add during low activity, inspect
  query plans.
- **Verification:** `SHOW INDEX FROM bookings;` and `SHOW INDEX FROM vehicles;`

### 6. Enforce one review per owned booking - recommended

- **Exact object:** unique key `uq_reviews_booking_customer` on
  `ratings_reviews(booking_id, customer_id)`.
- **Reason:** Current schema permits repeated reviews for the same booking.
- **Customer dependency:** Review submission eligibility.
- **Other effects:** Admin review moderation must handle pre-existing duplicates.
- **Safe order:** Find and resolve duplicates, then add the unique key.
- **Verification:**
  `SELECT booking_id, customer_id, COUNT(*) FROM ratings_reviews GROUP BY booking_id, customer_id HAVING COUNT(*) > 1;`

### 7. Adopt the authoritative PDO configuration - required before SQL testing

- **Exact object:** shared `app/config/database.php` from `origin/develop`, using
  `127.0.0.1;port=3308` without loading the duplicate core Database class.
- **Reason:** Current Customer database calls otherwise fail before PDO connects.
- **Customer dependency:** Every later database-backed feature and seed test.
- **Other effects:** All modules using the shared Database helper.
- **Safe order:** Team-approved baseline reconciliation, start XAMPP MySQL on
  3308, test one read-only connection, then test proposals in a disposable DB.
- **Verification:** `SELECT DATABASE(), @@port;`

## Review-only SQL files and execution order

1. Database coordinator reviews the current/develop schema difference.
2. Back up or create a disposable test database.
3. Apply only approved statements from `sql/customer_schema_proposal.sql`.
4. Coordinate fake prerequisite accounts described in the dummy script.
5. Run `sql/customer_dummy_data.sql` only in the approved test database.
6. Run the verification queries at the end of the dummy script.
7. Run the dummy script a second time and confirm record counts do not grow.

No Phase 2 SQL was executed while producing this contract.
