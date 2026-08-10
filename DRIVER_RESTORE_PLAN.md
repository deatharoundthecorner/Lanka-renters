# Lanka Renters - Driver Module Restoration Plan

This plan provides a safety audit for restoring the Driver module functionality into the `develop` integration branch without overwriting unified authentication layouts or config configurations.

---

## 1. Commit `b4aed74` File Classification

The revert commit `b4aed74` affected 20 files. They are categorized as follows:

### Driver-Specific (Safe to Restore):
* `app/controllers/DriverController.php`
* `app/models/Driver.php`
* `app/models/DriverPayment.php`
* `public/driver/dashboard.php`
* `public/driver/earnings.php`
* `public/driver/notifications.php`
* Any other files inside `public/driver/*`

### Shared & Core Configs (DO NOT Overwrite/Restore blindly):
* `public/login.php` (Must preserve our unified SaaS sign-in card layout)
* `public/register.php` (Registration portal)
* `database/lanka_renters.sql` (Must run SQL schema ALTER queries, not replace files)
* `app/config/database.php` / `app/helpers/Database.php` (Must retain active port 3308 database server configurations)
* `public/index.php` (Application router entry page)

### Other Modules Files (Merge/Compare carefully):
* `app/controllers/CustomerController.php` (Shared customer controller)
* `app/controllers/OwnerController.php` (Shared owner controller)
* `app/controllers/ChatController.php` / `app/models/DriverOwnerLink.php` (Shared models)
* `public/customer/dashboard.php` / `public/customer/select_driver.php`
* `public/owner/dashboard.php`

---

## 2. Comparison: `feature-driver` vs `develop`

* `feature-driver` holds the complete driver codebase.
* `develop` is aligned with `main` where driver files have been reverted (removed/emptied).
* Running a full merge `git merge feature-driver` into `develop` will fail or do nothing because git's history graph assumes the commits are already present (due to the pre-revert merge).

---

## 3. Safest Restoration Method

Instead of running a global `git revert b4aed74` (which would overwrite our unified `login.php` layout and database configs), the safest strategy is to **checkout the driver-specific files directly** from the `feature-driver` branch:

### Safe Commands Plan:

1. Check out the integration branch:
   ```bash
   git checkout develop
   ```

2. Restore Driver-specific code without touching shared authentication templates or database connection files:
   ```bash
   git checkout feature-driver -- app/controllers/DriverController.php app/models/Driver.php app/models/DriverPayment.php public/driver/
   ```

3. Confirm that the unified login page and database configuration remain unchanged:
   ```bash
   git diff public/login.php app/helpers/Database.php
   ```

4. Verify PHP syntax on restored files:
   ```bash
   php -l app/controllers/DriverController.php
   php -l app/models/Driver.php
   ```
