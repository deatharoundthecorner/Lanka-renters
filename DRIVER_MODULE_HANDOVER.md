# Lanka Renters - Driver Module Handover Document

This document outlines the purpose, architecture, database models, CRUD operations, integration points, and local testing instructions for the Lanka Renters Driver Module.

---

## 1. Driver Module Purpose
The Driver Module is designed for registered drivers to manage their bookings, verify profile credentials, request leaves, conduct vehicle pre-trip safety checklist inspections, record trip status transitions (manual note pickup tracking), communicate with customers/owners, and monitor performance and earnings.

---

## 2. Folder Structure
The Driver module is structured following the MVC pattern:

```
Lanka-renters/
├── app/
│   ├── controllers/
│   │   └── DriverController.php      # Main controller containing all driver action endpoints
│   ├── models/
│   │   ├── Driver.php               # Core driver model (availability, profile updates, deactivation)
│   │   ├── DriverDocument.php       # Verification credential uploads & edits
│   │   ├── DriverLeave.php          # Vacation/Leave submissions, edits, & cancellations
│   │   └── VehicleSafetyCheck.php   # Pre-trip checklist audits (brakes, lights, tires, fuel)
│   └── helpers/
│       └── AuthHelper.php           # Session check & CSRF token validations
├── public/
│   └── driver/                      # Main portal views
│       ├── availability.php         # Duty availability update interface
│       ├── dashboard.php            # Analytics metrics dashboard
│       ├── documents.php            # Credential CRUD panel (upload, replace, delete)
│       ├── earnings.php             # Trip payout transactions logs
│       ├── leave.php                # Time-off requests CRUD panel
│       ├── messages.php             # Real-time customer/owner chats index
│       ├── notifications.php        # System updates list
│       ├── performance.php          # Review stars rating summary
│       ├── profile.php              # Personal details CRUD panel
│       ├── report_incident.php      # Incident logging interface
│       ├── safety_check.php         # Vehicle checklist CRUD panel
│       ├── trips.php                # Booking logs & status note tracking
│       └── includes/                # Sidebar, Navbar, Header, and Footer partials
└── database/
    └── lanka_renters.sql            # Core database schema containing tables definitions
```

---

## 3. Database Tables Used

* **`drivers`**: Holds driver-specific profile columns (`address`, `emergency_contact`, `availability_status`, `rating_avg`). Linked to `users.id` via `user_id`.
* **`driver_documents`**: Stores credential type (NIC, License, Police Report), status, upload paths, and admin comments.
* **`driver_leaves`**: Logs start/end dates, reason, and request approval status.
* **`driver_vehicle_checks`**: Stores checklist records (brakes, lights, tires, fuel) checked before trips.
* **`driver_owner_links`**: Pairs drivers with vehicle owners for shift vehicle assignments.
* **`driver_payments`**: Logs earnings generated per completed booking trip.
* **`pickup_tracking`**: Logs manual status updates (`pending_pickup`, `dispatched`, `arrived`, `picked_up`, `dropped_off`) and driver notes.

---

## 4. CRUD Operations Implemented

* **Driver Profile**:
  - **Read**: View personal details on `profile.php`.
  - **Update**: Edit phone, address, and emergency contact details.
  - **Delete**: Soft-delete/deactivate profile (sets `users.status = 'inactive'`).
* **Documents Management**:
  - **Create**: Upload new documents (5MB size limits, binary MIME verification).
  - **Read**: View verification approval statuses and comments.
  - **Update**: Edit document number/expiry or replace rejected/pending files.
  - **Delete**: Cancel/remove document logs and files **only if status is pending**.
* **Leave Requests**:
  - **Create**: Submits date ranges and reasons.
  - **Read**: Log history overview.
  - **Update / Delete**: Edit or cancel request **only if status is pending**.
* **Vehicle Safety Checks**:
  - **Create**: Records pre-trip checklists.
  - **Read**: Logs previous inspections.
  - **Update / Delete**: Edit or delete checklist **only if the trip is pending pickup (`bookings.pickup_status = 'pending_pickup'`)**.

---

## 5. Integration Points

### Owner Integration
* **Pairing Requests**: Owners send connection requests which write to `driver_owner_links` as pending. Drivers accept or reject these requests.
* **Vehicle Assignment**: Linked owners assign vehicles to drivers (`vehicle_assignments`), which populates the assigned vehicles card on the driver dashboard.

### Customer Integration
* **Trips & Booking Status**: Drivers update trip pickup status. Completing a trip (`dropped_off`) automatically triggers payout entries in `driver_payments` and alerts the customer via `notifications`.
* **Messaging**: Chats are established between drivers and customers via shared chat room messages.

### Admin Integration
* **Verification**: Admins verify driver credentials (`driver_documents`). Statuses ('approved', 'rejected') affect driver booking availability.
* **Leaves**: Admins review and approve/reject request logs in `driver_leaves`.

---

## 6. Local Testing Instructions

1. **Database Import**:
   Import `database/lanka_renters.sql` into your local database server.
2. **Server Port Configuration**:
   Ensure `app/helpers/Database.php` matches your local database port settings (Lanka Renters defaults to localhost, port 3308 for local XAMPP environments).
3. **Login credentials**:
   Log in as a driver (ensure role is `'driver'` in `users` table).
4. **Testing Workflows**:
   - Go to **My Profile** -> Edit phone/address. Check updates reflect in database.
   - Go to **Documents** -> Upload a test file. Try editing and deleting it.
   - Go to **Leave Requests** -> Log a leave. Verify end date validation works.
   - Go to **Safety Checklist** -> Log a check for an assigned trip. Try editing or deleting it before the trip starts.
   - Go to **My Trips** -> Select an active trip and update the status dropdown with a manual note.

---

## 7. Files Protected From Modification by Other Branches
To prevent code breakages and merge conflicts, other feature branches should not modify:
* `public/driver/**` (Driver portal views)
* `app/controllers/DriverController.php` (Driver core operations)
* `app/models/Driver*.php` (Driver database methods)
* `app/models/VehicleSafetyCheck.php` (Pre-trip checks DB methods)
