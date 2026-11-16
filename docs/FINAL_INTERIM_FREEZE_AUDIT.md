# Lanka Renters — Final Interim Freeze Audit

Audit date: 2026-08-16  
Audit mode: read-only application audit; no application, configuration, or database files were changed. This report is the only file created.  
Environment tested: PHP development server at `http://127.0.0.1:8000`, application database configuration `127.0.0.1:3308`, database `lanka_renters`.

## Executive result

**NOT READY FOR INTERIM**

The current UI/theme was not changed or assessed for redesign. The project passes whole-project PHP syntax validation and the visible navigation targets exist, but the configured database is unavailable, raw PDO failures are shown on public pages, authentication cannot complete, three assigned CRUDs are incomplete or broken, and several visible Customer workflows are explicitly non-database demos.

Because this was a read-only freeze audit and the configured database was unavailable, no CRUD mutation was performed. Consequently, no assigned CRUD can be certified as currently demo-ready merely from method existence.

## Audit method and evidence rules

- Traced each required feature from rendered PHP page/form to controller method, model method, SQL statement, and schema table.
- Checked visible navigation targets for file existence and relative-path resolution.
- Performed unauthenticated HTTP smoke tests without following redirects.
- Re-ran PHP lint over every PHP file.
- Attempted connection through the application's own database helper.
- Did not change source code, database schema, data, or configuration.
- Did not perform Create, Update, or Delete database tests because this audit explicitly prohibited modification and the configured MySQL endpoint refused connections.

## 1. Interim requirement matrix

| Requirement | Implementation | Evidence | PASS/FAIL |
|---|---|---|---|
| Customer registration and login | Registration/login UI and backend code exist, but submission constructs a database-backed `AuthController`; the configured DB connection fails before credential or registration processing completes. | `public/register.php` → `AuthController::register()` → `User::create()` / role profile model; `public/login.php` → `AuthController::login()` → `User::findByEmail()`. Runtime POST displayed the PDO connection failure. | **FAIL** |
| Owner registration and login | Owner is a permitted public registration role and has a role redirect, but runtime registration/login is blocked by the DB outage. | `AuthController::$allowedRoles` includes `owner`; redirects to `owner/dashboard.php`. | **FAIL** |
| Driver registration and login | Driver is a permitted public registration role and has a role redirect, but runtime registration/login is blocked by the DB outage. | `AuthController::$allowedRoles` includes `driver`; redirects to `driver/dashboard.php`. | **FAIL** |
| Admin login | Admin is intentionally excluded from public registration and has a login redirect, but runtime login is blocked by the DB outage. | Public roles are only Customer/Owner/Driver; login redirects Admin to `admin/dashboard.php`. | **FAIL** |
| Logout | Session destruction and redirect code exist. Customer/Owner logout use POST and CSRF; root Admin logout is a GET link. A valid authenticated logout cycle could not be rerun because login cannot complete. | `AuthHelper::logout()`, `public/customer/logout.php`, `public/owner/logout.php`, `public/logout.php`, Driver dashboard logout action. | **FAIL** (end-to-end unverified) |
| Password security | Passwords are created with `password_hash(..., PASSWORD_DEFAULT)` and checked with `password_verify()`. | `app/models/User.php` (`create`, `verifyPassword`). | **PASS** (code inspection) |
| Session handling | HttpOnly/Lax cookie settings, session-ID regeneration on login, cookie clearing, and `session_destroy()` exist. | `app/helpers/AuthHelper.php`. | **PASS** (code inspection) |
| Role redirects and access protection | Role redirects target existing dashboards. Customer pages share `_bootstrap.php`; Owner, Driver, and Admin routes use `AuthHelper::requireRole()`. Guest requests to all four dashboards returned `302 /login.php`. | `AuthHelper::requireRole()` and route bootstraps; HTTP smoke test. | **PASS** (guard behavior) |
| Finalized UI is implemented and navigable | All checked visible targets exist, but public Home/Vehicles currently fatal, authenticated navigation cannot be exercised, Admin Users is missing a required model, and Customer Incidents/Driver Change/Returns visibly expose demo-only behavior. | Navigation and runtime sections below. | **FAIL** |
| Driver member CRUD — Leave Requests | All four operations are wired with POST/CSRF and ownership/status checks, but current DB execution cannot be verified. | `public/driver/leave.php` → `DriverController` → `DriverLeave` → `driver_leaves`. | **FAIL** (runtime blocked) |
| Owner member CRUD — Vehicle Management | Create/Read/Update exist. “Deactivate” is an `UPDATE status='unavailable'`, not Delete. No Delete action/model method exists. | `public/owner/vehicles.php` → `VehicleController` → `Vehicle` → `vehicles`. | **FAIL** |
| Customer member CRUD — Reviews & Ratings | Create and Read exist with Customer ownership checks. No Update or Delete route, UI, controller method, or model SQL exists. | `public/customer/reviews/*` → `CustomerPortalController` → `CustomerPortal` → `ratings_reviews`. | **FAIL** |
| Admin member CRUD — User Management | Intended page only reads Customer verification queues and updates verification state. Its required model file is absent; there is no Admin Create-user or Delete-user path. | `public/admin/users.php` and `customer_verification.php` require missing `app/models/AdminCustomerVerification.php`. | **FAIL** |
| No exposed fatal/SQL errors | Home and Vehicles return HTTP 200 bodies containing raw `PDOException`, `SQLSTATE`, file paths, and stack traces. Login/Register submissions do likewise. | Live HTTP output from `app/helpers/Database.php:50`. | **FAIL** |
| PHP syntax | All PHP files parse successfully. | `php -l`: **236 files, 0 failures**. | **PASS** |

## 2. Authentication audit

### Static authentication flow

| Area | Finding | Status |
|---|---|---|
| Registration roles | Public registration is restricted to `customer`, `owner`, and `driver`. Admin registration is not exposed. | PASS |
| Customer profile creation | Registration transaction inserts `users`, then creates a `customers` profile. | Implemented; runtime blocked |
| Owner profile creation | Registration transaction inserts `users`, then creates a `vehicle_owners` profile. | Implemented; runtime blocked |
| Driver profile creation | Registration transaction inserts `users`, then creates a `drivers` profile. Driver document/approval pages exist separately. | Implemented; runtime blocked |
| Credential lookup | `User::findByEmail()` uses a prepared statement. Unknown email and wrong password share the safe message “Invalid email or password.” | PASS in code; runtime blocked |
| Password storage | `PASSWORD_DEFAULT` hash; plaintext password is not inserted. | PASS |
| Login session | `AuthHelper::login()` regenerates the session ID and stores the user record. | PASS in code |
| Role redirect | Admin → `admin/dashboard.php`; Owner → `owner/dashboard.php`; Driver → `driver/dashboard.php`; Customer → `customer/dashboard/index.php` (existing redirect wrapper). | PASS in code |
| Route guards | `requireRole()` sends a guest to login and a wrong-role user to that user's dashboard. | PASS in code and guest HTTP test |
| Error containment | The database helper throws a new PDO exception containing the driver message, and PHP display errors expose it to users. Registration also returns exception text in its error message. | FAIL |

### Runtime authentication matrix

| Role | Register page | Register submission | Login page | Correct login | Incorrect login | Dashboard as guest | Logout |
|---|---|---|---|---|---|---|---|
| Customer | PASS | FAIL — DB connection fatal | PASS | FAIL — DB connection fatal | FAIL — DB error occurs before safe credential response | PASS — `302 /login.php` | End-to-end blocked |
| Owner | PASS | FAIL — DB connection fatal | PASS | FAIL — DB connection fatal | FAIL — DB error occurs before safe credential response | PASS — `302 /login.php` | End-to-end blocked |
| Driver | PASS | FAIL — DB connection fatal | PASS | FAIL — DB connection fatal | FAIL — DB error occurs before safe credential response | PASS — `302 /login.php` | End-to-end blocked |
| Admin | Not publicly offered (intended) | N/A | PASS | FAIL — DB connection fatal | FAIL — DB error occurs before safe credential response | PASS — `302 /login.php` | End-to-end blocked |

The required chain “credentials → session → role dashboard → protected page → logout → protected page denied” is therefore not currently demonstrable for any role.

## 3. Navigation audit

### File/route target check

Sixty visible navigation destinations were checked across the five roles; none of their target PHP files is missing. This only establishes route existence, not successful database-backed rendering.

| Role | Visible targets checked | Target-file result | Runtime result |
|---|---|---|---|
| Guest | Home, Vehicles, Login, Register, Vehicle Details, Logout; `#how-it-works` and `#why-us` anchors | All files/anchors exist | Home and Vehicles expose raw DB fatals; Login/Register GET pages load; submissions fail |
| Customer | Overview, Verification, Search Vehicles, Bookings, Inspection, Payment, Chat, Incidents, Driver Change Request, Return, Reviews, Profile, Notifications, Logout | All targets exist | Guest guard works; authenticated traversal blocked by login/DB failure |
| Owner | Dashboard, Vehicles, Drivers, Bookings, Earnings, Inspection, Chat, Replacement Requests, Profile, Logout | All targets exist | Guest guard works; authenticated traversal blocked by login/DB failure |
| Driver | Dashboard, Profile, Security, Documents, Owners, Availability, Leave, Safety Check, Trips, Vehicles, Report Incident, Messages, Notifications, Performance, Earnings | All targets exist | Guest guard works; authenticated traversal blocked by login/DB failure |
| Admin | Dashboard, Users, Vehicle Owners, Drivers, Vehicles, Bookings, Payments, Incidents, Replacement Requests, Settlements, Reports, Email Logs, Announcements, Logout | All targets exist | Guest guard works; authenticated Users route will fatal on a missing required model; all live navigation blocked by login/DB failure |

### Genuine navigation/UI-function failures

1. `public/index.php` and `public/vehicles.php` return HTTP 200 with a raw fatal body instead of a usable page because the DB connection is created during page construction.
2. `public/admin/users.php` requires the nonexistent `app/models/AdminCustomerVerification.php` before its guarded `try/catch`; an authenticated Admin cannot open User Management.
3. Customer Incidents is a visible hard-coded “Demo preview.” Its upload, map, submit, choice, and verification buttons are `type="button"` with `aria-disabled="true"`; it also displays fixed settlement figures such as `LKR 192,500` rather than database data.
4. Customer Driver Change and Return are visible “Prototype workflow” pages whose own messages state that records live only in the signed-in PHP session, not MySQL. They are not finalized database functionality.
5. Guest footer labels such as Support, Contact, FAQ, Privacy Policy, User Guidelines, and Rental Rules point to the two general homepage anchors rather than dedicated content. These are not 404s, but the labels do not lead to the specifically named content.

No redesign recommendation is made; these findings concern only broken or misleading behavior.

## 4. Required CRUD trace

### Driver — Leave Requests

Database table: `driver_leaves` (present in `database/lanka_renters.sql`).

| Operation | UI reachability | Controller | Model/SQL | Ownership/security | Audit result |
|---|---|---|---|---|---|
| CREATE | Leave form posts `action=request_leave` | `DriverController::requestLeave()` | `DriverLeave::create()` → `INSERT INTO driver_leaves` | Driver role guard, POST, CSRF; controller uses current Driver profile | Implemented; runtime DB test blocked |
| READ | Leave History list; `edit_id` loads one record | `DriverController::viewLeaveHistory()` | `getByDriverId()` / `getById()` → `SELECT` | History scoped by current driver; edit record is checked against Driver ID | Implemented; runtime DB test blocked |
| UPDATE | Pending record Edit link/form posts `action=edit_leave` | `DriverController::editLeave()` | `DriverLeave::update()` → `UPDATE driver_leaves ... WHERE id=:id AND status='pending'` | POST, CSRF, current-driver ownership check, pending-only | Implemented; runtime DB test blocked |
| DELETE | Pending record Delete form posts `action=delete_leave` (Cancel also exists) | `DriverController::deleteLeave()` / `cancelLeave()` | `DriverLeave::delete()` → `DELETE FROM driver_leaves WHERE id=:id AND status='pending'` | POST, CSRF, current-driver ownership check, pending-only | Implemented; runtime DB test blocked |

Conclusion: this is the only assigned feature with a genuine four-operation code path. It is not certifiable for the live demo until an actual Create→Read→Update→Delete cycle succeeds against the configured DB.

### Vehicle Owner — Vehicle Management

Database table: `vehicles` (present in `database/lanka_renters.sql`).

| Operation | UI reachability | Controller | Model/SQL | Ownership/security | Audit result |
|---|---|---|---|---|---|
| CREATE | Add Vehicle form posts `action=create_vehicle` | `VehicleController::createVehicle()` | `Vehicle::create()` → `INSERT INTO vehicles` | Owner role, POST, CSRF; session Owner ID is used | Implemented; runtime DB test blocked |
| READ | Owner vehicle table and edit selection | `VehicleController::getOwnerVehicles()` | `getByOwnerId()` / `getById()` → `SELECT` | Queries scope records by `owner_id` | Implemented; runtime DB test blocked |
| UPDATE | Edit form posts `action=update_vehicle`; status actions also update | `VehicleController::updateVehicle()` / `updateStatus()` | `Vehicle::update()` / `updateStatus()` → `UPDATE ... WHERE id=:id AND owner_id=:owner_id` | Owner role, POST, CSRF, Owner ID condition | Implemented; runtime DB test blocked |
| DELETE | No Delete UI action | No Delete controller method | No `Vehicle::delete()` and no `DELETE FROM vehicles` | N/A | **MISSING** |

`deactivate_vehicle` calls `Vehicle::deactivate()`, which executes `UPDATE vehicles SET status='unavailable'`; it cannot be presented as Delete.

### Customer — Reviews & Ratings

Database table: `ratings_reviews` (present in `database/lanka_renters.sql`).

| Operation | UI reachability | Controller | Model/SQL | Ownership/security | Audit result |
|---|---|---|---|---|---|
| CREATE | `reviews/create.php` submits the review form | `CustomerPortalController::reviewCreatePage()` | `CustomerPortal::createReview()` → `INSERT INTO ratings_reviews` | Customer bootstrap/role guard; booking must belong to signed-in Customer, be completed, and have no existing review | Implemented; runtime DB test blocked |
| READ | Review index and details pages | `reviewsPage()` / `reviewDetailsPage()` | `countReviews()`, `getReviews()`, `findReview()` → `SELECT` | All queries join `customers` and require current session `user_id` | Implemented; runtime DB test blocked |
| UPDATE | No edit route/form/action | No controller method | No update model method/SQL | N/A | **MISSING** |
| DELETE | No delete form/action | No controller method | No delete model method/SQL | N/A | **MISSING** |

### Admin — User Management

Primary tables intended by the page: `customers` and `users`.

| Operation | UI reachability | Controller/handler | Model/SQL | Role/security | Audit result |
|---|---|---|---|---|---|
| CREATE | No Admin Create User form | None | No Admin User Create workflow | N/A | **MISSING** |
| READ | `admin/users.php` intends pending/reviewed Customer lists | Page calls missing class directly | Required `AdminCustomerVerification` model file does not exist | Admin role guard is present | **BROKEN** |
| UPDATE | Approve/Reject POST forms target `customer_verification.php` | Handler intends verification-state update | Same missing model prevents execution | Admin role, POST, CSRF are present | **BROKEN** |
| DELETE | No Delete User form/action | None | No Admin user deletion workflow | N/A | **MISSING** |

This page is Customer verification management, not a complete Admin-owned Users CRUD.

## 5. Member CRUD readiness matrix

| Member | Entity | CREATE | READ | UPDATE | DELETE | Demo Ready |
|---|---|---|---|---|---|---|
| Driver | Leave Requests | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | **NO** |
| Vehicle Owner | Vehicle Management | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | **MISSING** | **NO** |
| Customer | Reviews & Ratings | CODE COMPLETE / DB BLOCKED | CODE COMPLETE / DB BLOCKED | **MISSING** | **MISSING** | **NO** |
| Admin | User Management | **MISSING** | **BROKEN — missing model** | **BROKEN — missing model** | **MISSING** | **NO** |

## 6. Runtime blocker audit

### Current live blockers

1. **Configured MySQL endpoint unavailable.** `app/config/database.php` targets `127.0.0.1;port=3308`. No listener was available on port 3308; the application reported `SQLSTATE[HY000] [2002] ... actively refused it`. A MySQL listener was observed on port 3306, but the configured root/blank-password credentials were rejected there, so changing only the port was not a valid audit workaround.
2. **Raw database exception exposure.** The public Home and Vehicles routes disclose exception class, SQLSTATE, absolute filesystem paths, and stack trace. Login/Register POST requests fail at controller/model construction in the same way.
3. **Admin User Management missing source dependency.** `app/models/AdminCustomerVerification.php` does not exist, while two Admin files require it.
4. **Required CRUD operations absent.** Owner Vehicle Delete; Customer Review Update/Delete; Admin User Create/Delete are not implemented. Admin User Read/Update are additionally broken by the absent model.
5. **No live CRUD evidence.** The configured DB outage prevents required DB verification, and the read-only freeze instruction prohibits record mutation even if the DB were available.

### Last reachable database/schema evidence

During the preceding read-only runtime inspection of this same workspace/database configuration, the reachable database did not match `database/lanka_renters.sql`. This could not be reconfirmed after port 3308 stopped accepting connections, but it remains a demo risk until verified:

- live `vehicles` lacked `image_url`, while `Vehicle::create()` inserts it and Owner inspection reads it;
- live `customers` lacked `district` and `address` used by Customer profile/verification;
- live `chat_messages` lacked `is_read` used by chat read-state updates;
- live `notifications` lacked `related_id` used by Customer notifications;
- live `payments` lacked `verified_by`, `verified_at`, and `failure_reason` used by Admin verification;
- live `invoices` was absent.

The checked-in schema does define these expected structures. No schema change was made during this audit.

### Checks that passed

- `public/admin/config/database.php` currently resolves the central helpers from the project root; no current `dirname(__DIR__)` failure was found there.
- `app/models/Driver.php` currently queries the checked-in/current migrated Driver document status naming; no current parse error exists.
- `app/models/ReplacementRequest.php` passes syntax validation.
- Whole-project PHP lint: **236 files checked, 0 syntax errors**.
- All checked visible navigation target files exist.
- Guest access to Customer, Owner, Driver, and Admin dashboards is denied with `302 /login.php`.

## 7. HTTP smoke-test record

| Route | HTTP result | Content result |
|---|---:|---|
| `/` | 200 | **FAIL** — raw PDO fatal/stack trace |
| `/vehicles.php` | 200 | **FAIL** — raw PDO fatal/stack trace |
| `/login.php` GET | 200 | PASS — Sign In UI loads |
| `/register.php` GET | 200 | PASS — Register UI loads |
| Login POST | 200/error body | **FAIL** — raw PDO fatal before credential validation |
| `/customer/dashboard.php` as guest | 302 → `/login.php` | PASS — guard |
| `/owner/dashboard.php` as guest | 302 → `/login.php` | PASS — guard |
| `/driver/dashboard.php` as guest | 302 → `/login.php` | PASS — guard |
| `/admin/dashboard.php` as guest | 302 → `/login.php` | PASS — guard |

Authenticated dashboard rendering, cross-role access, session-after-logout, and CRUD database cycles are **not verified in the current frozen runtime** because login cannot connect to MySQL.

## 8. Genuine blockers to interim readiness

1. The configured MySQL service on `127.0.0.1:3308` is unavailable, so authentication and database-backed pages cannot run.
2. Raw PDO/SQLSTATE exceptions and stack traces are exposed on public pages and authentication submissions.
3. Admin User Management requires a nonexistent `AdminCustomerVerification` model and lacks Create/Delete.
4. Owner Vehicle Management lacks Delete.
5. Customer Reviews & Ratings lacks Update and Delete.
6. None of the four required CRUDs has a currently successful MySQL Create→Read→Update→Delete runtime test; Driver Leave is code-complete but runtime-blocked.
7. Visible Customer Incidents, Driver Change, and Return workflows are explicitly disabled/demo/session-only rather than real MySQL functionality.
8. The last reachable live database showed schema drift from the checked-in source schema, including fields required by Owner Vehicle creation and several finalized pages; it must be reconfirmed once the configured DB is available.

## Final verdict

**NOT READY FOR INTERIM**
