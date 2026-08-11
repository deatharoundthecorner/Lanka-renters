<?php
// incidents.php - Lanka Renters Incident Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Incident Management";
$districts = getSriLankanDistricts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Management - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Incident Management</h1>
                <p class="page-subtitle">Track accidents, vehicle damage reports, driver issues, and emergency replacements.</p>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <div class="filter-group">
                    <label for="filterSearch">Search Incident</label>
                    <input type="text" id="filterSearch" class="form-control" placeholder="Search incident ID, customer or owner...">
                </div>
                <div class="filter-group">
                    <label for="filterDistrict">District</label>
                    <select id="filterDistrict" class="form-control">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterIncidentType">Incident Type</label>
                    <select id="filterIncidentType" class="form-control">
                        <option value="">All Incident Types</option>
                        <option value="Accident">Accident</option>
                        <option value="Vehicle Damage">Vehicle Damage</option>
                        <option value="Driver Issue">Driver Issue</option>
                        <option value="Customer Issue">Customer Issue</option>
                        <option value="Late Return">Late Return</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px; align-self: flex-end;">
                    <button class="btn btn-primary" onclick="initTableFilters()">Search</button>
                    <button class="btn btn-secondary" id="filterResetBtn">Reset</button>
                </div>
            </div>

            <!-- Incidents Cards Layout -->
            <div class="incident-grid">
                <!-- Incident Card 1 -->
                <div class="incident-card">
                    <div class="incident-card-header">
                        <div class="incident-id-badge">
                            <span class="incident-id">INC-001</span>
                            <h3 class="incident-type-title">Accident</h3>
                        </div>
                        <span class="badge badge-rejected">Action Required</span>
                    </div>

                    <div class="incident-subline">
                        Customer ID = CUS-102 | Vehicle Name = Toyota Prius | Booking ID = BKG-203 | Owner = Nimal Perera | Reported = 08 Aug 2026, 10:30 AM
                    </div>

                    <div class="incident-actions">
                        <button class="btn btn-doc" onclick="openDocModal('INC-001 Evidence Image', 'Accident Scene Photo')">View Evidence</button>
                        <button class="btn btn-secondary" onclick="alert('Contacting Owner Nimal Perera at 071 777 6666')">Contact Vehicle Owner</button>
                        <button class="btn btn-secondary" onclick="openDocModal('INC-001 Police Report', 'Official Traffic Police Statement')">View Additional Details</button>
                        <button class="btn btn-primary" onclick="alert('Redirecting to Assign Replacement Vehicle interface for BKG-203')">Assign Replacement Vehicle</button>
                        <button class="btn btn-reject" onclick="triggerReject('Incident INC-001', 'INC-001')">Reject</button>
                    </div>
                </div>

                <!-- Incident Card 2 -->
                <div class="incident-card">
                    <div class="incident-card-header">
                        <div class="incident-id-badge">
                            <span class="incident-id">INC-002</span>
                            <h3 class="incident-type-title">Vehicle Damage</h3>
                        </div>
                        <span class="badge badge-pending">Under Review</span>
                    </div>

                    <div class="incident-subline">
                        Customer ID = CUS-005 | Vehicle Name = Honda Vezel | Booking ID = BKG-102 | Owner = Dhanushka Ratnayake | Reported = 07 Aug 2026, 04:15 PM
                    </div>

                    <div class="incident-actions">
                        <button class="btn btn-doc" onclick="openDocModal('INC-002 Scratch Photo', 'Bumper Damage Image')">View Evidence</button>
                        <button class="btn btn-secondary" onclick="alert('Contacting Owner Dhanushka Ratnayake at 077 333 4444')">Contact Vehicle Owner</button>
                        <button class="btn btn-secondary" onclick="openDocModal('INC-002 Damage Appraisal', 'Garage Repair Quote')">View Additional Details</button>
                        <button class="btn btn-primary" onclick="alert('Assigning replacement driver or vehicle for BKG-102')">Assign Replacement Vehicle</button>
                        <button class="btn btn-reject" onclick="triggerReject('Incident INC-002', 'INC-002')">Reject</button>
                    </div>
                </div>

                <!-- Incident Card 3 -->
                <div class="incident-card">
                    <div class="incident-card-header">
                        <div class="incident-id-badge">
                            <span class="incident-id">INC-003</span>
                            <h3 class="incident-type-title">Driver Issue</h3>
                        </div>
                        <span class="badge badge-active">Assigned</span>
                    </div>

                    <div class="incident-subline">
                        Customer ID = CUS-006 | Vehicle Name = Toyota KDH Super GL | Booking ID = BKG-103 | Owner = Rohan Jayawardena | Reported = 06 Aug 2026, 08:45 AM
                    </div>

                    <div class="incident-actions">
                        <button class="btn btn-doc" onclick="openDocModal('INC-003 Communication Log', 'Customer Complaint Slip')">View Evidence</button>
                        <button class="btn btn-secondary" onclick="alert('Contacting Owner Rohan Jayawardena at 076 222 1111')">Contact Vehicle Owner</button>
                        <button class="btn btn-secondary" onclick="openDocModal('INC-003 Driver History', 'Driver Behavior Log')">View Additional Details</button>
                        <button class="btn btn-primary" onclick="alert('Assigning Replacement Driver for BKG-103')">Assign Replacement Vehicle</button>
                        <button class="btn btn-reject" onclick="triggerReject('Incident INC-003', 'INC-003')">Reject</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
