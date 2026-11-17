# Lanka Renters — Interim CRUD Matrix

Verification date: 2026-08-16  
Runtime: PHP application at `http://127.0.0.1:8000`; MariaDB 10.4.32 at `127.0.0.1:3308`; database `lanka_renters`.

Every result below was exercised through the HTTP UI and checked against the live MariaDB state. Test records were removed after verification.

## Final member matrix

| Member/role | Entity | CREATE | READ | UPDATE | DELETE | Demo ready |
|---|---|---|---|---|---|---|
| Driver | Leave Requests | PASS | PASS | PASS | PASS | **YES** |
| Vehicle Owner | Vehicle Management | PASS | PASS | PASS | PASS | **YES** |
| Customer | Reviews & Ratings | PASS | PASS | PASS | PASS | **YES** |
| Admin | Platform User Management | PASS | PASS | PASS | PASS | **YES** |

## Driver — Leave Requests

Database table: `driver_leaves`.

| Operation | UI action | Controller | Model and SQL | Runtime evidence |
|---|---|---|---|---|
| CREATE | `public/driver/leave.php`, Request Leave POST form | `DriverController::requestLeave()` | `DriverLeave::create()` → prepared `INSERT INTO driver_leaves` | Submitted as the seeded Driver; the new pending row was present in MariaDB and displayed in Leave History. |
| READ | Leave History and pending-record Edit link | `DriverController::viewLeaveHistory()` | `DriverLeave::getByDriverId()` / `getById()` → prepared `SELECT` | The created row and its stored values rendered for its owning Driver. |
| UPDATE | Edit pending leave POST form | `DriverController::editLeave()` | `DriverLeave::update()` → prepared `UPDATE driver_leaves ... WHERE id=:id AND status='pending'` | Changed dates/reason appeared in MariaDB and the refreshed UI. |
| DELETE | Delete pending leave POST form | `DriverController::deleteLeave()` | `DriverLeave::delete()` → prepared `DELETE FROM driver_leaves ... status='pending'` | Row disappeared from MariaDB and Leave History. |

Security evidence: Driver role guard, POST and CSRF validation are active. A second Driver's delete attempt was rejected and the first Driver's row remained. Controller checks the current Driver ID before update/delete.

## Vehicle Owner — Vehicle Management

Database table: `vehicles`.

| Operation | UI action | Controller | Model and SQL | Runtime evidence |
|---|---|---|---|---|
| CREATE | `public/owner/vehicles.php`, Add Vehicle POST form | `VehicleController::createVehicle()` | `Vehicle::create()` → prepared `INSERT INTO vehicles` | Created vehicle was present in MariaDB and rendered in My Vehicles. |
| READ | My Vehicles list and `edit_id` selection | `VehicleController::getOwnerVehicles()` | `Vehicle::getByOwnerId()` / `getById()` → owner-scoped prepared `SELECT` | The saved vehicle loaded only for its owning Owner. |
| UPDATE | Edit Vehicle POST form | `VehicleController::updateVehicle()` | `Vehicle::update()` → prepared `UPDATE vehicles ... WHERE id=:id AND owner_id=:owner_id` | Changed data was confirmed in MariaDB and in the refreshed table. |
| DELETE | Delete Vehicle POST form | `VehicleController::deleteVehicle()` | `Vehicle::hasBookings()` then `Vehicle::delete()` → prepared `DELETE ... WHERE id=:id AND owner_id=:owner_id` | Unreferenced test vehicle was removed from MariaDB and the UI. |

Security evidence: Owner role guard, POST, CSRF, and owner-scoped SQL are active. Owner B could not delete Owner A's vehicle. A vehicle with booking history was also tested: deletion was safely refused, the vehicle and booking remained, and a user-safe message was shown.

## Customer — Reviews & Ratings

Database table: `ratings_reviews`.

| Operation | UI action | Controller | Model and SQL | Runtime evidence |
|---|---|---|---|---|
| CREATE | `public/customer/reviews/create.php`, review POST form | `CustomerPortalController::reviewCreatePage()` | `CustomerPortal::createReview()` → prepared `INSERT INTO ratings_reviews` | Review for an owned completed booking was inserted and shown in the review list. |
| READ | Review list and `reviews/details.php?id=...` | `reviewsPage()` / `reviewDetailsPage()` | `getReviews()` / `findReview()` → Customer-scoped prepared `SELECT` | Stored ratings/text appeared in list and details UI. |
| UPDATE | Edit Review POST form on details page | `CustomerPortalController::reviewDetailsPage()` | `CustomerPortal::updateReview()` → prepared Customer-scoped `UPDATE ratings_reviews` | Updated rating/text was confirmed in MariaDB and the details UI. |
| DELETE | Delete Review POST form on details page | `CustomerPortalController::reviewDetailsPage()` | `CustomerPortal::deleteReview()` → prepared Customer-scoped `DELETE` | Review disappeared from MariaDB and the Customer review list. |

Security evidence: Customer bootstrap enforces the Customer role; state changes use POST and Customer CSRF. Rating values are limited to 1–5. Customer B's attempted delete of Customer A's review returned a safe not-found response and did not alter the row.

## Admin — Platform User Management

Primary table: `users`; matching role profile table: `customers`, `vehicle_owners`, or `drivers`.

| Operation | UI action | Controller/handler | Model and SQL | Runtime evidence |
|---|---|---|---|---|
| CREATE | `public/admin/users.php`, Create User POST form | `user_handler.php` → `AdminUserController::createUser()` | `User::createManagedUser()` → existing hashed `User::create()` plus role-profile `INSERT`, in a transaction | Created Owner was present in `users`, had a `vehicle_owners` profile, and appeared in the UI. |
| READ | Platform Users table and edit selection | `AdminUserController::listUsers()` / `findUser()` | `User::getAllManagedUsers()` / `findById()` → real DB `SELECT`; hashes excluded from list | The created record and the four seeded users rendered from MariaDB. |
| UPDATE | Edit User POST form | `user_handler.php` → `AdminUserController::updateUser()` | `User::updateManagedUser()` → validated prepared `UPDATE users`, transactional role-profile alignment | Updated name/phone were confirmed in MariaDB and in the UI. |
| DELETE | Delete User POST form | `user_handler.php` → `AdminUserController::deleteUser()` | `User::deleteManagedUser()` → prepared `DELETE FROM users` | Temporary Admin-created users were removed from MariaDB and the UI. |

Security evidence: Admin role required, all mutations are POST with CSRF, allowed roles/statuses are validated, passwords use `password_hash(..., PASSWORD_DEFAULT)`, the current Admin cannot delete itself or remove its own active Admin access, and foreign-key failures return a safe message rather than forcing cascades.

## Final CRUD result

All four assigned features completed a real Create → Read → Update → Delete cycle against MariaDB. Ownership-isolation and CSRF rejection tests left database counts unchanged. No QA users, QA vehicles, QA reviews, or QA leave records remain.
