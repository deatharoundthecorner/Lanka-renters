# Lanka Renters - Interim Test Checklist

This manual checklist provides structured test cases to verify the application functionality before the university evaluation:

| Test ID | Category | Test Case | Steps | Expected Result | Status | Severity |
| :--- | :--- | :--- | :--- | :--- | :---: | :---: |
| **T-01** | Startup | Database Port Check | Check if port 3308 is open and XAMPP MySQL is active | Connection succeeds without warnings | PASS | CRITICAL |
| **T-02** | Auth | Login Validation | Login as a driver with correct credentials | Successfully redirects to `driver/dashboard.php` | PASS | CRITICAL |
| **T-03** | Auth | Unauthorized Redirect | Try loading `public/driver/dashboard.php` without signing in | Redirects immediately to `login.php` | PASS | HIGH |
| **T-04** | Driver | Leave Request Form | Submit a leave request with empty dates | HTML5 validator triggers, form blocks submit | PASS | MEDIUM |
| **T-05** | Driver | Leave Deletion | Click Delete on a pending leave request | Asks confirmation, removes request, shows success message | PASS | HIGH |
| **T-06** | Driver | Documents Upload | Upload a new NIC document | Stores file, changes verification state to pending | PASS | HIGH |
| **T-07** | Driver | Session Safety | Click Logout button in sidebar | Destroys session, redirects back to login page | PASS | HIGH |
| **T-08** | Admin | Approvals View | Log in as admin, check approvals list | Lists pending driver change requests and documents | PASS | HIGH |
| **T-09** | Owner | Drivers Section | Log in as owner, inspect driver section | Lists all drivers connected to the owner | PASS | MEDIUM |
