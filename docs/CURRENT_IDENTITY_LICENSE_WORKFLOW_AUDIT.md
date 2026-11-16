# Current Customer Identity and Driving-Licence Workflow Audit

Date: 2026-09-15  
Scope: Read-only source and declared-schema audit before implementation.  
Constraint: No application code, database schema, data, or workflow logic was changed.

## Executive conclusion

The previous audit incorrectly treated `customers.verification_status` as if it represented both Customer identity approval and driving-licence approval. It does not.

The confirmed business workflow requires at least two independent decisions:

- Identity/NIC review: required for every Customer.
- Driving-licence review: required only when the Customer declares that they have a licence.

The current source does not implement that model. It has only one general Customer status, no Customer identity-document upload flow, no Customer licence-document upload flow, no “I do not have a driving licence” field, and no separate licence status. The current booking gate therefore cannot implement the required permission matrix.

The Driver document workflow is separate and must not be mistaken for a Customer document workflow.

Static inspection does not prove runtime success. Findings below are marked `[UNVERIFIED]` unless explicitly exercised with PHP, MariaDB, and real HTTP requests. No such end-to-end verification was performed for the revised audit.

## Status labels

- `[IMPLEMENTED]`: source and schema contain the described behavior, subject to runtime verification.
- `[PARTIAL]`: part of the behavior exists, but required stages or guarantees are missing.
- `[MISSING]`: no real source-backed implementation was found.
- `[CONFLICT]`: a route claims to call an implementation that is absent or inconsistent.
- `[SECURITY-HIGH]`: a high-impact security defect or unsafe exposure.
- `[UNVERIFIED]`: static evidence only; not exercised against the live application.

## 1. Customer document uploads

### NIC image

- Exact form: `[MISSING]`. `public/customer/profile/index.php:1-22` and `public/customer/verification/index.php:1-10` contain text inputs only. Neither form uses `enctype="multipart/form-data"` or an image input.
- Exact controller/handler: `[MISSING]`. `CustomerPortalController::profilePage()` and `::verificationPage()` accept only `$_POST` (`app/controllers/CustomerPortalController.php:76-126`, `:145-157`). No Customer upload handler reads `$_FILES`.
- Exact model: `[MISSING]`. `CustomerPortal::updateProfile()` updates scalar fields only (`app/models/CustomerPortal.php:95-130`). `Customer::update()` allows only `nic_number` and `driving_license_number` (`app/models/Customer.php:67-92`).
- Exact table/columns: `[MISSING]` for an image. `database/schema.sql:50-59` has `customers.nic_number`, `customers.driving_license_number`, and one `customers.verification_status`, but no NIC image path, document record, review reason, reviewer, or review timestamp.
- Exact storage directory: `[MISSING]` for Customer NIC images. The only matching document upload path found is Driver-specific `public/uploads/nics/` (`public/driver/documents.php:78-94`).
- Exact viewing endpoint: `[MISSING]` for Customer images. No Customer-authorized document endpoint was found.

Classification: `[MISSING] [UNVERIFIED]`. The existence of Driver NIC uploads does not satisfy Customer NIC upload requirements.

### Driving-licence image

- Exact form: `[MISSING]` for Customer. The Customer verification form has only `driving_license_number` text input (`public/customer/verification/index.php:9`).
- Exact controller/handler: `[MISSING]`. No Customer `$_FILES` path exists. The Driver upload page is `public/driver/documents.php:44-126` and is not a Customer route.
- Exact model: `[MISSING]` for Customer. `CustomerPortal::updateProfile()` stores only the number (`app/models/CustomerPortal.php:108-121`).
- Exact table/columns: `[MISSING]` for Customer. No Customer licence image column or Customer document table exists in `database/schema.sql:50-59`.
- Exact storage directory: `[MISSING]` for Customer licence images. `public/uploads/licenses/` is used by the Driver upload page (`public/driver/documents.php:80-94`).
- Exact viewing endpoint: `[MISSING]` for Customer. Driver pages render direct links such as `../uploads/...` (`public/driver/documents.php:216-224` and `:286-295`); this is not an authorized Customer endpoint.

Classification: `[MISSING] [UNVERIFIED]`.

### Existing Driver upload must not be conflated with Customer upload

`public/driver/documents.php:44-126` validates Driver uploads, stores them under `public/uploads/{nics,licenses,police_reports}/`, and calls `DriverController::uploadDocument()`. `DriverDocument::create()` persists them in `driver_documents` (`app/models/DriverDocument.php:28-76`). This proves only a Driver workflow.

## 2. “No driving licence” support

| Required capability | Evidence | Result |
| --- | --- | --- |
| `has_driving_license` or equivalent field | No match in application or declared SQL schema; `customers` has only the fields in `database/schema.sql:50-59` | `[MISSING] [UNVERIFIED]` |
| Registration option | `public/register.php` and `AuthController::register()` accept account fields and role, but no licence-choice field was found | `[MISSING] [UNVERIFIED]` |
| Profile/verification option | `public/customer/profile/index.php:15-16` and `public/customer/verification/index.php:9` expose only free-text number fields | `[MISSING] [UNVERIFIED]` |
| Conditional validation | `CustomerPortalController::profilePage()` validates length/format for general fields but has no licence-choice branch (`app/controllers/CustomerPortalController.php:90-111`) | `[MISSING] [UNVERIFIED]` |
| `not_submitted` or equivalent licence status | `customers.verification_status` allows only `pending`, `approved`, `rejected` (`database/schema.sql:55`); no licence status exists | `[MISSING] [UNVERIFIED]` |
| With-Driver-only enforcement for no-licence Customers | Booking logic has no licence-choice lookup or branch (`app/models/CustomerBooking.php:526-540`, `:551-558`) | `[MISSING] [UNVERIFIED]` |

The required “no licence” state must be explicit. It must not be represented by an empty number alone, because empty can also mean incomplete submission.

## 3. Separate verification statuses and required migration

### Current representation

`customers.verification_status` is one enum (`pending`, `approved`, `rejected`) at `database/schema.sql:50-59`. It is read by `CustomerPortalController::dashboardPage()` (`app/controllers/CustomerPortalController.php:24-48`) and enforced by `CustomerBooking::requireEligibleCustomer()` (`app/models/CustomerBooking.php:526-540`). No independent identity and licence decisions can be represented.

Classification: `[MISSING]` for separate statuses, `[UNVERIFIED]` for runtime behavior.

### Required schema direction

At minimum, a migration must add independent Customer fields:

- `identity_verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'`
- `driving_license_status ENUM('not_submitted','pending','approved','rejected') NOT NULL DEFAULT 'not_submitted'`
- `has_driving_license BOOLEAN NULL` or an equivalent explicit choice field
- `nic_image_path` and `driving_license_image_path` are technically possible columns, but a versioned `customer_documents` table is safer for replacement history and review metadata.

The recommended table should include Customer ownership, document type, number, private storage path, version, status, rejection reason, reviewer ID, review timestamp, upload timestamp, and current-version state. Terms acceptance should be stored separately or on the Customer verification submission with its content/version and timestamp.

The migration must not remove the existing general status until all consumers are migrated. A compatibility strategy is required during rollout.

## 4. Booking-type enforcement trace

### Self-drive creation

- UI control: `public/customer/components/booking/booking-form.php:31-36` posts `booking_type=self_drive`.
- Create route: `public/customer/bookings/create.php` calls `CustomerBookingController::createPage()`.
- Controller: `CustomerBookingController::validateBookingForm()` allows `self_drive` or `with_driver` (`app/controllers/CustomerBookingController.php:322-379`). For self-drive it forcibly sets `$driverId = null` (`:358-366`).
- Model: `CustomerBooking::createForCustomer()` calls `requireEligibleCustomer()` and `validateServiceAndDriver()` (`app/models/CustomerBooking.php:108-188`, `:551-558`). `validateServiceAndDriver()` accepts self-drive without a driver.
- SQL: `bookings.booking_type` and `bookings.driver_id` are persisted (`app/models/CustomerBooking.php:150-185`; schema booking definition must be migrated with the separate licence rule).

Current result: the general Customer verification gate blocks self-drive unless the one general status is `approved`; it does not inspect a separate licence decision. `[PARTIAL] [UNVERIFIED]`.

### With-Driver creation

- UI control: `booking-form.php:37-43` posts `booking_type=with_driver`; the Driver select is `driver_id` (`:66-77`).
- Controller: with-driver requires a vehicle with a driver price and a positive Driver ID (`CustomerBookingController.php:358-364`).
- Model: `validateServiceAndDriver()` requires `with_driver`, a valid price, and a selected Driver (`CustomerBooking.php:551-578`). `createForCustomer()` additionally checks Driver overlap (`:129-144`).
- SQL: selected `driver_id` and `booking_type` are persisted (`CustomerBooking.php:150-185`).

Current result: with-driver is structurally supported, but it is also blocked when the general Customer status is not approved. The required rule says an identity-approved Customer with licence pending/rejected/not-submitted must be allowed with Driver. `[PARTIAL] [UNVERIFIED]`.

### Booking updates that can change rental type

- UI route: `public/customer/bookings/edit.php:1-34` renders the same form for eligible future `pending_payment` bookings.
- Controller: `CustomerBookingController::editPage()` accepts and validates a new `booking_type` (`app/controllers/CustomerBookingController.php:181-280`, `:322-379`).
- Model: `CustomerBooking::updateCustomerBooking()` calls `requireEligibleCustomer()`, validates service/Driver, and writes `booking_type` and `driver_id` (`app/models/CustomerBooking.php:342-430`).
- SQL bypass resistance: the update is constrained by Customer ownership, `pending_payment`, and future start time (`CustomerBooking.php:400-425`).

Current result: a POST can attempt to change self-drive to with-driver or vice versa, but the same single-status gate applies. There is no licence-specific self-drive check. `[PARTIAL] [UNVERIFIED]`.

### Does `requireEligibleCustomer()` block all bookings or only self-drive?

It blocks all creation and update calls before service-type-specific permission is evaluated. The method rejects every Customer whose `customers.verification_status` is not `approved` (`app/models/CustomerBooking.php:526-540`). Therefore current behavior is:

- general status not approved: no self-drive and no with-driver;
- general status approved: both types may proceed if other rules pass.

That differs from the confirmed matrix. The required correction is to retain identity approval as the first gate, then apply the driving-licence status only when `booking_type === 'self_drive'`.

## 5. AdminCustomerVerification resolution

### Trace

1. Admin UI: no confirmed Customer verification UI was found in the current source search. `public/admin/users.php` is a user CRUD page and does not render a Customer identity review form (`public/admin/users.php:1-220`).
2. POST handler: `public/admin/customer_verification.php:1-53` requires `app/models/AdminCustomerVerification.php`, validates Admin role and CSRF, then calls `approveCustomer()` or `rejectCustomer()`.
3. Class/method definition: no `app/models/AdminCustomerVerification.php` or other class definition was found anywhere in the workspace. No definitions of `approveCustomer()` or `rejectCustomer()` were found.
4. SQL update: no reachable implementation was found.

Classification: `[CONFLICT] [MISSING] [UNVERIFIED]`. The endpoint is a call site to a missing dependency, not evidence of a working approval flow. It will fail when reached because the required file/class is absent.

## 6. Admin document review capabilities

| Capability | Evidence | Classification |
| --- | --- | --- |
| Securely view Customer NIC image | No Customer image storage or authorized endpoint exists | `[MISSING]` |
| Securely view Customer licence image | No Customer image storage or authorized endpoint exists | `[MISSING]` |
| Approve identity/NIC separately | Only missing `AdminCustomerVerification::approveCustomer()` is referenced; no independent status exists | `[MISSING] [CONFLICT]` |
| Approve licence separately | No Customer licence status or handler exists | `[MISSING]` |
| Reject identity separately | Missing class/method; one general status would not be independent | `[MISSING] [CONFLICT]` |
| Reject licence separately | No Customer licence status or handler exists | `[MISSING]` |
| Request resubmission | No Customer request/resubmission state or handler found | `[MISSING]` |
| Record rejection reason | Handler accepts `rejection_reason` (`customer_verification.php:43`), but no implementation exists to persist it | `[CONFLICT] [UNVERIFIED]` |
| Record reviewer ID | Handler obtains Admin ID (`customer_verification.php:35-36`), but no implementation exists to persist it | `[CONFLICT] [UNVERIFIED]` |
| Record review timestamp | No Customer review timestamp column or implementation found | `[MISSING]` |

## 7. Terms acceptance

No Customer identity terms checkbox, terms-version field, or terms acceptance persistence was found in the Customer registration/profile/verification paths.

- Displayed: `[MISSING]` for the required identity terms content.
- Required in HTML: `[MISSING]`.
- Validated server-side: `[MISSING]`.
- Stored in database: `[MISSING]`; `customers` has no terms columns (`database/schema.sql:50-59`).
- Timestamped: `[MISSING]`.
- Connected to terms content/version: `[MISSING]`.

This is more than a UI omission: acceptance must be tied to the exact terms version used for the identity submission. A standalone checkbox without persistence would be only `[UI-ONLY]` and would not satisfy the rule.

## 8. Upload security findings

### Customer identity uploads

There is no Customer identity upload workflow to assess. Every listed Customer upload-security capability is `[MISSING]`, not “secure by default”. A future implementation must not copy the Driver direct-public-link pattern.

### Existing Driver upload as a security comparison

Evidence: `public/driver/documents.php:44-126` and `:216-295`.

- Under public web root: yes, `public/uploads/...`; `[SECURITY-HIGH]` risk for identity documents.
- Direct URL access: yes, HTML emits `../uploads/...` links; no authorization endpoint is involved; `[SECURITY-HIGH]`.
- Authorization at view time: no per-request authorization check was found; `[SECURITY-HIGH]`.
- Stored filenames random: no. Names use `driver_{id}_{time()}.{extension}` (`public/driver/documents.php:90-94`), making them predictable.
- MIME checked with `finfo`: yes, conditionally (`:65-73`). If `fileinfo` is unavailable, the check is skipped.
- Size limited: yes, 5 MB (`:59-63`).
- SVG/PHP/HTML rejected: extension allowlist and MIME allowlist reject the normal forms (`:56-73`).
- Double extensions: not explicitly rejected; `pathinfo()` uses the final extension (`:55`), so this is not a robust filename-policy control even though MIME checks reduce risk.
- Path traversal: generated filenames and a fixed type-folder map prevent user-controlled path components in this path. This is `[PARTIAL]`, not a substitute for private storage and authorized download.
- Git exclusion: `.gitignore:2,21` ignores upload locations, which reduces accidental staging but does not protect deployed files.
- Orphaned files: if the database/model operation fails after `move_uploaded_file()`, the page deletes the moved file (`public/driver/documents.php:97-119`). Other failure/interruption cases are not transactionally coordinated with filesystem storage; replacement cleanup is therefore `[PARTIAL]`.

Required correction for Customer and preferably Driver identity documents: store files outside the public web root, use cryptographically random generated names, persist only controlled relative identifiers, and stream files through an authenticated owner/Admin endpoint after canonical-path and MIME checks.

## 9. Existing-user migration

Do not grant self-drive access merely because the legacy general status is `approved`.

Recommended migration classification:

1. Map the current `customers.verification_status` to `identity_verification_status` as follows: `pending -> pending`, `rejected -> rejected`, `approved -> approved`.
2. Set every existing Customer's new `driving_license_status` to `not_submitted` by default.
3. Set `has_driving_license` to an explicit migration-safe unknown/null value, or require the Customer to choose before any licence submission.
4. Do not infer a licence approval from the old general status or from a non-empty `driving_license_number`; the old system has no evidence of a reviewed licence image.
5. Consequently, existing Customers remain unable to self-drive until a separate licence decision is approved. Identity-approved existing Customers may use with-driver only, subject to driver availability and the other booking rules.

## 10. Revised implementation boundary

### Must modify

- `database/migrations/`: add a migration for separate identity/licence statuses, explicit licence choice, Customer document records or paths, terms version/acceptance timestamp, reviewer/reason/timestamp fields, and required indexes/foreign keys.
- `database/schema.sql`, `database/lanka_renters.sql`, and `database/lanka_renters_demo.sql`: keep declared schema/export contracts aligned with the migration after approval.
- `app/models/CustomerPortal.php`: read and persist the explicit licence choice, identity/licence document metadata, and terms submission fields through the authenticated Customer.
- `app/controllers/CustomerPortalController.php`: validate required NIC, conditional licence fields, terms acceptance, and resubmission behavior.
- `public/customer/verification/index.php`: add the required identity upload, licence-choice control, conditional licence upload/number fields, terms content/version acceptance, and independent status display.
- `app/models/CustomerBooking.php`: enforce identity approval for all bookings; enforce licence approval only for self-drive; preserve with-driver access for identity-approved Customers whose licence is not submitted, pending, or rejected.
- `app/controllers/CustomerBookingController.php` and `public/customer/components/booking/booking-form.php`: align server errors and UI availability with the same booking matrix. Server enforcement remains authoritative.
- New Customer/Admin verification model and handlers: implement separate review transactions, reviewer ID, timestamp, reason, and resubmission state.
- New authenticated document download endpoint and Admin review UI: never expose Customer identity files through direct public URLs.

### May modify only if necessary

- `app/models/Customer.php`: only if registration/profile creation must initialize the new fields.
- `app/controllers/AuthController.php` and `public/register.php`: only if the licence choice or terms acceptance belongs at registration rather than the verification submission.
- `app/helpers/AuthHelper.php` / `CustomerCsrf.php`: only if the new endpoints require a missing shared security primitive.
- `public/admin/users.php` or a new dedicated Admin Customer verification page: only after the missing `AdminCustomerVerification` contract is defined.
- Notification model/controller: only if approval/rejection notifications are confirmed as a business requirement.

### Must not modify

- `app/models/DriverDocument.php` and the Driver verification workflow, except for separately approved security hardening that does not alter Customer rules.
- Owner, vehicle, payment, incident, chat, and unrelated legacy modules.
- Existing Customer booking ownership, overlap, price, and Driver eligibility rules except where the new licence permission gate must be inserted.
- Seeded application credentials and unrelated local database configuration.

### New migration/document files

- One versioned database migration for the new Customer verification contract.
- A Customer verification model/service with an explicit status transition contract.
- An authenticated Customer/Admin document delivery endpoint.
- A focused test plan or test fixture covering the complete status matrix and upload authorization.

### Manual runtime-test requirements

After implementation, use a separate test database and exercise real PHP/MariaDB/HTTP flows:

1. Submit identity data without NIC image or terms: reject.
2. Submit NIC image and accepted terms: identity becomes pending; no booking.
3. Identity rejected: no booking.
4. Identity approved with no licence: with-driver succeeds; self-drive fails.
5. Identity approved with licence pending: with-driver succeeds; self-drive fails.
6. Identity approved with licence rejected: with-driver succeeds; self-drive fails.
7. Identity approved with licence approved: both booking types succeed when vehicle/Driver rules allow.
8. Change an existing pending-payment booking from self-drive to with-driver and back; repeat the permission checks server-side with manipulated POST values.
9. Confirm Customer and non-owner/non-Admin users cannot fetch document bytes by changing IDs or paths.
10. Confirm rejected submissions preserve reason, reviewer, timestamp, and permit resubmission without granting self-drive prematurely.

## Final assessment

Current Customer identity/licence workflow: `[MISSING/PARTIAL] [UNVERIFIED]`.  
Current Driver document workflow: separate, partially implemented, and publicly exposed in a way unsuitable for new Customer identity documents `[SECURITY-HIGH]`.  
Current Customer Admin approval route: `[CONFLICT] [MISSING]` because `AdminCustomerVerification.php` and its methods cannot be located.  
Current booking permission matrix: `[MISSING]`; the current single general status gate blocks all booking types before applying service-specific rules.

No application implementation was performed. Explicit approval is required before any code or schema change.
