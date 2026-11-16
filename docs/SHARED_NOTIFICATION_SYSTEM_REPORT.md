# Lanka Renters — Shared Notification System Report

Implementation and QA date: 2026-08-16  
Runtime: PHP local server; MariaDB 10.4.32 at `127.0.0.1:3308`; database `lanka_renters`

## Result

One shared, authenticated notification header is active for Customer, Vehicle Owner, Driver, and Admin. All four roles use the same neutral outline bell, orange unread indicator, five-row dropdown, timestamps, unread styling, empty state, mark-read controls, and View All treatment.

No notification data is fabricated. The dropdown reads the existing `notifications` table and retains the last known UI state when a polling request temporarily fails.

## Existing Architecture Reused

- Existing table: `notifications`
- Existing recipient key: `notifications.user_id` → `users.id`
- Existing fields reused: `title`, `message`, `notification_type`, `related_id`, `is_read`, and `created_at`
- Existing index reused: composite `idx_notifications_user (user_id, is_read)`
- Existing model reused and extended: `app/models/Notification.php`
- Existing Customer and Driver full notification pages remain their View All destinations.
- No duplicate table or model was created.
- The live table and checked-in schema agree; no schema change or migration was required.

## Files Modified

- `app/models/Notification.php`
- `public/customer/components/layout/header.php`
- `public/customer/components/layout/navbar.php`
- `public/driver/includes/header.php`
- `public/driver/includes/navbar.php`
- `public/driver/includes/footer.php`
- `public/owner/includes/header.php`
- `public/owner/includes/assets/css/owner-canonical.css`
- `public/admin/partials/header.php`
- `public/admin/partials/footer.php`
- `public/admin/assets/css/style.css`

## Files Created

- `public/includes/notification-dropdown.php` — shared server-rendered bell/dropdown
- `public/api/notifications.php` — session-scoped JSON polling and mark-read endpoint
- `public/assets/css/lr-notifications.css` — shared bell/dropdown visual contract
- `public/assets/js/lr-notifications.js` — shared polling and interaction client
- `public/notifications.php` — shared Owner/Admin notification history page
- `docs/SHARED_NOTIFICATION_SYSTEM_REPORT.md`

## Polling Behavior

- Polling interval: **5,000 ms**
- Query scope: authenticated user's unread count plus latest five rows only
- Refresh points: initial page load, bell open, five-second interval while the document is visible, and return to a visible tab
- Failure behavior: silent retry on the next interval; no alert, sound, intrusive toast, PHP warning, SQL text, or forced UI reset
- Data rendering uses DOM `textContent`, not notification-supplied HTML.

## Mark Read Behavior

| Action | Method | Protection | Result |
|---|---|---|---|
| Mark one | POST | Session recipient + CSRF + owner-scoped `id`/`user_id` update | PASS |
| Mark all | POST | Session recipient + CSRF + owner-scoped update | PASS |
| Cross-user mark attempt | POST | Target row belongs to another session user | Blocked with 404; row unchanged |
| Invalid CSRF | POST | Invalid token | Blocked with 403 |

The endpoint never accepts `user_id` as the recipient selector. A runtime request containing `?user_id=999999` still returned only the authenticated user's data.

## Notification Events Currently Supported

The existing business writers were preserved:

| Workflow event | Recipient | Current status |
|---|---|---|
| Driver completes an eligible with-driver trip; payment is generated | Driver | Supported |
| Driver completes the trip | Customer | Supported |
| Driver accepts an Owner connection request | Vehicle Owner | Supported |
| Driver rejects an Owner connection request | Vehicle Owner | Supported |

A real Driver acceptance workflow was exercised through `driver/owners.php`. It created an Owner notification, and the authenticated Owner polling response contained **Connection Request Accepted** after the polling interval without an Owner page reload.

## Events Still Deferred

No broad controller changes were made. The following current workflows do not yet create notification rows and remain deferred rather than being represented by fake data:

- Booking creation
- Payment approval/rejection and resulting booking confirmation/rejection
- Driver/document approval or rejection
- Initial Owner-to-Driver connection request
- Incident status update
- Replacement request update
- General chat-message notification generation

Booking pages remain request/refresh based. A separate booking-status polling endpoint was not added because the current workflows above do not yet provide a complete notification-backed status event path; adding it would expand the booking implementation beyond this focused shared-header phase.

## Security Model

- Recipient identity is resolved exclusively from `AuthHelper::getCurrentUser()`.
- Only authenticated Customer, Owner, Driver, and Admin sessions receive notification JSON.
- GET returns read-only notification state; mutations require POST.
- Existing application CSRF validation protects mark-one and mark-all.
- Notification IDs are updated with both `id` and authenticated `user_id`.
- The endpoint returns JSON 401 after logout and never redirects into an HTML login response.
- Database exceptions are logged server-side; clients receive only a safe temporary-unavailability message.
- Only five recent rows and the indexed unread count are queried per poll.

## Admin Privacy Behavior

Admin receives only notification rows explicitly addressed to the Admin user's ID. No chat query, chat participant expansion, or blanket Admin conversation visibility was added. Normal Customer–Owner–assigned Driver booking-chat privacy is unchanged.

**ADMIN BLANKET CHAT ACCESS: NO**

## Runtime Notification QA

| Check | Result |
|---|---|
| Customer bell renders and polls own rows | PASS |
| Owner bell renders and polls own rows | PASS |
| Driver bell renders and polls own rows | PASS |
| Admin bell renders and polls own rows | PASS |
| Zero unread hides orange indicator | PASS |
| One unread shows orange indicator | PASS |
| Latest five response shape | PASS |
| Real workflow notification visible by subsequent poll | PASS |
| Mark one updates DB and unread count | PASS |
| Mark all updates DB and unread count | PASS |
| Cross-user read-state mutation | BLOCKED as required |
| Client-supplied `user_id` | Ignored as required |
| Invalid CSRF | Rejected with 403 |
| Logged-out endpoint request | Rejected with JSON 401 |
| Shared Owner notification history | PASS |
| Shared Admin notification history | PASS |
| Customer existing View All page | PASS |
| Driver existing View All page | PASS |
| CSS and JavaScript assets | HTTP 200 |
| Raw PDO/SQL/PHP errors in tested output | NONE |

Temporary notification, connection, CRUD, booking, vehicle, review, leave, and user QA fixtures were removed. Final cleanup checks returned zero matching QA records.

## Navigation Regression

All tested destinations returned HTTP 200 with no fatal error, PHP warning, SQLSTATE, PDO exception, or stack trace marker.

| Role | Routes passed | Result |
|---|---:|---|
| Guest | 4/4 | PASS |
| Customer | 13/13 | PASS |
| Vehicle Owner | 10/10, including shared notifications | PASS |
| Driver | 15/15 | PASS |
| Admin | 14/14, including shared notifications | PASS |

## Authentication Regression

| Role | Login and redirect | Dashboard | Logout | Protected route denied afterward |
|---|---|---|---|---|
| Customer | PASS | PASS | PASS | PASS |
| Vehicle Owner | PASS | PASS | PASS | PASS |
| Driver | PASS | PASS | PASS | PASS |
| Admin | PASS | PASS | PASS | PASS |

Wrong-password rejection remained generic and safe. A Customer requesting the Admin dashboard was redirected to the Customer dashboard.

**AUTH REGRESSION: PASS**

## CRUD Regression

The four unchanged interim data paths were executed against MariaDB. Each temporary record was read after creation, read after update, deleted, and confirmed absent during cleanup.

| Interim entity | Create | Read | Update | Delete |
|---|---|---|---|---|
| Driver Leave Requests | PASS | PASS | PASS | PASS |
| Owner Vehicle Management | PASS | PASS | PASS | PASS |
| Customer Reviews & Ratings | PASS | PASS | PASS | PASS |
| Admin Platform User Management | PASS | PASS | PASS | PASS |

**FUNCTIONAL CRUD REGRESSION: PASS**

## PHP Lint

- PHP files checked: **241**
- Passed: **241**
- Failed: **0**
- Result: **PASS**

## Final Statements

**TRUE WEBSOCKETS USED: NO**

**NEAR-REAL-TIME AJAX POLLING: YES**

**ADMIN BLANKET CHAT ACCESS: NO**

**FUNCTIONAL CRUD REGRESSION: PASS**

**AUTH REGRESSION: PASS**
