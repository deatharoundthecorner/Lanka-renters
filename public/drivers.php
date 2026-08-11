<?php
// drivers.php - Lanka Renters Drivers Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Drivers";
$districts = getSriLankanDistricts();
$defaultView = isset($_GET['view']) && $_GET['view'] === 'registered' ? 'registered' : 'pending';
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

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <button type="button" class="tab-item <?php echo $defaultView === 'pending' ? 'active' : ''; ?>" id="tabPending" onclick="switchTab('pending')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (2)</span>
                </button>
                <button type="button" class="tab-item <?php echo $defaultView === 'registered' ? 'active' : ''; ?>" id="tabRegistered" onclick="switchTab('registered')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Registered Drivers (3)</span>
                </button>
            </div>

            <!-- SECTION 1: PENDING DRIVER APPROVALS -->
            <div class="card" id="sectionPending" style="<?php echo $defaultView === 'registered' ? 'display: none;' : 'display: block;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Pending Driver Approvals</h3>
                    <span class="badge badge-pending">2 Pending</span>
                </div>

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
                                        <button class="btn btn-doc" onclick="openDocModal('Kamal Silva - Driving Permit', 'Commercial Driving License')">View License</button>
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
                                        <button class="btn btn-doc" onclick="openDocModal('Nuwan Bandara - Driving Permit', 'Commercial Driving License')">View License</button>
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
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: REGISTERED DRIVERS -->
            <div class="card" id="sectionRegistered" style="<?php echo $defaultView === 'registered' ? 'display: block;' : 'display: none;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Registered Drivers</h3>
                    <span class="badge badge-approved">3 Approved</span>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label>Search Driver</label>
                        <input type="text" class="form-control" placeholder="Search by name, ID or owner...">
                    </div>
                    <div class="filter-group">
                        <label>District</label>
                        <select class="form-control">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button class="btn btn-primary">Search</button>
                        <button class="btn btn-secondary">Reset</button>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-003</span></td>
                                <td><div class="cell-primary-text">Roshan Ranasinghe</div></td>
                                <td>076 555 4433</td>
                                <td>Gampaha</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Roshan Ranasinghe - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Roshan Ranasinghe - Driving Permit', 'Commercial License')">View License</button>
                                    </div>
                                </td>
                                <td>Dhanushka Ratnayake</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Roshan Ranasinghe Details', 'Driver Profile Voucher')">View</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Roshan Ranasinghe', 'DRV-003')">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-004</span></td>
                                <td><div class="cell-primary-text">Asanka Gunawardena</div></td>
                                <td>075 111 2233</td>
                                <td>Galle</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Asanka Gunawardena - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Asanka Gunawardena - Driving Permit', 'Commercial License')">View License</button>
                                    </div>
                                </td>
                                <td>Mahesh Samarawickrama</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Asanka Gunawardena Details', 'Driver Profile Voucher')">View</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Asanka Gunawardena', 'DRV-004')">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">DRV-005</span></td>
                                <td><div class="cell-primary-text">Priyantha Cooray</div></td>
                                <td>077 444 5566</td>
                                <td>Kalutara</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Priyantha Cooray - Driver NIC', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Priyantha Cooray - Driving Permit', 'Commercial License')">View License</button>
                                    </div>
                                </td>
                                <td>Nimal Perera</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Priyantha Cooray Details', 'Driver Profile Voucher')">View</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Priyantha Cooray', 'DRV-005')">Suspend</button>
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
