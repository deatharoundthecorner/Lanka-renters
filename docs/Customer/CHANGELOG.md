# Changelog

## Phase 7 — Customer security and regression testing

### Fixed

- Repaired the Customer HTTP test router so local role and ownership checks use a writable temporary session directory and real development Customer fixtures instead of a missing hard-coded user.

### Approved profile extension

- Added nullable Customer profile `district` and `address` fields to the approved schema, local development database, and Customer profile workflow.

### Verified

- Completed syntax, static security, route, CSRF-missing-token, Customer ownership/IDOR, read-only database-integrity and model-read checks.
- Recorded browser-dependent and state-changing tests as blocked or not run where no disposable test database, safe sample upload or browser session was available.
- No unapproved Customer business rule or database schema was changed.

## Sprint 1

### Added

- Customer Module documentation
- MVC architecture documentation
- CSS design guide
- Business rules
- BPM automation documentation
- Testing checklist

### Next Sprint

- CSS Design System
- Customer Dashboard Layout
- Reusable Components
