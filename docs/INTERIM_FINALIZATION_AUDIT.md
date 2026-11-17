# Lanka Renters Interim Finalization Audit

Audit date: 2026-08-16  
Scope: read-only inspection of `app/`, `public/`, `database/`, `docs/`, the configured `lanka_renters` database, and local HTTP routes before application changes.

## 1. Audit Rules and Classification

This audit preserves the existing PHP MVC-style structure and approved visual design. No application file was changed before this document was created.

Classifications:

- **[A] FUNCTIONAL**: a real database-backed flow exists and the audited route loaded without a visible PHP/SQL error.
- **[B] PARTIAL**: a real backend exists, but an operation, runtime prerequisite, or complete CRUD operation is missing.
- **[C] UI ONLY**: the visible page is a mock/demo/session-only workflow and is not backed by the advertised database entity.
- **[D] BROKEN**: the route exposes or internally encounters a confirmed PHP/SQL runtime error.
- **[E] PLACEHOLDER**: no substantive implemented workflow exists.

`Loads` below records the baseline runtime result before fixes. A page that catches a database failure and displays a safe unavailable state is still marked broken when its real backend cannot execute.

## 2. Actual Role Architecture

| Role | Authentication entry | Protected area | Intended account creation | Authorization mechanism |
| --- | --- | --- | --- | --- |
| Guest | `public/login.php`, `public/register.php` | Public homepage/catalogue only | May self-register as Customer, Owner, or Driver | Public routes plus session-aware dashboard links |
| Customer | Unified login | `public/customer/` | Public self-registration | `AuthHelper::requireRole('customer')` in `_bootstrap.php` |
| Vehicle Owner | Unified login | `public/owner/` | Public self-registration | `AuthHelper::requireRole('owner')` in page entry points |
| Driver | Unified login | `public/driver/` | Public self-registration, followed by document/admin verification workflow | `AuthHelper::requireRole('driver')` in page entry points/controllers |
| Admin | Unified login | `public/admin/` | Internally provisioned only; Admin is not offered by public registration | `AuthHelper::requireRole('admin')` in Admin pages/handlers |

Public registration is correctly allowlisted to `customer`, `owner`, and `driver` in `AuthController::register()`. The Driver workflow in the current code is: account/profile creation, document submission, Admin document/driver review, then use of verified-driver eligibility queries. No public Admin registration path was found.

## 3. Architecture Trace

The implemented request path is:

`Browser -> public PHP view/endpoint -> controller (where present) -> model -> PDO prepared statement -> MySQL/MariaDB`

Shared infrastructure:

- Database connection: `app/helpers/Database.php` and `app/config/database.php`.
- Authentication/session/role/CSRF: `app/helpers/AuthHelper.php`; Customer routes also use `CustomerCsrf` in `public/customer/_bootstrap.php`.
- Views: `public/`, separated into Guest, Customer, Owner, Driver, and Admin areas.
- Controllers: `app/controllers/`.
- Models: `app/models/`.
- Schema: user-designated source `database/lanka_renters.sql`, with additional migrations in `database/migrations/`.

Some Admin endpoints use a page/handler -> model pattern without a controller class. This is existing project structure, not a new architecture introduced by finalization.

## 4. Feature Matrix

### Guest

| Role | Page | Exists | Loads | Real Backend | UI Only | Broken | CRUD | Classification / trace |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- | --- |
| Guest | Home `index.php` | Yes | Yes | Yes | No | No | Read | **[A]** `PublicVehicleController` -> `Vehicle` -> `vehicles` |
| Guest | Vehicles `vehicles.php` | Yes | Yes | Yes | No | No | Read | **[B]** catalogue reads are real, but vehicle creation is blocked by live-schema drift |
| Guest | Vehicle details `vehicle-details.php` | Yes | Not fixture-tested | Yes | No | No visible baseline error | Read | **[B]** `PublicVehicleController` -> `Vehicle` -> `vehicles`, `ratings_reviews` |
| Guest | Login `login.php` | Yes | Yes | Yes | No | No | N/A | **[A]** `AuthController::login` -> `User` -> `users` |
| Guest | Register `register.php` | Yes | Yes | Yes | No | Partial | Create | **[B]** real transactional registration, but exception text can be returned too directly and valid role registrations require runtime retest |

### Customer

| Role | Page | Exists | Loads | Real Backend | UI Only | Broken | CRUD | Classification / trace |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- | --- |
| Customer | Dashboard | Yes | Safe shell | Yes | No | Yes | Read | **[D]** `CustomerPortalController::dashboardPage` -> `CustomerPortal::getDashboard`; live `customers` lacks `district/address` used by context query |
| Customer | Verification | Yes | Safe unavailable state | Yes | No | Yes | Update | **[D]** `CustomerPortalController::verificationPage/profilePage` -> `CustomerPortal`; same column drift |
| Customer | Vehicle search | Yes | Empty state | Yes | No | Partial | Read | **[B]** `CustomerController::vehicleCatalogue` -> `CustomerVehicle`; result query expects live-missing `vehicles.image_url` once rows exist |
| Customer | Bookings | Yes | Yes | Yes | No | No baseline error | C/R/U/Cancel | **[B]** `CustomerBookingController` -> `CustomerBooking` -> `bookings`; cancellation preserves a row and is not being claimed as Delete CRUD |
| Customer | Inspections | Yes | Yes | Yes | No | No baseline error | Read | **[A]** `CustomerPortalController` -> `CustomerPortal` -> `vehicle_inspections` |
| Customer | Payments | Yes | Yes | Yes | No | No baseline error | Create/Read | **[B]** `CustomerPaymentController` -> `CustomerPayment` -> `payments`; not full CRUD |
| Customer | Chat | Yes | Yes | Yes | No | No baseline error | Create/Read messages | **[A]** `CustomerPortalController` -> `CustomerPortal` -> chat tables |
| Customer | Incidents navigation page | Yes | Yes | No | Yes | Misleading | None | **[C]** hard-coded demo amounts/workflow; real incident controller/model methods exist but this index does not call them |
| Customer | Incident create/details | Yes | Not fixture-tested | Yes | No | No static blocker | Create/Read | **[B]** `CustomerPortalController` -> `CustomerPortal` -> `incidents`, `replacement_requests` |
| Customer | Driver Change Request | Yes | Yes | No production table | Yes | No fatal error | Session-only | **[C]** explicitly labelled session prototype via `CustomerDemoData` |
| Customer | Return | Yes | Yes | No production table | Yes | No fatal error | Session-only | **[C]** explicitly labelled session prototype via `CustomerDemoData` |
| Customer | Reviews | Yes | Yes | Yes | No | No baseline error | C/R only | **[B]** `CustomerPortalController` -> `CustomerPortal` -> `ratings_reviews`; Update/Delete missing |
| Customer | Profile | Yes | Safe unavailable state | Yes | No | Yes | Read/Update | **[D]** live `customers` lacks required `district/address` |
| Customer | Notifications | Yes | Yes | Yes | No | No baseline error | Read/Update-read-state | **[A]** `CustomerPortalController` -> `CustomerPortal` -> `notifications` |

### Vehicle Owner

| Role | Page | Exists | Loads | Real Backend | UI Only | Broken | CRUD | Classification / trace |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- | --- |
| Owner | Dashboard | Yes | Yes | Yes | No | No | Read | **[A]** direct model/PDO summary using `vehicle_owners`, `vehicles`, `driver_owner_links` |
| Owner | Vehicles | Yes | Empty page loads | Yes | No | Partial | C/R/U/Deactivate | **[B]** `VehicleController` -> `Vehicle` -> `vehicles`, `vehicle_documents`; hard Delete missing and Create expects live-missing `image_url` |
| Owner | Drivers | Yes | Yes | Yes | No | No baseline error | Create/Read link | **[A]** `OwnerDriverController` -> `DriverOwnerLink` |
| Owner | Bookings | Yes | Yes | Yes | No | No baseline error | Read | **[A]** `OwnerBookingController` -> booking data |
| Owner | Earnings | Yes | Yes | Yes | No | No baseline error | Read | **[A]** `OwnerEarningsController` -> payments/bookings |
| Owner | Inspections | Yes | No | Yes | No | Yes | Read | **[D]** `VehicleInspection::getInspectionsByOwner` selects live-missing `vehicles.image_url` and exposes a PDO fatal error |
| Owner | Chat | Yes | Yes | Yes | No | No baseline error | Create/Read messages | **[A]** `OwnerChatController` -> `Chat` -> chat tables |
| Owner | Replacement Requests | Yes | No | Yes | No | Yes | Read/Update | **[D]** `ReplacementRequest.php` parse error |
| Owner | Profile | Yes | Yes | Yes | No | No baseline error | Read/Update | **[A]** `ProfileController` -> `VehicleOwner`, `User` |

### Driver

| Role | Page | Exists | Loads | Real Backend | UI Only | Broken | CRUD | Classification / trace |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- | --- |
| Driver | Dashboard | Yes | No | Yes | No | Yes | Read | **[D]** `DriverController::dashboard` -> `Driver::getDashboardData`; wrong `driver_documents.verification_status` assumption for migrated/live schema |
| Driver | Profile | Yes | No | Yes | No | Yes | Read/request update | **[D]** shared Driver dashboard query failure |
| Driver | Account & Security | Yes | No | Yes | No | Yes | Update/request update | **[D]** shared Driver query failure |
| Driver | Documents | Yes | Yes | Yes | No | No baseline fatal | C/R/U/D pending docs | **[A]** `DriverController` -> `DriverDocument` -> `driver_documents` |
| Driver | Connected Owners | Yes | Yes | Yes | No | No baseline fatal | Read/Update link state | **[A]** `DriverController` -> `DriverOwnerLink` |
| Driver | Availability | Yes | No | Yes | No | Yes | Read/Update | **[D]** shared Driver query failure |
| Driver | Leave Requests | Yes | Yes | Yes | No | No baseline fatal | C/R/U/D | **[A]** `DriverController` -> `DriverLeave` -> `driver_leaves`; lifecycle runtime test still required |
| Driver | Safety Checklist | Yes | No | Yes | No | Yes | C/R/U/D | **[D]** page is blocked by shared Driver query before `VehicleSafetyCheck` workflow |
| Driver | Trips & Tracking | Yes | Yes | Yes | No | No baseline fatal | Read/Update status | **[A]** `DriverController` -> `Driver` -> `bookings`, `pickup_tracking` |
| Driver | Assigned Vehicles | Yes | Yes | Yes | No | No baseline fatal | Read | **[A]** `DriverController` -> `Driver` -> `vehicle_assignments`, `vehicles` |
| Driver | Report Incident | Yes | No | Yes | No | Yes | Create | **[D]** shared Driver query failure before `DriverIncident` workflow |
| Driver | Messages | Yes | Yes | Yes | No | No baseline fatal | Create/Read messages | **[A]** `ChatController` -> `Chat` |
| Driver | Notifications | Yes | Yes | Yes | No | No baseline fatal | Read/Update-read-state | **[A]** `Notification` -> `notifications` |
| Driver | Performance | Yes | Yes | Yes | No | No baseline fatal | Read | **[A]** `DriverPerformance` -> bookings/reviews |
| Driver | Earnings | Yes | Yes | Yes | No | No baseline fatal | Read | **[A]** `DriverPayment` -> `driver_payments` |

### Admin

All Admin navigation pages except `users.php` share the broken `public/admin/config/database.php` include and therefore failed the baseline load. `users.php` independently references a missing `AdminCustomerVerification.php` model.

| Role | Page | Exists | Loads | Real Backend | UI Only | Broken | CRUD | Classification / trace |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- | --- |
| Admin | Dashboard | Yes | No | Yes | No | Yes | Read | **[D]** shared include path blocker |
| Admin | Users | Yes | No | Intended | No | Yes | Read/verification update | **[D]** required `AdminCustomerVerification` model file is absent |
| Admin | Vehicle Owners | Yes | No | Yes | No | Yes | Read/verification update | **[D]** `AdminOwnerVerification`; shared include path blocker |
| Admin | Drivers | Yes | No | Yes | No | Yes | Read/verification update | **[D]** `AdminDriverVerification`; shared include path blocker, then migrated document column compatibility must be checked |
| Admin | Vehicles | Yes | No | Yes | No | Yes | Read/verification update | **[D]** `AdminVehicleVerification`; shared include path blocker |
| Admin | Bookings | Yes | No | Yes | No | Yes | Read | **[D]** `AdminBookingMonitoring`; shared include path blocker |
| Admin | Payments | Yes | No | Yes | No | Yes | Read/verification update | **[D]** `AdminPaymentVerification`; shared include path blocker |
| Admin | Incidents | Yes | No | Yes | No | Yes | Read/Update status | **[D]** `AdminIncidentManagement`; shared include path blocker |
| Admin | Replacement Requests | Yes | No | Yes | No | Yes | Read/Update assignment | **[D]** `ReplacementRequest.php` parse error |
| Admin | Settlements | Yes | No | Yes | No | Yes | Create/Read/process | **[D]** `Settlement`; shared include path blocker |
| Admin | Reports | Yes | No | Yes | No | Yes | Read | **[D]** `Report`; shared include path blocker |
| Admin | Email Logs | Yes | No | Yes | No | Yes | Read | **[D]** `EmailLog`; shared include path blocker |
| Admin | Announcements | Yes | No | Yes | No | Yes | C/R/D | **[D]/[B]** shared include blocker; `Announcement` lacks Update |

## 5. Authentication Audit

| Area | Static finding | Baseline runtime finding | Status |
| --- | --- | --- | --- |
| Login | Prepared lookup, `password_verify`, generic invalid-credential response, active-status check | Seeded role credentials create sessions and reach role destinations; Admin/Driver destinations then fail for unrelated blockers | Partial |
| Registration | Transaction creates `users` plus role profile; public role allowlist excludes Admin | Page loads; role-by-role disposable registration test pending | Partial |
| Password storage | `User::create` uses `password_hash`; login uses `password_verify` | Seeded password works | Pass |
| Session | ID regenerated on login; HttpOnly, SameSite=Lax, dynamic Secure flag | Runtime cookie/logout matrix pending | Pass static |
| Role redirects | Central role mapping exists in `AuthHelper` and auth controller/pages | Customer/Owner reach dashboards; Driver/Admin reach broken dashboards | Partial |
| Protected routes | Role guards exist in all role areas (Customer centralized in bootstrap) | Cross-role/guest route matrix pending | Partial |
| Logout | Root logout destroys session; Customer/Owner have POST+CSRF logout; Driver sidebar POSTs dashboard logout | Role-by-role logout/back-route test pending | Partial |
| Error safety | Customer bootstrap suppresses display errors and logs safely | Owner/Admin/Driver pages currently expose raw warning/fatal/SQLSTATE output | Fail |

Security concern to address minimally: several non-Customer controllers/handlers return raw exception messages to UI. Confirmed SQL errors are currently visible in Driver/Owner/Admin pages.

## 6. Navigation Audit

- Guest visible links point to existing files or sections.
- All Customer sidebar targets exist. `Driver Change Request` and `Return` are session-only prototypes and should be hidden for the interim rather than presented beside database-backed features. The current Incidents index is a hard-coded demo and must be replaced by the already-implemented real incident list or hidden.
- All Owner sidebar targets exist; Inspections and Replacement Requests are runtime-broken.
- All Driver sidebar targets exist; six routes are blocked by the Driver document-status query mismatch.
- All Admin sidebar targets exist; the shared Admin include path blocks most pages, and Users has an additional missing model dependency.
- Owner sidebar uses `/owner/...` paths, which are correct for the current `public/` document root.

## 7. CRUD Assignment Matrix (Before Implementation)

| Student/Role | Entity | Create | Read | Update | Delete | Current Status |
| --- | --- | --- | --- | --- | --- | --- |
| Driver | Leave Request | View `driver/leave.php`; `DriverController::requestLeave`; `DriverLeave::create`; `driver_leaves` | View `driver/leave.php`; `DriverController::viewLeaveHistory`; `DriverLeave::getByDriverId/getById`; `driver_leaves` | View `driver/leave.php`; `DriverController::editLeave`; `DriverLeave::update`; `driver_leaves` | POST `driver/leave.php`; `DriverController::deleteLeave`; `DriverLeave::delete`; `driver_leaves` | **COMPLETE IN CODE; runtime C/R/U/D verification pending** |
| Vehicle Owner | Vehicle | View `owner/vehicles.php`; `VehicleController::createVehicle`; `Vehicle::create`; `vehicles` | View `owner/vehicles.php`; `VehicleController::getOwnerVehicles`; `Vehicle::getByOwnerId/getById`; `vehicles` | View `owner/vehicles.php`; `VehicleController::updateVehicle`; `Vehicle::update`; `vehicles` | **MISSING** (`deactivate` is an Update, not Delete) | **PARTIAL; Create also blocked by live-missing `image_url`** |
| Customer | Review | View `customer/reviews/create.php`; `CustomerPortalController::reviewCreatePage`; `CustomerPortal::createReview`; `ratings_reviews` | Views `reviews/index.php/details.php`; `reviewsPage/reviewDetailsPage`; `getReviews/findReview`; `ratings_reviews` | **MISSING** | **MISSING** | **PARTIAL** |
| Admin | Announcement | View `admin/announcements.php` + `announcement_handler.php`; `Announcement::create`; `announcements` | View `admin/announcements.php`; `Announcement::getAll`; `announcements` | **MISSING** | POST handler; `Announcement::delete`; `announcements` | **PARTIAL; Admin include blocker also prevents UI** |

The minimum CRUD work after blockers is therefore:

1. Keep Driver Leave Request as-is unless runtime testing reveals a defect.
2. Add an ownership-checked, POST+CSRF, booking-safe Vehicle Delete operation.
3. Add ownership-checked, POST+CSRF Review Update and Delete operations using the existing Customer UI shell.
4. Add Admin Announcement Update with Admin guard and CSRF using the existing Admin table/form styling.

## 8. Confirmed Runtime and Static Blockers

1. `public/admin/config/database.php` resolves the application root as `public/`, producing a missing `public/app/helpers/AuthHelper.php` fatal error.
2. `app/models/Driver.php` queries `driver_documents.verification_status`, while migration `002_driver_documents.sql`, the live table, and `DriverDocument` use `status` plus version/current fields.
3. `app/models/ReplacementRequest.php` has a malformed docblock/missing method completion near line 50; global lint reports the only current PHP syntax error there.
4. `public/admin/users.php` and `customer_verification.php` require a nonexistent `app/models/AdminCustomerVerification.php`.
5. `VehicleInspection` selects `vehicles.image_url`; the configured live `vehicles` table lacks that column and exposes a PDO fatal error.
6. Customer profile/context queries select `customers.district` and `customers.address`; the configured live table lacks both. The Customer UI catches the failure, but Dashboard/Profile/Verification are not genuinely functional.
7. Owner Vehicle Create includes `vehicles.image_url`; it will fail against the configured live table.
8. Customer Incidents index is a hard-coded UI demo containing fabricated monetary values, despite real incident model/controller methods being present elsewhere.
9. Admin announcement handler currently exposes caught exception messages as `Error: ...` flash text.

## 9. Database Source and Live-Schema Reconciliation Gate

The task designates `database/lanka_renters.sql` as source of truth, but the repository itself contains two schema generations:

- `lanka_renters.sql` includes `customers.district`, `customers.address`, `vehicles.image_url`, and the pre-versioning Driver document column names.
- Migration `002_driver_documents.sql`, `database/schema.sql`, the live database, and current Driver document code use the versioned `driver_documents.status` design.
- Migrations 007-009 add `settlements`, `email_logs`, and `announcements`; those tables exist live but are not integrated into `lanka_renters.sql`.
- `lanka_renters.sql` defines `invoices`; the live database does not currently contain that table, though no active invoice model implementation was found.
- The live database lacks `customers.district`, `customers.address`, and `vehicles.image_url`, even though active current PHP uses them.

An automated column-by-column comparison produced this exact source/live drift:

| Table | Declared in `lanka_renters.sql` but missing live | Live replacement/additions | Active impact |
| --- | --- | --- | --- |
| `customers` | `district`, `address` | None | Customer Dashboard, Verification, and Profile context queries fail |
| `vehicles` | `image_url` | None | Owner Vehicle Create and Owner Inspection fail; populated catalogue rendering would fail |
| `chat_messages` | `is_read` | None | `Chat::markMessagesAsRead` cannot execute |
| `notifications` | `related_id` | None | Customer notification reads fail |
| `payments` | `verified_by`, `verified_at`, `failure_reason` | None | Admin payment verification actions cannot execute as designed |
| `driver_documents` | Source has legacy `verification_status`, `rejected_reason` | Live has migration-002 `version`, `status`, `is_current`, `reviewed_by`, `reviewed_at`, `rejection_reason` | Current Driver document code follows migration 002; the Driver dashboard mismatch was a PHP defect |
| `invoices` | Entire table missing live | None | No active implemented invoice model methods were found, so this is not an immediate interim runtime blocker |

Live-only tables `announcements`, `email_logs`, and `settlements` correspond to migrations 007-009 and are actively used by Admin pages.

Minimum schema alignment appears genuinely required for full interim success. Per the task's critical database rule, no schema was changed during this audit. Before any database alteration, the team must approve aligning the configured local database to the already-declared current fields, preferably through a small explicit migration rather than a destructive re-import.

## 10. Baseline Test Evidence

- PHP runtime: 8.2.12.
- Configured database: `lanka_renters` on `127.0.0.1:3308`; connection succeeds.
- Local HTTP server: `http://127.0.0.1:8000`.
- Global PHP lint: 236 PHP files checked; one failure, `ReplacementRequest.php`.
- Guest homepage/login/register: HTTP 200.
- Customer visible routes: return a rendered page, but Profile/Verification/Dashboard backend failures are caused by live-schema drift; Incidents/Driver Change/Return are non-production UI/session flows.
- Owner: Dashboard/Vehicles/Drivers/Bookings/Earnings/Chat/Profile render; Inspection and Replacement Requests fail.
- Driver: Dashboard/Profile/Security/Availability/Safety Check/Report Incident fail from the shared document-status mismatch; other primary pages render.
- Admin: all visible primary pages fail from the shared include path, ReplacementRequest parse error, or missing Customer verification model.

## 11. Read-Only Audit Conclusion

The project has substantial real backend coverage and an approved UI worth preserving, but it is **NOT READY** for the interim demo in its baseline state. The first safe work is the three named blockers, followed by the missing Admin Customer verification dependency, removal/reconnection of misleading Customer demo navigation, and the minimal CRUD gaps above. Full success also depends on an explicitly approved live-schema alignment for fields already declared in the designated schema source.
