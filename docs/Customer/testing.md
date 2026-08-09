# Customer Module Phase 7 Testing

## Environment

- Branch: `Customer`; baseline commit: `cd9e73d`.
- XAMPP: Apache 2.4.58, PHP 8.2.12 and MariaDB 10.4.32.
- Current configured database: `lanka_renters` at `127.0.0.1:3306`.
- Non-mutating HTTP tests used `tests/Customer/Phase3TestRouter.php` with real development Customer rows. It uses temporary session storage and does not write database data.
- Browser controller startup failed before a session could be created. Browser/viewport/keyboard/console tests are therefore **Blocked**.
- No private upload, credentials, personal data or session value was included in this report.

## Result meanings

**Pass** = performed successfully. **Fail** = performed and unsuccessful. **Blocked** = environment or shared prerequisite unavailable. **Not Run** = deliberately skipped to avoid mutating non-disposable development data.

## Inventory and results

| ID | Feature / route | Storage | Preconditions and steps | Expected / actual result | Result | Evidence / defect / retest |
| --- | --- | --- | --- | --- | --- | --- |
| P7-01 | Customer PHP/JS syntax | N/A | Lint Customer routes, controllers, models and helpers; check scripts. | 105 PHP and 3 JS files passed. | Pass | Automated check. |
| P7-02 | Existing foundation/UI tests | N/A | Run Phase 1 and Phase 3 tests. | Both exit 0. | Pass | Phase 1 retains a non-failing historical port-3308 note. |
| P7-03 | Guest guard: dashboard and cancellation POST | Database/session | Request both without session. | HTTP 302 to `/login.php`; no action occurs. | Pass | HTTP checks. |
| P7-04 | Driver guard: dashboard and proof download | Database/session | Request isolated Driver fixture. | HTTP 302 to `/driver/dashboard.php`. | Pass | HTTP checks. |
| P7-05 | Admin/Owner guards; login/logout/back button | Shared auth | Use Admin/Owner fixtures and approved login credentials in browser. | Correct role redirect and session destruction. | Blocked | Browser and fixtures unavailable; shared auth was not changed. |
| P7-06 | Session protection | Shared auth | Review helper and foundation test. | `HttpOnly`, `SameSite=Lax`, dynamic HTTPS `Secure`, regenerated login ID. | Pass (static) | Runtime cookie/browser test blocked. |
| P7-07 | Dashboard, verification, profile and sidebar navigation | Mixed | GET all sidebar destinations as authenticated Customer. | Dashboard, verification, profile, vehicles, bookings, inspections, payments, chat, incidents, driver-change, returns, reviews and notifications each return HTTP 200. | Pass | HTTP route sweep. |
| P7-08 | Vehicle search/details | Database | Submit safe SQL-style search; open valid vehicle in browser. | Search returns HTTP 200 without database error; visual detail test blocked. | Pass / Blocked | Search check passed; browser detail test blocked. |
| P7-09 | Booking Create/Update/Cancel | Database | Use disposable verified Customer, bookable vehicle and Driver; submit valid/invalid cases. | Dates, eligibility, overlap, price and status rules enforced; cancellation preserves row. | Not Run | No disposable database copy; shared rows not changed. |
| P7-10 | Booking Read and IDOR | Database | Request own booking 9, another Customer's booking 9, zero, injection-style and duplicate IDs. | Owner HTTP 200; non-owner/invalid IDs HTTP 404. | Pass | Generic not-found responses; no disclosure. |
| P7-11 | Payment history/details/evidence IDOR | Database/filesystem | Request owned payment 3 and same record as another Customer. | Owner HTTP 200; non-owner HTTP 404. | Pass | HTTP ownership check. |
| P7-12 | Payment submit and upload runtime | Database/filesystem | Submit valid/invalid safe sample files in disposable DB. | Booking total is authoritative; duplicate/status/amount forgery rejected. | Not Run | Needs disposable DB and non-private fixtures. |
| P7-13 | Payment upload/download source safeguards | Database/filesystem | Review controller/model. | MIME `finfo`, `is_uploaded_file`, 5 MB limit, allowlist, random name, owned-ID download and path containment are present. | Pass (static) | Runtime JPEG/PNG/PDF/invalid-file matrix not run. |
| P7-14 | Notifications | Database | Load page and POST without CSRF. | HTTP 200 load; write rejected with HTTP 403. | Pass | Owned data / valid mark-read not run. |
| P7-15 | Chat | Database | Load page; POST start/send without CSRF; review output. | HTTP 200 load; writes HTTP 403; message output escaped. | Pass | Valid send, refresh and cross-room runtime cases not run. |
| P7-16 | Incidents/replacement decisions | Database/demo session | Load page; POST create/decision without CSRF. | HTTP 200 load; writes HTTP 403; no Customer approval/assignment action. | Pass | Eligible write flow not run. |
| P7-17 | Driver change and returns | Demo session | Load routes and POST without CSRF. | HTTP 200 load; writes HTTP 403; visible demo label, no production-table claim. | Pass | Valid create/duplicate/disable-mode tests not run. |
| P7-18 | Inspections | Mixed read-only | Load inspection route and review views. | HTTP 200; Customer presentation remains read-only. | Pass | Linked-inspection detail fixture unavailable. |
| P7-19 | Reviews | Database | Load page; POST without CSRF; review output. | HTTP 200 load; write HTTP 403; rating/text output constrained/escaped. | Pass | Completed-booking and duplicate runtime tests not run. |
| P7-20 | CSRF all write routes | Database/demo session | POST profile, booking create/edit/cancel, payment, notification, chat, incident, replacement, driver change, return and review without token. | All 12 writes returned HTTP 403 before controller logic. | Pass | Valid/altered/second-session token tests not run. |
| P7-21 | SQL injection/server validation | Database | Use safe SQL-like search/filter/detail values. | Filters return 200; invalid IDs 404; no SQL/PDO error text. | Pass | Views have no direct SQL; models use prepared statements. |
| P7-22 | XSS/escaping | Database/demo session | Review chat, payment, incident, return, driver-change and review output. | Dynamic output uses `htmlspecialchars`; no untrusted `innerHTML` found. | Pass (static) | Stored payload test not run. |
| P7-23 | Database integrity | Database | Read-only orphan and duplicate review queries; Customer model read smoke test. | Checked booking/payment/incident/review/chat/notification orphans = 0; duplicate reviews = 0; model reads pass. | Pass | No data changed. |
| P7-24 | Navigation/assets/static integrity | N/A | Scan placeholders, merge markers, Customer view SQL, imports, scripts, deletions and frameworks. | No active conflict/placeholder/direct-view-SQL/permanent-deletion/framework marker; all 9 CSS imports exist. | Pass | Zero-byte `.gitkeep` is not an active route. |
| P7-25 | Responsive/browser refresh/console | Browser/XAMPP | Test 1440×900, 1024×768, 390×844 and refresh after POST. | Usable responsive shell with no console error or duplicate POST. | Blocked | Browser controller unavailable. |
| P7-26 | Accessibility interaction | Browser/XAMPP | Keyboard/focus/zoom/heading/label/screen-reader review. | Reachable controls, visible focus, readable status/error messages. | Blocked | Static labels and semantic structure are present; interaction test needs browser. |

## Defects

| ID | Severity | Title / route | Owner | Fix and retest |
| --- | --- | --- | --- | --- |
| P7-D01 | Medium | Test router used XAMPP's non-writable CLI session directory and absent hard-coded Customer ID 93, blocking authenticated regression tests. | Customer test harness | Updated `tests/Customer/Phase3TestRouter.php` to use `sys_get_temp_dir()` and runtime active Customer fixtures, including a second Customer fixture. Retest passed: guest/Driver redirects, dashboard 200, own records 200 and other-Customer records 404. |

## Remaining coordination and test work

- Browser-dependent visual, responsive, console, keyboard, zoom and logout tests remain blocked by browser-controller startup failure.
- Valid state-changing booking/payment/incident/review/demo tests and upload MIME matrix remain not run until a disposable database copy and safe non-private files are available.
- The current configured port is 3306; the Phase 1 test's historical 3308 note should be aligned by the repository/database coordinator.
- Following explicit approval after the initial Phase 7 test pass, nullable `customers.district` and `customers.address` fields were added to the schema and local development database. Driver-change, return and replacement-decision records remain explicitly labelled server-side demo-session data because the approved schema has no Customer-owned table for them.

## Safe local commands

```powershell
C:\xampp\php\php.exe tests\Customer\Phase1FoundationTest.php
C:\xampp\php\php.exe tests\Customer\Phase3UiTest.php
C:\xampp\php\php.exe -l tests\Customer\Phase3TestRouter.php
```

Do not stage private payment evidence from `storage/customer-payment-evidence/`.
