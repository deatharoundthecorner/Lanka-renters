# Lanka Renters — Component Diagram

## Overview
This Component Diagram displays the modular decomposition of the **Lanka Renters** system, detailing how client-side assets, server-side presentation components, MVC controllers, core service abstractions, data persistence, and file system storage interoperate.

```mermaid
componentDiagram
    %% Presentation Tier
    package "Presentation Layer (public/)" {
        [Customer Portal Views] as CP_Views
        [Owner Operations Views] as OO_Views
        [Driver Portal Views] as DP_Views
        [Admin Control Center] as AD_Views

        package "Reusable UI Components" {
            [Booking Actions & Forms] as Comp_Booking
            [Booking Agreement Component] as Comp_Agreement
            [Pre-Booking Checklist] as Comp_Checklist
            [Rental Usage & Pricing Specs] as Comp_Specs
            [Inspection Photo Gallery] as Comp_Gallery
            [Incident Status & Replacement] as Comp_Incident
        }
    }

    %% Controller Tier
    package "Application Controllers (app/controllers/)" {
        [CustomerBookingController] as Ctrl_CustBooking
        [CustomerPortalController] as Ctrl_CustPortal
        [DriverChangeRequestController] as Ctrl_DCR
        [OwnerInspectionController] as Ctrl_OwnerInsp
        [OwnerVehicleController] as Ctrl_OwnerVeh
        [AdminController] as Ctrl_Admin
        [AuthController] as Ctrl_Auth
    }

    %% Core Helpers & Security
    package "Core Foundation (app/helpers/)" {
        [Database Gateway] as Help_DB
        [Auth & Session Manager] as Help_Auth
        [CSRF Protection] as Help_CSRF
        [File Upload Validator] as Help_Upload
    }

    %% Domain Model Tier
    package "Domain Logic & Models (app/models/)" {
        [CustomerBooking] as Mod_Booking
        [CustomerPortal] as Mod_Portal
        [CustomerVehicle] as Mod_CustVeh
        [VehicleInspection] as Mod_Insp
        [DriverChangeRequest] as Mod_DCR
        [ReplacementRequest] as Mod_Repl
        [Notification] as Mod_Notif
    }

    %% Storage & Database Tier
    database "MariaDB (Port 3308)" {
        folder "Relational Tables" {
            [bookings & customer_booking_agreements] as DB_Bookings
            [vehicles & vehicle_documents] as DB_Vehicles
            [vehicle_inspections & photos] as DB_Inspections
            [incidents & replacement_requests] as DB_Incidents
            [driver_change_requests] as DB_DCR
            [payments & invoices & settlements] as DB_Financials
            [users & customers & owners & drivers] as DB_Users
        }
    }

    folder "Secure File Storage (public/uploads/)" {
        [inspections/] as FS_Inspections
        [police_reports/] as FS_Police
        [incident_photos/] as FS_Incidents
        [customer_docs/] as FS_CustDocs
        [driver_docs/] as FS_DriverDocs
    }

    %% Linkages: Views to Components
    CP_Views ..> Comp_Booking
    CP_Views ..> Comp_Agreement
    CP_Views ..> Comp_Checklist
    CP_Views ..> Comp_Specs
    CP_Views ..> Comp_Gallery
    CP_Views ..> Comp_Incident

    %% Linkages: Views to Controllers
    CP_Views --> Ctrl_CustBooking : HTTP POST / Form
    CP_Views --> Ctrl_CustPortal : AJAX / HTTP POST
    CP_Views --> Ctrl_DCR : HTTP POST
    OO_Views --> Ctrl_OwnerInsp : Multipart POST
    OO_Views --> Ctrl_OwnerVeh : Form POST
    OO_Views --> Ctrl_DCR : Review POST
    AD_Views --> Ctrl_Admin : Administration Requests

    %% Linkages: Controllers to Helpers
    Ctrl_CustBooking ..> Help_Auth
    Ctrl_CustBooking ..> Help_CSRF
    Ctrl_CustPortal ..> Help_Upload
    Ctrl_OwnerInsp ..> Help_Upload

    %% Linkages: Controllers to Models
    Ctrl_CustBooking --> Mod_Booking
    Ctrl_CustPortal --> Mod_Portal
    Ctrl_CustPortal --> Mod_Repl
    Ctrl_DCR --> Mod_DCR
    Ctrl_OwnerInsp --> Mod_Insp
    Ctrl_OwnerInsp --> Mod_Notif

    %% Linkages: Models to Database
    Mod_Booking --> Help_DB
    Mod_Portal --> Help_DB
    Mod_Insp --> Help_DB
    Mod_DCR --> Help_DB
    Mod_Notif --> Help_DB
    Help_DB --> MariaDB : PDO Prepared Queries

    %% Linkages: Uploads to File System
    Ctrl_OwnerInsp --> FS_Inspections : Store Inspection Photos
    Ctrl_CustPortal --> FS_Police : Store Police Reports
    Ctrl_CustPortal --> FS_Incidents : Store Incident Evidence
```

## Component Roles & Interactions
1. **Reusable UI Components**: Self-contained modular PHP components (e.g. `accepted-booking-agreement.php`, `pre-booking-checklist.php`, `rental-usage-details.php`) ensure uniform presentation across customer and administrative interfaces.
2. **File Upload Validator**: Verifies MIME types against byte magic numbers (`finfo`), checks extension whitelists, enforces maximum 5MB quotas, and creates cryptographically random file names.
3. **Database Gateway**: Singleton PDO wrapper that manages MariaDB transaction states, connection persistence, and character encoding (`utf8mb4`).
