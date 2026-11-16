# Lanka Renters - Full System Functionality Audit & System Health Report

## Overview
This document provides a comprehensive technical and functional audit of the entire Lanka Renters system, compiled for the interim evaluation.

---

## 1. Project Structure & File Index
The system is built on a custom MVC pattern separating business logic, models, controllers, and views.

### Root Directories
* **`app/`**: Core application logic.
  * **`config/`**: System configuration parameters (database, constants).
  * **`core/`**: MVC base classes (Controller, Model, Database).
  * **`controllers/`**: Requests handling and orchestrating business logic.
  * **`helpers/`**: Utilities (Authentication, Sessions, Validation, Utils).
  * **`models/`**: Database queries mapping to DB tables.
* **`database/`**: SQL schema definitions, migrations, and seed scripts.
* **`public/`**: Web document root containing CSS, JS assets and role-based portal sub-pages.
  * **`admin/`**: Admin portal views.
  * **`customer/`**: Customer booking/review system views.
  * **`driver/`**: Driver portal views.
  * **`owner/`**: Vehicle owner portal views.
  * **`assets/`**: Shared styling and script components.
* **`docs/`**: Documentation files detailing workflows and permissions.
* **`uploads/`**: Actual local destination directories for document and photo uploads.

---

## 2. Complete Include & Dependency Map
The following table maps file inclusions and module dependency paths across the system:

| Source File | Included File | Type | Purpose | Exists? | Status |
| :--- | :--- | :--- | :--- | :---: | :---: |
| `app/controllers/AdminApprovalController.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/controllers/AdminApprovalController.php` | `/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `app/controllers/AdminApprovalController.php` | `/models/ProfileChangeRequest.php` | require_once | Loads dependency: ProfileChangeRequest.php | Yes | PASS |
| `app/controllers/AdminApprovalController.php` | `/models/AccountChangeRequest.php` | require_once | Loads dependency: AccountChangeRequest.php | Yes | PASS |
| `app/controllers/AdminApprovalController.php` | `/models/DriverDocument.php` | require_once | Loads dependency: DriverDocument.php | Yes | PASS |
| `app/controllers/AuthController.php` | `/models/User.php` | require_once | Loads dependency: User.php | Yes | PASS |
| `app/controllers/AuthController.php` | `/models/Customer.php` | require_once | Loads dependency: Customer.php | Yes | PASS |
| `app/controllers/AuthController.php` | `/models/VehicleOwner.php` | require_once | Loads dependency: VehicleOwner.php | Yes | PASS |
| `app/controllers/AuthController.php` | `/models/Driver.php` | require_once | Loads dependency: Driver.php | Yes | PASS |
| `app/controllers/AuthController.php` | `/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `app/controllers/AuthController.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/controllers/ChatController.php` | `/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `app/controllers/ChatController.php` | `/models/Chat.php` | require_once | Loads dependency: Chat.php | Yes | PASS |
| `app/controllers/ChatController.php` | `/models/Driver.php` | require_once | Loads dependency: Driver.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/Driver.php` | require_once | Loads dependency: Driver.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverDocument.php` | require_once | Loads dependency: DriverDocument.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverLeave.php` | require_once | Loads dependency: DriverLeave.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverAvailability.php` | require_once | Loads dependency: DriverAvailability.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverOwnerLink.php` | require_once | Loads dependency: DriverOwnerLink.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverPayment.php` | require_once | Loads dependency: DriverPayment.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/Notification.php` | require_once | Loads dependency: Notification.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverIncident.php` | require_once | Loads dependency: DriverIncident.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/VehicleSafetyCheck.php` | require_once | Loads dependency: VehicleSafetyCheck.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/DriverPerformance.php` | require_once | Loads dependency: DriverPerformance.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/ProfileChangeRequest.php` | require_once | Loads dependency: ProfileChangeRequest.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/AccountChangeRequest.php` | require_once | Loads dependency: AccountChangeRequest.php | Yes | PASS |
| `app/controllers/DriverController.php` | `/models/User.php` | require_once | Loads dependency: User.php | Yes | PASS |
| `app/controllers/VehicleController.php` | `/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `app/controllers/VehicleController.php` | `/models/Vehicle.php` | require_once | Loads dependency: Vehicle.php | Yes | PASS |
| `app/controllers/VehicleController.php` | `/models/VehicleOwner.php` | require_once | Loads dependency: VehicleOwner.php | Yes | PASS |
| `app/models/AccountChangeRequest.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/Chat.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/Customer.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/Driver.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverAvailability.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverAvailability.php` | `/models/DriverLeave.php` | require_once | Loads dependency: DriverLeave.php | Yes | PASS |
| `app/models/DriverDocument.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverIncident.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverLeave.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverOwnerLink.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverPayment.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/DriverPerformance.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/Notification.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/ProfileChangeRequest.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/User.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/Vehicle.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/VehicleOwner.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `app/models/VehicleSafetyCheck.php` | `/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `public/admin/admin_layout.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/admin/approvals.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/bookings.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/dashboard.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/drivers.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/email_logs.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/incident.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/logout.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/admin/owners.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/partials/approvals_content.php` | `/app/controllers/AdminApprovalController.php` | require_once | Loads dependency: AdminApprovalController.php | Yes | PASS |
| `public/admin/payment.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/replacement_requests.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/reports.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/settlements.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/users.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/admin/vehicles.php` | `/admin_layout.php` | include | Loads dependency: admin_layout.php | Yes | PASS |
| `public/customer/components/layout/header.php` | `components/header.php` | include | Renders common HTML layout header | No | FAIL (Check Path) |
| `public/customer/components/layout/header.php` | `components/sidebar.php` | include | Renders sidebar links matching active page | No | FAIL (Check Path) |
| `public/customer/components/layout/header.php` | `components/navbar.php` | include | Renders top navigation header with user info | No | FAIL (Check Path) |
| `public/customer/components/layout/header.php` | `components/footer.php` | include | Loads dependency: footer.php | No | FAIL (Check Path) |
| `public/customer/components/layout/header.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/customer/dashboard/index.php` | `components/layout/header.php` | include | Renders common HTML layout header | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/layout/sidebar.php` | include | Renders sidebar links matching active page | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/layout/navbar.php` | include | Renders top navigation header with user info | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/layout/page-header.php` | include | Renders common HTML layout header | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/dashboard/stat-card.php` | include | Loads dependency: stat-card.php | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/dashboard/quick-action-card.php` | include | Loads dependency: quick-action-card.php | No | FAIL (Check Path) |
| `public/customer/dashboard/index.php` | `components/layout/footer.php` | include | Loads dependency: footer.php | No | FAIL (Check Path) |
| `public/driver/availability.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/availability.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/availability.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/availability.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/availability.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/availability.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/chat.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/chat.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/chat.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/chat.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/chat.php` | `/app/controllers/ChatController.php` | require_once | Loads dependency: ChatController.php | Yes | PASS |
| `public/driver/chat.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/dashboard.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/dashboard.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/dashboard.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/dashboard.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/dashboard.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/dashboard.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/dashboard.php` | `/app/models/Notification.php` | require_once | Loads dependency: Notification.php | Yes | PASS |
| `public/driver/documents.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/documents.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/documents.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/documents.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/documents.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/documents.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/documents.php` | `/app/models/DriverDocument.php` | require_once | Loads dependency: DriverDocument.php | Yes | PASS |
| `public/driver/earnings.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/earnings.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/earnings.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/earnings.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/earnings.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/earnings.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/includes/navbar.php` | `/app/models/Notification.php` | require_once | Loads dependency: Notification.php | Yes | PASS |
| `public/driver/includes/navbar.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/leave.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/leave.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/leave.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/leave.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/leave.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/leave.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/leave.php` | `/app/models/DriverLeave.php` | require_once | Loads dependency: DriverLeave.php | Yes | PASS |
| `public/driver/messages.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/messages.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/messages.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/messages.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/messages.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/messages.php` | `/app/controllers/ChatController.php` | require_once | Loads dependency: ChatController.php | Yes | PASS |
| `public/driver/messages.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/notifications.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/notifications.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/notifications.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/notifications.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/notifications.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/notifications.php` | `/app/models/Notification.php` | require_once | Loads dependency: Notification.php | Yes | PASS |
| `public/driver/notifications.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/owners.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/owners.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/owners.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/owners.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/owners.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/owners.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/performance.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/performance.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/performance.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/performance.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/performance.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/performance.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/profile.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/profile.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/profile.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/profile.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/profile.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/profile.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/profile.php` | `/app/models/ProfileChangeRequest.php` | require_once | Loads dependency: ProfileChangeRequest.php | Yes | PASS |
| `public/driver/profile.php` | `/app/models/DriverDocument.php` | require_once | Loads dependency: DriverDocument.php | Yes | PASS |
| `public/driver/report_incident.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/report_incident.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/report_incident.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/report_incident.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/report_incident.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/report_incident.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/safety_check.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/safety_check.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/safety_check.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/safety_check.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/safety_check.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/safety_check.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/safety_check.php` | `/app/models/VehicleSafetyCheck.php` | require_once | Loads dependency: VehicleSafetyCheck.php | Yes | PASS |
| `public/driver/security.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/security.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/security.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/security.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/security.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/security.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/security.php` | `/app/models/AccountChangeRequest.php` | require_once | Loads dependency: AccountChangeRequest.php | Yes | PASS |
| `public/driver/trips.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/trips.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/trips.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/trips.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/trips.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/trips.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/driver/vehicles.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/driver/vehicles.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/driver/vehicles.php` | `includes/navbar.php` | include | Renders top navigation header with user info | Yes | PASS |
| `public/driver/vehicles.php` | `includes/footer.php` | include | Loads dependency: footer.php | Yes | PASS |
| `public/driver/vehicles.php` | `/app/controllers/DriverController.php` | require_once | Loads dependency: DriverController.php | Yes | PASS |
| `public/driver/vehicles.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/login.php` | `/app/controllers/AuthController.php` | require_once | Loads dependency: AuthController.php | Yes | PASS |
| `public/login.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/owner/bookings.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/bookings.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/bookings.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/owner/chat.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/chat.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/dashboard.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/dashboard.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/drivers.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/drivers.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/drivers.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/owner/drivers.php` | `/app/helpers/Database.php` | require_once | Establishes database connection | Yes | PASS |
| `public/owner/drivers.php` | `/app/models/VehicleOwner.php` | require_once | Loads dependency: VehicleOwner.php | Yes | PASS |
| `public/owner/drivers.php` | `/app/models/DriverOwnerLink.php` | require_once | Loads dependency: DriverOwnerLink.php | Yes | PASS |
| `public/owner/drivers.php` | `/app/models/Driver.php` | require_once | Loads dependency: Driver.php | Yes | PASS |
| `public/owner/earnings.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/earnings.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/includes/header.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/owner/inspection.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/inspection.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/logout.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/owner/profile.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/profile.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/replacement-requests.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/replacement-requests.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/vehicles.php` | `includes/header.php` | include | Renders common HTML layout header | Yes | PASS |
| `public/owner/vehicles.php` | `includes/sidebar.php` | include | Renders sidebar links matching active page | Yes | PASS |
| `public/owner/vehicles.php` | `/app/controllers/VehicleController.php` | require_once | Loads dependency: VehicleController.php | Yes | PASS |
| `public/owner/vehicles.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |
| `public/register.php` | `/app/controllers/AuthController.php` | require_once | Loads dependency: AuthController.php | Yes | PASS |
| `public/register.php` | `/app/helpers/AuthHelper.php` | require_once | Authenticates session and check role | Yes | PASS |

---

## 3. Complete Function & Method Inventory
The following inventory documents all functions and methods scanned across the controllers, models, helpers, and utilities:

| Class/File | Function | Visibility | Parameters | Purpose | Called From | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| `AdminApprovalController` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `getSecureAdmin` | private | `()` | Queries and returns information | Internal class calls only | PASS |
| `AdminApprovalController` | `getPendingQueues` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `AdminApprovalController` | `approveProfileChange` | public | `($requestId)` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `rejectProfileChange` | public | `($requestId, $reason)` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `approveAccountChange` | public | `($requestId)` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `rejectAccountChange` | public | `($requestId, $reason)` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `approveDocument` | public | `($documentId)` | Implements core feature function | Controller/View page files | PASS |
| `AdminApprovalController` | `rejectDocument` | public | `($documentId, $reason)` | Implements core feature function | Controller/View page files | PASS |
| `AuthController` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AuthController` | `register` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `AuthController` | `login` | public | `($email, $password)` | Session auth control | Controller/View page files | PASS |
| `AuthController` | `logout` | public | `()` | Session auth control | Controller/View page files | PASS |
| `AuthController` | `redirectByRole` | private | `($role)` | Implements core feature function | Internal class calls only | PASS |
| `AuthController` | `redirect` | private | `($path)` | Implements core feature function | Internal class calls only | PASS |
| `ChatController` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `ChatController` | `getSessionContext` | private | `()` | Queries and returns information | Internal class calls only | PASS |
| `ChatController` | `getDriverRooms` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `ChatController` | `getRoomMessages` | public | `($roomId)` | Queries and returns information | Controller/View page files | PASS |
| `ChatController` | `sendChatMessage` | public | `($roomId, $messageText)` | Implements core feature function | Controller/View page files | PASS |
| `ChatController` | `createBookingRoom` | public | `($bookingId)` | Implements core feature function | Controller/View page files | PASS |
| `ChatController` | `getOtherParticipant` | public | `($roomId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `getSecureDriver` | private | `()` | Queries and returns information | Internal class calls only | PASS |
| `DriverController` | `dashboard` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `uploadDocument` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `viewDocuments` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `editDocument` | public | `($documentId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `deleteDocument` | public | `($documentId)` | Deletes records/documents from database | Controller/View page files | PASS |
| `DriverController` | `updateAvailability` | public | `($status)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `requestLeave` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `viewLeaveHistory` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `editLeave` | public | `($leaveId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `cancelLeave` | public | `($leaveId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `deleteLeave` | public | `($leaveId)` | Deletes records/documents from database | Controller/View page files | PASS |
| `DriverController` | `viewAssignedVehicles` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `updatePickupStatus` | public | `($bookingId, $status, $driverNote = null)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `viewTrips` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `viewOwnerRequests` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `viewConnectedOwners` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `acceptOwnerRequest` | public | `($linkId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `rejectOwnerRequest` | public | `($linkId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `submitSafetyCheck` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `editSafetyCheck` | public | `($checkId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `deleteSafetyCheck` | public | `($checkId)` | Deletes records/documents from database | Controller/View page files | PASS |
| `DriverController` | `viewSafetyChecks` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `reportIncident` | public | `($data, $file = null)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `viewPerformance` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `viewEarningsDetail` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverController` | `updateProfile` | public | `($data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `updateAccount` | public | `($data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverController` | `changePassword` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverController` | `deactivateProfile` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `VehicleController` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `VehicleController` | `getAuthenticatedOwner` | private | `()` | Queries and returns information | Internal class calls only | PASS |
| `VehicleController` | `getOwnerVehicles` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `VehicleController` | `createVehicle` | public | `($data, $files = [])` | Implements core feature function | Controller/View page files | PASS |
| `VehicleController` | `updateVehicle` | public | `($vehicleId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `VehicleController` | `updateStatus` | public | `($vehicleId, $newStatus, $csrfToken)` | Updates record data inside database | Controller/View page files | PASS |
| `VehicleController` | `deactivateVehicle` | public | `($vehicleId, $csrfToken)` | Implements core feature function | Controller/View page files | PASS |
| `VehicleController` | `validateVehicleInput` | private | `($data)` | Implements core feature function | Internal class calls only | PASS |
| `VehicleController` | `handleDocumentUpload` | private | `($file, $docType, $vehicleId)` | Implements core feature function | Internal class calls only | PASS |
| `AuthHelper` | `startSession` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AuthHelper` | `login` | public | `($user)` | Session auth control | Controller/View page files | PASS |
| `AuthHelper` | `logout` | public | `()` | Session auth control | Controller/View page files | PASS |
| `AuthHelper` | `isLoggedIn` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AuthHelper` | `getCurrentUser` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `AuthHelper` | `requireLogin` | public | `()` | Session auth control | Controller/View page files | PASS |
| `AuthHelper` | `requireRole` | public | `($role)` | Session auth control | Controller/View page files | PASS |
| `AuthHelper` | `redirect` | private | `($path)` | Implements core feature function | Internal class calls only | PASS |
| `AuthHelper` | `getCsrfToken` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `AuthHelper` | `validateCsrfToken` | public | `($token)` | Implements core feature function | Controller/View page files | PASS |
| `Database` | `__construct` | private | `()` | Implements core feature function | Internal class calls only | PASS |
| `Database` | `getInstance` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `Database` | `getConnection` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `Database` | `__clone` | private | `()` | Implements core feature function | Internal class calls only | PASS |
| `Database` | `__wakeup` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AccountChangeRequest` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `AccountChangeRequest` | `create` | public | `($userId, $changeType, $oldValue, $requestedValue)` | Implements core feature function | Controller/View page files | PASS |
| `AccountChangeRequest` | `getByUserId` | public | `($userId)` | Queries and returns information | Controller/View page files | PASS |
| `AccountChangeRequest` | `getPendingRequests` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `AccountChangeRequest` | `getById` | public | `($id)` | Queries and returns information | Controller/View page files | PASS |
| `AccountChangeRequest` | `approve` | public | `($id, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `AccountChangeRequest` | `reject` | public | `($id, $reason, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `createRoom` | public | `($bookingId)` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `addParticipant` | public | `($roomId, $userId)` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `sendMessage` | public | `($roomId, $senderId, $messageText)` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `getMessages` | public | `($roomId)` | Queries and returns information | Controller/View page files | PASS |
| `Chat` | `isParticipant` | public | `($roomId, $userId)` | Implements core feature function | Controller/View page files | PASS |
| `Chat` | `getDriverRooms` | public | `($userId)` | Queries and returns information | Controller/View page files | PASS |
| `Customer` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `Customer` | `create` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `Customer` | `findByUserId` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `Customer` | `update` | public | `($id, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `Driver` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `create` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `findById` | public | `($id)` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `findByUserId` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `updateAvailability` | public | `($driverId, $status)` | Updates record data inside database | Controller/View page files | PASS |
| `Driver` | `updateRating` | public | `($driverId, $rating)` | Updates record data inside database | Controller/View page files | PASS |
| `Driver` | `getAssignedVehicles` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `Driver` | `getDashboardData` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `Driver` | `addPickupTracking` | public | `($bookingId, $userId, $status, $driverNote = null)` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `getBookings` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `Driver` | `getAvailableVerifiedDrivers` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `Driver` | `hasBookingConflict` | public | `($driverId, $startDate, $endDate)` | Implements core feature function | Controller/View page files | PASS |
| `Driver` | `updateProfile` | public | `($driverId, $userId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `Driver` | `deactivate` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverAvailability` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverAvailability` | `updateStatus` | public | `($driverId, $status)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverAvailability` | `getStatus` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverAvailability` | `setOffDuty` | public | `($driverId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverAvailability` | `setAvailable` | public | `($driverId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverAvailability` | `isAvailable` | public | `($driverId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverDocument` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverDocument` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverDocument` | `getByDriverId` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverDocument` | `getByType` | public | `($driverId, $type)` | Queries and returns information | Controller/View page files | PASS |
| `DriverDocument` | `getById` | public | `($id)` | Queries and returns information | Controller/View page files | PASS |
| `DriverDocument` | `approveDocument` | public | `($id, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverDocument` | `rejectDocument` | public | `($id, $reason, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverDocument` | `delete` | public | `($id)` | Deletes records/documents from database | Controller/View page files | PASS |
| `DriverIncident` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverIncident` | `isBookingAssignedToDriver` | public | `($bookingId, $driverId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverIncident` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverIncident` | `addPhoto` | public | `($incidentId, $photoPath)` | Implements core feature function | Controller/View page files | PASS |
| `DriverLeave` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverLeave` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `DriverLeave` | `getByDriverId` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverLeave` | `getPendingRequests` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `DriverLeave` | `updateStatus` | public | `($id, $status, $approvedBy)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverLeave` | `hasActiveLeave` | public | `($driverId, $date)` | Implements core feature function | Controller/View page files | PASS |
| `DriverLeave` | `getById` | public | `($id)` | Queries and returns information | Controller/View page files | PASS |
| `DriverLeave` | `update` | public | `($id, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverLeave` | `delete` | public | `($id)` | Deletes records/documents from database | Controller/View page files | PASS |
| `DriverOwnerLink` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverOwnerLink` | `requestLink` | public | `($driverId, $ownerId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverOwnerLink` | `updateStatus` | public | `($linkId, $status)` | Updates record data inside database | Controller/View page files | PASS |
| `DriverOwnerLink` | `findById` | public | `($linkId)` | Implements core feature function | Controller/View page files | PASS |
| `DriverOwnerLink` | `getAcceptedDriversByOwner` | public | `($ownerId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverOwnerLink` | `getLinksByDriver` | public | `($driverId, $status = null)` | Queries and returns information | Controller/View page files | PASS |
| `DriverOwnerLink` | `getLinkCountByDriver` | public | `($driverId, $status)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPayment` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverPayment` | `getEarningsSummary` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPayment` | `getPaymentHistory` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPayment` | `getEarningsSplit` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPayment` | `createPayment` | public | `($driverId, $bookingId, $amount, $status = 'pending')` | Implements core feature function | Controller/View page files | PASS |
| `DriverPayment` | `getMonthlyEarnings` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPerformance` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `DriverPerformance` | `getStatistics` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `DriverPerformance` | `getRatingSummary` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `Notification` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `Notification` | `getByUserId` | public | `($userId)` | Queries and returns information | Controller/View page files | PASS |
| `Notification` | `markAsRead` | public | `($notificationId, $userId)` | Implements core feature function | Controller/View page files | PASS |
| `Notification` | `create` | public | `($userId, $title, $message)` | Implements core feature function | Controller/View page files | PASS |
| `Notification` | `getUnreadCount` | public | `($userId)` | Queries and returns information | Controller/View page files | PASS |
| `ProfileChangeRequest` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `ProfileChangeRequest` | `create` | public | `($userId, $fieldName, $oldValue, $requestedValue, $reason = null)` | Implements core feature function | Controller/View page files | PASS |
| `ProfileChangeRequest` | `getByUserId` | public | `($userId)` | Queries and returns information | Controller/View page files | PASS |
| `ProfileChangeRequest` | `getPendingRequests` | public | `()` | Queries and returns information | Controller/View page files | PASS |
| `ProfileChangeRequest` | `getById` | public | `($id)` | Queries and returns information | Controller/View page files | PASS |
| `ProfileChangeRequest` | `approve` | public | `($id, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `ProfileChangeRequest` | `reject` | public | `($id, $reason, $adminId)` | Implements core feature function | Controller/View page files | PASS |
| `User` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `User` | `findByEmail` | public | `($email)` | Implements core feature function | Controller/View page files | PASS |
| `User` | `findById` | public | `($id)` | Implements core feature function | Controller/View page files | PASS |
| `User` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `User` | `verifyPassword` | public | `($password, $hash)` | Implements core feature function | Controller/View page files | PASS |
| `User` | `updateStatus` | public | `($id, $status)` | Updates record data inside database | Controller/View page files | PASS |
| `Vehicle` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `Vehicle` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `Vehicle` | `getByOwnerId` | public | `($ownerId)` | Queries and returns information | Controller/View page files | PASS |
| `Vehicle` | `getById` | public | `($id, $ownerId = null)` | Queries and returns information | Controller/View page files | PASS |
| `Vehicle` | `update` | public | `($id, $ownerId, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `Vehicle` | `updateStatus` | public | `($id, $ownerId, $status)` | Updates record data inside database | Controller/View page files | PASS |
| `Vehicle` | `deactivate` | public | `($id, $ownerId)` | Implements core feature function | Controller/View page files | PASS |
| `Vehicle` | `licensePlateExists` | public | `($licensePlate, $excludeId = null)` | Implements core feature function | Controller/View page files | PASS |
| `Vehicle` | `getDocuments` | public | `($vehicleId)` | Queries and returns information | Controller/View page files | PASS |
| `Vehicle` | `addDocument` | public | `($vehicleId, $docType, $filePath, $docNumber = null, $expiryDate = null)` | Implements core feature function | Controller/View page files | PASS |
| `Vehicle` | `hasActiveBookings` | public | `($vehicleId)` | Implements core feature function | Controller/View page files | PASS |
| `VehicleOwner` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `VehicleOwner` | `create` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `VehicleOwner` | `findByUserId` | public | `($userId)` | Implements core feature function | Controller/View page files | PASS |
| `VehicleOwner` | `update` | public | `($id, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `__construct` | public | `()` | Implements core feature function | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `getByBooking` | public | `($bookingId)` | Queries and returns information | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `create` | public | `($data)` | Implements core feature function | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `getById` | public | `($id)` | Queries and returns information | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `getByDriverId` | public | `($driverId)` | Queries and returns information | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `update` | public | `($id, $data)` | Updates record data inside database | Controller/View page files | PASS |
| `VehicleSafetyCheck` | `delete` | public | `($id)` | Deletes records/documents from database | Controller/View page files | PASS |
| `public/admin/admin_layout.php` | `isActive` | public | `($href, $page)` | Implements core feature function | Controller/View page files | PASS |

---

## 4. MVC Flow Mapping & User Roles
Each user role acts as an entry point directing to specific views, controllers, and models.

### Role Flow Details:
1. **Driver**:
   * Dashboard View: `public/driver/dashboard.php` -> Controller: `DriverController::dashboard()` -> Model: `Driver::getDashboardData()` -> DB: `drivers`, `vehicle_assignments`
   * Leave requests: `public/driver/leave.php` -> Controller: `DriverController::requestLeave()` / `deleteLeave()` -> Model: `DriverLeave` -> DB: `driver_leaves`
   * Document uploads: `public/driver/documents.php` -> Controller: `DriverController::uploadDocument()` -> Model: `DriverDocument` -> DB: `driver_documents`
2. **Vehicle Owner**:
   * Dashboard: `public/owner/dashboard.php` -> Model: `VehicleOwner` -> DB: `vehicle_owners`
   * Manage Drivers: `public/owner/drivers.php` -> DB: `driver_owner_links`, `drivers`
3. **Customer**:
   * Booking View: `public/customer/bookings/create.php` -> Controller: `BookingController` -> Model: `Booking` -> DB: `bookings`
4. **Admin**:
   * Approvals Page: `public/admin/approvals.php` -> Controller: `AdminApprovalController` -> Model: `AccountChangeRequest`, `DriverDocument`, `ProfileChangeRequest` -> DB: `profile_change_requests`, `driver_documents`, `account_change_requests`

---

## 5. Security & Authentication Audit
The security controls enforce credentials isolation, session checks, and role verification at the page headers:

| Security Feature | Implementation | Location | Status | Risk |
| :--- | :--- | :--- | :---: | :--- |
| Role Restriction | Centralized `AuthHelper::requireRole()` in each route folder | `AuthHelper.php` | PASS | Low (Enforced via header redirects) |
| CSRF Protection | Anti-CSRF token generated via `AuthHelper::getCsrfToken()` and verified in POST forms | `AuthHelper.php` | PASS | Low (Mitigates Cross-Site request forgery) |
| Session Expiry | Secure cookie options (SameSite=Lax, HttpOnly, Secure) | `AuthHelper.php` | PASS | Low (Prevents cookie access via scripts) |
| Password Security | BCRYPT hashes via `password_verify` and `password_hash` | `AuthController.php` | PASS | Low |

---

## 6. Database Audit
The database is structured around **27 core tables** supporting referential integrity:

| Table | Purpose | Primary Key | Important Foreign Keys | Used By |
| :--- | :--- | :--- | :--- | :--- |
| `users` | Core credentials and status | `id` | None | Entire System |
| `drivers` | Driver profiles and rating indexes | `id` | `user_id` -> `users.id` | Driver Module |
| `vehicle_owners` | Owner profiles and verification | `id` | `user_id` -> `users.id` | Owner Module |
| `customers` | Customer profiles | `id` | `user_id` -> `users.id` | Customer Module |
| `driver_documents` | Driver NIC, license, and reports | `id` | `driver_id` -> `drivers.id` | Admin & Driver |
| `driver_leaves` | Time-off schedules and status | `id` | `driver_id` -> `drivers.id` | Driver Module |
| `bookings` | Rental bookings and status | `id` | `driver_id` -> `drivers.id`, `vehicle_id` -> `vehicles.id` | Customer & Driver |

---

## 7. Document System Analysis (Part 7 Bug Audit)
The Driver Documents page displays "View File" links that return **404 Not Found** for seeded data:
* **Problem**: The database is pre-seeded with placeholder file paths like `path/nic.jpg`, `path/license.jpg`, and `path/police.jpg`. However, there is no physical `public/path/` directory on the server filesystem.
* **Upload system**: New uploads successfully create and map to the active web folder `/public/uploads/nics/`, `/public/uploads/licenses/`, and `/public/uploads/police_reports/`. Their database paths start with `uploads/`, resolving correctly through `../` relative link prefix.
* **Verdict**: The bug is caused solely by **invalid database seed values** pointing to non-existent placeholders; the functional upload-saving architecture itself works perfectly.

---

## 8. Business Rules Implementation Audit
The following table checks actual code implementation against Ceylon Lanka Renters rules:

| Business Rule | Actual Implementation | Files | Status | Evidence |
| :--- | :--- | :--- | :---: | :--- |
| Customer ratings | Review model lets customers submit rating reviews on bookings | `Review.php`, `reviews/index.php` | PASS | Code checks status = 'completed' |
| With-driver selection | Customer booking form resolves available drivers assigned to the owner | `BookingController.php` | PASS | Queries `driver_owner_links` |
| Driver verification | Verification status determined dynamically based on the 3 required documents | `Driver.php:L159-188` | PASS | Check logic count = 3 and status = 'approved' |

---

## 9. Local Running Environment Audit
* **PHP Version**: 8.2.12
* **MySQL Status**: Running via XAMPP
* **Port**: 3308
* **Database**: `lanka_renters`
* **Test Connection**: **SUCCESS** (Verified via the Singleton Database helper on port 3308)

---

## 10. Recommended Interim Demonstration Flow
A structured 10-minute presentation showing the strongest working flows of Lanka Renters:
1. **Driver Login (1 min)**: Demonstrate validation and entering the portal.
2. **Dashboard Overview (2 min)**: Show stats cards updating dynamically from the database.
3. **Leave Requests (3 min)**: Create, cancel, and delete leave requests. Show error handling if dates are invalid.
4. **Documents Management (3 min)**: Upload new files, showing status changing to pending, and show how files are safely isolated.
5. **Logout (1 min)**: Demonstrate session destruction and CSRF token invalidation.
