# MVC Architecture Short Note

## Structure Map

- `app/controllers/`: Request coordination, session-derived actor handling, input validation, and model orchestration. Important files include `AuthController.php`, `CustomerPortalController.php`, `DriverController.php`, `OwnerBookingController.php`, `AdminUserController.php`, and the focused `AdminDashboardController.php`.
- `app/models/`: PDO-backed entity, persistence, and aggregate query ownership. Important files include `User.php`, `Booking.php`, `Payment.php`, `Notification.php`, `Chat.php`, `Report.php`, and the focused `AdminAnalytics.php`.
- `app/helpers/`: Shared infrastructure such as `Database.php`, `AuthHelper.php`, CSRF/session helpers, `SimplePdf.php`, and general utilities.
- `public/`: Rendered views, role pages, form endpoints, static assets, and small JSON endpoints. Views should not introduce new database-query ownership.
- `database/`: Checked-in schema, seed data, and migrations.

## Module Classification

| Module | Classification | Note |
| --- | --- | --- |
| Authentication | MVC-STYLE | Controllers/helpers coordinate session and account behavior. |
| Customer | MVC-STYLE | Customer portal/controller and models coexist with legacy view pages. |
| Owner | LEGACY MIXED | Controllers/models exist, with some direct page/dashboard data access. |
| Driver | MVC-STYLE | Driver controller/model paths are primary, with legacy page rendering. |
| Admin | LEGACY MIXED | Administration uses models/controllers alongside direct dashboard/page queries. |
| Reports | MVC-STYLE | Report model and Admin report endpoints coexist with presentation-level filtering. |
| Notifications | MVC-STYLE | Notification model and shared API/UI surfaces. |
| Chat | MVC-STYLE | Role controllers and Chat model enforce participant-scoped access. |

## Focused Analytics Boundary

This phase moved only the Admin live analytics endpoint to `public/admin/data/dashboard-live.php -> AdminDashboardController -> AdminAnalytics -> Database`. The dashboard page’s pre-existing legacy summary queries remain preserved for functional safety. The project is not strict MVC overall.
