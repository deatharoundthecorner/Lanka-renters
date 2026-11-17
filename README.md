# Lanka Renters

Lanka Renters is a vehicle rental management system that connects customers, vehicle owners, drivers, and administrators through a centralized platform.

## Technology Stack

### Frontend
- HTML
- CSS
- JavaScript

### Backend
- PHP

### Database
- MySQL

## Main System Modules

- User Authentication and Role Management
- Customer Management
- Vehicle Owner Management
- Vehicle Management
- Driver Management
- Booking Management
- Payment Verification
- Vehicle Inspection Management
- Incident and Replacement Vehicle Management
- Communication and Notification Management
- Admin Management

## Project Structure

### database/
Contains MySQL database scripts.

### public/
Contains user interface pages and frontend assets.

### app/
Contains PHP backend logic including controllers, models, and helpers.

### uploads/
Stores uploaded documents and images.

### docs/
Contains project documentation and diagrams.

## Development Rules

- Use only PHP, HTML, CSS, JavaScript, and MySQL.
- Do not use frameworks.
- Keep MVC-style separation between interface, business logic, and database operations.
- Never upload sensitive user documents to GitHub.

## Core System Lifecycles

### A. Booking Lifecycle

The standard reservation progresses through the following sequential states. Customer bookings are created as `pending_payment`; the `pending_approval` status and the Owner approve / reject actions exist in the schema and Owner module but are not used by the Customer booking flow.

```
pending_payment
    ↓ (Customer submits bank slip, Admin approves payment)
confirmed
    ↓ (Pre-rental inspection passes + Owner handover / Driver pickup)
ongoing
    ↓ (Post-rental inspection passes / Driver dropoff)
completed
```

#### Additional Terminal / Exception States:
- **`cancelled`**: Occurs when a Customer cancels a pending booking or an Owner rejects a request.
- **`disputed`**: Triggered when an ongoing rental experiences an incident that is investigated and marked resolved by Admin, temporarily pausing trip progression while dispute resolution is arranged.

---

### B. With-Driver Customer Pickup Lifecycle

For bookings with an assigned driver (`booking_type = 'with_driver'`), pickup progress proceeds as follows:

```
Driver marked picked_up
    ↓
Customer confirmed pickup (in-person confirmation)
    ↓
Customer started rental (active journey confirmation)
    ↓
Driver dropped_off (trip completion)
```

#### Important Workflow Rules:
- **Driver Pickup Authority:** The Driver recording `picked_up` remains the sole authoritative trigger that transitions `bookings.status` to `ongoing` and `vehicles.status` to `rented`.
- **Additive Customer Actions:** `Customer confirmed pickup` and `Customer started rental` are additive tracking events recorded in `pickup_tracking` and `bookings.pickup_status`. They do not replace or delay driver-side state machine triggers.
- **Self-Drive Isolation:** Self-drive rentals do **not** use the Customer Pickup Status workflow. They rely exclusively on direct Owner-Customer vehicle inspection and handover.

---

### C. Incident & Dispute Resolution Workflow

When an emergency or vehicle breakdown occurs during an active rental:

```
ongoing
   ↓
incident reported (Customer or Driver submits incident report + evidence photos)
   ↓
Admin resolution (Admin investigates evidence and resolves incident)
   ↓
disputed (Active booking pauses; Driver progression is blocked)
   ├── PATH A: Refund Resolution
   └── PATH B: Vehicle Replacement Resolution
```

#### Resolution Paths:

1. **Path A — Transactional Refund:**
   - `payments.payment_status` = `refunded`
   - `invoices.invoice_status` = `cancelled`
   - `bookings.status` = `completed`
   - `vehicles.status` = `available`
   - *Result:* Rental concludes immediately; vehicle returns to available inventory; driver cannot progress trip further.

2. **Path B — Vehicle Replacement:**
   - Admin assigns an available, approved vehicle matching the required category.
   - Customer accepts the replacement vehicle offer in the customer portal.
   - Original vehicle: `vehicles.status` = `maintenance`
   - Replacement vehicle: `vehicles.status` = `rented`
   - Booking synchronization: `bookings.vehicle_id` updated to replacement vehicle ID.
   - Booking resumes: `bookings.status` = `ongoing`
   - *Result:* Driver/Customer continues the trip with the replacement vehicle.

