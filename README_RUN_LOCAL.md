# Lanka Renters - Local Setup

## Requirements

Install XAMPP with:

- Apache
- PHP 8.2 or a compatible PHP 8.x runtime
- MariaDB/MySQL

No Composer required.
No npm required.
No Node.js required.
No external API required.

The application uses plain PHP, HTML, CSS, vanilla JavaScript, PDO, and MariaDB/MySQL.

## Step 1: Copy the Project

Extract or copy the project to a normal development folder. A typical XAMPP location is:

`C:\xampp\htdocs\Lanka-renters`

You may also use another folder with PHP's built-in development server.

## Step 2: Start XAMPP

Start:

- Apache
- MySQL/MariaDB

## Step 3: Check the Database Port

The checked-in connection settings are development placeholders for the `lanka_renters` database. Confirm your own MySQL host, port, username, and password locally; the shared helper uses the configured host and your MySQL server default port. Do not add private database credentials to the repository.

## Step 4: Create the Database

Ensure the local MySQL/MariaDB service is running and open phpMyAdmin or a MySQL client.

## Step 5: Create and initialize the database

Create an empty database named `lanka_renters`, then import `database/schema.sql` with phpMyAdmin or the MySQL client. This structure-only file provides the application schema. Schema migration files are retained for reviewing or upgrading existing installations; optional demo data migrations are omitted. Do not run migrations after importing the current schema without first checking which changes are already present.

Database exports, demo seed records, and login credentials are intentionally omitted from this repository. Create local test accounts only in a private, disposable database.

Public registration does not create Admin users. Follow [Create the Initial Admin Account](database/ADMIN_SETUP.md) to create the first Admin locally without adding credentials to the repository.

## Step 6: Check Local Connection Values

Confirm the local values for:

- host
- port
- database name
- username
- password

Never copy a private local database password into the project or documentation.

## Step 7: Run the Application

Preferred PHP built-in server option:

1. Open a terminal in the project root.
2. Run:

```text
php -S 127.0.0.1:8000 -t public
```

3. Open:

`http://127.0.0.1:8000/`

With Apache and the project in `C:\xampp\htdocs\Lanka-renters`, the URL is typically:

`http://localhost/Lanka-renters/public/`

The exact Apache URL may differ if the folder or Apache virtual host is configured differently.

## Folder Guide

- `app/controllers/` - Request and business-flow coordination.
- `app/models/` - PDO/database query and persistence ownership.
- `app/helpers/` - Database, authentication, CSRF, PDF, and shared infrastructure.
- `public/` - Role pages, views, endpoints, and assets.
- `database/` - Structure-only schema and numbered migrations.
- `docs/` - Audits, reports, and project notes.

The project is MVC-style / mixed. New backend development should follow the controller -> model -> view separation, but the legacy application is not strict MVC throughout.

## Confirmed PHP Extensions

The current source and runtime confirm these requirements:

- `PDO`
- `pdo_mysql`
- `fileinfo`
- `session`
- `json`

## Writable Folders

Keep these application storage locations writable by the PHP/Apache process when using upload workflows:

- `storage/customer-payment-evidence/`
- `storage/customer-verification-documents/`
- `public/uploads/`
- `public/uploads/incidents/`
- `public/assets/uploads/vehicles/`

Do not change permissions automatically. Configure permissions according to your operating system and local server account.

## Fresh-Copy Smoke Plan

Use a separate test copy and disposable database when validating a teammate setup:

1. Copy/extract the project to a new folder.
2. Create a test database and import `database/schema.sql`.
3. Configure the database connection for that local environment.
4. Create test accounts and fixtures in the private database.
5. Start PHP with `php -S 127.0.0.1:8000 -t public`.
6. Check the guest homepage and authenticated routes using the private test accounts.
7. Confirm responses contain no `SQLSTATE`, `PDOException`, `Fatal error`, `Warning`, `Notice`, or `Stack trace` output.

## Known Portability Notes

The project is configured for MariaDB on `127.0.0.1:3306` (XAMPP default). If your installation uses another port (one earlier development machine used `3308`), change only the local database connection value. References to `127.0.0.1:8000` in documentation and tests refer to the PHP built-in server URL, not a database port.

The repository may contain project audit notes; they are documentation, not runtime dependencies. Uploaded files, screenshots, database exports, and seeded login credentials are omitted.
