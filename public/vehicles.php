<?php
// vehicles.php - Lanka Renters Vehicles Management Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Vehicles";
$districts = getSriLankanDistricts();
$vehicleTypes = getVehicleTypes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Vehicles</h1>
                <p class="page-subtitle">Inspect registered vehicle fleets and review revenue license/insurance books.</p>
            </div>

            <div class="card">
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Vehicle</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search vehicle ID, model or owner...">
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
                                <th>Vehicle ID</th>
                                <th>Vehicle Model</th>
                                <th>Type</th>
                                <th>District</th>
                                <th>Submitted Documents</th>
                                <th>Owner Name</th>
                                <th>Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">VEH-001</span></td>
                                <td><div class="cell-primary-text">Toyota Prius 2021</div></td>
                                <td>Car (4 Seater)</td>
                                <td>Colombo</td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Toyota Prius 2021 - Revenue License & Insurance', 'Vehicle Revenue License')">View Vehicle Documents</button>
                                </td>
                                <td>Sunil Wickramasinghe</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Toyota Prius 2021', 'VEH-001')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Toyota Prius 2021', 'VEH-001')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">VEH-002</span></td>
                                <td><div class="cell-primary-text">Honda Vezel 2020</div></td>
                                <td>SUV (6 Seater)</td>
                                <td>Gampaha</td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Honda Vezel 2020 - Revenue License & Insurance', 'Vehicle Revenue License')">View Vehicle Documents</button>
                                </td>
                                <td>Dhanushka Ratnayake</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Honda Vezel 2020', 'VEH-002')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Honda Vezel 2020', 'VEH-002')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">VEH-003</span></td>
                                <td><div class="cell-primary-text">Toyota KDH Super GL</div></td>
                                <td>Van (8 Seater)</td>
                                <td>Kandy</td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Toyota KDH Super GL - Revenue License & Insurance', 'Vehicle Revenue License')">View Vehicle Documents</button>
                                </td>
                                <td>Rohan Jayawardena</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Toyota KDH Super GL', 'VEH-003')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Toyota KDH Super GL', 'VEH-003')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="cell-secondary-text">VEH-004</span></td>
                                <td><div class="cell-primary-text">Suzuki Wagon R FX</div></td>
                                <td>Minivan (6 Seater)</td>
                                <td>Galle</td>
                                <td>
                                    <button class="btn btn-doc" onclick="openDocModal('Suzuki Wagon R FX - Revenue License & Insurance', 'Vehicle Revenue License')">View Vehicle Documents</button>
                                </td>
                                <td>Mahesh Samarawickrama</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-approve btn-sm" onclick="triggerApprove('Suzuki Wagon R FX', 'VEH-004')">Approve</button>
                                        <button class="btn btn-reject btn-sm" onclick="triggerReject('Suzuki Wagon R FX', 'VEH-004')">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 4 of 24 entries</span>
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
