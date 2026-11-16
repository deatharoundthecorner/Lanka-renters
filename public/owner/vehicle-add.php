<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/VehicleController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$vehicleController = new VehicleController();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_vehicle') {
        $result = $vehicleController->createVehicle($_POST, $_FILES);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['error'];
        }
    }
}

$csrfToken = AuthHelper::getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle - LankaRenters Owner</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
    <link rel="stylesheet" href="../assets/css/lr-tokens.css">
    <link rel="stylesheet" href="../assets/css/lr-shell.css">
    <link rel="stylesheet" href="../assets/css/lr-components.css">
    <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
    <style>
        :root {
            --step-primary: #1e3a8a;
            --step-accent: #f59e0b;
            --step-bg: #f8fafc;
            --step-surface: #ffffff;
            --step-text: #0f172a;
            --step-muted: #64748b;
            --step-border: #e2e8f0;
            --step-radius: 12px;
        }

        .add-vehicle-container {
            max-width: 960px;
            margin: 0 auto;
        }

        /* Stepper Navigation */
        .wizard-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            padding: 16px 24px;
            background: var(--step-surface);
            border: 1px solid var(--step-border);
            border-radius: var(--step-radius);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        .wizard-step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            color: var(--step-muted);
            font-weight: 600;
            font-size: 13.5px;
            user-select: none;
            transition: all 0.2s ease;
        }

        .wizard-step-item.is-active {
            color: var(--step-primary);
        }

        .wizard-step-item.is-completed {
            color: #059669;
        }

        .wizard-step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            background: #f1f5f9;
            color: var(--step-muted);
            border: 2px solid #cbd5e1;
            transition: all 0.2s ease;
        }

        .wizard-step-item.is-active .wizard-step-circle {
            background: var(--step-primary);
            color: #ffffff;
            border-color: var(--step-primary);
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.12);
        }

        .wizard-step-item.is-completed .wizard-step-circle {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        .wizard-step-connector {
            flex: 1;
            height: 2px;
            background: var(--step-border);
            margin: 0 14px;
            border-radius: 2px;
            transition: background 0.2s ease;
        }

        .wizard-step-connector.is-active {
            background: var(--step-primary);
        }

        /* Step Panels */
        .wizard-panel {
            display: none;
            background: var(--step-surface);
            border: 1px solid var(--step-border);
            border-radius: var(--step-radius);
            padding: 32px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }

        .wizard-panel.is-active {
            display: block;
        }

        .wizard-panel-header {
            margin-bottom: 24px;
            border-bottom: 1px solid var(--step-border);
            padding-bottom: 16px;
        }

        .wizard-panel-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--step-text);
            margin: 0 0 4px;
        }

        .wizard-panel-header p {
            font-size: 0.88rem;
            color: var(--step-muted);
            margin: 0;
        }

        /* Form Grid */
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 640px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
            .wizard-stepper {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .wizard-step-connector {
                display: none;
            }
        }

        .field-label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--step-text);
        }

        .field-label .field-hint {
            font-weight: 400;
            font-size: 0.78rem;
            color: var(--step-muted);
        }

        .field-input, .field-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            font-size: 0.92rem;
            color: var(--step-text);
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .field-input:focus, .field-select:focus {
            outline: none;
            border-color: var(--step-primary);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        /* Document Upload Cards */
        .doc-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .doc-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .doc-card {
            border: 1px solid var(--step-border);
            border-radius: var(--step-radius);
            padding: 20px;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .doc-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.05);
        }

        .doc-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .doc-card-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--step-text);
        }

        .doc-badge-optional {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .doc-dropzone {
            display: block;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            background: var(--step-bg);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 12px;
        }

        .doc-dropzone:hover {
            border-color: var(--step-primary);
            background: #f0f4ff;
        }

        .doc-dropzone input[type="file"] {
            display: none;
        }

        .doc-file-status {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--step-muted);
            margin-top: 4px;
            word-break: break-all;
        }

        .doc-meta-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        /* Review Summary Card */
        .review-summary-card {
            background: #f8fafc;
            border: 1px solid var(--step-border);
            border-radius: var(--step-radius);
            padding: 24px;
            margin-bottom: 24px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--step-border);
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .review-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--step-text);
        }

        .review-plate-badge {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 640px) {
            .review-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .review-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .review-label {
            font-size: 0.78rem;
            color: var(--step-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .review-value {
            font-size: 0.95rem;
            color: var(--step-text);
            font-weight: 600;
        }

        .review-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 14px;
            font-size: 0.86rem;
            color: #1e3a8a;
            line-height: 1.45;
        }

        /* Action Buttons */
        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--step-border);
        }
    </style>
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <!-- Include Header -->
        <?php include 'includes/header.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="add-vehicle-container">
                <section class="vehicles-header welcome-container" style="margin-bottom: 20px;">
                    <div class="vehicles-title">
                        <h1>Add a New Vehicle</h1>
                        <p>Complete the vehicle specifications, upload verification documents, and submit for admin review.</p>
                    </div>
                    <div class="vehicles-action">
                        <a href="vehicles.php" class="button button-outline">&larr; Back to Fleet</a>
                    </div>
                </section>

                <!-- Success / Error Alert Banners -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert-banner alert-success" role="alert" style="margin-bottom: 20px;">
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                        <a href="vehicles.php" style="margin-left: 12px; font-weight: 700; color: inherit; text-decoration: underline;">View in Fleet &rarr;</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-banner alert-error" role="alert" style="margin-bottom: 20px;">
                        <span><?php echo htmlspecialchars($errorMessage); ?></span>
                        <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit; margin-left: auto;">✕</button>
                    </div>
                <?php endif; ?>

                <!-- Wizard Progress Stepper -->
                <nav class="wizard-stepper" aria-label="Add Vehicle Progress">
                    <div class="wizard-step-item is-active" id="step-nav-1" onclick="wizardNav(1)">
                        <span class="wizard-step-circle">1</span>
                        <span>1. Specifications</span>
                    </div>
                    <div class="wizard-step-connector" id="step-conn-1"></div>
                    <div class="wizard-step-item" id="step-nav-2" onclick="wizardNav(2)">
                        <span class="wizard-step-circle">2</span>
                        <span>2. Documents</span>
                    </div>
                    <div class="wizard-step-connector" id="step-conn-2"></div>
                    <div class="wizard-step-item" id="step-nav-3" onclick="wizardNav(3)">
                        <span class="wizard-step-circle">3</span>
                        <span>3. Review &amp; Submit</span>
                    </div>
                </nav>

                <!-- Unified Form Container -->
                <form id="owner-add-vehicle-form" method="POST" action="vehicle-add.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_vehicle">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <!-- STEP 1: Vehicle Information -->
                    <div class="wizard-panel is-active" id="panel-step-1">
                        <div class="wizard-panel-header">
                            <h2>Step 1: Vehicle Specifications &amp; Pricing</h2>
                            <p>Enter vehicle identification, transmission type, fuel category, and daily rental rates.</p>
                        </div>

                        <div class="form-grid-2">
                            <label class="field-label">
                                <span>Vehicle Make *</span>
                                <input type="text" name="make" id="inp-make" class="field-input" placeholder="e.g. Toyota" required />
                                <span class="field-hint">Manufacturer brand name</span>
                            </label>
                            <label class="field-label">
                                <span>Vehicle Model *</span>
                                <input type="text" name="model" id="inp-model" class="field-input" placeholder="e.g. Aqua" required />
                                <span class="field-hint">Model variant or trim</span>
                            </label>
                        </div>

                        <div class="form-grid-2">
                            <label class="field-label">
                                <span>Manufacturing Year *</span>
                                <input type="number" name="year" id="inp-year" class="field-input" min="1990" max="<?php echo (int)date('Y') + 1; ?>" placeholder="e.g. 2021" required />
                                <span class="field-hint">Year of manufacture (1990 - <?php echo (int)date('Y') + 1; ?>)</span>
                            </label>
                            <label class="field-label">
                                <span>License Plate Number *</span>
                                <input type="text" name="license_plate" id="inp-plate" class="field-input" placeholder="e.g. CAB-1234" required />
                                <span class="field-hint">Unique Sri Lankan registration number</span>
                            </label>
                        </div>

                        <div class="form-grid-2">
                            <label class="field-label">
                                <span>Vehicle Type *</span>
                                <select name="vehicle_type" id="inp-type" class="field-select" required>
                                    <option value="car">Car</option>
                                    <option value="van">Van</option>
                                    <option value="suv">SUV</option>
                                    <option value="lorry">Lorry</option>
                                    <option value="motorbike">Motorbike</option>
                                </select>
                            </label>
                            <label class="field-label">
                                <span>Transmission *</span>
                                <select name="transmission" id="inp-transmission" class="field-select" required>
                                    <option value="automatic">Automatic</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </label>
                        </div>

                        <div class="form-grid-2">
                            <label class="field-label">
                                <span>Fuel Type *</span>
                                <select name="fuel_type" id="inp-fuel" class="field-select" required>
                                    <option value="petrol">Petrol</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="electric">Electric</option>
                                </select>
                            </label>
                            <label class="field-label">
                                <span>Seating Capacity *</span>
                                <input type="number" name="seating_capacity" id="inp-seats" class="field-input" min="1" placeholder="e.g. 5" required />
                            </label>
                        </div>

                        <div class="form-grid-2">
                            <label class="field-label">
                                <span>Price Per Day (Self Drive LKR) *</span>
                                <input type="number" step="0.01" name="price_per_day" id="inp-price" class="field-input" placeholder="e.g. 8500.00" required />
                                <span class="field-hint">Standard daily rate without driver</span>
                            </label>
                            <label class="field-label">
                                <span>Price Per Day (With Driver LKR)</span>
                                <input type="number" step="0.01" name="price_with_driver_per_day" id="inp-price-driver" class="field-input" placeholder="Optional (e.g. 12500.00)" />
                                <span class="field-hint">Leave blank if self-drive only</span>
                            </label>
                        </div>

                        <div style="margin-top: 8px;">
                            <label class="field-label">
                                <span>Main Vehicle Image (.jpg, .png, .webp - Max 5MB)</span>
                                <input type="file" name="vehicle_image" id="inp-image" accept=".jpg,.jpeg,.png,.webp" class="field-input" onchange="previewMainImage(this)" />
                                <span class="field-hint">Front three-quarter view recommended for catalog listing</span>
                            </label>
                            <div id="image-preview-container" style="display:none; margin-top:10px;">
                                <img id="image-preview-img" src="" alt="Vehicle Preview" style="width:120px; height:80px; object-fit:cover; border-radius:8px; border:1px solid #cbd5e1;" />
                            </div>
                        </div>

                        <div class="wizard-actions">
                            <a href="vehicles.php" class="button button-outline">Cancel</a>
                            <button type="button" class="button button-primary" onclick="proceedToStep(2)">
                                Next: Required Documents &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Required Documents -->
                    <div class="wizard-panel" id="panel-step-2">
                        <div class="wizard-panel-header">
                            <h2>Step 2: Verification Documents</h2>
                            <p>Attach official vehicle documents (Registration CR Book, Insurance, Emission Test, Fitness Certificate) for admin approval.</p>
                        </div>

                        <div class="doc-cards-grid">
                            <!-- Registration / CR Book -->
                            <div class="doc-card">
                                <div class="doc-card-header">
                                    <span class="doc-card-title">Registration / CR Book</span>
                                    <span class="doc-badge-optional">PDF / Image</span>
                                </div>
                                <label class="doc-dropzone" for="doc-reg-file">
                                    <span>📁 Choose Registration Document</span>
                                    <input type="file" id="doc-reg-file" name="document_registration" accept=".pdf,.png,.jpg,.jpeg" onchange="updateDocFileName(this, 'status-reg')" />
                                    <div class="doc-file-status" id="status-reg">No file selected</div>
                                </label>
                                <div class="doc-meta-row">
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Document / CR No</span>
                                        <input type="text" name="document_registration_number" class="field-input" style="padding:6px 10px; font-size:0.85rem;" placeholder="Optional" />
                                    </label>
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Expiry Date</span>
                                        <input type="date" name="document_registration_expiry" class="field-input" style="padding:6px 10px; font-size:0.85rem;" />
                                    </label>
                                </div>
                            </div>

                            <!-- Insurance Policy -->
                            <div class="doc-card">
                                <div class="doc-card-header">
                                    <span class="doc-card-title">Insurance Policy</span>
                                    <span class="doc-badge-optional">PDF / Image</span>
                                </div>
                                <label class="doc-dropzone" for="doc-ins-file">
                                    <span>📁 Choose Insurance Policy</span>
                                    <input type="file" id="doc-ins-file" name="document_insurance" accept=".pdf,.png,.jpg,.jpeg" onchange="updateDocFileName(this, 'status-ins')" />
                                    <div class="doc-file-status" id="status-ins">No file selected</div>
                                </label>
                                <div class="doc-meta-row">
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Policy Number</span>
                                        <input type="text" name="document_insurance_number" class="field-input" style="padding:6px 10px; font-size:0.85rem;" placeholder="Optional" />
                                    </label>
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Expiry Date</span>
                                        <input type="date" name="document_insurance_expiry" class="field-input" style="padding:6px 10px; font-size:0.85rem;" />
                                    </label>
                                </div>
                            </div>

                            <!-- Emission Test Report -->
                            <div class="doc-card">
                                <div class="doc-card-header">
                                    <span class="doc-card-title">Emission Test Report</span>
                                    <span class="doc-badge-optional">PDF / Image</span>
                                </div>
                                <label class="doc-dropzone" for="doc-emi-file">
                                    <span>📁 Choose Emission Certificate</span>
                                    <input type="file" id="doc-emi-file" name="document_emission" accept=".pdf,.png,.jpg,.jpeg" onchange="updateDocFileName(this, 'status-emi')" />
                                    <div class="doc-file-status" id="status-emi">No file selected</div>
                                </label>
                                <div class="doc-meta-row">
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Report / Cert No</span>
                                        <input type="text" name="document_emission_number" class="field-input" style="padding:6px 10px; font-size:0.85rem;" placeholder="Optional" />
                                    </label>
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Expiry Date</span>
                                        <input type="date" name="document_emission_expiry" class="field-input" style="padding:6px 10px; font-size:0.85rem;" />
                                    </label>
                                </div>
                            </div>

                            <!-- Fitness Certificate -->
                            <div class="doc-card">
                                <div class="doc-card-header">
                                    <span class="doc-card-title">Fitness Certificate</span>
                                    <span class="doc-badge-optional">PDF / Image</span>
                                </div>
                                <label class="doc-dropzone" for="doc-fit-file">
                                    <span>📁 Choose Fitness Certificate</span>
                                    <input type="file" id="doc-fit-file" name="document_fitness" accept=".pdf,.png,.jpg,.jpeg" onchange="updateDocFileName(this, 'status-fit')" />
                                    <div class="doc-file-status" id="status-fit">No file selected</div>
                                </label>
                                <div class="doc-meta-row">
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Fitness Cert No</span>
                                        <input type="text" name="document_fitness_number" class="field-input" style="padding:6px 10px; font-size:0.85rem;" placeholder="Optional" />
                                    </label>
                                    <label class="field-label" style="font-size:0.8rem;">
                                        <span>Expiry Date</span>
                                        <input type="date" name="document_fitness_expiry" class="field-input" style="padding:6px 10px; font-size:0.85rem;" />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-outline" onclick="proceedToStep(1)">
                                &larr; Back to Specifications
                            </button>
                            <button type="button" class="button button-primary" onclick="proceedToStep(3)">
                                Next: Review &amp; Submit &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Review & Submit -->
                    <div class="wizard-panel" id="panel-step-3">
                        <div class="wizard-panel-header">
                            <h2>Step 3: Review &amp; Submit</h2>
                            <p>Verify all entered specifications and uploaded evidence before sending for administrator approval.</p>
                        </div>

                        <!-- Live Summary Preview Card -->
                        <div class="review-summary-card">
                            <div class="review-header">
                                <div>
                                    <span class="review-title" id="rev-title">Vehicle Name (Year)</span>
                                </div>
                                <span class="review-plate-badge" id="rev-plate">PLATE-NO</span>
                            </div>

                            <div class="review-grid">
                                <div class="review-item">
                                    <span class="review-label">Vehicle Type</span>
                                    <span class="review-value" id="rev-type">Car</span>
                                </div>
                                <div class="review-item">
                                    <span class="review-label">Transmission</span>
                                    <span class="review-value" id="rev-transmission">Automatic</span>
                                </div>
                                <div class="review-item">
                                    <span class="review-label">Fuel Type</span>
                                    <span class="review-value" id="rev-fuel">Petrol</span>
                                </div>
                                <div class="review-item">
                                    <span class="review-label">Seating Capacity</span>
                                    <span class="review-value" id="rev-seats">5 Seats</span>
                                </div>
                                <div class="review-item">
                                    <span class="review-label">Daily Rate (Self Drive)</span>
                                    <span class="review-value" id="rev-price" style="color:#1e3a8a;">Rs. 0.00 / day</span>
                                </div>
                                <div class="review-item">
                                    <span class="review-label">Daily Rate (With Driver)</span>
                                    <span class="review-value" id="rev-price-driver">N/A</span>
                                </div>
                            </div>

                            <div style="border-top: 1px solid var(--step-border); padding-top: 14px;">
                                <span class="review-label" style="display:block; margin-bottom:8px;">Verification Documents Attached</span>
                                <div id="rev-docs-list" style="display:flex; flex-wrap:wrap; gap:8px; font-size:0.85rem;">
                                    <span style="color:var(--step-muted); font-style:italic;">No documents selected</span>
                                </div>
                            </div>
                        </div>

                        <div class="review-notice">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <div>
                                <strong>Admin Verification Policy:</strong> Newly submitted vehicles receive an initial <em>Pending</em> verification status and remain unavailable for public booking until verified by platform administrators.
                            </div>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-outline" onclick="proceedToStep(2)">
                                &larr; Back to Documents
                            </button>
                            <button type="submit" class="button button-primary" id="btn-submit-vehicle" style="padding: 12px 24px; font-size: 1rem; font-weight: 700;">
                                Submit Vehicle for Verification
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
let currentWizardStep = 1;

function proceedToStep(targetStep) {
    if (targetStep > 1 && currentWizardStep === 1) {
        // Validate Step 1 required fields using standard HTML5 validity
        const step1Inputs = document.querySelectorAll('#panel-step-1 [required]');
        let valid = true;
        step1Inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity();
                valid = false;
                return false;
            }
        });
        if (!valid) return;
    }

    if (targetStep === 3) {
        updateReviewSummary();
    }

    currentWizardStep = targetStep;

    // Toggle panels
    document.querySelectorAll('.wizard-panel').forEach((panel, idx) => {
        panel.classList.toggle('is-active', idx + 1 === targetStep);
    });

    // Update stepper UI
    for (let i = 1; i <= 3; i++) {
        const nav = document.getElementById('step-nav-' + i);
        if (nav) {
            nav.classList.toggle('is-active', i === targetStep);
            nav.classList.toggle('is-completed', i < targetStep);
        }
        if (i < 3) {
            const conn = document.getElementById('step-conn-' + i);
            if (conn) {
                conn.classList.toggle('is-active', i < targetStep);
            }
        }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function wizardNav(step) {
    if (step < currentWizardStep) {
        proceedToStep(step);
    } else if (step > currentWizardStep) {
        proceedToStep(step);
    }
}

function updateDocFileName(input, statusId) {
    const el = document.getElementById(statusId);
    if (!el) return;
    if (input.files && input.files[0]) {
        el.textContent = '✅ ' + input.files[0].name;
        el.style.color = '#059669';
        input.closest('.doc-card').style.borderColor = '#10b981';
    } else {
        el.textContent = 'No file selected';
        el.style.color = 'var(--step-muted)';
        input.closest('.doc-card').style.borderColor = 'var(--step-border)';
    }
}

function previewMainImage(input) {
    const container = document.getElementById('image-preview-container');
    const img = document.getElementById('image-preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
    }
}

function updateReviewSummary() {
    const make = document.getElementById('inp-make').value.trim() || 'Vehicle';
    const model = document.getElementById('inp-model').value.trim() || 'Model';
    const year = document.getElementById('inp-year').value || 'Year';
    const plate = document.getElementById('inp-plate').value.trim().toUpperCase() || 'CAB-XXXX';
    const type = document.getElementById('inp-type').value;
    const transmission = document.getElementById('inp-transmission').value;
    const fuel = document.getElementById('inp-fuel').value;
    const seats = document.getElementById('inp-seats').value || '1';
    const price = parseFloat(document.getElementById('inp-price').value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 });
    const priceDriverVal = document.getElementById('inp-price-driver').value;
    const priceDriver = priceDriverVal ? ('Rs. ' + parseFloat(priceDriverVal).toLocaleString('en-LK', { minimumFractionDigits: 2 }) + ' / day') : 'Optional / Self-drive only';

    document.getElementById('rev-title').textContent = make + ' ' + model + ' (' + year + ')';
    document.getElementById('rev-plate').textContent = plate;
    document.getElementById('rev-type').textContent = type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('rev-transmission').textContent = transmission.charAt(0).toUpperCase() + transmission.slice(1);
    document.getElementById('rev-fuel').textContent = fuel.charAt(0).toUpperCase() + fuel.slice(1);
    document.getElementById('rev-seats').textContent = seats + ' Seats';
    document.getElementById('rev-price').textContent = 'Rs. ' + price + ' / day';
    document.getElementById('rev-price-driver').textContent = priceDriver;

    // Docs summary
    const docInputs = [
        { id: 'doc-reg-file', label: 'Registration / CR Book' },
        { id: 'doc-ins-file', label: 'Insurance Policy' },
        { id: 'doc-emi-file', label: 'Emission Test' },
        { id: 'doc-fit-file', label: 'Fitness Certificate' }
    ];
    const docsContainer = document.getElementById('rev-docs-list');
    docsContainer.innerHTML = '';
    let anyDoc = false;
    docInputs.forEach(d => {
        const inp = document.getElementById(d.id);
        if (inp && inp.files && inp.files[0]) {
            anyDoc = true;
            const pill = document.createElement('span');
            pill.style.background = '#d1fae5';
            pill.style.color = '#065f46';
            pill.style.fontWeight = '600';
            pill.style.padding = '3px 10px';
            pill.style.borderRadius = '12px';
            pill.textContent = '✓ ' + d.label + ' (' + inp.files[0].name + ')';
            docsContainer.appendChild(pill);
        }
    });
    if (!anyDoc) {
        docsContainer.innerHTML = '<span style="color:var(--step-muted); font-style:italic;">No verification documents attached (can be added later during review).</span>';
    }
}
</script>
</body>
</html>
