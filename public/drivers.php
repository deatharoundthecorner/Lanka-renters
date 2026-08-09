<?php
// drivers.php - Lanka Renters Drivers Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Drivers";
$districts = getSriLankanDistricts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Drivers</h1>
                <p class="page-subtitle">Review registered driver applications and verify driver license documentation.</p>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Driver</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by driver name, ID or owner...">
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
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button class="btn btn-primary" onclick="initTableFilters()">Search</button>
                        <button class="btn btn-secondary" id="filterResetBtn">Reset</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Driver ID</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>District</th>
                                <th>Submitted Documents</th>
                                <th>Owner Name</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-001</span></td>
                                <td><div class="cell-primary-text">Kamal Silva</div></td>
                                <td>077 888 1234</td>
                                <td>Colombo</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Kamal Silva - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Kamal Silva - Driving Permit', 'Commercial Driving License')">View Vehicle Documents</button>
                                    </div>
                                </td>
                                <td>Sunil Wickramasinghe</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Kamal Silva', 'DRV-001')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Kamal Silva', 'DRV-001')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-002</span></td>
                                <td><div class="cell-primary-text">Nuwan Bandara</div></td>
                                <td>071 999 4321</td>
                                <td>Kandy</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Nuwan Bandara - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Nuwan Bandara - Driving Permit', 'Commercial Driving License')">View Vehicle Documents</button>
                                    </div>
                                </td>
                                <td>Rohan Jayawardena</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Nuwan Bandara', 'DRV-002')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Nuwan Bandara', 'DRV-002')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-003</span></td>
                                <td><div class="cell-primary-text">Roshan Ranasinghe</div></td>
                                <td>076 555 4433</td>
                                <td>Gampaha</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Roshan Ranasinghe - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Roshan Ranasinghe - Driving Permit', 'Commercial Driving License')">View Vehicle Documents</button>
                                    </div>
                                </td>
                                <td>Dhanushka Ratnayake</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Roshan Ranasinghe', 'DRV-003')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Roshan Ranasinghe', 'DRV-003')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 3 of 12 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
