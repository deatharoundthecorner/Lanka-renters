# Lanka Renters — Authoritative Enhanced Entity-Relationship (EER) Diagram

## Overview
This EER Diagram represents the complete, reconciled database schema for the **Lanka Renters** system across all 35 tables (incorporating Migrations 001 through 024). All primary keys (`PK`), foreign keys (`FK`), unique keys (`UQ`), and critical domain attributes are documented exactly as materialized in the production MariaDB database.

```mermaid
erDiagram
    %% 1. Users & Core Profiles
    users ||--o| customers : "1:1 profile"
    users ||--o| vehicle_owners : "1:1 profile"
    users ||--o| drivers : "1:1 profile"
    users ||--o{ notifications : "receives"
    users ||--o{ profile_change_requests : "submits"
    users ||--o{ account_change_requests : "submits"
    users ||--o{ announcements : "creates (admin)"

    %% 2. Documents & Verification
    customers ||--o{ customer_documents : "uploads"
    drivers ||--o{ driver_documents : "uploads"
    drivers ||--o{ driver_leaves : "registers"
    drivers ||--o{ driver_owner_links : "associated with"
    vehicle_owners ||--o{ driver_owner_links : "employs/links"
    vehicle_owners ||--o{ vehicles : "owns"
    vehicles ||--o{ vehicle_documents : "has"

    %% 3. Booking Core & Extensions
    customers ||--o{ bookings : "places"
    vehicles ||--o{ bookings : "reserved in"
    drivers ||--o{ bookings : "assigned to"
    bookings ||--|| customer_booking_agreements : "1:1 signed agreement"
    bookings ||--o| pickup_tracking : "1:1 handover confirmation"
    bookings ||--o{ driver_change_requests : "requests driver swap"
    bookings ||--o{ vehicle_inspections : "undergoes"
    vehicle_inspections ||--o{ vehicle_inspection_photos : "contains"

    %% 4. Financials
    bookings ||--o{ payments : "funded by"
    customers ||--o{ payments : "pays"
    bookings ||--o{ invoices : "billed via"
    customers ||--o{ invoices : "issued to"
    payments ||--o| invoices : "settles"
    bookings ||--o{ settlements : "owner payout"
    vehicle_owners ||--o{ settlements : "earns"
    bookings ||--o{ driver_payments : "driver wage"
    drivers ||--o{ driver_payments : "receives"

    %% 5. Incidents & Replacements
    bookings ||--o{ incidents : "encounters"
    users ||--o{ incidents : "reported by"
    incidents ||--o{ incident_photos : "evidenced by"
    incidents ||--o| replacement_requests : "triggers"
    bookings ||--o{ replacement_requests : "modifies"
    vehicles ||--o{ replacement_requests : "substituted by"

    %% 6. Reviews & Messaging
    bookings ||--o{ ratings_reviews : "evaluated via"
    customers ||--o{ ratings_reviews : "authors"
    vehicles ||--o{ ratings_reviews : "subject"
    drivers ||--o{ ratings_reviews : "subject"
    bookings ||--|| chat_rooms : "1:1 chat context"
    chat_rooms ||--o{ chat_participants : "joins"
    users ||--o{ chat_participants : "participant"
    chat_rooms ||--o{ chat_messages : "contains"
    users ||--o{ chat_messages : "sends"

    %% 7. Operations & Logs
    drivers ||--o{ driver_vehicle_checks : "inspects"
    vehicles ||--o{ driver_vehicle_checks : "checked"
    bookings ||--o{ email_logs : "referenced in"
    users ||--o{ admin_reviews : "audited by"

    %% Entity Definitions with Attributes
    users {
        int id PK
        varchar name
        varchar username UQ
        varchar email UQ
        varchar password_hash
        varchar phone
        enum role "customer,owner,driver,admin"
        enum status "active,inactive,suspended,pending"
        timestamp created_at
        timestamp updated_at
    }

    customers {
        int id PK
        int user_id FK,UQ
        varchar nic_number UQ
        varchar driving_license_number UQ
        enum verification_status "pending,approved,rejected"
        enum identity_verification_status "pending,approved,rejected,resubmission_required"
        enum driving_license_status "not_submitted,pending,approved,rejected,resubmission_required"
        tinyint has_driving_license
        text identity_rejection_reason
        text license_rejection_reason
        timestamp identity_verified_at
        timestamp license_verified_at
    }

    vehicle_owners {
        int id PK
        int user_id FK,UQ
        varchar business_name
        varchar business_reg_number UQ
        text address
        enum verification_status "pending,approved,rejected"
        timestamp created_at
    }

    drivers {
        int id PK
        int user_id FK,UQ
        varchar license_number UQ
        int experience_years
        enum verification_status "pending,approved,rejected"
        enum availability_status "available,busy,on_leave,inactive"
        tinyint is_active
        decimal rating
        int total_trips
    }

    vehicles {
        int id PK
        int owner_id FK
        varchar make
        varchar model
        int year
        varchar license_plate UQ
        enum vehicle_type "car,van,suv,lorry,motorbike"
        enum transmission "manual,automatic"
        enum fuel_type "petrol,diesel,hybrid,electric"
        int seating_capacity
        varchar location
        tinyint air_conditioning
        decimal price_per_day
        decimal price_with_driver_per_day
        decimal monthly_rental_price
        decimal refundable_key_money
        int mileage_limit_km
        tinyint mileage_is_unlimited
        decimal additional_mileage_charge_per_km
        varchar image_url
        enum status "available,rented,maintenance,unavailable"
        enum verification_status "pending,approved,rejected"
    }

    bookings {
        int id PK
        int customer_id FK
        int vehicle_id FK
        int driver_id FK "nullable"
        date start_date
        date end_date
        decimal total_amount
        enum status "pending_approval,pending_payment,confirmed,ongoing,completed,cancelled,rejected"
        timestamp created_at
        timestamp updated_at
    }

    customer_booking_agreements {
        int id PK
        int booking_id FK,UQ
        int customer_id FK
        varchar agreement_version
        timestamp accepted_at
    }

    pickup_tracking {
        int id PK
        int booking_id FK,UQ
        timestamp customer_confirmed_at
        text customer_notes
        timestamp created_at
    }

    driver_change_requests {
        int id PK
        int booking_id FK
        int requested_by FK
        int current_driver_id FK
        int requested_driver_id FK
        text reason
        enum status "pending,approved,rejected,cancelled"
        text owner_remarks
        int reviewed_by FK "nullable"
        timestamp reviewed_at
    }

    vehicle_inspections {
        int id PK
        int vehicle_id FK
        int booking_id FK
        int inspected_by FK
        enum inspection_type "pre_rental,post_rental"
        int odometer_reading
        varchar fuel_level
        text exterior_condition
        text interior_condition
        enum status "pass,flagged,fail"
        timestamp created_at
    }

    vehicle_inspection_photos {
        int id PK
        int inspection_id FK
        varchar photo_path
        varchar caption
        timestamp created_at
    }

    payments {
        int id PK
        int booking_id FK
        int customer_id FK
        decimal amount
        enum payment_method "credit_card,debit_card,bank_transfer,cash"
        varchar transaction_id UQ
        enum payment_status "pending,completed,failed,refunded"
        timestamp payment_date
    }

    invoices {
        int id PK
        varchar invoice_number UQ
        int booking_id FK
        int customer_id FK
        int payment_id FK "nullable"
        decimal rental_fee
        decimal driver_fee
        decimal additional_charges
        decimal discount
        decimal tax
        decimal total_amount
        enum invoice_status "pending,paid,cancelled"
        timestamp generated_at
    }

    incidents {
        int id PK
        int booking_id FK
        int reported_by FK
        varchar incident_type
        text description
        datetime incident_date
        varchar district
        varchar vehicle_body_category
        varchar google_maps_url
        varchar police_report_path
        enum resolution_preference "replacement,refund"
        enum severity "minor,moderate,major"
        enum status "reported,investigating,resolved"
    }

    incident_photos {
        int id PK
        int incident_id FK
        varchar photo_path
        timestamp uploaded_at
    }

    replacement_requests {
        int id PK
        int incident_id FK,UQ
        int booking_id FK
        int original_vehicle_id FK
        int replacement_vehicle_id FK "nullable"
        text reason
        enum status "pending,approved,rejected,accepted,declined"
        text admin_remarks
    }

    settlements {
        int id PK
        int owner_id FK
        int booking_id FK
        decimal gross_amount
        decimal commission_amount
        decimal net_amount
        date settlement_date
        enum status "pending,completed"
    }

    driver_payments {
        int id PK
        int driver_id FK
        int booking_id FK
        decimal amount
        enum payment_status "pending,paid"
    }

    ratings_reviews {
        int id PK
        int booking_id FK
        int customer_id FK
        int vehicle_id FK
        int driver_id FK "nullable"
        int rating
        text review
        timestamp created_at
    }

    chat_rooms {
        int id PK
        int booking_id FK,UQ
        timestamp created_at
    }

    chat_messages {
        int id PK
        int room_id FK
        int sender_id FK
        text message_text
        timestamp sent_at
    }
```

## Relational Invariants & Constraints Summary
1. **User Role Specialization**: A record in `users` maps to at most one profile among `customers`, `vehicle_owners`, or `drivers` enforced by business logic and unique foreign keys (`user_id`).
2. **Booking Immutability & Agreements**: A `booking` cannot proceed to `pending_payment` without an associated unique record in `customer_booking_agreements` capturing the accepted legal standard terms and timestamp.
3. **Driver Change Isolation**: Only `confirmed` or `ongoing` bookings can initiate a `driver_change_request`. Concurrency checks ensure neither the current nor replacement driver has scheduling conflicts.
4. **Inspection Photo Integrity**: Photos uploaded during pre- or post-rental inspections are stored in `vehicle_inspection_photos` and linked directly to the parent inspection record with foreign key cascade delete.
5. **Incident & Replacement Linkage**: Every incident reported with a `resolution_preference = 'replacement'` triggers or associates with a record in `replacement_requests`, allowing seamless vehicle substitution upon customer acceptance.
