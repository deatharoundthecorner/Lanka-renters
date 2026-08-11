<?php
// vehicle_owners.php - Lanka Renters Vehicle Owners Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Vehicle Owners";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();
$defaultView = isset($_GET['view']) && $_GET['view'] === 'registered' ? 'registered' : 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Owners - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Vehicle Owners</h1>
                <p class="page-subtitle">Review vehicle owner registrations and verify vehicle ownership documentation.</p>
            </div>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <button type="button" class="tab-item <?php echo $defaultView === 'pending' ? 'active' : ''; ?>" id="tabPending" onclick="switchTab('pending')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (2)</span>
                </button>
                <button type="button" class="tab-item <?php echo $defaultView === 'registered' ? 'active' : ''; ?>" id="tabRegistered" onclick="switchTab('registered')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Registered Owners (3)</span>
                </button>
            </div>

            <!-- SECTION 1: PENDING VEHICLE OWNERS -->
            <div class="card" id="sectionPending" style="<?php echo $defaultView === 'registered' ? 'display: none;' : 'display: block;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Pending Vehicle Owner Approvals</h3>
                    <span class="badge badge-pending">2 Pending</span>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Owner</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by name, ID or model...">
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
                        <label for="filterType">Vehicle Type</label>
                        <select id="filterType" class="form-control">
                            <option value="">All Vehicle Types</option>
                            <?php foreach ($vehicleTypes as $vt): ?>
                                <option value="<?php echo htmlspecialchars($vt); ?>"><?php echo htmlspecialchars($vt); ?></option>
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
                                <th>Owner ID</th>
                                <th>Name</th>
                                <th>District</th>
                                <th>Type</th>
                                <th>Model Name</th>
                                <th>Submitted Documents</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">OWN-001</span></td>
                                <td><div class="cell-primary-text">Sunil Wickramasinghe</div></td>
                                <td>Colombo</td>
                                <td>Car (4 Seater)</td>
                                <td>Toyota Prius 2021</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Sunil Wickramasinghe - Owner NIC', 'Owner NIC Document')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Sunil Wickramasinghe - Vehicle Registration', 'Vehicle Ownership Book')">View Vehicle Documents</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Sunil Wickramasinghe', 'OWN-001')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Sunil Wickramasinghe', 'OWN-001')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">OWN-002</span></td>
                                <td><div class="cell-primary-text">Dhanushka Ratnayake</div></td>
                                <td>Gampaha</td>
                                <td>SUV (6 Seater)</td>
                                <td>Honda Vezel 2020</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Dhanushka Ratnayake - Owner NIC', 'Owner NIC Document')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Dhanushka Ratnayake - Vehicle Registration', 'Vehicle Ownership Book')">View Vehicle Documents</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Dhanushka Ratnayake', 'OWN-002')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Dhanushka Ratnayake', 'OWN-002')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: REGISTERED VEHICLE OWNERS -->
            <div class="card" id="sectionRegistered" style="<?php echo $defaultView === 'registered' ? 'display: block;' : 'display: none;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Registered Vehicle Owners</h3>
                    <span class="badge badge-approved">3 Approved</span>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label>Search Owner</label>
                        <input type="text" class="form-control" placeholder="Search by name, ID or model...">
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
                    <div class="filter-group">
                        <label>Vehicle Type</label>
                        <select class="form-control">
                            <option value="">All Vehicle Types</option>
                            <?php foreach ($vehicleTypes as $vt): ?>
                                <option value="<?php echo htmlspecialchars($vt); ?>"><?php echo htmlspecialchars($vt); ?></option>
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
                                <th>Owner ID</th>
                                <th>Name</th>
                                <th>District</th>
                                <th>Type</th>
                                <th>Model Name</th>
                                <th>Submitted Documents</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">OWN-003</span></td>
                                <td><div class="cell-primary-text">Rohan Jayawardena</div></td>
                                <td>Kandy</td>
                                <td>Van (8 Seater)</td>
                                <td>Toyota KDH 2019</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Rohan Jayawardena - Owner NIC', 'Owner NIC Document')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Rohan Jayawardena - Vehicle Registration', 'Vehicle Ownership Book')">View Documents</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Rohan Jayawardena Details', 'Owner Profile Voucher')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="showToast('Edit Owner modal opened', 'info')">Edit</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Rohan Jayawardena', 'OWN-003')">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">OWN-004</span></td>
                                <td><div class="cell-primary-text">Mahesh Samarawickrama</div></td>
                                <td>Galle</td>
                                <td>Minivan (6 Seater)</td>
                                <td>Suzuki Wagon R 2022</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Mahesh Samarawickrama - Owner NIC', 'Owner NIC Document')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Mahesh Samarawickrama - Vehicle Registration', 'Vehicle Ownership Book')">View Documents</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Mahesh Samarawickrama Details', 'Owner Profile Voucher')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="showToast('Edit Owner modal opened', 'info')">Edit</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Mahesh Samarawickrama', 'OWN-004')">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">OWN-005</span></td>
                                <td><div class="cell-primary-text">Nimal Perera</div></td>
                                <td>Kalutara</td>
                                <td>Car (4 Seater)</td>
                                <td>Toyota Aqua 2018</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Nimal Perera - Owner NIC', 'Owner NIC Document')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Nimal Perera - Vehicle Registration', 'Vehicle Ownership Book')">View Documents</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openDocModal('Nimal Perera Details', 'Owner Profile Voucher')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="showToast('Edit Owner modal opened', 'info')">Edit</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Nimal Perera', 'OWN-005')">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 3 of 18 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
