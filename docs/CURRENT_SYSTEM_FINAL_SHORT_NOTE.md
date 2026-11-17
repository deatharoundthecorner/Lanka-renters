# Lanka Renters - Current System Final Short Note

## 1. Overview

Lanka Renters is a PHP/MariaDB vehicle-rental application with Guest, Customer, Vehicle Owner, Driver, and Admin workspaces. The current interim scenario contains one real Owner vehicle, one Customer booking, one completed payment, and a Customer/Owner booking conversation.

## 2. Technologies

- HTML
- CSS
- Vanilla JavaScript
- PHP
- MariaDB 10.4.32

Runtime database: `127.0.0.1:3308`, `lanka_renters`.

## 3. Architecture

The application uses direct PHP public pages, role-specific controllers/models, shared helpers, and a layered CSS shell. Customer uses a newer portal layout; Owner, Driver, and Admin retain direct-page patterns while consuming the shared authenticated visual contract.

## 4. Main Folders

- `app/controllers/`
- `app/models/`
- `app/helpers/`
- `app/config/`
- `database/`
- `public/`
- `docs/`
- `storage/`

## 5. Authentication

- `public/login.php`
- `public/register.php`
- `public/logout.php`
- `app/controllers/AuthController.php`
- `app/helpers/AuthHelper.php`
- `app/models/User.php`

Passwords use PHP `password_hash()`/`password_verify()`. Sessions are role-scoped and state-changing requests use CSRF tokens.

## 6. Guest

- `public/index.php`
- `public/vehicles.php`
- `public/vehicle-details.php`
- `public/assets/css/lr-public.css`
- `public/assets/css/lr-auth.css`

Guest pages use real vehicle queries and truthful empty states.

## 7. Customer

Customer routes live under `public/customer/`. Features include dashboard, vehicle search/details, bookings, payment evidence, verification/profile, inspections, reviews, incidents, notifications, and booking-scoped Messages. Driver Change and some replacement/return flows remain partial or session-only demo workflows.

## 8. Owner

Owner routes live under `public/owner/`. Features include dashboard, vehicle CRUD, Driver connection, bookings, inspections, replacement requests, Messages, earnings, profile, and notifications.

## 9. Driver

Driver routes live under `public/driver/`. Features include dashboard, profile/security, documents, verification-derived status, availability, leave CRUD, safety checks, trips, assigned vehicles, incidents, Messages, notifications, performance, and earnings.

## 10. Admin

Admin routes live under `public/admin/`. Features include user CRUD, Customer/Owner/Driver/Vehicle verification, booking monitoring, payment review, incidents, replacements, settlements, reports, email logs, and announcements. Admin has no blanket private chat access.

## 11. Interim CRUDs

- Driver Leave: `public/driver/leave.php` -> `DriverController` -> `DriverLeave` -> `driver_leaves`.
- Owner Vehicle: `public/owner/vehicles.php` -> `VehicleController` -> `Vehicle` -> `vehicles`.
- Customer Reviews: `public/customer/reviews/` -> `CustomerPortalController` -> `CustomerPortal` -> `ratings_reviews`.
- Admin Users: `public/admin/users.php`/`user_handler.php` -> `AdminUserController` -> `User` -> `users` and role profiles.

All four have current Create/Read/Update/Delete paths and prior verified regression evidence.

## 12. Booking Lifecycle

Booking statuses are `pending_payment`, `confirmed`, `ongoing`, `completed`, and `cancelled`. Pickup tracking is separate: `pending_pickup`, `dispatched`, `arrived`, `picked_up`, `dropped_off`.

## 13. Payment Lifecycle

Customer submits bank-transfer evidence through `CustomerPaymentController`; payment starts `pending`. Admin approves/rejects through `payment_verification.php`. The retained presentation booking is confirmed through this normal path.

## 14. Driver Verification

Driver verification is derived from three document types: NIC, driving licence, and police report. Missing/pending/rejected documents prevent the derived approved state.

## 15. Owner-Driver Connection

`driver_owner_links` stores `pending`, `accepted`, `rejected`, and `blocked` relationships. The retained seeded Owner-Driver relationship is already accepted.

## 16. Incidents and Replacements

Incident records are database-backed for eligible owned/assigned bookings. Replacement requests are database-backed for Owner/Admin flows. Customer replacement/refund previews and Driver Change remain partial/demo-safe.

## 17. Inspections

`vehicle_inspections` stores inspection records linked to vehicles/bookings and users. Customer and Owner views are read-oriented; Driver safety checks are operational.

## 18. Chat

Chat uses `chat_rooms`, `chat_participants`, and `chat_messages`. Customer and Owner chat was exercised for the retained booking. The Driver was not assigned because current verification/eligibility did not expose the Driver in the booking form. Admin has no blanket access.

## 19. Notifications

`public/includes/notification-dropdown.php`, `public/api/notifications.php`, `public/assets/js/lr-notifications.js`, and `app/models/Notification.php` provide user-scoped bell, polling, unread state, mark-read, and mark-all behavior.

## 20. Email Notifications

`app/helpers/MailHelper.php` provides native `mail()` capability detection and safe `email_logs` logging. No external mail transport is installed or assumed; event wiring remains partial when transport/deduplication cannot be proven.

## 21. PDF/Reports

`app/helpers/SimplePdf.php` is a deliberately limited built-in PDF generator. `public/admin/report-pdf.php` generates authorized Admin payment and trip reports from current records.

## 22. Admin Live Analytics

`public/admin/data/dashboard-live.php` returns authorized real metrics. Admin dashboard canvas charts use vanilla JavaScript and refresh every 15 seconds. Empty data produces empty-state text rather than fabricated bars.

## 23. Security

Role guards, session-derived ownership, prepared statements, CSRF validation, POST-only mutations, foreign-key protections, and chat participant checks are active.

## 24. Important Tables

`users`, `customers`, `vehicle_owners`, `drivers`, `driver_documents`, `driver_owner_links`, `vehicles`, `vehicle_documents`, `bookings`, `vehicle_assignments`, `vehicle_inspections`, `payments`, `incidents`, `replacement_requests`, `ratings_reviews`, `chat_rooms`, `chat_participants`, `chat_messages`, `notifications`, `pickup_tracking`, `email_logs`.

## 25. Shared CSS/JS

- `public/assets/css/lr-tokens.css`
- `public/assets/css/lr-shell.css`
- `public/assets/css/lr-components.css`
- `public/assets/css/lr-notifications.css`
- `public/assets/js/lr-notifications.js`
- `public/customer/assets/css/customer-canonical.css`
- `public/admin/assets/css/admin-canonical.css`
- `public/owner/includes/assets/css/owner-canonical.css`

## 26. Known Deferred Features

Chat participant formation remains timing-dependent. Driver Change is session-only. Return and Customer replacement/refund workflows are partial. Native email transport is not configured/verified. No Inspector role or external inspection institution exists. No Admin support-chat backend exists.

## 27. Exact Enhancement Files

- `app/helpers/SimplePdf.php`
- `app/helpers/MailHelper.php`
- `public/admin/report-pdf.php`
- `public/admin/data/dashboard-live.php`
- `public/admin/reports.php`
- `public/admin/dashboard.php`
