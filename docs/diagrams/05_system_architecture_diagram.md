# Lanka Renters — System Architecture Diagram

## Overview
This System Architecture Diagram outlines the tiered architecture of **Lanka Renters**, showcasing the client presentation environment, network routing, application hosting tier, database layer, persistent file assets, and external gateway integration.

```mermaid
graph TD
    %% Client Tier
    subgraph Client_Tier ["Client Tier (Browsers & Devices)"]
        Browser_Cust["Customer Web Client\n(Desktop & Mobile Browser)"]
        Browser_Owner["Owner Fleet Portal\n(Fleet Ops & Dispatch)"]
        Browser_Driver["Driver Mobile Interface\n(Status, Trips & Checks)"]
        Browser_Admin["Administrator Control Room\n(Verification, Incidents & Oversight)"]
    end

    %% Network & Edge Tier
    subgraph Edge_Tier ["Edge & Network Tier"]
        HTTPS_Gateway["HTTPS / TLS 1.3 Termination\n(Reverse Proxy / Web Server)"]
        Static_Asset_Cache["Static Asset Server\n(CSS3, Vanilla JS, Web Fonts, Icons)"]
    end

    %% Application Server Tier
    subgraph App_Tier ["Application Server Tier (PHP 8.2 Runtime)"]
        Router["Front Controller & Semantic Router\n(public/index.php & public/*/*.php)"]
        
        subgraph Middleware_Stack ["Security & Session Middleware"]
            Auth_Guard["Role-Based Access Control (RBAC)"]
            CSRF_Guard["CSRF Token Verification"]
            Input_Sanitizer["XSS Sanitizer & Type Coercion"]
            Upload_Sanitizer["MIME / Byte-Header Upload Validator"]
        end

        subgraph Core_Controllers ["MVC Controller Engine"]
            Ctrl_Booking["CustomerBookingController"]
            Ctrl_Portal["CustomerPortalController"]
            Ctrl_DCR["DriverChangeRequestController"]
            Ctrl_Insp["OwnerInspectionController"]
            Ctrl_AdminOps["AdminReviewController"]
        end

        subgraph Core_Services ["Domain Logic & Business Services"]
            Svc_Booking["Booking State Engine & Legal Agreement"]
            Svc_DriverDispatch["Driver Allocation & Conflict Detection"]
            Svc_Incident["Incident & Emergency Vehicle Replacement"]
            Svc_Audit["Financial Settlements & Billing Engine"]
        end
    end

    %% Persistence Tier
    subgraph Data_Tier ["Data & Persistence Tier"]
        subgraph MariaDB_Cluster ["MariaDB Relational Database (Port 3308)"]
            InnoDB_Engine["InnoDB Storage Engine (ACID Compliant)"]
            Tables_Bookings["Bookings, Agreements, Tracking (35 Tables)"]
            Tables_Vehicles["Vehicles, Specs, Inspections & Photos"]
            Tables_Users["Users, KYC Profiles, Notifications"]
            Tables_Incidents["Incidents, Replacements, Disputes"]
        end

        subgraph Local_Storage ["Protected File Storage (public/uploads/)"]
            Store_Inspections["Inspection Photos (/uploads/inspections/)"]
            Store_Police["Police Reports (/uploads/police_reports/)"]
            Store_KYC["Identity & License Documents (/uploads/customer_docs/)"]
        end
    end

    %% External Third-Party Integrations
    subgraph External_Services ["External Service Integrations"]
        Payment_Gateway["Secure Payment Gateway\n(IPG / Card Processor)"]
        SMS_Email["Email / In-App Notification Hub\n(SMTP / Notification Subsystem)"]
        Google_Maps["Google Maps & Geolocation API\n(Incident Coordinates)"]
    end

    %% Connections
    Browser_Cust -->|HTTPS Request| HTTPS_Gateway
    Browser_Owner -->|HTTPS Request| HTTPS_Gateway
    Browser_Driver -->|HTTPS Request| HTTPS_Gateway
    Browser_Admin -->|HTTPS Request| HTTPS_Gateway

    HTTPS_Gateway --> Static_Asset_Cache
    HTTPS_Gateway --> Router

    Router --> Middleware_Stack
    Middleware_Stack --> Core_Controllers
    Core_Controllers --> Core_Services

    Core_Services -->|PDO / Prepared Queries| InnoDB_Engine
    InnoDB_Engine --> Tables_Bookings
    InnoDB_Engine --> Tables_Vehicles
    InnoDB_Engine --> Tables_Users
    InnoDB_Engine --> Tables_Incidents

    Core_Controllers -->|Safe File Stream| Local_Storage
    Local_Storage --> Store_Inspections
    Local_Storage --> Store_Police
    Local_Storage --> Store_KYC

    Core_Services -.->|REST / HTTPS| Payment_Gateway
    Core_Services -.->|SMTP / API| SMS_Email
    Core_Services -.->|HTTP Geocoding| Google_Maps
```

## Architectural Characteristics
1. **Tier Isolation**: The presentation layer remains completely decoupled from physical persistence. Controllers communicate with models via strictly typed interfaces.
2. **Defensive Storage Strategy**: All user-submitted binaries (police accident reports, pre-rental photos, driving licenses) are stored in partitioned physical directories with randomized non-enumerable naming to prevent directory traversal and arbitrary execution.
3. **Database Concurrency**: Critical workflows execute inside transactional boundaries with row-level locks to eliminate race conditions during vehicle booking, driver reassignment, and replacement vehicle allocation.
