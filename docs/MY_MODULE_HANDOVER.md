# Lanka Renters - Driver Module Handover Document

This document outlines the architecture, database models, API/controller endpoints, integration requirements, and current testing status of the Driver Module to facilitate integration with other system components (Customer, Owner, and Admin modules).

---

## 1. Scope of Implementation
The following components are implemented and statically verified:
- **Authentication & RBAC guards** (`AuthHelper.php`, `AuthController.php`).
- **Driver Profile & Identity Requests** (`Driver.php`, `ProfileChangeRequest.php`, `AccountChangeRequest.php`).
- **Versioned Document Submission** (`DriverDocument.php` for NIC, license, and police report).
- **Shift Availability Toggle** (`DriverAvailability.php`).
- **Leave Request Management** (`DriverLeave.php`).
- **Owner-Driver Linking** (`DriverOwnerLink.php`).
- **Pre-trip Safety Checklist** (`VehicleSafetyCheck.php`).
- **Trip Pickup Tracking** (manual logging in `pickup_tracking` via `Driver->addPickupTracking()`).
- **Performance Summaries** (`DriverPerformance.php`).
- **Driver Payment Payout Generation** (`DriverPayment.php`).

---

## 2. Source Code Inventory
The following files comprise the Driver and Authentication modules:
- **Helpers**:
  - `app/helpers/AuthHelper.php` (Session management, requireRole guards, CSRF tokens)
- **Controllers**:
  - `app/controllers/AuthController.php` (Authentication, registrations, logins, redirects)
  - `app/controllers/DriverController.php` (Driver operations backend handlers)
- **Models**:
  - `app/models/Driver.php` (Core driver updates, pickup updates)
  - `app/models/DriverDocument.php` (Credentials version CRUD, status updates)
  - `app/models/DriverOwnerLink.php` (Link pairing CRUD)
  - `app/models/DriverLeave.php` (Leave history CRUD)
  - `app/models/VehicleSafetyCheck.php` (Pre-trip checks logs)
  - `app/models/DriverPayment.php` (Payout log insertions and aggregate retrievals)
- **Portal Views (`public/driver/`)**:
  - `dashboard.php`, `profile.php`, `security.php`, `documents.php`, `availability.php`, `leave.php`, `vehicles.php`, `owners.php`, `trips.php`, `safety_check.php`, `report_incident.php`, `notifications.php`, `messages.php`, `chat.php`, `performance.php`, `earnings.php`.

---

## 3. Database Integration Map
The Driver module utilizes the following database tables:
- **`users`**: Login credentials (email, name, role, status).
- **`drivers`**: Associated user details, shift status, and stars average.
- **`driver_documents`**: Stores files (NIC, license, police report) and versioning indicators.
- **`driver_owner_links`**: Tracks pairings between drivers and vehicle owners.
- **`driver_leaves`**: Logs driver time-off bookings.
- **`driver_vehicle_checks`**: Stores pre-trip safety checkboxes.
- **`pickup_tracking`**: Logs status updates during active trips.
- **`driver_payments`**: Stores credit generated per booking.

---

## 4. API Endpoints for Team Integration

### For the Owner Module:
- **Driver Discovery**: Fetch list of eligible drivers using `Driver->getAvailableVerifiedDrivers()`. Returns drivers who are `available` and have an `approved` document verification status.
- **Request Connections**: Owners send requests to drivers via `DriverOwnerLink->requestLink($driverId, $ownerId)`.
- **Get Connected Drivers**: Fetch drivers connected to the owner using `DriverOwnerLink->getAcceptedDriversByOwner($ownerId)`.

### For the Admin Module:
- **Verify Documents**: Admins review pending documents via `DriverDocument->approveDocument($id, $adminId)` and `DriverDocument->rejectDocument($id, $reason, $adminId)`.
- **Verify Profile Changes**: Admins process pending updates via `ProfileChangeRequest->approve()` and `ProfileChangeRequest->reject()`.
- **Verify Account Changes**: Admins process pending email/username updates via `AccountChangeRequest->approve()` and `AccountChangeRequest->reject()`.
- **Approve Leaves**: Admins approve or reject leaves in `driver_leaves`.

### For the Customer/Booking Module:
- **Trip Status Updates**: Drivers update travel statuses using `DriverController->updatePickupStatus($bookingId, $status)`. Updating status to `dropped_off` automatically triggers booking completion and generates driver payments.
- **Reviews**: Customer feedback writes to `ratings_reviews` with `driver_id`. Ratings are aggregated via `DriverPerformance->getRatingSummary($driverId)`.

---

## 5. Security & Integration Rules — DO NOT BREAK
1. **Never Bypass Guards**: Every driver view/handler must be protected by calling `AuthHelper::requireRole('driver');` first.
2. **Secure Post Data**: Do not pass `driver_id` from client-side POST inputs. Always resolve the driver context on the server side using the session ID via `DriverController->getSecureDriver()`.
3. **Atomic Operations**: All profile updates, document reviews, and trip completions MUST execute inside database transactions.
4. **No Online Payment Gateway**: Lanka Renters strictly uses offline payments. Do not introduce any online gateways (Stripe, PayPal, card APIs). Payments are recorded manually.

---

## 6. Known Limitations & Future Work
- **GPS Coordinates**: Currently, coordinates are not sent from the mobile or web view during travel updates.
- **Real-time Chats**: Chat messages use AJAX HTTP polling. Moving to WebSockets is a potential future update.
- **Leave Overlaps**: Submitting a leave request does not check if the driver is currently assigned to a booking that overlaps with the requested dates.

---

## 7. Handover Testing Status
- Code Audit: **STATIC VERIFIED** (all controllers, logic structures, and variables check out).
- Runtime Test: **NOT TESTED** (requires database setup).
