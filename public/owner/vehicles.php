<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/VehicleController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$vehicleController = new VehicleController();

$successMessage = '';
$errorMessage = '';
$editVehicleData = null;

// Handle Form Submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_vehicle') {
        $result = $vehicleController->createVehicle($_POST, $_FILES);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['error'];
        }
    } elseif ($action === 'update_vehicle') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $result = $vehicleController->updateVehicle($vehicleId, $_POST);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['error'];
        }
    } elseif ($action === 'update_status') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        $result = $vehicleController->updateStatus($vehicleId, $newStatus, $csrfToken);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['error'];
        }
    } elseif ($action === 'deactivate_vehicle') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $csrfToken = $_POST['csrf_token'] ?? '';
        $result = $vehicleController->deactivateVehicle($vehicleId, $csrfToken);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['error'];
        }
    }
}

// Check if edit parameter is passed via GET
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $auth = AuthHelper::getCurrentUser();
    // Fetch vehicle for editing
    $vModel = new Vehicle();
    $ownerModel = new VehicleOwner();
    $owner = $ownerModel->findByUserId($auth['id']);
    if ($owner) {
        $editVehicleData = $vModel->getById($editId, $owner['id']);
    }
}

// Fetch Owner's Vehicles from Database
$dataResult = $vehicleController->getOwnerVehicles();
$vehicles = $dataResult['success'] ? $dataResult['vehicles'] : [];
$csrfToken = AuthHelper::getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - LankaRenters Owner</title>
    <!-- Link Owner Design CSS -->
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
    <style>
        /* Supplementary Component Styles */
        .alert-banner {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .empty-state-card {
            background-color: #ffffff;
            border: 2px dashed #cbd5e1;
            border-radius: 24px;
            padding: 48px 24px;
            text-align: center;
            margin-bottom: 32px;
        }
        .empty-state-card h3 {
            margin: 0 0 8px;
            font-size: 1.25rem;
            color: #0f172a;
        }
        .empty-state-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.98rem;
        }
        .badge-status-available { background-color: #ecfdf5; color: #166534; }
        .badge-status-rented { background-color: #eff6ff; color: #1d4ed8; }
        .badge-status-maintenance { background-color: #fffbeb; color: #b45309; }
        .badge-status-unavailable { background-color: #f1f5f9; color: #475569; }
        .badge-verify-pending { background-color: #fef3c7; color: #92400e; }
        .badge-verify-approved { background-color: #dcfce7; color: #15803d; }
        .badge-verify-rejected { background-color: #fee2e2; color: #b91c1c; }
        
        .vehicle-specs-list {
            list-style: none;
            padding: 0;
            margin: 8px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 12px;
            font-size: 0.88rem;
            color: #475569;
        }
        .vehicle-specs-list li {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .vehicle-price-tag {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 4px 0;
        }
        .vehicle-price-sub {
            font-size: 0.85rem;
            color: #64748b;
        }
        .status-select-form {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }
        .status-select-form select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 0.85rem;
            background-color: #f8fafc;
        }
        .docs-list {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.82rem;
        }
        .docs-list-title {
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .doc-item {
            display: inline-block;
            margin-right: 6px;
            margin-bottom: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            background-color: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
        }
        .doc-item:hover { background-color: #e2e8f0; }
        .edit-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 20px;
        }
        .edit-modal-content {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <!-- Include Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main Content Area -->
            <main class="main-content">
            <section class="vehicles-header">
                <div class="vehicles-title">
                    <h1>My Vehicles</h1>
                    <p>Manage your fleet, update daily rates, and track verification status.</p>
                </div>
                <div class="vehicles-action">
                    <a href="#add-vehicle-form" class="button button-primary">+ Add Vehicle</a>
                </div>
            </section>

            <!-- Success / Error Alert Banners -->
            <?php if (!empty($successMessage)): ?>
                <div class="alert-banner alert-success" role="alert">
                    <span>✅ <?php echo htmlspecialchars($successMessage); ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit;">✕</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert-banner alert-error" role="alert">
                    <span>⚠️ <?php echo htmlspecialchars($errorMessage); ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit;">✕</button>
                </div>
            <?php endif; ?>

            <!-- Vehicles Grid Section -->
            <section aria-label="Vehicle listing">
                <?php if (empty($vehicles)): ?>
                    <div class="empty-state-card">
                        <h3>No Vehicles Listed Yet</h3>
                        <p>You haven't added any vehicles to your account. Use the form below to list your first vehicle.</p>
                    </div>
                <?php else: ?>
                    <div class="vehicle-grid">
                        <?php foreach ($vehicles as $v): ?>
                            <article class="vehicle-card">
                                <div class="vehicle-image" style="background-color: #eef2ff; display: flex; align-items: center; justify-content: center; height: 160px;">
                                    <span style="font-size: 3.5rem;" aria-hidden="true">
                                        <?php 
                                            switch($v['vehicle_type']) {
                                                case 'van': echo '🚐'; break;
                                                case 'suv': echo '🚙'; break;
                                                case 'lorry': echo '🚛'; break;
                                                case 'motorbike': echo '🏍️'; break;
                                                default: echo '🚗'; break;
                                            }
                                        ?>
                                    </span>
                                </div>

                                <div class="vehicle-card-body">
                                    <div class="vehicle-card-meta">
                                        <span class="vehicle-title"><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?> (<?php echo (int)$v['year']; ?>)</span>
                                        <span class="vehicle-badge badge-verify-<?php echo htmlspecialchars($v['verification_status']); ?>">
                                            Verification: <?php echo ucfirst(htmlspecialchars($v['verification_status'])); ?>
                                        </span>
                                    </div>

                                    <div style="margin: 4px 0;">
                                        <span class="vehicle-badge badge-status-<?php echo htmlspecialchars($v['status']); ?>">
                                            Status: <?php echo ucfirst(htmlspecialchars($v['status'])); ?>
                                        </span>
                                        <span style="font-family: monospace; font-size: 0.88rem; font-weight: 700; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #334155;">
                                            <?php echo htmlspecialchars($v['license_plate']); ?>
                                        </span>
                                    </div>

                                    <ul class="vehicle-specs-list">
                                        <li>Type: <strong><?php echo ucfirst(htmlspecialchars($v['vehicle_type'])); ?></strong></li>
                                        <li>Gear: <strong><?php echo ucfirst(htmlspecialchars($v['transmission'])); ?></strong></li>
                                        <li>Fuel: <strong><?php echo ucfirst(htmlspecialchars($v['fuel_type'])); ?></strong></li>
                                        <li>Seats: <strong><?php echo (int)$v['seating_capacity']; ?> Seats</strong></li>
                                    </ul>

                                    <div class="vehicle-price-tag">
                                        LKR <?php echo number_format($v['price_per_day'], 2); ?> <span class="vehicle-price-sub">/ day (Self Drive)</span>
                                    </div>

                                    <?php if (!empty($v['price_with_driver_per_day'])): ?>
                                        <div class="vehicle-price-sub" style="font-weight: 600; color: #1e293b;">
                                            LKR <?php echo number_format($v['price_with_driver_per_day'], 2); ?> / day (With Driver)
                                        </div>
                                    <?php endif; ?>

                                    <!-- Attached Documents List -->
                                    <?php if (!empty($v['documents'])): ?>
                                        <div class="docs-list">
                                            <div class="docs-list-title">Attached Documents:</div>
                                            <?php foreach ($v['documents'] as $doc): ?>
                                                <a href="/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="doc-item" title="View Document">
                                                    📄 <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions Section -->
                                    <div class="vehicle-card-actions" style="margin-top: 12px; align-items: center; justify-content: space-between;">
                                        <a href="vehicles.php?edit_id=<?php echo (int)$v['id']; ?>" class="button button-secondary" style="text-decoration: none; padding: 6px 14px; font-size: 0.88rem;">Edit</a>

                                        <!-- Status Change Form -->
                                        <form method="POST" action="vehicles.php" class="status-select-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="vehicle_id" value="<?php echo (int)$v['id']; ?>">
                                            
                                            <select name="status" onchange="this.form.submit()" <?php echo $v['status'] === 'rented' ? 'disabled' : ''; ?>>
                                                <option value="available" <?php echo $v['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="maintenance" <?php echo $v['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                <option value="unavailable" <?php echo $v['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                            </select>
                                        </form>

                                        <!-- Safe Deactivate Form -->
                                        <?php if ($v['status'] !== 'unavailable'): ?>
                                            <form method="POST" action="vehicles.php" onsubmit="return confirm('Are you sure you want to deactivate this vehicle? It will be set to Unavailable.');" style="display:inline;">
                                                <input type="hidden" name="action" value="deactivate_vehicle">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="vehicle_id" value="<?php echo (int)$v['id']; ?>">
                                                <button type="submit" class="button button-outline" style="color: #991b1b; border-color: #fecaca; padding: 6px 10px; font-size: 0.85rem;">Deactivate</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Edit Vehicle Modal / Overlay (If edit_id GET parameter is active) -->
            <?php if (!empty($editVehicleData)): ?>
                <div class="edit-modal-overlay">
                    <div class="edit-modal-content">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                            <h2 style="margin: 0; font-size: 1.3rem; color: #0f172a;">Edit Vehicle: <?php echo htmlspecialchars($editVehicleData['make'] . ' ' . $editVehicleData['model']); ?></h2>
                            <a href="vehicles.php" style="text-decoration: none; font-size: 1.5rem; color: #64748b; font-weight: 700;">✕</a>
                        </div>

                        <form class="vehicle-form" method="POST" action="vehicles.php">
                            <input type="hidden" name="action" value="update_vehicle">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="vehicle_id" value="<?php echo (int)$editVehicleData['id']; ?>">

                            <div class="form-row">
                                <label class="form-field">
                                    <span>Make *</span>
                                    <input type="text" name="make" value="<?php echo htmlspecialchars($editVehicleData['make']); ?>" required />
                                </label>
                                <label class="form-field">
                                    <span>Model *</span>
                                    <input type="text" name="model" value="<?php echo htmlspecialchars($editVehicleData['model']); ?>" required />
                                </label>
                            </div>

                            <div class="form-row">
                                <label class="form-field">
                                    <span>Year *</span>
                                    <input type="number" name="year" min="1990" max="<?php echo (int)date('Y') + 1; ?>" value="<?php echo (int)$editVehicleData['year']; ?>" required />
                                </label>
                                <label class="form-field">
                                    <span>License Plate Number *</span>
                                    <input type="text" name="license_plate" value="<?php echo htmlspecialchars($editVehicleData['license_plate']); ?>" required />
                                </label>
                            </div>

                            <div class="form-row">
                                <label class="form-field">
                                    <span>Vehicle Type *</span>
                                    <select name="vehicle_type" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db;">
                                        <option value="car" <?php echo $editVehicleData['vehicle_type'] === 'car' ? 'selected' : ''; ?>>Car</option>
                                        <option value="van" <?php echo $editVehicleData['vehicle_type'] === 'van' ? 'selected' : ''; ?>>Van</option>
                                        <option value="suv" <?php echo $editVehicleData['vehicle_type'] === 'suv' ? 'selected' : ''; ?>>SUV</option>
                                        <option value="lorry" <?php echo $editVehicleData['vehicle_type'] === 'lorry' ? 'selected' : ''; ?>>Lorry</option>
                                        <option value="motorbike" <?php echo $editVehicleData['vehicle_type'] === 'motorbike' ? 'selected' : ''; ?>>Motorbike</option>
                                    </select>
                                </label>
                                <label class="form-field">
                                    <span>Transmission *</span>
                                    <select name="transmission" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db;">
                                        <option value="automatic" <?php echo $editVehicleData['transmission'] === 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                                        <option value="manual" <?php echo $editVehicleData['transmission'] === 'manual' ? 'selected' : ''; ?>>Manual</option>
                                    </select>
                                </label>
                            </div>

                            <div class="form-row">
                                <label class="form-field">
                                    <span>Fuel Type *</span>
                                    <select name="fuel_type" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db;">
                                        <option value="petrol" <?php echo $editVehicleData['fuel_type'] === 'petrol' ? 'selected' : ''; ?>>Petrol</option>
                                        <option value="diesel" <?php echo $editVehicleData['fuel_type'] === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                                        <option value="hybrid" <?php echo $editVehicleData['fuel_type'] === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                        <option value="electric" <?php echo $editVehicleData['fuel_type'] === 'electric' ? 'selected' : ''; ?>>Electric</option>
                                    </select>
                                </label>
                                <label class="form-field">
                                    <span>Seating Capacity *</span>
                                    <input type="number" name="seating_capacity" min="1" value="<?php echo (int)$editVehicleData['seating_capacity']; ?>" required />
                                </label>
                            </div>

                            <div class="form-row">
                                <label class="form-field">
                                    <span>Daily Price (Self Drive LKR) *</span>
                                    <input type="number" step="0.01" name="price_per_day" value="<?php echo htmlspecialchars($editVehicleData['price_per_day']); ?>" required />
                                </label>
                                <label class="form-field">
                                    <span>Daily Price (With Driver LKR)</span>
                                    <input type="number" step="0.01" name="price_with_driver_per_day" value="<?php echo htmlspecialchars($editVehicleData['price_with_driver_per_day'] ?? ''); ?>" placeholder="Optional" />
                                </label>
                            </div>

                            <div class="form-actions" style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
                                <a href="vehicles.php" class="button button-outline">Cancel</a>
                                <button type="submit" class="button button-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add Vehicle Form Section -->
            <section class="vehicle-form-section" id="add-vehicle-form">
                <div class="form-card">
                    <div class="form-card-header">
                        <h2>Add a New Vehicle</h2>
                        <p>Fill out the database fields and attach required verification documents for admin review.</p>
                    </div>

                    <form class="vehicle-form" method="POST" action="vehicles.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_vehicle">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-row">
                            <label class="form-field">
                                <span>Make *</span>
                                <input type="text" name="make" placeholder="e.g. Toyota" required />
                            </label>
                            <label class="form-field">
                                <span>Model *</span>
                                <input type="text" name="model" placeholder="e.g. Aqua" required />
                            </label>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Manufacturing Year *</span>
                                <input type="number" name="year" min="1990" max="<?php echo (int)date('Y') + 1; ?>" placeholder="e.g. 2021" required />
                            </label>
                            <label class="form-field">
                                <span>License Plate Number *</span>
                                <input type="text" name="license_plate" placeholder="e.g. CAB-1234" required />
                            </label>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Vehicle Type *</span>
                                <select name="vehicle_type" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db; background-color:#f8fafc;">
                                    <option value="car">Car</option>
                                    <option value="van">Van</option>
                                    <option value="suv">SUV</option>
                                    <option value="lorry">Lorry</option>
                                    <option value="motorbike">Motorbike</option>
                                </select>
                            </label>
                            <label class="form-field">
                                <span>Transmission *</span>
                                <select name="transmission" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db; background-color:#f8fafc;">
                                    <option value="automatic">Automatic</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </label>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Fuel Type *</span>
                                <select name="fuel_type" required style="width:100%; padding:12px; border-radius:14px; border:1px solid #d1d5db; background-color:#f8fafc;">
                                    <option value="petrol">Petrol</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="electric">Electric</option>
                                </select>
                            </label>
                            <label class="form-field">
                                <span>Seating Capacity *</span>
                                <input type="number" name="seating_capacity" min="1" placeholder="e.g. 5" required />
                            </label>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Price Per Day (Self Drive LKR) *</span>
                                <input type="number" step="0.01" name="price_per_day" placeholder="e.g. 8500.00" required />
                            </label>
                            <label class="form-field">
                                <span>Price Per Day (With Driver LKR)</span>
                                <input type="number" step="0.01" name="price_with_driver_per_day" placeholder="Optional (e.g. 12500.00)" />
                            </label>
                        </div>

                        <div style="margin-top: 16px;">
                            <h4 style="margin: 0 0 12px; font-size: 1rem; color: #0f172a;">Verification Documents (PDF, PNG, JPG - Max 5MB)</h4>
                            <div class="document-grid">
                                <label class="upload-zone">
                                    <span>Registration Certificate</span>
                                    <input type="file" name="document_registration" accept=".pdf,.png,.jpg,.jpeg" onchange="this.parentElement.style.borderColor='#2563eb';" />
                                </label>
                                <label class="upload-zone">
                                    <span>Insurance Policy</span>
                                    <input type="file" name="document_insurance" accept=".pdf,.png,.jpg,.jpeg" onchange="this.parentElement.style.borderColor='#2563eb';" />
                                </label>
                                <label class="upload-zone">
                                    <span>Emission Test Report</span>
                                    <input type="file" name="document_emission" accept=".pdf,.png,.jpg,.jpeg" onchange="this.parentElement.style.borderColor='#2563eb';" />
                                </label>
                                <label class="upload-zone">
                                    <span>Fitness Certificate</span>
                                    <input type="file" name="document_fitness" accept=".pdf,.png,.jpg,.jpeg" onchange="this.parentElement.style.borderColor='#2563eb';" />
                                </label>
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top: 24px;">
                            <button type="submit" class="button button-primary">Submit Vehicle for Verification</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</div>

</body>
</html>