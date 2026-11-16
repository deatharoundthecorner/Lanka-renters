# Lanka Renters — Sequence Diagram: Incident & Replacement Flow

## Overview
This sequence diagram illustrates the lifecycle of an in-rental emergency road incident and subsequent vehicle replacement in **Lanka Renters**, showcasing multi-party interactions across the **Customer**, **Customer Portal Controller**, **Secure Upload Storage**, **MariaDB**, **Administrator**, and **Fleet Owner**.

```mermaid
sequenceDiagram
    autonumber
    actor Customer as 👤 Customer
    participant UI as 🖥️ Customer Portal UI
    participant Ctrl as ⚙️ CustomerPortalController
    participant ModelPortal as 📦 CustomerPortal Model
    participant UploadFS as 📁 Secure File Storage (/uploads/)
    participant DB as 🗄️ MariaDB Database
    participant Notif as 🔔 Notification System
    actor Admin as 🛡️ Administrator
    actor Owner as 👤 Vehicle Owner

    Customer ->> UI: Navigates to /customer/incidents/create.php?booking_id={id}
    UI ->> Ctrl: GET incidentCreatePage()
    Ctrl ->> ModelPortal: getIncidentEligibleBookings(customerUserId)
    ModelPortal ->> DB: SELECT b.* FROM bookings b WHERE b.status = 'ongoing' AND ...
    DB -->> ModelPortal: Return ongoing bookings list
    ModelPortal -->> Ctrl: Eligible bookings data
    Ctrl -->> UI: Render Incident Reporting Form

    Customer ->> UI: Enters Incident Details (Accident/Breakdown, District: "Colombo", GPS URL, Resolution: "Replacement", Uploads Incident Photo & Police Report)
    UI ->> Ctrl: POST incidentCreatePage() [multipart/form-data]
    
    Ctrl ->> Ctrl: Validate Input, Severity ('minor'|'moderate'|'major') & CSRF
    Ctrl ->> UploadFS: Validate & Store Incident Photo (/uploads/incident_photos/rnd_abc.jpg)
    Ctrl ->> UploadFS: Validate & Store Police Report PDF (/uploads/police_reports/rnd_xyz.pdf)

    Ctrl ->> ModelPortal: createIncident(userId, bookingId, desc, date, severity, photoPath, type, district, bodyCategory, mapUrl, policeReportPath, pref="replacement")
    ModelPortal ->> DB: BEGIN TRANSACTION
    ModelPortal ->> DB: SELECT b.id, b.vehicle_id, vo.user_id FROM bookings b ... FOR UPDATE
    ModelPortal ->> DB: INSERT INTO incidents (booking_id, reported_by, incident_type, description, incident_date, district, vehicle_body_category, google_maps_url, police_report_path, resolution_preference, severity, status) VALUES (...)
    DB -->> ModelPortal: Incident ID #inc_21

    ModelPortal ->> DB: INSERT INTO incident_photos (incident_id, photo_path) VALUES (#inc_21, 'uploads/incident_photos/rnd_abc.jpg')
    ModelPortal ->> DB: COMMIT TRANSACTION

    ModelPortal ->> Notif: dispatchIncidentAlerts(incidentId, bookingId, ownerUserId, adminUserIds)
    Notif ->> DB: INSERT INTO notifications (...)
    Notif -->> Owner: "Incident #21 reported for Booking #ID (Colombo, Major)."
    Notif -->> Admin: "High-priority Incident #21 reported. Emergency review required."

    Ctrl -->> UI: Flash message & Redirect to /customer/incidents/details.php?id=21

    %% Admin Investigation & Replacement Provisioning
    Admin ->> DB: Review Incident #21 & Inspect Police Report / GPS coordinates
    Admin ->> DB: INSERT INTO replacement_requests (incident_id, booking_id, original_vehicle_id, replacement_vehicle_id, reason, status, admin_remarks) VALUES (#inc_21, #bk_12, #veh_15, #veh_22, 'Collision replacement', 'approved', 'Replacement vehicle staged')
    DB -->> Admin: Replacement Request #rep_08 Created

    Notif ->> Customer: "Replacement vehicle (Toyota Corolla #CAR-8821) allocated. Please review and accept."

    %% Customer Acceptance Flow
    Customer ->> UI: Navigates to Incident Details & Clicks "Accept Replacement Vehicle"
    UI ->> Ctrl: POST replacementAcceptPage(incident_id=21, replacement_id=8)
    Ctrl ->> ModelPortal: acceptReplacement(customerUserId, incidentId=21, replacementRequestId=8)
    
    ModelPortal ->> DB: BEGIN TRANSACTION
    ModelPortal ->> DB: SELECT * FROM replacement_requests WHERE id = 8 FOR UPDATE
    ModelPortal ->> DB: UPDATE replacement_requests SET status = 'accepted' WHERE id = 8
    ModelPortal ->> DB: UPDATE bookings SET vehicle_id = #veh_22 WHERE id = bookingId
    ModelPortal ->> DB: UPDATE vehicles SET status = 'maintenance' WHERE id = #veh_15
    ModelPortal ->> DB: UPDATE vehicles SET status = 'rented' WHERE id = #veh_22
    ModelPortal ->> DB: COMMIT TRANSACTION

    ModelPortal ->> Notif: notifyReplacementCompleted(bookingId, customerUserId, ownerUserId)
    Notif -->> Customer: "Replacement vehicle accepted! Updated rental details active."
    Notif -->> Owner: "Vehicle #15 moved to maintenance. Replacement #22 in service."
    Ctrl -->> UI: Refresh Details with Active Replacement Badge
```

## Fault-Tolerant File & Transaction Isolation
1. **Compensating Upload Deletion**: If the database transaction fails during incident creation, uploaded files are cleanly deleted from the filesystem (`$this->deletePoliceReport()`, `$this->deleteIncidentPhoto()`).
2. **Pessimistic Locking**: `CustomerPortal::acceptReplacement` executes with `FOR UPDATE` locks on both the incident, booking, and replacement request rows to prevent concurrent vehicle race conditions.
3. **Audit Trail**: Every state transition between initial reporting, police documentation, admin provisioning, and customer sign-off is indelibly timestamped.
