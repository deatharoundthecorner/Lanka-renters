# Lanka Renters - Current System Logic Forensic Audit

Date: 2026-08-18
Scope: Current source code and current declared schema. Read-only forensic analysis plus the separately authorized Login/Register presentation work. No backend, route, auth, schema, data, or workflow logic was changed.

## Executive Summary

The application is a PHP application with no framework. It is not architecturally uniform:

- public pages often act as controllers, handling session gates, POST actions, model calls, and HTML directly;
- some flows use dedicated controllers and models;
- the newer Customer portal uses a more consistent controller/model/layout pattern;
- Driver and Owner retain older direct-page patterns with shared shell includes;
- Admin pages use direct pages, Admin-specific models, and POST handler endpoints;
- `app/core/Controller.php` and `app/core/Model.php` exist, but most legacy flows do not inherit from them.

The live development target remains `127.0.0.1:3308`, database `lanka_renters`, via `app/config/database.php` and `app/helpers/Database.php`. No database configuration was changed in this phase.

The current database recently contained four role users but no booking/chat data. Therefore booking-dependent features may render empty states or cannot be runtime-tested with a legitimate record. This is a data-state fact, not proof that every corresponding code path is broken.

## Status Vocabulary Used

- **IMPLEMENTED**: current server-side operation exists and is connected to current schema.
- **PARTIAL**: some server-side behavior exists, but the workflow is incomplete, inconsistent, or missing required stages.
- **DEMO-SAFE**: explicitly session-only/demo behavior, labelled as such by the application.
- **DISABLED**: visible control is deliberately unavailable or routed away.
- **NOT IMPLEMENTED**: no current functional path found.
- **NOT TESTABLE DUE TO DATA**: code path exists, but current data has no legitimate record to exercise it.
- **VIEW ONLY**: reads current records but exposes no mutation in that feature.

# 1. Architecture Map

## Entry points and page families

- Guest/public: `public/index.php`, `public/vehicles.php`, `public/vehicle-details.php`, `public/login.php`, `public/register.php`, `public/logout.php`.
- Customer: `public/customer/_bootstrap.php`, `dashboard.php`, nested `bookings/`, `vehicles/`, `payments/`, `verification/`, `chat/`, `incidents/`, `driver-change/`, `return/`, `reviews/`, `notifications/`, `profile/` pages.
- Owner: direct pages under `public/owner/`, including `dashboard.php`, `vehicles.php`, `drivers.php`, `bookings.php`, `inspection.php`, `replacement-requests.php`, `chat.php`, `earnings.php`, `profile.php`, plus shared `includes/`.
- Driver: direct pages under `public/driver/`, including dashboard, profile, security, documents, owners, availability, leave, safety, trips, vehicles, incident reporting, messages/chat, notifications, performance, and earnings.
- Admin: direct pages under `public/admin/`, shared `partials/`, and dedicated POST endpoints such as `user_handler.php`, `customer_verification.php`, `driver_verification.php`, `owner_verification.php`, `vehicle_verification.php`, `payment_verification.php`, and `settlement_handler.php`.

## Application layers actually used

- Database: `app/config/database.php`, `app/helpers/Database.php`, plus a separate Admin connection helper at `public/admin/config/database.php` used by Admin pages.
- Auth/session/CSRF: `app/helpers/AuthHelper.php`, `app/helpers/CustomerCsrf.php`.
- Controllers: `app/controllers/` contains Auth, Customer, Customer booking/payment/portal, Driver, Owner, Admin, Chat, and Public Vehicle controllers.
- Models: `app/models/` contains domain models for users, profiles, vehicles, bookings, payment, notification, chat, incidents, reviews, inspections, driver operations, links, and Admin verification.
- Shared UI: Customer layout under `public/customer/components/layout/`; Owner/Driver includes under `public/owner/includes/` and `public/driver/includes/`; Admin partials under `public/admin/partials/`; notification dropdown under `public/includes/notification-dropdown.php`.
- JavaScript: Customer workflow scripts under `public/customer/assets/js/`; shared notification polling under `public/assets/js/lr-notifications.js`; Driver/Owner/Admin shell scripts are mostly inline or role-specific assets.

## Architectural conflicts

- Customer has a newer controller/model/layout foundation beside legacy Customer-style components and older `app/models/Customer.php` methods.
- Driver and Owner pages frequently call controllers but perform request dispatch and rendering directly in the page.
- Admin uses direct SQL in `public/admin/dashboard.php` and Admin models elsewhere.
- Shared shell CSS is layered over portal-specific CSS rather than replacing the older selectors.
- Some page links point at redirect shims or unavailable feature routes.

Risk: **INTERIM RISK**, **FINAL-PROJECT RISK** medium/high because business rules are duplicated between page/controller/model layers.

# 2. Authentication Trace

## Login

`public/login.php`:

1. Requires `AuthController.php` and `AuthHelper.php`.
2. Starts the PHP session.
3. If already logged in, redirects by session role to Admin, Owner, Driver, or Customer dashboard.
4. On POST, reads `csrf_token`, `email`, and `password`.
5. Validates CSRF through `AuthHelper::validateCsrfToken()`.
6. Calls `AuthController::login($email, $password)`.
7. `AuthController` requires non-empty email/password, finds the user by exact email through `User::findByEmail()`, verifies with `password_verify()`, rejects any status other than `active`, then calls `AuthHelper::login()`.
8. `AuthHelper::login()` regenerates the session ID and stores the full user row in `$_SESSION['user']`.
9. Role redirect is performed by `AuthController::redirectByRole()`; Customer may use the `redirect` query value if it passes the page's relative-or-matching-host test.

No remember-me, forgot-password, or password-visibility feature exists in current login markup or backend.

## Registration

`public/register.php` uses the same session/start/already-logged-in redirect pattern. On POST it validates CSRF, then passes the full POST array to `AuthController::register()`.

Accepted fields are exactly:

- `name`
- `email`
- `phone`
- `password`
- `role`
- hidden `csrf_token`

All five visible fields are required by `AuthController`. Public roles are exactly `customer`, `owner`, and `driver`; Admin is rejected as an invalid registration role and has no public registration option.

Registration behavior:

- duplicate email is checked with `User::findByEmail()` and returns `An account with this email already exists.`;
- user is inserted with status `active`;
- `User::create()` hashes using `password_hash($password, PASSWORD_DEFAULT)`;
- a transaction creates the role profile;
- Customer profile: `customers`, `verification_status = pending`;
- Owner profile: `vehicle_owners`, schema default `verification_status = pending`;
- Driver profile: `drivers`, model default `availability_status = off_duty`, rating default from the model/schema path;
- transaction rolls back on failure;
- successful registration logs the new user in and redirects by role.

There is no separate public Admin registration flow.

## Logout and protected access

- Public `logout.php` starts session, calls `AuthHelper::logout()`, redirects to `login.php`.
- Owner logout only destroys the session for POST plus valid CSRF; otherwise it still redirects to login.
- Driver sidebar logout posts to `dashboard.php` with CSRF and action `logout`.
- Customer logout posts to `customer/logout.php` with `CustomerCsrf` token.
- `AuthHelper::requireRole()` requires a session and redirects a wrong-role user to that user's own dashboard. Missing/invalid role logs out and redirects to login.

# 3. Role and Permission Matrix

| Role | Current access pattern | Server enforcement | Current notable limits |
| --- | --- | --- | --- |
| Guest | Public home, vehicle list/details, login, register | Public pages only; protected pages redirect | Cannot use role workspaces or booking actions |
| Customer | Customer portal, owned booking/vehicle/payment/incident/review/profile areas | `_bootstrap.php` requires role; Customer models resolve from session user; CSRF on POST | Verification is Admin-owned; some workflows are demo/session-only or unavailable |
| Vehicle Owner | Owner dashboard, own vehicles, own bookings, own inspections/replacements, driver links, chat, earnings, profile | `requireRole('owner')`; controllers resolve Owner by session user; many queries constrain owner ID | Owner chat enrollment is GET-triggered; some legacy page handlers do not use a uniform CSRF boundary |
| Driver | Driver dashboard, own profile/security/docs/availability/leave/safety/trips/vehicles/incidents/messages/notifications/performance/earnings | `requireRole('driver')`; DriverController resolves Driver by session user; mutations generally validate CSRF | Legacy direct pages and controller methods duplicate checks; booking data currently absent |
| Admin | Admin dashboard and listed review/management pages | `requireRole('admin')`; Admin POST endpoints validate CSRF and model-specific IDs/statuses | No Admin chat page or blanket private chat access found |

UI-only labels are not treated as permissions. Server-side controller/model predicates are authoritative.

# 4. Customer Logic Trace

## Dashboard

`CustomerPortalController::dashboardPage()` calls `CustomerPortal::getDashboard()` for the session user, returning verification status, booking counts, recent booking, latest payment, unread notifications, progress, reminders, and quick links. Real DB-backed display. Empty current data produces zero/empty records. A legacy `CustomerController::dashboard()` also contains static Phase 3 display data; this is duplicate/legacy logic and is not the active `public/customer/dashboard.php` path.

## Verification and profile

- Read/update: `CustomerPortalController::profilePage()` and `verificationPage()`; `CustomerPortal` resolves context by session user.
- Customer may update permitted contact/identity fields through `profile/index.php` and verification page. Customer verification status is not customer-editable.
- Customer model `Customer::update()` permits only `nic_number` and `driving_license_number` by Customer profile ID.
- Profile controller path also updates permitted user/contact fields through session-scoped methods.
- Statuses: `pending`, `approved`, `rejected`; Admin models perform approval/rejection.
- Classification: **IMPLEMENTED**, with duplicate Customer patterns.

## Search Vehicles and details

- `CustomerController::vehicleCatalogue()` calls `CustomerVehicle::countVehicles()` and `searchVehicles()` with validated server-side filters, sorting, and pagination.
- `CustomerController::vehicleDetails()` calls `findVisibleVehicleById()` and `customerVerificationStatus()`.
- Legacy public `vehicles.php`/`vehicle-details.php` use `PublicVehicleController` and separately query approved/available public listings.
- Vehicle visibility is server-filtered by approved/available conditions in the respective models/controllers.
- Classification: **IMPLEMENTED**, but duplicate public/Customer catalogue paths exist.

## Bookings

- Pages: `public/customer/bookings/index.php`, `create.php`, `edit.php`, `details.php`, `cancel.php`, `history.php`.
- Controller: `CustomerBookingController`.
- Model: `CustomerBooking` plus `CustomerVehicle`/`CustomerPortal` dependencies.
- Read queries constrain booking through Customer profile/session user.
- Create validates vehicle, Customer ownership context, booking type, dates, duration, availability, and price through controller/model code; exact rules are listed in Section 8.
- Edit is restricted by server rules to the current Customer's future `pending_payment` booking; details explicitly says other statuses are not editable.
- Cancel is POST-only and controller-enforced; ownership and allowed state are checked server-side.
- Tables: `bookings`, `customers`, `vehicles`, `drivers`, and related payment/inspection records when displayed.
- Classification: **IMPLEMENTED/PARTIAL**, because lifecycle transition coverage is distributed across multiple paths and current DB has no booking rows.

## Inspection

`CustomerPortalController::inspectionIndexPage()` reads authorized Customer booking inspection aggregates from `CustomerPortal`; page is read-only. No Customer inspection create/update/delete path found. Classification: **VIEW ONLY/NOT TESTABLE DUE TO DATA**.

## Payments

- Pages: `public/customer/payments/`, `payment.php`.
- Controller: `CustomerPaymentController`.
- Model: `CustomerPayment`.
- Customer can read own payment records and submit payment evidence for eligible owned bookings; POST is CSRF-protected and ownership is session-derived.
- Payment evidence is stored in `payments` with `payment_slip_path`, method, amount/reference, and status.
- Admin review is separate. Classification: **IMPLEMENTED/PARTIAL**; invoice behavior is present in model/page paths but not all lifecycle claims are connected uniformly.

## Chat

Current truth is documented in `docs/CHAT_RUNTIME_AUDIT.md` and remains unchanged:

- Customer page/controller/model: `public/customer/chat/index.php`, `CustomerPortalController::chatPage()`, `CustomerPortal::getChatRooms()`, `getChatEligibleBookings()`, `ensureChatRoom()`, `findOwnedChatRoom()`, `getChatMessages()`, `sendChatMessage()`.
- Eligible statuses: `pending_payment`, `confirmed`, `ongoing`.
- Customer room creation validates session Customer ownership and adds Customer plus assigned Driver, or Owner when no Driver is assigned.
- Read/send require owned booking plus participant membership; send also requires eligible status and Customer CSRF.
- Current live DB had zero bookings, rooms, participants, and messages, so full runtime chat was not testable.
- Participant formation is timing-dependent across Customer/Owner/Driver paths.
- Admin has no blanket private-chat route or enrollment path.

Classification: **PARTIAL/NOT TESTABLE DUE TO DATA**.

## Incidents

- `CustomerPortalController::incidentsPage()`, `incidentCreatePage()`, `incidentDetailsPage()`.
- Real DB incident create/read for Customer-owned ongoing bookings; server constrains Customer ownership and `reported_by`.
- Incident photos, maps, replacement/refund choices shown on `public/customer/incidents/index.php` are disabled final-release controls.
- Replacement decisions in incident detail can be session-only demo data through `CustomerDemoData` when demo mode is enabled.
- Classification: incident report **IMPLEMENTED**; photo/map/replacement/refund UI **DISABLED/DEMO-SAFE**.

## Driver Change

`public/customer/driver-change/` explicitly states there is no approved Driver-change table. Requests are stored in the signed-in PHP session through `CustomerDemoData`; demo mode defaults enabled and can be disabled by environment setting. Eligible booking display is DB-backed, but request records are **SESSION-ONLY DEMO**.

## Return

Return pages use the Customer portal shell and current booking data. The return/inspection flow is partial; no complete approved Customer return state machine or dedicated return table exists in the declared schema. Classification: **PARTIAL/VIEW ONLY** where no mutation path is present.

## Reviews

- Pages: `public/customer/reviews/index.php`, `reviews.php`, details/create paths.
- Controller/model: Customer portal/review methods and `Review` model.
- Reads constrain reviews to the session Customer; create requires an eligible Customer booking and prevents duplicate review according to model checks.
- Updates/deletes are not uniformly available; the interim verified CRUD is Customer Reviews & Ratings.
- Table: `ratings_reviews`.
- Classification: **IMPLEMENTED** for the verified path; current database emptiness makes mutation runtime testing data-dependent.

## Notifications

Customer notification list/read operations are DB-backed and session-user scoped. Shared dropdown uses `public/includes/notification-dropdown.php`, `public/assets/js/lr-notifications.js`, and the notification endpoint/API. See Section 14.

# 5. Owner Logic Trace

- Dashboard: direct SQL in `public/owner/dashboard.php`; counts own vehicles/verified vehicles/accepted drivers/own pending bookings/completed earnings and recent own bookings.
- Vehicles: `VehicleController` + `Vehicle` model; create/update/status/deactivate/delete and documents/images; owner is resolved from session, CSRF is handled in controller/page path, and deletion is blocked by dependent booking/history checks.
- Drivers: `OwnerDriverController` + `DriverOwnerLink`; owner creates pending link to an eligible verified/available Driver, duplicate accepted/pending links rejected, Driver responds through Driver page. Owner ownership is session-derived.
- Bookings: `OwnerBookingController`; reads own vehicle bookings, summaries, and permitted booking actions; status and owner vehicle predicates are applied in model/controller.
- Inspections: `OwnerInspectionController`; read-only own-fleet inspection records and filters.
- Replacement requests: `OwnerReplacementController`; reads requests related to Owner vehicles/incidents and can update permitted request statuses with CSRF and owner checks.
- Chat: `OwnerChatController` + `Chat`; GET auto-enrolls Owner for bookings of Owner-owned vehicles, then reads/sends only as participant. This is a GET state mutation and does not guarantee the Customer/Driver participant set.
- Earnings: `OwnerEarningsController`/`Settlement`/payment queries; reads completed/settlement records for Owner-owned vehicles.
- Profile: `ProfileController`; updates Owner profile fields with session Owner resolution and CSRF path.
- Notifications: shared dropdown/API and role/user scoping; Owner actions generate some notifications through model/controller calls, not every visible action.

Owner classification overall: **IMPLEMENTED/PARTIAL** with direct-page/legacy duplication and GET enrollment risk.

# 6. Driver Logic Trace

- Dashboard: `DriverController::dashboard()` resolves Driver from session, aggregates availability, rating, active vehicle assignments, document-derived verification, connected owners, pending links, monthly earnings, performance, trips, and notifications.
- Profile: `DriverController::updateProfile()` and `deactivateProfile()`; profile is session-resolved, CSRF checked in page, deactivation logs out when accepted.
- Account & Security: `updateAccount()` creates `account_change_requests`; `changePassword()` handles password change path; Admin approval model exists for account changes.
- Documents: `DriverController::uploadDocument()`, `viewDocuments()`, `deleteDocument()`; document types `nic`, `driving_license`, `police_report`, file extension/MIME/5MB checks in page, current/versioned records in `driver_documents`, pending-only deletion and Admin verification.
- Connected Owners: DriverOwnerLink methods list accepted/pending links, Driver can accept/reject eligible Owner requests; link ownership is session-derived.
- Availability: only `available`, `busy`, `off_duty`; update is CSRF-protected and Driver-scoped.
- Leave: `DriverLeave` CRUD for own Driver; create/update/delete/status predicates include dates and pending/approval restrictions; Admin approval path exists.
- Safety: `VehicleSafetyCheck` CRUD; assigned booking/Driver checks, create/update/delete CSRF; edit restricted to `pending_pickup` in page logic.
- Trips/tracking: `DriverController` reads assigned bookings and updates pickup tracking only for assigned Driver and allowed pickup statuses; table `pickup_tracking`.
- Assigned vehicles: reads active `vehicle_assignments` for authenticated Driver.
- Incident reporting: `DriverIncident` create/read for assigned bookings, optional photo path in page; CSRF and Driver assignment checks.
- Messages/chat: `ChatController` lists participant rooms; Driver creates a room only for booking assigned to that Driver and enrolls Driver + Customer; room read/send requires participant membership and CSRF. Current data had no bookings/rooms.
- Notifications: reads own notifications, marks via notification model/API.
- Performance: `DriverPerformance` aggregates completed/cancelled trips, hours, ratings/reviews.
- Earnings: `DriverPayment` reads payment/earnings records related to Driver trips.

Driver classification overall: **IMPLEMENTED/PARTIAL**, with strong session/assignment checks in controllers but legacy direct-page rendering and duplicate status logic.

# 7. Admin Logic Trace

Admin pages and models currently provide:

- Dashboard: direct SQL counts for users, owners, drivers, vehicles, pending Customer/vehicle/payment items, active incidents, bookings, commission, and pending payment amount.
- Users: `AdminUserController` + `User`; create/list/find/update/delete users, role/status validation, role-profile create/delete, self-admin protection, CSRF in `user_handler.php`.
- Vehicle Owners: `AdminOwnerVerification`; list pending/reviewed owners; approve/reject via `owner_verification.php` with CSRF.
- Drivers: `AdminDriverVerification`; list pending/registered, approve/reject driver documents/profile via CSRF endpoint.
- Vehicles: `AdminVehicleVerification`; approve/reject vehicle status through CSRF endpoint; listing page reads vehicles.
- Bookings: `AdminBookingMonitoring`; read/filter all booking records, no booking mutation found in page.
- Payments: `AdminPaymentVerification`; read pending/reviewed, approve/reject payment evidence via CSRF endpoint.
- Incidents: `AdminIncidentManagement`; read/filter incidents and update permitted incident state/remarks through Admin endpoint.
- Replacement Requests: Admin model/page reads and updates approved request states according to model/status rules.
- Settlements: `Settlement`; reads completed booking calculations and marks payment through handler with CSRF; commission is calculated as 10% in current code.
- Reports: `Report`; read-only financial, booking-status, and vehicle-category summaries.
- Email Logs: `EmailLog`; read-only audit records.
- Announcements: `Announcement`; Admin can create/update/delete/list announcements through page/handler and CSRF path.

Admin does not have a chat page, automatic chat participant path, or blanket private-chat transcript query. Admin access is not assumed beyond explicit page/model guards.

# 8. Booking System: Actual Statuses and Lifecycle

Schema booking status enum:

- `pending_payment`
- `confirmed`
- `ongoing`
- `completed`
- `cancelled`

Pickup tracking status is separate:

- `pending_pickup`
- `dispatched`
- `arrived`
- `picked_up`
- `dropped_off`

Actual references include Customer booking create/edit/cancel, Owner dashboards/bookings, Admin filters/reports, Driver trips, chat eligibility, incident eligibility, and payment/review logic.

| Status | Current source evidence | Enters/leaves | Role/action |
| --- | --- | --- | --- |
| `pending_payment` | Booking schema/default; Customer create; Owner/Admin lists; chat eligibility | Created by Customer booking creation. Customer edit is allowed here. Payment evidence may move the booking through payment review code. | Customer creates/edits; Admin payment review participates in transition |
| `confirmed` | Customer/Owner/Admin lists; chat eligibility; Driver trips; driver-change eligibility | Payment approval/booking confirmation path in booking/payment controllers. Can move to ongoing, cancelled, or remain confirmed. | Admin/payment/Owner/booking paths depending exact controller action |
| `ongoing` | Customer dashboard/details/chat/incidents; Owner/Driver/Admin pages | Pickup/trip operation and booking controller paths move a confirmed rental into active state. | Driver pickup/trip and booking workflow |
| `completed` | Owner earnings/dashboard, Admin reports/settlements, Driver performance/earnings, Customer history/reviews | Completion occurs through trip/booking completion code; not all pages expose the same transition. | Driver/booking workflow |
| `cancelled` | Customer cancel, Owner/Admin displays, reports | Customer cancellation has server conditions; other cancellation paths exist in model/controller-specific code. | Customer and possibly Owner/admin-specific handlers |

Contradictions/risks:

- Some older pages assume payment approval directly confirms bookings while newer Customer controllers treat `pending_payment` as the edit/chat state.
- Driver `viewTrips()` combines active/completed arrays, while other pages use raw booking status and pickup status independently.
- The current database has no bookings, so transition behavior cannot be runtime-proven now.

Actual date/availability logic found in Customer booking controllers/models includes minimum 28-day and maximum six-calendar-month constraints, exclusive end-date handling, date collision checks against bookings, and vehicle status/verification visibility checks. Customer verification is queried and displayed; where it blocks a booking, the exact controller predicate is authoritative, and UI copy alone is not treated as enforcement. Driver assignment is stored in `bookings.driver_id`; with-driver booking paths require a valid assignment at the applicable stage. Price is calculated from vehicle daily price and rental duration, with separate self-drive/with-driver pricing fields.

# 9. Owner-Driver Relationship

Tables: `driver_owner_links(driver_id, owner_id, status, created_at, accepted_at)` with unique `(driver_id, owner_id)`.

- Owner initiates through `OwnerDriverController::requestDriverConnection()`.
- Driver eligibility is derived from verified/available Drivers.
- Existing `accepted` or `pending` links are rejected as duplicates.
- Link begins `pending`.
- Driver accepts/rejects through Driver link controller/page; status becomes `accepted` or `rejected`.
- `blocked` exists in schema/status vocabulary but no complete UI flow was found.
- Owner and Driver lists constrain by authenticated profile IDs.
- Verification and availability prerequisites are applied when Owner discovers/request Drivers.
- Notification creation is distributed across request/accept paths. The accepted-connection POST can replay on refresh/resubmission and create a duplicate notification in the current source, as documented in prior audits; it remains unfixed.

Classification: **IMPLEMENTED/PARTIAL**. Risk: **INTERIM RISK** medium; **FINAL-PROJECT RISK** high.

# 10. Vehicle Logic

- Owner creates vehicles through `public/owner/vehicles.php` -> `VehicleController::createVehicle()` -> `Vehicle::create()`.
- Owner identity is resolved from session; owner ID is not trusted from ordinary form input.
- Fields follow schema: make/model/year/plate/type/transmission/fuel/seats/prices/status.
- Vehicle documents are stored in `vehicle_documents`; document type and file validations are applied in the Owner page/controller path.
- Owner updates own vehicles through `updateVehicle()` and `Vehicle::update()`.
- Status values: `available`, `rented`, `maintenance`, `unavailable`; verification: `pending`, `approved`, `rejected`.
- Admin approves/rejects verification through `AdminVehicleVerification` endpoint.
- Owner can deactivate and delete own vehicles; delete is guarded against booking/history dependencies in model/controller code.
- Public and Customer visibility requires approved/available predicates in their respective vehicle models/controllers.
- Assigned drivers use `vehicle_assignments`, separate from booking `driver_id`.

Classification: **IMPLEMENTED**, with duplicate public/Customer visibility implementations and mixed file upload handling. Risk: **INTERIM RISK** medium.

# 11. Payments

Customer flow:

- Customer selects an owned eligible booking and submits amount/method/evidence/reference through `CustomerPaymentController`/`CustomerPayment`.
- Evidence path uses `payments.payment_slip_path`; storage also exists under `storage/customer-payment-evidence/`.
- Server checks Customer ownership and permitted booking/payment state; POST uses Customer CSRF.

Payment states from schema/code:

- `pending`
- `completed`
- `failed`
- `refunded`

Admin flow:

- `AdminPaymentVerification` lists pending/reviewed payments.
- `public/admin/payment_verification.php` requires Admin role, POST, CSRF, valid payment ID/status, and calls approve/reject model methods.
- Approval/rejection records review information where model/schema supports it; the exact schema currently has payment status, timestamps, evidence, and reference fields, while some historical docs mention `verified_by`/`verified_at` patterns that are not present in the shown current schema. Current source wins.

Invoice behavior exists through `Invoice.php`/related pages but is not a separate invoice table in the current declared schema; classify as **PARTIAL** where generated/displayed from payment/booking data.

# 12. Verification

## Customer

- Table: `customers.verification_status` = `pending|approved|rejected`.
- Fields: NIC and driving licence numbers.
- Admin approves/rejects through `AdminCustomerVerification` with Admin ID/reason where supported.
- Customer cannot self-change status.
- Actual feature blocking must come from Customer booking/vehicle controller predicates; dashboard wording alone is not enforcement.

## Owner

- Table: `vehicle_owners.verification_status` = `pending|approved|rejected`.
- Admin Owner verification page/model approves/rejects owner records.
- Vehicle creation/listing and public visibility apply their own owner/vehicle predicates; no assumption is made that every Owner page is blocked by status.

## Driver

- No single driver verification column is used as the sole source. `Driver::getDashboardData()` derives status from `driver_documents`: no documents/missing required types/pending => `pending`; any rejected => `rejected`; all three distinct required types approved => `approved`.
- Required document types: NIC, driving licence, police report.
- Admin approves/rejects documents through Admin Driver verification.
- Driver pages display status; controller guards are feature-specific rather than one universal driver verification gate.

## Vehicle

- `vehicles.verification_status` = `pending|approved|rejected`.
- Vehicle documents have separate `verification_status`.
- Admin vehicle verification approves/rejects the vehicle record; public/customer catalogue queries approved records.

# 13. Chat Current Truth

See `docs/CHAT_RUNTIME_AUDIT.md`, verified against current files:

- tables: `chat_rooms`, `chat_participants`, `chat_messages`;
- Customer creates/reuses room after validating Customer-owned eligible booking and enrolls Customer + assigned Driver or Owner;
- Owner GET enrolls Owner for Owner-vehicle bookings;
- Driver creates/reuses room for assigned booking and enrolls Driver + Customer;
- read/send require role-specific session context and participant membership;
- Customer eligible statuses: `pending_payment`, `confirmed`, `ongoing`;
- Owner/Driver sends use role CSRF; Customer uses `CustomerCsrf`;
- unread count/read update uses `is_read` in `app/models/Chat.php`; current declared schema in `database/schema.sql` does not show `is_read` on `chat_messages`, while the live database used in the prior audit did. This is a declared-schema/current-runtime drift risk;
- no Admin chat access path;
- participant formation is timing-dependent;
- current live data had zero bookings/rooms/participants/messages.

Classification: **PARTIAL/NOT TESTABLE DUE TO DATA**. Risk: **INTERIM RISK** high.

# 14. Notifications Current Truth

- Table: `notifications(user_id, title, message, is_read, created_at, notification_type, related_id where declared in current schema continuation)`.
- Model: `app/models/Notification.php`.
- Shared UI: `public/includes/notification-dropdown.php`, `public/assets/css/lr-notifications.css`, `public/assets/js/lr-notifications.js`.
- API/polling endpoint: `public/api/notifications.php` and related endpoint files found under `public/api/`.
- Role pages pass a role/user-specific view-all URL; database queries scope notifications to authenticated user ID.
- Bell/dropdown displays unread count/dot from real notification data, not a blanket fixed claim in the shared component.
- Mark-read and mark-all behavior is implemented where the endpoint/model exposes it; individual notification ownership is checked by user ID.
- Real notification producers include booking events, payment review, verification decisions, incident/replacement actions, driver-owner connection events, and chat-related actions where controllers/models explicitly call Notification methods. Not every visible UI action creates a notification.
- Known issue: accepted driver-owner connection POST can be replayed and create a duplicate notification; current source still retains that risk and it was not fixed.

Classification: **IMPLEMENTED/PARTIAL**. Risk: **INTERIM RISK** medium/high.

# 15. Incident, Replacement, Return, Driver Change

| Feature | Current classification | Evidence |
| --- | --- | --- |
| Customer incident report | REAL DATABASE FUNCTION | Customer controller creates `incidents` for owned ongoing booking |
| Driver incident report | REAL DATABASE FUNCTION/PARTIAL | Driver controller creates assigned-booking incidents; optional photo handling exists |
| Incident photos/maps | DISABLED | Customer page buttons are disabled and labelled final release; schema has `incident_photos`, but approved Customer controls are not connected |
| Replacement requests | REAL DATABASE FUNCTION/PARTIAL | `replacement_requests`, Owner/Admin controllers; Customer replacement choices are not approved live workflow |
| Customer replacement decision | SESSION-ONLY DEMO | `CustomerDemoData`, enabled by default |
| Return | PARTIAL/VIEW ONLY | Customer return/inspection pages exist but no complete dedicated return state/table workflow |
| Driver change request | SESSION-ONLY DEMO | Explicit page message says no approved table; records live in session |
| Admin incident resolution | REAL DATABASE FUNCTION/PARTIAL | Admin model/page handles incident state/remarks |

# 16. Four Interim CRUDs

## Driver Leave Requests

- CREATE: `public/driver/leave.php` -> `DriverController` leave handler -> `DriverLeave::create()` -> `driver_leaves`.
- READ: same page -> DriverController/model list by authenticated Driver -> `driver_leaves`.
- UPDATE: page/controller -> `DriverLeave::update()` for own pending request where allowed -> `driver_leaves`.
- DELETE: page/controller -> `DriverLeave::delete()` for own pending request where allowed -> `driver_leaves`.
- CSRF: page validates `AuthHelper::validateCsrfToken()`.
- Restrictions: Driver ID is session-resolved; date/status rules and pending-only mutation restrictions apply; Admin approval uses `approved_by`.

## Owner Vehicle Management

- CREATE: `public/owner/vehicles.php` -> `VehicleController::createVehicle()` -> `Vehicle::create()` -> `vehicles`, plus `vehicle_documents` where uploaded.
- READ: page -> `VehicleController::getOwnerVehicles()` -> `Vehicle` owner-scoped queries -> `vehicles`/documents.
- UPDATE: page -> `updateVehicle()` -> `Vehicle::update()` -> `vehicles`/documents.
- DELETE: page -> `deleteVehicle()` -> `Vehicle::delete()` -> `vehicles`, blocked by dependencies/history.
- Status update/deactivate are additional UPDATE operations.
- CSRF and owner ownership checks are applied in controller/model path; status/dependency restrictions apply.

## Customer Reviews & Ratings

- CREATE: Customer review page/controller -> `Review`/Customer review method -> `ratings_reviews`.
- READ: Customer review list/details and public vehicle details -> review model queries -> `ratings_reviews` joined to bookings/users/vehicles.
- UPDATE: no broadly exposed Customer review update path found; any method is not treated as active CRUD without a current route.
- DELETE: no broadly exposed Customer review delete path found.
- CSRF: create POST uses Customer CSRF.
- Restrictions: Customer ownership of booking, eligible/completed booking, rating bounds, and duplicate-review checks are enforced in current model/controller path.

## Admin User Management

- CREATE: `public/admin/users.php` -> `user_handler.php` -> `AdminUserController::createUser()` -> `User::createManagedUser()` -> `users` plus role profile.
- READ: users page -> `listUsers()`/`findUser()` -> `users` without password hash.
- UPDATE: user handler -> `updateUser()` -> `User::updateManagedUser()` -> `users` and role profiles.
- DELETE: user handler -> `deleteUser()` -> `User::deleteManagedUser()` -> `users`; foreign keys may reject dependent deletion.
- CSRF: Admin handler validates token.
- Restrictions: Admin role, valid role/status, unique email, minimum temporary password, self-admin protection, and foreign-key dependencies.

# 17. Database Relationship Map

| Table | Primary key | Main foreign keys | Current feature use |
| --- | --- | --- | --- |
| `users` | `id` | root auth table | authentication, roles, notifications, audit references |
| `customers` | `id` | `user_id -> users` | Customer profile/verification/bookings |
| `vehicle_owners` | `id` | `user_id -> users` | Owner profile/vehicles |
| `drivers` | `id` | `user_id -> users` | Driver profile/assignments/bookings |
| `driver_documents` | `id` | `driver_id -> drivers`, reviewer -> users | Driver verification |
| `driver_owner_links` | `id` | driver/owner | Owner-driver connection |
| `driver_leaves` | `id` | driver, approver -> users | Leave CRUD/approval |
| `vehicles` | `id` | owner -> vehicle_owners | Fleet, public/customer catalogue |
| `vehicle_documents` | `id` | vehicle -> vehicles | Vehicle verification |
| `bookings` | `id` | customer, vehicle, optional driver | rental lifecycle |
| `vehicle_assignments` | `id` | driver, vehicle | assigned vehicles |
| `vehicle_inspections` | `id` | vehicle, optional booking, inspector -> users | inspections |
| `payments` | `id` | booking | payment evidence/review |
| `incidents` | `id` | booking, reporter -> users | incident workflow |
| `incident_photos` | `id` | incident | photo evidence; not fully connected in Customer UI |
| `replacement_requests` | `id` | incident, booking, requester, vehicles | replacement workflow |
| `ratings_reviews` | `id` | booking, customer, optional driver/vehicle | reviews/ratings |
| `chat_rooms` | `id` | optional booking | booking conversations |
| `chat_participants` | `id` | room, user | chat authorization |
| `chat_messages` | `id` | room, sender | chat transcripts |
| `notifications` | `id` | user | notification inbox/polling |
| `pickup_tracking` | `id` | booking, updater -> users | Driver pickup lifecycle |
| `driver_vehicle_checks` | `id` | driver/vehicle/booking where declared | newer driver safety path |
| `profile_change_requests` | `id` | user/reviewer where declared | Driver profile approval |
| `account_change_requests` | `id` | user/reviewer where declared | Driver security changes |
| `admin_reviews` | `id` | domain record/admin where declared | Admin audit/review |
| `driver_payments` | `id` | driver/booking where declared | Driver earnings |
| `settlements` | `id` | booking/owner where declared | Owner payout ledger |
| `email_logs` | `id` | user/related record where declared | email audit |
| `announcements` | `id` | Admin author where declared | Admin announcements |

The current `database/schema.sql` contains the core tables plus appended `CREATE TABLE IF NOT EXISTS` definitions. Runtime/live schema may differ from the checked-in SQL, as demonstrated by the chat `is_read` drift found in the prior runtime audit.

# 18. Dead, Duplicate, Conflicting, and Risky Logic

| Finding | Severity |
| --- | --- |
| Customer legacy `CustomerController::dashboard()` contains static Phase 3 data beside DB-backed `CustomerPortalController::dashboardPage()` | MEDIUM / INTERIM RISK |
| Public vehicle pages and Customer vehicle pages implement separate catalogue/detail paths | MEDIUM / FINAL-PROJECT RISK |
| Direct page handlers and controllers duplicate CSRF, ownership, and status rules | MEDIUM / FINAL-PROJECT RISK |
| Owner chat GET creates rooms/enrolls Owner | INTERIM RISK |
| Customer/Owner/Driver chat enrollment does not guarantee one three-party participant set | INTERIM RISK |
| `Chat::getUserRooms()` is generic while role pages supply role-specific context outside the model | FINAL-PROJECT RISK |
| Checked-in chat schema and live chat query expectations differ on `is_read` | INTERIM RISK |
| Driver/Owner/Admin/Customer use different shell/layout and CSS conventions | LOW visual risk, MEDIUM maintenance risk |
| Customer Driver-change workflow is explicitly session-only demo data | INTERIM RISK |
| Customer incident replacement/refund controls are visibly disabled but adjacent demo paths exist | INTERIM RISK |
| Accepted connection POST can replay duplicate notification creation | INTERIM RISK |
| Some Admin pages use direct SQL and swallow exceptions into empty metrics | MEDIUM |
| Some pages use `die()` with raw operation-specific error text while Customer catches and hides details | MEDIUM |
| Admin sidebar logout links to public logout without the same POST+CSRF pattern as Owner/Customer | MEDIUM |
| Several legacy helper/model methods are unused by current route paths | LOW |

# 19. Interim Functionality Gap Matrix

| Feature | Role | UI present? | Backend present? | DB-backed? | Authorization present? | Interim safe? | Known issue? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Login/register | Guest | YES | YES | YES | CSRF/status/role | YES | Admin public registration absent by design |
| Vehicle catalogue | Guest/Customer | YES | YES | YES | visibility filters | YES | duplicate implementations |
| Booking create/edit/cancel | Customer | YES | YES | YES | session ownership/status | PARTIAL | no live booking data; distributed lifecycle |
| Verification | Customer/Owner/Driver/Vehicle | YES | YES | YES | Admin-only decisions | YES | feature blocking differs by path |
| Payment evidence/review | Customer/Admin | YES | YES | YES | ownership/Admin role | PARTIAL | invoice/verification field drift risk |
| Reviews | Customer | YES | YES | YES | booking/customer checks | YES | no current data to exercise |
| Chat | Customer/Owner/Driver | YES | YES | YES | participant/role checks | PARTIAL | no current data; timing-dependent enrollment |
| Notifications | All roles | YES | YES | YES | user-scoped | PARTIAL | accepted-link replay duplicate |
| Incidents | Customer/Driver/Admin | YES | YES | YES | owned/assigned/Admin | PARTIAL | evidence/replacement UI incomplete |
| Replacement | Owner/Admin | YES | YES | YES | owner/request/Admin | PARTIAL | Customer replacement decisions demo-only |
| Return | Customer/Driver/Owner | YES | PARTIAL | PARTIAL | feature-specific | PARTIAL | no complete return state machine |
| Driver change | Customer | YES | NO approved table | SESSION ONLY | session user | DEMO-SAFE | not final DB workflow |
| Driver leave CRUD | Driver/Admin | YES | YES | YES | Driver/Admin | YES | status/date edge cases distributed |
| Owner vehicle CRUD | Owner/Admin | YES | YES | YES | Owner/Admin | YES | file/dependency paths vary |
| Admin user CRUD | Admin | YES | YES | YES | Admin/self/dependency | YES | role-profile replacement can delete old profile |
| Owner-driver links | Owner/Driver/Admin view | YES | YES | YES | owner/driver session | PARTIAL | duplicate notification replay |
| Driver safety checks | Driver/Owner view | YES | YES | YES | assigned Driver | PARTIAL | booking/pickup status coupling |
| Settlements | Admin/Owner | YES | YES | YES/derived | Owner/Admin | PARTIAL | settlement schema/runtime drift possible |

# Critical Interim Risks

1. **Booking-dependent features are not currently testable with live data.** The current database has no booking rows, so chat, booking transitions, payments, incidents, reviews, inspections, and Driver trips cannot all be end-to-end proven.
2. **Chat participant formation is timing-dependent** across three role implementations, and the checked-in schema/live schema differ around `is_read`.
3. **Notification duplication on accepted connection POST replay remains present.**
4. **Customer has parallel legacy and newer portal logic**, making it possible for displayed behavior and active behavior to diverge.
5. **Business rules are distributed between direct pages, controllers, and models**, especially booking status transitions, verification enforcement, payment review, and vehicle lifecycle.
6. **Demo/session-only workflows are adjacent to real DB workflows**, especially Driver Change and Customer replacement/refund choices.
7. **GET-triggered Owner chat enrollment mutates database state.**
8. **Admin and legacy pages have inconsistent exception/error exposure and CSRF conventions.**
9. **Runtime schema should be treated as authoritative for current execution; checked-in SQL is not fully synchronized with every live query expectation.**

This audit records current behavior only. It does not recommend a broad redesign and does not change any of the above behavior.
