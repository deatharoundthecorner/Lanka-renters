# Driver Approval Workflow Documentation

This document explains the document replacement versioning and profile change verification workflows.

---

## 1. Profile/Account Modifications Workflow

When a Driver requests a modification to their Phone, Address, Emergency Contact, Name, or Email:
1. **Pending Capture**: The controller intercepts the POST submission and creates a pending record in `profile_change_requests` or `account_change_requests`.
2. **Attribution**: The active values remain unmodified in `users` and `drivers` tables.
3. **Queue visibility**: The Driver sees the request status as "Pending Admin Approval" with the requested values.
4. **Admin Decision**:
   - **Approved**: The Admin commits the update to `users` and `drivers`, updating the active profile, and marks the request as `approved`.
   - **Rejected**: The Admin saves a rejection reason, keeping the active profile unchanged.

---

## 2. Document Replacements Workflow

To ensure drivers never lose access to approved credentials during replacement uploads:
1. **Versioning**: When a Driver uploads a replacement document (NIC, License, Police Report):
   - A new record is created in `driver_documents` with `status = 'pending'`, `version = current_version + 1`, and `is_current = FALSE`.
   - The active approved document remains as `status = 'approved'` and `is_current = TRUE`.
2. **Review Action**:
   - **Approved**:
     - The old version is set to `status = 'superseded'` and `is_current = FALSE`.
     - The new version is set to `status = 'approved'` and `is_current = TRUE`.
   - **Rejected**:
     - The new version is set to `status = 'rejected'` and `is_current = FALSE`.
     - The old approved version remains `approved` and `is_current = TRUE`.
