<?php
// users.php - Lanka Renters Customer Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Users";
$districts = getSriLankanDistricts();
$defaultView = isset($_GET['view']) && $_GET['view'] === 'registered' ? 'registered' : 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Users</h1>
                <p class="page-subtitle">Manage registered customers and review submitted documents.</p>
            </div>

            <!-- Segmented Tab Bar -->
            <div class="nav-tabs">
                <button type="button" class="tab-item <?php echo $defaultView === 'pending' ? 'active' : ''; ?>" id="tabPending" onclick="switchTab('pending')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Pending Approval (3)</span>
                </button>
                <button type="button" class="tab-item <?php echo $defaultView === 'registered' ? 'active' : ''; ?>" id="tabRegistered" onclick="switchTab('registered')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Registered Users (5)</span>
                </button>
            </div>

            <!-- SECTION 1: PENDING CUSTOMER REGISTRATIONS -->
            <div class="card" id="sectionPending" style="<?php echo $defaultView === 'registered' ? 'display: none;' : 'display: block;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Pending Customer Registrations</h3>
                    <span class="badge badge-pending">3 Pending</span>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Customer</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search by name, ID or email...">
                    </div>
                    <div class="filter-group">
                        <label for="filterDistrict">District (All 25 Districts)</label>
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
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Contact Details</th>
                                <th>Submitted Documents</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-001</span></td>
                                <td><div class="cell-primary-text">Kasun Perera</div></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">kasun.perera@gmail.com</span>
                                        <span class="cell-secondary-text">071 234 5678</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Kasun Perera - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Kasun Perera - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Kasun Perera', 'CUS-001')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Kasun Perera', 'CUS-001')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-002</span></td>
                                <td><div class="cell-primary-text">Nimal Silva</div></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">nimal.silva@yahoo.com</span>
                                        <span class="cell-secondary-text">077 456 7890</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Nimal Silva - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Nimal Silva - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Nimal Silva', 'CUS-002')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Nimal Silva', 'CUS-002')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-003</span></td>
                                <td><div class="cell-primary-text">Tharindu Fernando</div></td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">tharindu.f@hotmail.com</span>
                                        <span class="cell-secondary-text">076 321 4567</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Tharindu Fernando - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Tharindu Fernando - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Tharindu Fernando', 'CUS-003')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Tharindu Fernando', 'CUS-003')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: REGISTERED USERS -->
            <div class="card" id="sectionRegistered" style="<?php echo $defaultView === 'registered' ? 'display: block;' : 'display: none;'; ?>">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Registered Users</h3>
                    <span class="badge badge-approved">5 Approved</span>
                </div>

                <div class="filter-card">
                    <div class="filter-group">
                        <label>Search Users</label>
                        <input type="text" class="form-control" placeholder="Search by name, ID...">
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
                        <label>Search by Date</label>
                        <input type="date" class="form-control">
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
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Registered Date</th>
                                <th>Contact Details</th>
                                <th>Submitted Documents</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-004</span></td>
                                <td><div class="cell-primary-text">Chamara Jayasinghe</div></td>
                                <td>15 Jul 2026</td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">chamara.j@gmail.com</span>
                                        <span class="cell-secondary-text">075 654 3210</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Chamara Jayasinghe - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Chamara Jayasinghe - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Chamara Jayasinghe', 'CUS-004')">Suspend</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-005</span></td>
                                <td><div class="cell-primary-text">Sanduni Perera</div></td>
                                <td>18 Jul 2026</td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">sanduni.p@gmail.com</span>
                                        <span class="cell-secondary-text">071 987 6543</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Sanduni Perera - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Sanduni Perera - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Sanduni Perera', 'CUS-005')">Suspend</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">CUS-006</span></td>
                                <td><div class="cell-primary-text">Dilshan Kumara</div></td>
                                <td>20 Jul 2026</td>
                                <td>
                                    <div class="cell-stacked">
                                        <span class="cell-secondary-text">dilshan.k@outlook.com</span>
                                        <span class="cell-secondary-text">077 123 9876</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc" onclick="openDocModal('Dilshan Kumara - NIC Document', 'National Identity Card')">View NIC</button>
                                        <button class="btn btn-doc" onclick="openDocModal('Dilshan Kumara - Driving License', 'Driving License')">View License</button>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-reject btn-sm" onclick="triggerSuspend('Dilshan Kumara', 'CUS-006')">Suspend</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 3 of 5 entries</span>
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
