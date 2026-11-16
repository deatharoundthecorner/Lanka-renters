# Actor integration plan

Reference: `latest-system-update` at `124bd87` (PR #10, targeting `main`).  
Foundation base: `origin/main` at `4c785ed`.  
Scope: every changed path between those commits is assigned exactly once below. Status is relative to `main`: A = added, M = modified, D = deleted, R = rename.

## Assignment and dependencies

| Order | Section | Depends on | Review branch |
|---|---|---|---|
| 1 | Shared foundation | Current `main` | `shared-foundation` |
| 2 | Driver | Shared foundation | `driver-contribution` |
| 3 | Owner | Shared foundation | `owner-section-review` / `patches/owner.patch` |
| 4 | Customer | Shared foundation | `customer-section-review` / `patches/customer.patch` |
| 5 | Admin | Shared foundation | `admin-section-review` / `patches/admin.patch` |

Owner, Customer, and Admin patches are review handoffs only; their application changes remain uncommitted for those developers. Driver work is prepared for the user's contribution. For full-system validation, apply all four actor patches on top of the foundation in the order above. Any patch overlap should be resolved with the actor owner; changes spanning multiple actors were assigned as a whole to Shared foundation.

### Ownership rules

Shared foundation owns the install schema and migrations, database/config/core, authentication and public entry points, shared models/helpers/layouts/assets, cross-role controllers and documentation. Driver owns Driver workflows and Driver-specific UI/models. Owner owns Owner workflows plus Owner-specific profile/vehicle controllers. Customer owns Customer portal, booking, payment and supporting UI/tests. Admin owns Admin workflows, models and pages. Assignment was based on changed contents and dependencies, not directory alone. Shared auth/profile/vehicle/chat code, cross-role models and combined architecture reports stay together in foundation where splitting would be unsafe.

### Dependencies and merge order

1. Review/merge the foundation PR into `main` first. It supplies the common runtime, shared contracts, schema and retained migration chain.
2. Merge actor sections after foundation. The Driver PR targets `shared-foundation`; after foundation lands, retarget it to `main`. Owner, Customer and Admin developers should apply their patch to a branch based on the updated foundation, review it, run their tests, then commit under their own identities and open their PRs.
3. The resulting tree should match `latest-system-update` at `124bd87`. Actor patches are independent by assigned path; do not merge actor branches into each other as a substitute for the foundation dependency.

### Tests and review checklist

- **Shared foundation:** PHP syntax checks across the application, JavaScript syntax checks, retained PHP tests, and install `database/schema.sql` into a fresh isolated database. Apply retained migrations in order and compare resulting schema metadata with schema installation. Verify initial admin creation using `database/ADMIN_SETUP.md`; generate any password/hash locally and do not publish it.
- **Driver:** PHP syntax checks on changed PHP files; JavaScript syntax checks on changed JS assets; Driver workflow tests for authenticated dashboard, availability, leave, trips, incidents/safety check, messaging and profile as supported by the local environment. Exercise with test records only in the isolated test database.
- **Owner:** PHP/JS syntax checks for changed files; test Owner login/registration, dashboard, vehicle CRUD, booking/driver management, chat, earnings, inspections and replacement requests where fixtures/environment permit.
- **Customer:** run `tests/Customer/Phase1FoundationTest.php` and `tests/Customer/Phase3UiTest.php`, PHP/JS syntax checks, then test registration/profile, dashboard/catalogue, booking lifecycle, payment, chat, incidents/returns/reviews and verification using isolated test data.
- **Admin:** PHP/JS syntax checks; test initial admin login, dashboard, users, driver/owner/vehicle verification, bookings, payments, incidents, reports and announcements in the isolated environment.
- Recheck staged paths and patch contents for credentials, private uploads/documents, real database exports, local config and temporary test data before publishing.

### Developer instructions

Start from the latest shared foundation (or the foundation PR branch while it is open). Check out a clean branch based on that foundation, copy the relevant patch from `handoff/patches/`, run `git apply --check <patch>`, then `git apply <patch>`. Review `git diff`, run that section's checks above, and commit only your own reviewed section using your configured Git identity. Do not copy the uploaded project's `.git` directory. Keep generated credentials, local environment files, private uploads, database exports and test data out of commits. If the Driver PR is still based on foundation when foundation merges, retarget Driver to `main`; its diff should remain Driver-only.

### Changes that cannot safely be separated

- Shared authentication, database access, authorization, common layouts/assets, public routes and cross-role domain models are assigned wholly to Shared foundation.
- `app/controllers/ChatController.php` is assigned to Driver because the reference change is Driver messaging behavior; shared chat model/contracts are in foundation.
- `app/controllers/ProfileController.php` and `app/controllers/VehicleController.php` are assigned to Owner because supplied changes implement Owner operations while common profile/vehicle contracts remain in foundation.
- Combined cross-actor reports, architecture docs and root/setup docs remain in foundation.
- The legacy `public/admin` file is deleted as an obsolete stub and replaced by the Admin page tree; this is one Admin assignment.
- The source migration rename into `database/migrations/001_initial_schema.sql`, schema and retained migrations are kept together in foundation. Omitted demo migrations 024–027 contain fixture/reference-content inserts (drivers, vehicles, catalogue updates and inspection rows), not required DDL or required lookup schema. They are not needed for installation.
- Supplied trailing whitespace is left as provided; no UI or business logic was changed to clean it up.

## Complete file assignment

### Shared foundation (149)

| Status | Path |
|---|---|
| M | `.gitignore` |
| M | `app/config/database.php` |
| M | `app/controllers/AuthController.php` |
| D | `app/controllers/BookingController.php` |
| D | `app/controllers/IncidentController.php` |
| D | `app/controllers/NotificationController.php` |
| D | `app/controllers/PaymentController.php` |
| A | `app/controllers/PublicVehicleController.php` |
| D | `app/controllers/ReviewController.php` |
| M | `app/helpers/AuthHelper.php` |
| M | `app/helpers/Database.php` |
| A | `app/helpers/MailHelper.php` |
| A | `app/helpers/SimplePdf.php` |
| A | `app/models/AccountChangeRequest.php` |
| M | `app/models/Chat.php` |
| M | `app/models/Customer.php` |
| A | `app/models/CustomerVerification.php` |
| M | `app/models/Driver.php` |
| A | `app/models/DriverChangeRequest.php` |
| M | `app/models/DriverDocument.php` |
| A | `app/models/DriverOwnerLink.php` |
| A | `app/models/Guarantor.php` |
| M | `app/models/Notification.php` |
| A | `app/models/ProfileChangeRequest.php` |
| A | `app/models/ReplacementRequest.php` |
| A | `app/models/Report.php` |
| A | `app/models/Settlement.php` |
| M | `app/models/User.php` |
| M | `app/models/Vehicle.php` |
| A | `database/ADMIN_SETUP.md` |
| R084 | `database/migrations/001_initial_schema.sql` |
| A | `database/migrations/002_driver_documents.sql` |
| A | `database/migrations/003_profile_change_requests.sql` |
| A | `database/migrations/004_account_change_requests.sql` |
| A | `database/migrations/005_admin_reviews.sql` |
| A | `database/migrations/006_add_username_to_users.sql` |
| A | `database/migrations/007_settlements.sql` |
| A | `database/migrations/008_email_logs.sql` |
| A | `database/migrations/009_announcements.sql` |
| A | `database/migrations/010_customer_guarantors.sql` |
| A | `database/migrations/011_customer_guarantor_verification_status.sql` |
| A | `database/migrations/012_customer_identity_license_verification.sql` |
| A | `database/migrations/013_customer_verification_documents.sql` |
| A | `database/migrations/014_bookings_pending_approval.sql` |
| A | `database/migrations/015_vehicle_rental_details.sql` |
| A | `database/migrations/016_incident_extended_attributes.sql` |
| A | `database/migrations/017_customer_booking_agreements.sql` |
| A | `database/migrations/018_vehicle_inspection_photos.sql` |
| A | `database/migrations/019_incident_dispute_resolution.sql` |
| A | `database/migrations/020_customer_pickup_confirmation.sql` |
| A | `database/migrations/021_driver_change_requests.sql` |
| A | `database/migrations/022_customer_guarantor_admin_review.sql` |
| A | `database/migrations/023_schema_from_exports.sql` |
| A | `database/migrations/028_unique_driver_payment_booking.sql` |
| A | `database/migrations/029_unique_settlement_booking.sql` |
| A | `database/migrations/030_unique_chat_room_booking.sql` |
| A | `database/migrations/031_customer_return_requests.sql` |
| D | `database/sample_data.sql` |
| A | `database/schema.sql` |
| A | `docs/AI_HANDOFF_AUDIT.md` |
| A | `docs/AUTH_GUEST_UI_REPORT.md` |
| A | `docs/AUTH_UI_IMPLEMENTATION_REPORT.md` |
| A | `docs/CHAT_RUNTIME_AUDIT.md` |
| A | `docs/CURRENT_IDENTITY_LICENSE_WORKFLOW_AUDIT.md` |
| A | `docs/CURRENT_SYSTEM_FINAL_SHORT_NOTE.md` |
| A | `docs/CURRENT_SYSTEM_LOGIC_AUDIT.md` |
| A | `docs/CUSTOMER_IDENTITY_LICENSE_IMPLEMENTATION_REPORT.md` |
| A | `docs/CUSTOMER_LOGOUT_ADMIN_REPORT_CENTER_REPAIR.md` |
| A | `docs/CUSTOMER_NAV_ADMIN_CHART_MVC_REPORT.md` |
| A | `docs/DATABASE.md` |
| A | `docs/diagrams/01_use_case_diagram.md` |
| A | `docs/diagrams/02_eer_diagram.md` |
| A | `docs/diagrams/03_class_diagram.md` |
| A | `docs/diagrams/04_component_diagram.md` |
| A | `docs/diagrams/05_system_architecture_diagram.md` |
| A | `docs/diagrams/06_activity_diagram.md` |
| A | `docs/diagrams/07_state_diagram.md` |
| A | `docs/diagrams/08_sequence_payment_flow.md` |
| A | `docs/diagrams/09_sequence_incident_replacement_flow.md` |
| A | `docs/diagrams/10_sequence_driver_change_request_flow.md` |
| A | `docs/diagrams/11_sequence_kyc_verification_flow.md` |
| A | `docs/diagrams/12_deployment_diagram.md` |
| A | `docs/diagrams/README.md` |
| A | `docs/diagrams/use_case_diagram.drawio.xml` |
| A | `docs/diagrams/use_case_diagram.xml` |
| A | `docs/diagrams/user_authentication_activity_diagram.puml` |
| A | `docs/diagrams/vehicle_booking_activity_diagram.puml` |
| A | `docs/diagrams/vehicle_booking_phase_diagram.puml` |
| A | `docs/DRIVER_APPROVAL_WORKFLOW.md` |
| A | `docs/FINAL_AUTHENTICATED_UI_UNIFICATION_REPORT.md` |
| A | `docs/FINAL_CONTROLLED_ENHANCEMENT_REPORT.md` |
| A | `docs/FINAL_CUSTOMER_ADMIN_VISUAL_REPAIR.md` |
| A | `docs/FINAL_ICON_SIDEBAR_UX_REPAIR.md` |
| A | `docs/FINAL_INTERIM_FIX_REPORT.md` |
| A | `docs/FINAL_INTERIM_FREEZE_AUDIT.md` |
| A | `docs/FINAL_MESSAGES_UI_UNIFICATION.md` |
| A | `docs/FULL_SYSTEM_FUNCTIONALITY_AUDIT.md` |
| A | `docs/INTERIM_CRUD_MATRIX.md` |
| A | `docs/INTERIM_FINALIZATION_AUDIT.md` |
| A | `docs/INTERIM_TEST_CHECKLIST.md` |
| A | `docs/MVC_ARCHITECTURE_SHORT_NOTE.md` |
| A | `docs/ROLE_PERMISSIONS.md` |
| A | `docs/SHARED_NOTIFICATION_SYSTEM_REPORT.md` |
| A | `docs/TEAM_HANDOFF_READINESS.md` |
| A | `docs/UI_CENTRALIZATION_ADMIN_REPAIR_REPORT.md` |
| A | `docs/UI_CENTRALIZATION_AUDIT.md` |
| A | `docs/UI_SAFETY_CLEANUP_REPORT.md` |
| A | `public/api/notifications.php` |
| D | `public/assets/css/alerts.css` |
| D | `public/assets/css/badges.css` |
| D | `public/assets/css/base.css` |
| D | `public/assets/css/buttons.css` |
| D | `public/assets/css/cards.css` |
| M | `public/assets/css/driver.css` |
| D | `public/assets/css/forms.css` |
| A | `public/assets/css/guest.css` |
| A | `public/assets/css/lr-auth.css` |
| A | `public/assets/css/lr-components.css` |
| A | `public/assets/css/lr-messages.css` |
| A | `public/assets/css/lr-notifications.css` |
| A | `public/assets/css/lr-public.css` |
| A | `public/assets/css/lr-shell.css` |
| A | `public/assets/css/lr-tokens.css` |
| D | `public/assets/css/reset.css` |
| D | `public/assets/css/sidebar.css` |
| M | `public/assets/css/style.css` |
| D | `public/assets/css/tables.css` |
| D | `public/assets/css/variables.css` |
| A | `public/assets/images/brand/lanka-renters-logo.png` |
| A | `public/assets/images/brand/lanka-renters-mark.png` |
| A | `public/assets/images/car.svg` |
| A | `public/assets/images/no-image.svg` |
| A | `public/assets/images/suv.svg` |
| A | `public/assets/images/van.svg` |
| A | `public/assets/js/lr-notifications.js` |
| A | `public/assets/js/lr-sidebar-scroll.js` |
| A | `public/assets/js/password-visibility.js` |
| A | `public/includes/notification-dropdown.php` |
| M | `public/index.php` |
| M | `public/login.php` |
| A | `public/logout.php` |
| A | `public/notifications.php` |
| M | `public/register.php` |
| A | `public/report-delivery.php` |
| A | `public/vehicle-details.php` |
| A | `public/vehicles.php` |
| A | `README_RUN_LOCAL.md` |
| M | `README.md` |
| A | `tests/Customer/Phase3TestRouter.php` |

### Driver (28)

| Status | Path |
|---|---|
| M | `app/controllers/ChatController.php` |
| M | `app/controllers/DriverController.php` |
| M | `app/models/DriverPayment.php` |
| A | `docs/DRIVER_CANONICAL_UI_REPORT.md` |
| A | `docs/MY_MODULE_FUNCTIONALITY.md` |
| A | `docs/MY_MODULE_HANDOVER.md` |
| M | `public/driver/availability.php` |
| M | `public/driver/chat.php` |
| M | `public/driver/dashboard.php` |
| M | `public/driver/documents.php` |
| M | `public/driver/earnings.php` |
| A | `public/driver/incidents.php` |
| M | `public/driver/includes/footer.php` |
| M | `public/driver/includes/header.php` |
| M | `public/driver/includes/navbar.php` |
| M | `public/driver/includes/sidebar.php` |
| M | `public/driver/leave.php` |
| M | `public/driver/messages.php` |
| M | `public/driver/notifications.php` |
| A | `public/driver/owners.php` |
| M | `public/driver/performance.php` |
| A | `public/driver/profile.php` |
| M | `public/driver/report_incident.php` |
| M | `public/driver/safety_check.php` |
| A | `public/driver/safety-check.php` |
| A | `public/driver/security.php` |
| M | `public/driver/trips.php` |
| M | `public/driver/vehicles.php` |

### Owner (31)

| Status | Path |
|---|---|
| A | `app/controllers/OwnerBookingController.php` |
| A | `app/controllers/OwnerChatController.php` |
| A | `app/controllers/OwnerDriverChangeController.php` |
| A | `app/controllers/OwnerDriverController.php` |
| A | `app/controllers/OwnerEarningsController.php` |
| A | `app/controllers/OwnerInspectionController.php` |
| A | `app/controllers/OwnerReplacementController.php` |
| M | `app/controllers/ProfileController.php` |
| M | `app/controllers/VehicleController.php` |
| A | `app/models/VehicleInspection.php` |
| A | `docs/OWNER_CANONICAL_UI_REPORT.md` |
| A | `docs/OWNER_OPERATIONS_POLISH_REPORT.md` |
| M | `public/owner/bookings.php` |
| M | `public/owner/chat.php` |
| M | `public/owner/dashboard.php` |
| A | `public/owner/driver-changes.php` |
| M | `public/owner/drivers.php` |
| M | `public/owner/earnings.php` |
| A | `public/owner/includes/assets/css/owner-canonical.css` |
| M | `public/owner/includes/assets/css/owner-style.css` |
| M | `public/owner/includes/header.php` |
| M | `public/owner/includes/sidebar.php` |
| M | `public/owner/inspection.php` |
| A | `public/owner/login.php` |
| A | `public/owner/logout.php` |
| A | `public/owner/notifications.php` |
| M | `public/owner/profile.php` |
| M | `public/owner/replacement-requests.php` |
| A | `public/owner/replacement.php` |
| A | `public/owner/vehicle-add.php` |
| M | `public/owner/vehicles.php` |

### Customer (134)

| Status | Path |
|---|---|
| A | `app/controllers/CustomerBookingController.php` |
| M | `app/controllers/CustomerController.php` |
| A | `app/controllers/CustomerPaymentController.php` |
| A | `app/controllers/CustomerPortalController.php` |
| A | `app/helpers/CustomerCsrf.php` |
| A | `app/helpers/CustomerDemoData.php` |
| A | `app/models/CustomerBooking.php` |
| A | `app/models/CustomerPayment.php` |
| A | `app/models/CustomerPortal.php` |
| A | `app/models/CustomerVehicle.php` |
| A | `docs/CUSTOMER_CANONICAL_UI_REPORT.md` |
| M | `docs/Customer/architecture.md` |
| M | `docs/Customer/bussiness-rules.md` |
| M | `docs/Customer/CHANGELOG.md` |
| A | `docs/Customer/DATABASE_CONTRACT.md` |
| A | `docs/Customer/phase-1-foundation.md` |
| A | `docs/Customer/phase-3-ui.md` |
| A | `docs/Customer/sql/customer_schema_proposal.sql` |
| M | `docs/Customer/testing.md` |
| D | `public/assets/css/customer.css` |
| A | `public/customer/_bootstrap.php` |
| A | `public/customer/_foundation_page.php` |
| A | `public/customer/assets/css/customer-base.css` |
| A | `public/customer/assets/css/customer-bookings.css` |
| A | `public/customer/assets/css/customer-canonical.css` |
| A | `public/customer/assets/css/customer-components.css` |
| A | `public/customer/assets/css/customer-dashboard.css` |
| A | `public/customer/assets/css/customer-layout.css` |
| A | `public/customer/assets/css/customer-responsive.css` |
| A | `public/customer/assets/css/customer-ui.css` |
| A | `public/customer/assets/css/customer-variables.css` |
| A | `public/customer/assets/css/customer-vehicles.css` |
| A | `public/customer/assets/css/customer-workflows.css` |
| A | `public/customer/assets/js/customer-bookings.js` |
| A | `public/customer/assets/js/customer-ui.js` |
| A | `public/customer/assets/js/customer-workflows.js` |
| A | `public/customer/bookings/cancel.php` |
| M | `public/customer/bookings/create.php` |
| M | `public/customer/bookings/details.php` |
| A | `public/customer/bookings/edit.php` |
| M | `public/customer/bookings/history.php` |
| M | `public/customer/bookings/index.php` |
| A | `public/customer/bookings/inspection.php` |
| M | `public/customer/chat/index.php` |
| M | `public/customer/components/alert.php` |
| M | `public/customer/components/badge.php` |
| A | `public/customer/components/booking/accepted-booking-agreement.php` |
| M | `public/customer/components/booking/booking-actions.php` |
| M | `public/customer/components/booking/booking-card.php` |
| A | `public/customer/components/booking/booking-form.php` |
| A | `public/customer/components/booking/booking-review.php` |
| M | `public/customer/components/booking/booking-status.php` |
| M | `public/customer/components/booking/booking-summary.php` |
| M | `public/customer/components/booking/booking-timeline.php` |
| A | `public/customer/components/booking/pre-booking-checklist.php` |
| A | `public/customer/components/booking/service-requirements.php` |
| M | `public/customer/components/button.php` |
| M | `public/customer/components/card.php` |
| M | `public/customer/components/chat/chat-list.php` |
| M | `public/customer/components/chat/chat-window.php` |
| M | `public/customer/components/chat/message-bubble.php` |
| M | `public/customer/components/comon/confirmation-modal.php` |
| M | `public/customer/components/comon/empty-state.php` |
| M | `public/customer/components/comon/filter-bar.php` |
| M | `public/customer/components/comon/loading-spinner.php` |
| M | `public/customer/components/comon/pagination.php` |
| M | `public/customer/components/comon/search-box.php` |
| M | `public/customer/components/dashboard/activity-card.php` |
| M | `public/customer/components/dashboard/notification-panel.php` |
| M | `public/customer/components/dashboard/quick-action-card.php` |
| M | `public/customer/components/dashboard/recent-booking-card.php` |
| M | `public/customer/components/dashboard/stat-card.php` |
| M | `public/customer/components/dashboard/welcome-banner.php` |
| M | `public/customer/components/empty-state.php` |
| A | `public/customer/components/icon.php` |
| M | `public/customer/components/incident/incident-card.php` |
| M | `public/customer/components/incident/incident-status.php` |
| M | `public/customer/components/incident/incident-timeline.php` |
| A | `public/customer/components/layout/feature-end.php` |
| A | `public/customer/components/layout/feature-start.php` |
| M | `public/customer/components/layout/footer.php` |
| M | `public/customer/components/layout/header.php` |
| M | `public/customer/components/layout/navbar.php` |
| M | `public/customer/components/layout/page-header.php` |
| M | `public/customer/components/layout/sidebar.php` |
| M | `public/customer/components/payment/invoice-card.php` |
| M | `public/customer/components/payment/payment-history-table.php` |
| M | `public/customer/components/payment/payment-status.php` |
| M | `public/customer/components/payment/payment-summary.php` |
| M | `public/customer/components/review/rating-card.php` |
| M | `public/customer/components/review/review-card.php` |
| M | `public/customer/components/review/review-summary.php` |
| M | `public/customer/components/vehicle/availability-badge.php` |
| A | `public/customer/components/vehicle/booking-rules.php` |
| M | `public/customer/components/vehicle/owner-card.php` |
| A | `public/customer/components/vehicle/rental-usage-details.php` |
| M | `public/customer/components/vehicle/vehicle-card.php` |
| M | `public/customer/components/vehicle/vehicle-gallery.php` |
| M | `public/customer/components/vehicle/vehicle-specs.php` |
| A | `public/customer/dashboard.php` |
| M | `public/customer/dashboard/index.php` |
| A | `public/customer/driver-change-request/index.php` |
| A | `public/customer/driver-change/create.php` |
| A | `public/customer/driver-change/details.php` |
| A | `public/customer/driver-change/index.php` |
| A | `public/customer/incidents/create.php` |
| A | `public/customer/incidents/details.php` |
| M | `public/customer/incidents/index.php` |
| A | `public/customer/inspection/index.php` |
| A | `public/customer/logout.php` |
| M | `public/customer/notifications.php` |
| A | `public/customer/notifications/index.php` |
| M | `public/customer/payment.php` |
| A | `public/customer/payments/create.php` |
| A | `public/customer/payments/details.php` |
| M | `public/customer/payments/index.php` |
| M | `public/customer/payments/invoice.php` |
| A | `public/customer/payments/proof.php` |
| A | `public/customer/payments/summary.php` |
| A | `public/customer/pickup-status/index.php` |
| M | `public/customer/profile/index.php` |
| A | `public/customer/return/index.php` |
| A | `public/customer/returns/create.php` |
| A | `public/customer/returns/details.php` |
| A | `public/customer/returns/index.php` |
| M | `public/customer/reviews.php` |
| A | `public/customer/reviews/create.php` |
| A | `public/customer/reviews/details.php` |
| M | `public/customer/reviews/index.php` |
| M | `public/customer/vehicles/details.php` |
| M | `public/customer/vehicles/index.php` |
| A | `public/customer/verification/index.php` |
| A | `tests/Customer/Phase1FoundationTest.php` |
| A | `tests/Customer/Phase3UiTest.php` |

### Admin (56)

| Status | Path |
|---|---|
| A | `app/controllers/AdminApprovalController.php` |
| A | `app/controllers/AdminDashboardController.php` |
| A | `app/controllers/AdminUserController.php` |
| A | `app/models/AdminAnalytics.php` |
| A | `app/models/AdminBookingMonitoring.php` |
| A | `app/models/AdminDriverVerification.php` |
| A | `app/models/AdminIncidentManagement.php` |
| A | `app/models/AdminOwnerVerification.php` |
| A | `app/models/AdminPaymentVerification.php` |
| A | `app/models/AdminVehicleVerification.php` |
| A | `app/models/Announcement.php` |
| A | `app/models/EmailLog.php` |
| A | `docs/SIDEBAR_ADMIN_REPORTS_REPAIR.md` |
| D | `public/admin` |
| A | `public/admin/account_change_requests.php` |
| A | `public/admin/analytics.php` |
| A | `public/admin/announcement_handler.php` |
| A | `public/admin/announcements.php` |
| A | `public/admin/assets/css/admin-canonical.css` |
| A | `public/admin/assets/css/style.css` |
| A | `public/admin/assets/js/admin.js` |
| A | `public/admin/bookings.php` |
| A | `public/admin/config/database.php` |
| A | `public/admin/customer_document.php` |
| A | `public/admin/customer_verification.php` |
| A | `public/admin/dashboard.php` |
| A | `public/admin/data/dashboard-live.php` |
| A | `public/admin/driver_changes.php` |
| A | `public/admin/driver_verification.php` |
| A | `public/admin/drivers.php` |
| A | `public/admin/email_logs.php` |
| A | `public/admin/guarantors.php` |
| A | `public/admin/incident_handler.php` |
| A | `public/admin/incidents.php` |
| A | `public/admin/index.php` |
| A | `public/admin/messages.php` |
| A | `public/admin/owner_verification.php` |
| A | `public/admin/partials/footer.php` |
| A | `public/admin/partials/header.php` |
| A | `public/admin/partials/modals.php` |
| A | `public/admin/partials/sidebar.php` |
| A | `public/admin/payment_document.php` |
| A | `public/admin/payment_verification.php` |
| A | `public/admin/payments.php` |
| A | `public/admin/replacement_handler.php` |
| A | `public/admin/replacement_requests.php` |
| A | `public/admin/report-pdf.php` |
| A | `public/admin/report-send.php` |
| A | `public/admin/reports.php` |
| A | `public/admin/settlement_handler.php` |
| A | `public/admin/settlements.php` |
| A | `public/admin/user_handler.php` |
| A | `public/admin/users.php` |
| A | `public/admin/vehicle_owners.php` |
| A | `public/admin/vehicle_verification.php` |
| A | `public/admin/vehicles.php` |


