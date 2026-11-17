# Customer Identity and Driving-Licence Implementation Report

Date: 2026-09-15
Scope: Focused Phase 2 implementation based on the approved identity/licence workflow audit.

## 1. Summary

Implemented the Customer verification foundation with independent identity and driving-licence decisions.

- Every submission requires an NIC number, NIC image, explicit terms acknowledgement, and a licence-choice selection.
- Licence holders must provide a licence number and image; non-licence Customers receive `not_submitted` rather than rejection.
- Admin identity and licence reviews are separate and support approval, rejection, and resubmission-required outcomes.
- New Customer documents are stored outside the public web root and served through role-guarded PHP endpoints.
- Booking authorization now requires approved identity for all booking types and approved licence status only for self-drive.
- Existing UI structure, CSS classes, PHP/MariaDB stack, authentication, CSRF conventions, booking calculations, availability checks, and unrelated modules were preserved.

This report does not claim the full workflow is production-ready. The complete upload/review/booking matrix still requires the manual runtime scenarios listed below.

Verification pass note: this workspace is not a Git worktree, so a Git baseline diff was unavailable. The exact implementation file inventory below is based on the Phase 2 changes present in the workspace and source inspection. The current database was backed up before isolated testing.

## 2. Exact files changed

- `.gitignore`
- `database/migrations/010_customer_identity_license_verification.sql`
- `database/schema.sql`
- `app/models/CustomerVerification.php`
- `app/controllers/CustomerPortalController.php`
- `public/customer/verification/index.php`
- `public/customer/document.php`
- `app/models/CustomerBooking.php`
- `app/controllers/CustomerBookingController.php`
- `public/customer/components/booking/booking-form.php`
- `public/admin/customer_verification.php`
- `public/admin/customer_document.php`
- `public/admin/partials/sidebar.php`
- `README_RUN_LOCAL.md`

The Customer demo export `database/lanka_renters_demo.sql` was intentionally not edited.

Registration and authentication files were not modified. `app/config/database.php` was temporarily pointed at the isolated test database and restored to `lanka_renters`; it is not a final implementation change.

No unrelated Owner, Driver, payment, incident, chat, review, vehicle, or styling files were changed.

## 2a. Exact implementation seams reviewed

Migration 010 executes these exact database operations:

- `ALTER TABLE customers` adds `identity_verification_status`, `driving_license_status`, `has_driving_license`, identity/licence reason/reviewer/timestamp fields, `terms_version`, and `terms_accepted_at`, while preserving legacy `verification_status`.
- `UPDATE customers` maps legacy `approved`, `rejected`, and other values to the new identity status and initializes licence status to `not_submitted` with unknown licence choice.
- `CREATE TABLE customer_documents` adds Customer ownership, `nic`/`driving_license` type, private `storage_key`, MIME, version, status, current flag, reason, reviewer, review/upload/create/update timestamps, indexes, and foreign keys.

Booking authorization changes are limited to `CustomerBooking::getCustomerContext()`, `createForCustomer()`, `updateCustomerBooking()`, and `requireEligibleCustomer()`. The latter requires identity approval for every booking and requires `has_driving_license = 1` plus `driving_license_status = approved` only for `self_drive`.

Customer upload handling is in `CustomerPortalController::stageIdentityUpload()`. It writes only to `storage/customer-identity-documents/{customer_id}/{random_bytes_name}.jpg|png`, outside `public/`. No Customer document URL is emitted; the two delivery endpoints resolve by database document ID and authorize before reading the canonical private path.

Admin transitions are implemented by `CustomerVerification::review()` and the POST branch of `public/admin/customer_verification.php`. Allowed values are exactly `approved`, `rejected`, and `resubmission_required`; rejection/resubmission requires a reason; licence review is rejected when `has_driving_license` is false; only a current `pending` document can be reviewed; status, reason, reviewer, timestamps, and document state update transactionally.

The temporary test-only database name was `lanka_renters_phase2_verify`. The pre-test backup was created and retained outside the workspace at `C:\Users\user\AppData\Local\Temp\lanka-renters-verification-test-db-backup_20260915_113818.sql`.

## 3. Migration and database structures

Migration added:

- `database/migrations/010_customer_identity_license_verification.sql`

New `customers` columns:

- `identity_verification_status`
- `driving_license_status`
- `has_driving_license`
- `identity_rejection_reason`
- `identity_reviewed_by`
- `identity_reviewed_at`
- `license_rejection_reason`
- `license_reviewed_by`
- `license_reviewed_at`
- `terms_version`
- `terms_accepted_at`

New table:

- `customer_documents`

The table stores Customer ownership, `nic` or `driving_license` type, document number, private `storage_key`, MIME type, version, current flag, review status, reason, reviewer, timestamps, and foreign keys.

The legacy `customers.verification_status` column remains. Identity review keeps it synchronized for compatibility with existing dashboard consumers; the new identity/licence columns are authoritative for booking decisions.

## 4. Existing-user migration

Migration 010 maps legacy status as follows:

- legacy `approved` -> `identity_verification_status = approved`
- legacy `rejected` -> `identity_verification_status = rejected`
- legacy `pending` -> `identity_verification_status = pending`
- all existing licence states -> `driving_license_status = not_submitted`
- all existing licence choices -> `has_driving_license = NULL`

No existing licence number or legacy approval state grants self-drive permission.

The local configured MariaDB database was checked after migration. Existing rows showed identity status mapped independently and licence status remaining `not_submitted`; no licence approval was inferred.

## 5. Verification workflow

`CustomerVerification` owns:

- session-scoped Customer reads
- submission validation contract
- document version creation and current-record retirement
- separate Admin review transitions
- rejection/resubmission reason persistence
- reviewer ID and review timestamp persistence
- authorized document lookup

Customer submission is handled by `CustomerPortalController::verificationPage()` and the existing Customer verification route. The UI uses the existing Customer layout and form classes, with JavaScript limited to showing/disabling conditional licence fields. Server-side validation remains authoritative.

The terms identifier is currently `customer-identity-v1`. Acceptance timestamp is stored with the submission.

## 6. Booking matrix enforcement

`CustomerBooking::requireEligibleCustomer()` now applies:

1. active Customer account check;
2. independent identity status check for every booking create/update;
3. independent licence approval check only when `booking_type = self_drive`.

Therefore the intended server-side behavior is:

| Identity | Licence | Result |
| --- | --- | --- |
| Pending/rejected/resubmission required | Any | No booking |
| Approved | Not submitted/pending/rejected/resubmission required | With Driver only |
| Approved | Approved | Self-drive or With Driver |

The controller and booking form also communicate and present the same rule, but direct or manipulated POST requests are protected by the model gate.

Existing ownership, vehicle availability, overlap, Driver eligibility, dates, prices, payment status, and booking lifecycle rules were not rewritten.

## 7. Document access protection

New Customer documents are stored under:

- `storage/customer-identity-documents/{customer_id}/{random_filename}`

The directory is outside `public/` and is ignored by Git.

Uploads require:

- JPEG or PNG MIME type detected by `finfo`
- available `fileinfo` support
- maximum size of 5 MB
- successful `getimagesize()` validation
- generated `random_bytes()` filename

Documents are delivered only through:

- `public/customer/document.php` for the owning Customer
- `public/admin/customer_document.php` for an authenticated Admin

Both endpoints resolve by database document ID, verify authorization, canonicalize the private path, restrict the final MIME type, and send no-store and anti-sniffing headers.

The existing Driver public upload workflow was not modified by this focused implementation.

## 8. Admin review interface

The previous `AdminCustomerVerification` call path was replaced with the canonical `public/admin/customer_verification.php` page and handler.

The page provides:

- Customer submission list
- Customer account details
- NIC/licence numbers
- terms version and acceptance timestamp
- separate identity and licence status panels
- secure current-image links
- approve, reject, and request-resubmission actions
- required reason for rejection/resubmission
- reviewer and timestamp display

Customers who selected no licence display `No licence submitted — Driver required.` and do not receive licence review controls.

## 9. Static checks executed

Passed:

- PHP syntax checks for every modified PHP file.
- Migration content check for independent statuses and `customer_documents`.
- Private storage `.gitignore` check.
- Workspace source scan confirming no remaining PHP reference to the missing `AdminCustomerVerification` class.
- MariaDB migration application and schema-object verification.

The PowerShell environment did not provide `git` or `rg`; equivalent file/content checks were used where possible.

## 10. Runtime checks executed

Each item below has one classification. No source-only inference is classified as a runtime pass.

### Authentication and navigation

| Test | Classification | Evidence |
| --- | --- | --- |
| Guest `GET /login.php` | `PASS — executed` | HTTP 200 observed. |
| Admin login, redirect, protected page | `PASS — executed` | HTTP 302 login response; Admin dashboard HTTP 200 with Admin content. |
| Owner login, redirect, protected page | `PASS — executed` | HTTP 302 login response; Owner dashboard HTTP 200 with Owner content. |
| Driver login, redirect, protected page | `PASS — executed` | HTTP 302 login response; Driver dashboard HTTP 200 with Driver content. |
| Customer login response | `PASS — executed` | HTTP 302 response observed for `customer@example.com`. |
| Customer complete redirect chain and final dashboard | `BLOCKED` | Source shows `/customer/dashboard/index.php` -> `/customer/dashboard.php`; the clean follow-up HTTP command was blocked by the terminal's PowerShell continuation state, so no final HTTP 200 is claimed. |
| Wrong Customer password | `PASS — executed` | HTTP 200 with invalid-credentials message. |
| Admin cross-role access to Customer route | `PASS — executed` | HTTP 302 role-protection response. |
| Admin logout and protected page after logout | `PASS — executed` | Logout HTTP 302; protected Admin page HTTP 302 afterward. |
| Customer verification page | `PASS — executed` | Authenticated Customer verification GET HTTP 200 with NIC upload and licence-choice controls. |

### Customer multipart submission

| Test | Classification | Evidence |
| --- | --- | --- |
| Valid multipart no-licence upload | `PASS — executed` | Dummy PNG; HTTP 303; identity pending, licence `not_submitted`, `has_driving_license = 0`, current NIC only, stale licence cleared. |
| Valid multipart has-licence upload | `PASS — executed` | Dummy PNG files; HTTP 303; identity pending, licence pending, both current documents, terms timestamp, no reviewer metadata. |
| Missing NIC number | `PASS — executed` | HTTP 200 with `Enter your NIC number.`; no successful submission. |
| Missing terms | `NOT EXECUTED` | Not separately captured in a reliable completed request. |
| Missing NIC image | `NOT EXECUTED` | Not separately captured in a reliable completed request. |
| Missing licence number/image, invalid MIME, oversized, PHP, SVG, corrupted, mismatched extension, duplicate numbers | `NOT EXECUTED` | Not executed as individually attributable completed requests. |
| Multipart file cleanup | `PASS — executed` | Temporary dummy files and isolated private storage were removed after testing; temporary database was dropped. |

The accurate summary is: **Valid multipart upload: PASS**. **Invalid multipart upload cases: NOT EXECUTED**, except missing NIC number, which was executed and passed.

### Admin review and document delivery

| Test | Classification | Evidence |
| --- | --- | --- |
| Authenticated Admin identity approval POST | `PASS — executed` | CSRF-protected HTTP POST for Customer ID 1; HTTP 302; identity and NIC became approved; reviewer ID 1 and review timestamp persisted. |
| Authenticated Admin licence approval POST | `PASS — executed` | CSRF-protected HTTP POST for Customer ID 1; HTTP 302; licence and document became approved; reviewer ID 1 and review timestamp persisted. |
| Licence rejection without reason | `PASS — executed` | Authenticated Admin POST returned HTTP 302 and did not perform a reasonless rejection; later licence approval remained possible. |
| Customer-owner document delivery | `NOT EXECUTED` | No reliable authenticated Customer-owner byte-response result was captured. |
| Admin document delivery | `PASS — executed` | Authenticated Admin endpoint returned HTTP 200, `Content-Type: image/png`, and the dummy image response was non-empty; no private filesystem path was exposed. |
| Guest document delivery | `PASS — executed` | Guest request was denied by role guard with HTTP 302. |
| Different Customer, Owner, Driver document delivery | `NOT EXECUTED` | No reliable completed requests were captured for these identities. |
| Admin invalid status/ID/CSRF and no-licence licence review | `NOT EXECUTED` | Not individually executed in the final pass. |

### Booking authorization

| Test | Classification | Evidence |
| --- | --- | --- |
| Pending identity with-driver denial | `PASS — executed` | Authoritative `CustomerBooking::createForCustomer()` path raised the identity-approval rule exception. |
| Approved identity plus `not_submitted` self-drive denial | `PASS — executed` | Authoritative model path raised the approved-licence rule exception. |
| Approved identity plus `not_submitted` with-driver success | `BLOCKED` | The untouched demo Driver had no approved Driver documents, so existing Driver eligibility correctly blocked this unrelated prerequisite. |
| Approved identity plus approved licence self-drive success | `NOT EXECUTED` | No complete HTTP booking success was captured. |
| Manipulated ineligible self-drive POST | `NOT EXECUTED` | No reliable completed HTTP booking POST was captured. |
| Full create/update booking matrix | `BLOCKED` | Missing approved Driver fixture prevents a clean with-driver success path; the full matrix was not executed. |

### Regression and setup

| Test | Classification | Evidence |
| --- | --- | --- |
| Fresh demo import plus Migration 010 | `PASS — executed` | Untouched export was streamed into isolated database context, Migration 010 applied, required schema objects and four users verified. |
| All four seeded login credentials | `PARTIAL` | Admin, Owner, Driver final pages passed; Customer final dashboard chain was later verified separately, but the full four-role fresh-copy dashboard suite was not rerun after the live repair. |
| Driver Leave CRUD | `NOT EXECUTED` | No isolated CRUD cycle was run. |
| Owner Vehicle CRUD | `NOT EXECUTED` | No isolated CRUD cycle was run. |
| Customer Review CRUD | `NOT EXECUTED` | No isolated CRUD cycle was run. |
| Admin assigned CRUD | `NOT EXECUTED` | No isolated CRUD cycle was run. |
| Other interim navigation/ownership/availability regressions | `NOT EXECUTED` | Outside the focused executed checks. |

## 10b. Final runtime-correction pass

| Test | Classification | Evidence |
| --- | --- | --- |
| Environment confirmation | `PASS — executed` | Project directory, PHP 8.2.12, MariaDB 10.4.32 on port 3308, database configuration, and current project `public` server were confirmed. Existing duplicate project PHP servers were left running. |
| Active database backup | `PASS — executed` | Non-empty backup created at `C:\Users\user\AppData\Local\Temp\lanka_renters_before_verification_20260915_135526.sql`. |
| Live schema classification | `PASS — executed` | `customer_documents` existed with its expected indexes/foreign keys, but all new Customer columns were absent. Classification: **partial Migration 010 state**. |
| Verification runtime diagnosis | `PASS — executed` | Before repair, the Customer row existed and the verification page rendered `Verification details unavailable`; the missing live Customer columns were the demonstrated database mismatch. |
| Safe schema repair | `PASS — executed` | Applied only additive missing Customer columns, reviewer foreign keys, and legacy-status mapping. Existing `customer_documents` table and rows were preserved. |
| Customer redirect after repair | `PASS — executed` | Browser reached `/customer/dashboard.php` with HTTP 200 and dashboard content through the intended `/customer/dashboard/index.php` compatibility hop. |
| Customer verification page after repair | `PASS — executed` | Browser HTTP 200; form rendered NIC number/image, licence choice, terms checkbox, conditional controls, and separate statuses. The unavailable message was absent. |
| Valid no-licence multipart submission | `PASS — executed` | Plain Customer credentials via external curl cookie jar; dummy PNG; HTTP 303; identity pending, licence `not_submitted`, choice false, terms timestamp set, one pending NIC row. |
| Admin dummy document delivery | `PASS — executed` | Plain Admin credentials; protected endpoint returned HTTP 200, `Content-Type: image/png`, and 68 non-empty bytes. |
| Admin identity approval | `PASS — executed` | CSRF-protected Admin POST returned HTTP 302; Customer/document became approved with reviewer ID 1 and review timestamp. |
| Test data cleanup | `PASS — executed` | Temporary document row deleted, private dummy file removed, `customer_documents` returned to zero rows, and seeded Customer fields/status restored. |

## 11. Manual tests still required

Use a separate test database and test Customer/document fixtures to verify:

- missing NIC image and missing terms rejection
- missing licence number/image when licence choice is yes
- successful no-licence submission
- invalid MIME, SVG, PHP, HTML, malformed image, and oversized upload rejection
- identity and licence pending states
- independent Admin approval/rejection/resubmission
- required rejection/resubmission reasons
- Customer/Admin/other-role document access boundaries
- failed upload/database cleanup
- every booking permission matrix row
- manipulated POST attempts on create and update
- unchanged Owner, Driver, Admin CRUD, payment, incident, chat, and navigation behavior

## 11a. Interim-readiness verdict

| Requirement | Result |
| --- | --- |
| Authentication for all roles | `PARTIAL` |
| Customer verification submission | `PARTIAL` |
| Admin identity review | `PASS — executed` |
| Admin licence review | `PASS — executed` |
| No-licence with-Driver rule | `BLOCKED` |
| Self-drive licence restriction | `PASS — executed` |
| Navigable UIs | `PARTIAL` |
| Four assigned CRUD modules | `NOT EXECUTED` |
| Fresh-copy setup | `PASS — executed` |

Final verdict: **PARTIALLY VERIFIED — NOT YET INTERIM DEMO READY**.

## 12. Known limitations and blockers

- `database/lanka_renters_demo.sql` was not regenerated because the requested full runtime verification has not completed. A fresh demo import must apply migration 010 before using the new workflow.
- Existing Driver identity documents remain in the legacy public upload workflow. That security concern was identified in the audit but intentionally left outside this focused Customer implementation.
- The Admin list/detail route is implemented, but full review verification requires seeded document fixtures and a separate test database.
- The untouched demo export contains a hardcoded `CREATE DATABASE/USE lanka_renters` target, so the fresh-database portability test required an in-memory database-name substitution in the import stream; the export file was not changed. Migration 010 then applied successfully.
- A complete with-driver booking success test requires approved Driver document fixtures, which are absent from the untouched demo dataset. It was not fabricated.
- The Customer profile page still exposes legacy scalar identity fields; the canonical document submission flow is the Customer Verification page.
- Root cause of the runtime failure: the active database was in a partial Migration 010 state. `customer_documents` existed with its expected structure, but `customers` lacked `identity_verification_status`, `driving_license_status`, `has_driving_license`, terms fields, and reviewer fields. `CustomerVerification::getForUser()` selected those missing columns and caught the resulting database exception, producing the generic `Verification details unavailable` page.
- Minimal correction made: additive repair only. No table was dropped, no existing document row was deleted, no demo export was reimported, and no credentials/ports/UI/authentication code was changed.
- The complete has-licence multipart path, separate licence Admin approval, invalid upload matrix, document authorization matrix for every role, complete booking matrix, four CRUD cycles, and full visible-navigation smoke remain `NOT EXECUTED` or `BLOCKED` and are not represented as passes.

## 13. Preservation confirmation

No framework, Composer package, npm package, frontend library, route rename, authentication redesign, colour/layout redesign, or unrelated module rewrite was introduced. Existing booking calculations and availability logic were preserved, and the new authorization is limited to the required identity/licence decision boundary.
