# Lanka Renters — State Machine Diagram

## Overview
This document specifies the authoritative finite state machines (FSM) governing key domain lifecycles in **Lanka Renters**: **Booking Lifecycle**, **Vehicle Lifecycle**, **Driver Change Request Lifecycle**, **Incident Lifecycle**, and **Customer KYC Lifecycle**.

```mermaid
stateDiagram-v2
    %% 1. Booking State Machine
    state "Booking Lifecycle" as BookingLifecycle {
        [*] --> pending_approval : Customer creates reservation + signs agreement
        
        pending_approval --> rejected : Owner rejects request
        pending_approval --> cancelled : Customer cancels request
        pending_approval --> pending_payment : Owner approves request
        
        pending_payment --> cancelled : Customer cancels before payment
        pending_payment --> confirmed : Payment successfully processed
        
        confirmed --> ongoing : Pre-rental inspection done + Customer confirms pickup
        confirmed --> cancelled : Customer cancels (subject to policy)
        
        ongoing --> completed : Post-rental inspection done + Vehicle returned
        ongoing --> ongoing : Driver changed OR Replacement vehicle assigned
        
        completed --> [*]
        rejected --> [*]
        cancelled --> [*]
    }
```

```mermaid
stateDiagram-v2
    %% 2. Vehicle State Machine
    state "Vehicle Lifecycle" as VehicleLifecycle {
        [*] --> unavailable : Owner registers vehicle (pending verification)
        unavailable --> available : Admin approves vehicle verification
        
        available --> rented : Booking pickup confirmed (status becomes 'ongoing')
        rented --> available : Vehicle returned & post-inspection passed (status 'completed')
        
        available --> maintenance : Scheduled servicing or routine repair
        rented --> maintenance : Severe incident/accident during rental
        maintenance --> available : Maintenance completed & inspection passed
        
        available --> unavailable : Owner delists or pauses fleet availability
        maintenance --> unavailable : Permanent mechanical retirement
    }
```

```mermaid
stateDiagram-v2
    %% 3. Driver Change Request State Machine
    state "Driver Change Request Lifecycle" as DCRLifecycle {
        [*] --> pending : Customer initiates swap on confirmed/ongoing booking
        
        pending --> approved : Owner approves request (atomic driver swap executed)
        pending --> rejected : Owner rejects request (reason recorded)
        pending --> cancelled : Customer cancels request before owner review
        
        approved --> [*]
        rejected --> [*]
        cancelled --> [*]
    }
```

```mermaid
stateDiagram-v2
    %% 4. Incident & Replacement State Machine
    state "Incident & Replacement Lifecycle" as IncidentLifecycle {
        [*] --> reported : Customer reports breakdown/accident with GPS & evidence
        
        reported --> investigating : Admin/Safety team begins inquiry
        
        state investigating {
            [*] --> assessing
            assessing --> replacement_provisioned : Resolution preference = replacement
            assessing --> refund_provisioned : Resolution preference = refund
            
            replacement_provisioned --> replacement_accepted : Customer confirms substitution
            replacement_provisioned --> replacement_declined : Customer declines substitution
        }
        
        investigating --> resolved : Repairs finished / replacement settled / refund disbursed
        resolved --> [*]
    }
```

```mermaid
stateDiagram-v2
    %% 5. Customer KYC State Machine
    state "Customer KYC Verification Lifecycle" as KYCLifecycle {
        [*] --> pending : Customer submits NIC / Driving License uploads
        
        pending --> approved : Admin approves identity & license
        pending --> rejected : Admin rejects due to invalid / fraudulent data
        pending --> resubmission_required : Admin flags blurry photo or missing back side
        
        resubmission_required --> pending : Customer uploads corrected documents
        
        approved --> [*]
        rejected --> [*]
    }
```

## State Transition Integrity Rules
1. **No Out-of-Order Booking Advances**: A booking can never enter `ongoing` without passing through `confirmed` (payment complete) and having a signed record in `customer_booking_agreements`.
2. **Vehicle Lockout**: When a booking transitions to `ongoing`, the assigned vehicle status is synchronously locked to `rented`. It cannot be assigned to another active reservation.
3. **Driver Change Non-Disruption**: An approved driver change modifies `bookings.driver_id` in place without resetting or perturbing the active booking state (`ongoing`).
