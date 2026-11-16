# Customer Phase 1 Foundation

## Scope

Phase 1 establishes Customer routing, MVC flow, authentication guards, safe
includes, output handling, and CSRF protection. It intentionally does not
implement vehicle search, booking CRUD, sample data, payment workflows, or the
full prototype/Figma interface.

## Canonical request flow

The canonical dashboard URL is:

`public/customer/dashboard.php`

The request flow is:

1. `public/customer/_bootstrap.php` starts the existing authentication guard.
2. `CustomerController` obtains the authenticated session user.
3. `Customer` converts the authenticated user into the Customer view identity.
4. `public/customer/dashboard/index.php` renders escaped view data.

The historical `public/customer/dashboard/index.php` browser URL is retained as
a guarded compatibility route. It redirects once to the canonical dashboard
and cannot form a redirect loop.

## Authentication boundary

Every Customer entry point loads `public/customer/_bootstrap.php` either
directly or through `_foundation_page.php`. The bootstrap calls the existing
`AuthHelper::requireRole('customer')` guard before rendering content.

Customer identity is read from `$_SESSION['user']`. Customer pages do not trust
`customer_id` or `user_id` request values as identity evidence.

## CSRF boundary

`app/helpers/CustomerCsrf.php` stores a random 256-bit token in the session.
All POST requests entering through the Customer bootstrap must provide the
`_csrf_token` field. Validation uses `hash_equals`. Invalid or missing tokens
receive a generic HTTP 403 response, and token values are not placed in URLs or
logs.

Future Customer forms must include:

```php
<?= CustomerCsrf::field() ?>
```

## Include and output rules

- PHP includes use paths anchored by `__DIR__` or `dirname(__DIR__)`.
- Customer URLs are generated from the active `/customer/` URL segment so the
  project can run from an XAMPP subdirectory.
- Dynamic HTML uses `htmlspecialchars` with UTF-8 and quote escaping.
- Customer requests disable browser display of PHP errors while leaving PHP
  error logging enabled.
- Uncaught exceptions produce a generic response; technical details go to the
  configured PHP error log.

## Shared database blocker

The Customer branch currently has both `app/helpers/Database.php` and
`app/core/Database.php` declaring `Database`. Its current
`app/config/database.php` loads the core file and uses the local default host,
which causes a fatal duplicate-class error when a model opens a connection and
does not implement the verified XAMPP port `3308` baseline.

Phase 1 does not edit that shared configuration. The safest coordinated fix is
to take the authoritative `app/config/database.php` from `origin/develop`, which
uses `127.0.0.1;port=3308` and does not load the duplicate core class. The team
should also decide whether the unused Customer-branch `app/core/Database.php`
is removed in a shared architecture cleanup.

Until that coordinated change is approved:

- Customer foundation pages avoid opening a database connection.
- Database-dependent Customer features remain blocked.
- Developers should not commit machine-specific credentials or paths.
- MySQL should be started through XAMPP on port `3308` before database testing.

## Automated check

Run:

```powershell
C:\xampp\php\php.exe tests\Customer\Phase1FoundationTest.php
```

The test reports the shared database configuration as `BLOCKED` until the
coordinated baseline change is present.
