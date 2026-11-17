# Lanka Renters — Sequence Diagram: Customer KYC Verification Flow

## Overview
This sequence diagram details the **Customer Identity & Driving License Verification (KYC)** workflow in **Lanka Renters**, showcasing customer document upload, secure storage, admin review, rejection/resubmission feedback loops, and automated unlocking of self-drive reservations.

```mermaid
sequenceDiagram
    autonumber
    actor Customer as 👤 Customer
    participant UI as 🖥️ Customer Portal UI
    participant Ctrl as ⚙️ CustomerVerificationController
    participant ModelCust as 📦 Customer Model
    participant UploadFS as 📁 Secure Document Storage (/uploads/customer_docs/)
    participant DB as 🗄️ MariaDB Database
    participant Notif as 🔔 Notification System
    actor Admin as 🛡️ Administrator

    Customer ->> UI: Navigates to /customer/verification/
    UI -->> Customer: Render Verification Form (NIC / Passport & Driving License Uploads)

    Customer ->> UI: Enters NIC Number, License Number, Selects Front & Back Document Scans
    UI ->> Ctrl: POST /customer/verification/submit.php [multipart/form-data]
    
    Ctrl ->> Ctrl: Validate CSRF & File Constraints (MIME: image/jpeg, image/png, application/pdf; Max Size: 5MB)
    Ctrl ->> UploadFS: Save NIC Scan (/uploads/customer_docs/nic_sec772.jpg)
    Ctrl ->> UploadFS: Save License Scan (/uploads/customer_docs/lic_sec914.jpg)

    Ctrl ->> DB: BEGIN TRANSACTION
    Ctrl ->> DB: INSERT INTO customer_documents (customer_id, document_type, file_path, status) VALUES (cust_id, 'nic', 'uploads/customer_docs/nic_sec772.jpg', 'pending')
    Ctrl ->> DB: INSERT INTO customer_documents (customer_id, document_type, file_path, status) VALUES (cust_id, 'license', 'uploads/customer_docs/lic_sec914.jpg', 'pending')
    Ctrl ->> DB: UPDATE customers SET nic_number = :nic, driving_license_number = :lic, identity_verification_status = 'pending', driving_license_status = 'pending', verification_status = 'pending' WHERE id = cust_id
    Ctrl ->> DB: COMMIT TRANSACTION

    Ctrl ->> Notif: alertAdminsNewKYCSubmission(cust_id)
    Notif ->> DB: INSERT INTO notifications (...)
    Notif -->> Admin: "New Customer KYC submission awaiting verification: Customer #cust_id"

    Ctrl -->> UI: Flash "Documents uploaded successfully. Verification in progress."

    %% Admin Review Process
    Admin ->> UI: Accesses /admin/verification/customer-details.php?id={cust_id}
    UI ->> DB: Fetch Customer KYC records & inspect linked document files
    DB -->> UI: Return NIC file, License file, submission history
    UI -->> Admin: Render Document Viewer with Approval / Rejection Controls

    alt Scenario A: Document Quality Issue (e.g. Blurry License Back)
        Admin ->> UI: Selects "Resubmission Required" & Enters Reason ("License back scan unreadable")
        UI ->> Ctrl: POST /admin/verification/decision.php (decision='resubmission_required', reason=...)
        Ctrl ->> DB: UPDATE customers SET driving_license_status = 'resubmission_required', license_rejection_reason = 'License back scan unreadable' WHERE id = cust_id
        Ctrl ->> Notif: dispatchResubmissionNotice(cust_id, "License back scan unreadable")
        Notif -->> Customer: Alert: "Driving license verification requires resubmission: License back scan unreadable."
    else Scenario B: Clean Documents Approved
        Admin ->> UI: Clicks "Approve Customer Identity & License"
        UI ->> Ctrl: POST /admin/verification/decision.php (decision='approved')
        Ctrl ->> DB: BEGIN TRANSACTION
        Ctrl ->> DB: UPDATE customer_documents SET status = 'approved' WHERE customer_id = cust_id
        Ctrl ->> DB: UPDATE customers SET identity_verification_status = 'approved', driving_license_status = 'approved', verification_status = 'approved', identity_verified_at = NOW(), license_verified_at = NOW() WHERE id = cust_id
        Ctrl ->> DB: INSERT INTO admin_reviews (admin_id, target_type, target_id, decision, remarks) VALUES (admin_id, 'customer', cust_id, 'approved', 'All credentials verified against national database')
        Ctrl ->> DB: COMMIT TRANSACTION
        
        Ctrl ->> Notif: dispatchApprovalNotice(cust_id)
        Notif -->> Customer: Alert: "Congratulations! Your identity & driving license are verified. You can now book self-drive rentals."
    end
```

## Security & Verification Invariants
1. **Self-Drive Gating**: A customer cannot submit a booking without driver (`driver_id IS NULL`) unless `driving_license_status = 'approved'`.
2. **Document Partitioning**: Uploaded identity documents are stored in dedicated directories inaccessible to unauthenticated external HTTP access except through authorized application proxy scripts.
3. **Audit Trail**: Every verification decision records the inspecting administrator's ID and timestamp in `admin_reviews`.
