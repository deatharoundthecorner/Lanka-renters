# Lanka Renters - Driver Module & Authentication Functionality Documentation

This document describes the current active implementation of the Authentication, Driver Portal, and Driver-Owner connection systems in the Lanka Renters project.

---

## 1. Module Overview
The Auth + Driver + Driver-Owner Integration module handles:
1. Secure central login, registration, and logout.
2. Centralized role-based access control (RBAC) guards.
3. Driver profile management with admin verification request queuing.
4. Version-controlled document replacement flows (NIC, License, Police Report).
5. Leave and shift availability toggling.
6. Vehicle pre-trip safety checklist submission.
7. Active trip pickup tracking with manual logs.
8. Driver-Owner link management (discovery, sending request, accept/reject).
9. Performance summaries (ratings & reviews) and monthly payout tracking.

---

## 2. My Responsibilities
- **Authentication**: Managing security protocols (CSRF, Password hashing, Session control).
- **Driver Portal**: Ensuring role guards and secure endpoints.
- **Verification Workflows**: Document replacements, profile/credentials modifications.
- **Operations & Collaboration**: Driver-owner links, assigned vehicles, trip tracking, safety checks, and incident reports.

---

## 3. Authentication
- **User Logins**: Handled by `AuthController->login($email, $password)`. Validates fields, queries `User->findByEmail()`, verifies hashes via `password_verify()`, asserts status = 'active', and writes credentials to session using `AuthHelper::login()`.
- **User Logouts**: Handled by `AuthController->logout()`. Clears session array, clears cookies, and redirects to `login.php`.
- **Session Control**: Session parameters set HttpOnly, SameSite='Lax', and Secure (if HTTPS is active) dynamically.
- **CSRF Protection**: CSRF tokens are generated via `AuthHelper::getCsrfToken()` and verified using `AuthHelper::validateCsrfToken($token)` on all POST requests.

---

## 4. Driver Registration
- **Process**: Users sign up via `public/register.php` (selecting customer, owner, or driver role).
- **Execution**: The request is routed to `AuthController->register($data)`.
- **Transaction Flow**:
  1. Validates input properties.
  2. Asserts email uniqueness via `User->findByEmail()`.
  3. Begins a database transaction.
  4. Inserts into `users` (status is default 'active', role is 'driver').
  5. Creates a matching record in `drivers` (default 'off_duty', rating = 5.0).
  6. Commits the transaction.
  7. Automatic session creation via `AuthHelper::login()` and redirect.

---

## 5. Driver Profile
- **Read**: Handled by `public/driver/profile.php` and `DriverController->dashboard()`. Reads from the joined `drivers` and `users` tables.
- **Profile Changes**: Address, phone, and emergency contact details are updated through `DriverController->updateProfile($data)`.
- **Admin Verification Request**: To prevent fraudulent profile updates:
  - Phone, Address, and Emergency Contact changes are intercept-logged in the `profile_change_requests` table as pending.
  - Active profile values remain unmodified until approved by an admin.
- **Deactivation**: Drivers can soft-delete their accounts via `DriverController->deactivateProfile()`, which updates `users.status = 'inactive'` and performs an automatic logout.

---

## 6. Driver Documents
- **Document Requirements**: NIC, Driving License, Police Report.
- **Upload Flow**: Drivers upload files in `public/driver/documents.php`, routed to `DriverController->uploadDocument($data)`.
- **MIME & File Verification**: Uploads are restricted to 5MB and validated using `FILEINFO_MIME_TYPE` (MIMEs allowed: jpeg, png, pdf; extensions allowed: jpg, jpeg, png, pdf).
- **Versioning Flow**:
  - Uploading a replacement creates a new version record in `driver_documents` with `status = 'pending'`, `is_current = FALSE`, and `version = max_version + 1`.
  - The previous approved version remains `is_current = TRUE` to prevent account locks during admin reviews.
- **Document Deletion**: Drivers can delete uploaded documents only if they are still in a `pending` status.

---

## 7. Admin Verification Integration
- **Account Verification Status**: Consolidated verification status is resolved dynamically in `Driver->getDashboardData()`:
  - Returns `rejected` if any current document is rejected.
  - Returns `pending` if less than 3 documents are uploaded or any document is pending.
  - Returns `approved` if all 3 documents are uploaded and approved.
- **Admin Document Review**: Admins approve or reject documents:
  - **Approval**: Sets the new document version to `approved` and `is_current = TRUE`. Marks the older version as `superseded` and `is_current = FALSE`.
  - **Rejection**: Sets the new document version to `rejected` and `is_current = FALSE` with a comments reason log. The older version remains active.
- **Admin Profile Review**: Admins approve or reject profile change requests:
  - **Approval**: Overwrites the active columns in `users` / `drivers` and sets status = `approved`.
  - **Rejection**: Sets status = `rejected` and records rejection reasons.

---

## 8. Driver Availability
- **Flow**: Toggle shift status (`available`, `busy`, `off_duty`) via `public/driver/availability.php` or dashboard card.
- **Endpoint**: Calls `DriverController->updateAvailability($status)` which writes to `drivers.availability_status` using the `DriverAvailability` model.
- **Discovery**: Only drivers with `availability_status = 'available'` and `verification_status = 'approved'` are selectable by owners in matching search results.

---

## 9. Driver Leave
- **Submit**: Log start date, end date, and reason in `public/driver/leave.php`.
- **Validation**: Checks that the start date does not fall after the end date. Inserts pending log into `driver_leaves` table.
- **Updates**: Drivers can edit or cancel (delete) leave requests *only if* the leave status remains `pending`.
- **Admin Review**: Admins approve or reject leave requests in the admin panel.

---

## 10. Driver Vehicles
- **Assignment**: Vehicle owners assign connected drivers to vehicles, writing to `vehicle_assignments`.
- **Read**: Drivers review active assigned vehicles in `public/driver/vehicles.php` and the dashboard sidebar.
- **Eligibility**: Multiple vehicles can be assigned to a driver, but only if the assignment remains active (status = 'active', unassigned_at IS NULL).

---

## 11. Driver-Owner Connections
- **Discovery**: Owners discover available, verified drivers.
- **Requests**: Owners send requests which write to `driver_owner_links` as `pending`.
- **Driver Decision**: Drivers accept or reject links in `public/driver/owners.php`.
- **Business Rules**:
  - Limit: A driver can connect with a maximum of 4 owners simultaneously.
  - Duplication check: Prevent duplicate pending or accepted requests.
  - Disconnects: Disconnecting removes the link from active lists.

---

## 12. Driver Trips
- **Access**: Assigned bookings display under `public/driver/trips.php`.
- **Pickup Status Steps**: Drivers update status through `DriverController->updatePickupStatus()`:
  - `pending_pickup` &rarr; `dispatched` &rarr; `arrived` &rarr; `picked_up` &rarr; `dropped_off`.
- **Atomic History**: Every status transition is written to `pickup_tracking` table alongside a manual driver's note.
- **Completion Trigger**: Setting status to `dropped_off` updates `bookings.status = 'completed'` and triggers driver payment generation.

---

## 13. Safety Checks
- **Submit Check**: Pre-trip inspection submitted in `public/driver/safety_check.php`.
- **Checks**: Brakes, lights, tires, fuel (boolean flags stored in `driver_vehicle_checks`).
- **Permissions**: Safety checks can be edited or deleted only if the trip status is still `pending_pickup` (before departure).

---

## 14. Incident Reporting
- **Submission**: Drivers log accidents or issues in `public/driver/report_incident.php`.
- **Security Check**: Asserts the booking is assigned to the reporting driver.
- **Photo Upload**: Supports uploading an incident photo with 5MB size limits and MIME validation. Logs to `incidents` and `incident_photos` tables.

---

## 15. Driver Notifications Center
- **Center**: Lists all notifications for the driver under `public/driver/notifications.php`.
- **Actions**: Shows incoming owner requests, alerts on document reviews, and allows marking alerts as read.

---

## 16. Driver Messaging/Chat
- **Chats Rooms**: Chat rooms are initialized per booking. Calls `ChatController->createBookingRoom($bookingId)`.
- **Message Exchange**: Handled in `public/driver/chat.php` using AJAX/polling (reading from `chat_messages` table).

---

## 17. Driver Performance
- **Metrics**: Displays average rating, completed trips count, month trips count, and driving hours.
- **Reviews**: Displays customer reviews and comments stored in `ratings_reviews`.

---

## 18. Driver Earnings
- **Data Source**: Payments generated in `driver_payments` upon trip completion.
- **Splits**: Displays accumulated paid earnings, pending earnings, and monthly stats.

---

## 19. Offline Payment Integration
- **Payment Philosophy**: Lanka Renters is strictly offline. No online payment gateways are integrated.
- **Flow**:
  - Customers pay owners/admin offline (cash/bank transfer).
  - Admin records payments in the `payments` table.
  - Driver payments are generated automatically as `pending` upon completing a booking. Admins clear these payouts manually to `paid`.

---

## 20. Database Tables
- `users`: Core login credentials.
- `drivers`: Driver metadata and ratings.
- `driver_documents`: Uploaded identity and license documents (versioned).
- `driver_owner_links`: Connection link pairings.
- `driver_leaves`: Vacation entries.
- `driver_payments`: Driver trip payouts.
- `pickup_tracking`: Booking progress tracking.
- `driver_vehicle_checks`: Inspection logs.

---

## 21. Controller Methods (`DriverController`)
- `getSecureDriver()`: Validates roles and returns driver profile context.
- `dashboard()`: Aggregates active stats and vehicles.
- `uploadDocument()`, `editDocument()`, `deleteDocument()`, `viewDocuments()`: CRUD for credentials.
- `updateAvailability()`: Toggle availability status.
- `requestLeave()`, `editLeave()`, `cancelLeave()`, `viewLeaveHistory()`: Leave requests.
- `updatePickupStatus()`, `viewTrips()`: Trip tracking.
- `acceptOwnerRequest()`, `rejectOwnerRequest()`, `viewOwnerRequests()`, `viewConnectedOwners()`: Connection link reviews.
- `submitSafetyCheck()`, `editSafetyCheck()`, `deleteSafetyCheck()`, `viewSafetyChecks()`: Pre-trip inspection audits.
- `reportIncident()`: Logs incident report.
- `viewPerformance()`: Returns review metrics.
- `viewEarningsDetail()`: Returns payouts split.
- `updateProfile()`, `updateAccount()`, `changePassword()`: Updates user information.
- `deactivateProfile()`: Soft-deletes user profile.

---

## 22. Model Methods
- `Driver`: `create()`, `findById()`, `findByUserId()`, `updateAvailability()`, `getDashboardData()`, `addPickupTracking()`, `updateProfile()`.
- `DriverDocument`: `create()`, `getByDriverId()`, `getByType()`, `approveDocument()`, `rejectDocument()`, `delete()`.
- `DriverOwnerLink`: `requestLink()`, `updateStatus()`, `getLinksByDriver()`, `getLinkCountByDriver()`.
- `DriverPayment`: `getEarningsSummary()`, `getPaymentHistory()`, `getEarningsSplit()`, `createPayment()`, `getMonthlyEarnings()`.

---

## 23. Routes/Pages (`public/driver/`)
- `dashboard.php`: Main overview and KPIs.
- `profile.php`: Contacts CRUD.
- `security.php`: Credential updates.
- `documents.php`: Upload credentials.
- `availability.php`: Set active shift.
- `leave.php`: Time-off requests.
- `vehicles.php`: Assigned vehicle details.
- `owners.php`: Review owner requests.
- `trips.php`: Update pickup status.
- `safety_check.php`: Pre-trip checklists.
- `report_incident.php`: Log breakdowns/accidents.
- `notifications.php`: Logs of approvals/rejections.
- `messages.php` & `chat.php`: Inbox and chat.
- `performance.php`: Reviews history.
- `earnings.php`: Completed trip transactions.

---

## 24. Authorization Rules
- Every driver view includes `AuthHelper::requireRole('driver')`.
- Spoofing or URL bypasses (e.g. from customers or owners) are immediately caught and redirected to their own dashboards or `login.php`.

---

## 25. Security Rules
- CSRF validation is mandatory on all data-mutating forms.
- File uploads are validated via binary signature/MIME checking and limited to 5MB.
- Mutating safety checks and leave requests is prohibited if status is no longer pending or trip has already commenced.

---

## 26. Dependencies on Other Modules
- **Vehicles Module**: Relies on vehicles listed by owners.
- **Bookings Module**: Relies on bookings initiated by customers and vehicle/driver assignments made by owners.
- **Admin Approvals**: Depends on admin pages to approve change requests and document uploads.

---

## 27. Data Flow
1. Driver registers &rarr; Profile created as pending.
2. Uploads 3 documents &rarr; Verification status changes from pending to approved upon admin review.
3. Availability toggle to 'Available' &rarr; Visible in owner search queries.
4. Owner sends link &rarr; Driver accepts &rarr; Assigned to vehicle/booking.
5. Trip commences &rarr; Driver logs status notes &rarr; Completes trip &rarr; Payment record generated &rarr; Admin manually records offline settlement.

---

## 28. Integration Points
- `driver_owner_links` (Owner ↔ Driver)
- `vehicle_assignments` (Owner Vehicles ↔ Driver)
- `bookings` (Customer ↔ Driver assignment)
- `driver_payments` (Trip completions ↔ Payment logs)

---

## 29. Known Limitations
- Real-time GPS location tracking is not implemented (requires active frontend JS tracking integration).
- Chat utilizes HTTP polling instead of WebSockets.

---

## 30. Testing Status
- Code Audit: **STATIC VERIFIED** (all routes, files, database migrations, controllers, and helpers are in place).
- Runtime Test: **NOT TESTED** (awaiting local database import and server startup).
