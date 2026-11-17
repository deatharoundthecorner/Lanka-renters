# Customer Module Architecture

## Phase 7 test harness

`tests/Customer/Phase3TestRouter.php` is a local PHP built-in-server router for non-mutating Customer route checks. It uses `sys_get_temp_dir()` for test-only session storage and selects an existing active Customer profile at runtime. Ports 8765, 8766, 8767 and 8768 represent isolated guest, first-Customer, Driver and second-Customer fixtures. This harness does not change application authentication or write database data.

## Architecture Pattern

MVC (Model–View–Controller)

## Components

### Models

Responsible for:

- Database operations
- CRUD functionality
- Business data handling

### Controllers

Responsible for:

- Processing user requests
- Calling models
- Returning customer pages

### Views

Location:

public/customer/

Responsibilities:

- Display UI
- No SQL
- Minimal PHP
- Business logic handled by controllers

## Request Flow

Customer

↓

Customer Page

↓

Controller

↓

Model

↓

Database

↓

Model

↓

Controller

↓

Customer Page

## Shared Project Structure

Controllers

app/controllers/

Models

app/models/

Views

public/customer/

Assets

public/assets/
