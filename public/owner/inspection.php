<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerInspectionController.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$controller = new OwnerInspectionController();
$actionError = null;
$actionSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspectionType = $_POST['inspection_type'] ?? 'pre_rental';
    if ($inspectionType === 'post_rental') {
        $result = $controller->recordPostRentalInspection($_POST);
    } else {
        $result = $controller->recordPreRentalInspection($_POST);
    }
    if (!empty($result['success'])) {
        $actionSuccess = $result['message'] ?? 'Inspection recorded successfully.';
    } else {
        $actionError = $result['error'] ?? 'Failed to record inspection.';
    }
}

$dataResult = $controller->getInspectionsData();

$inspections      = $dataResult['success'] ? $dataResult['inspections'] : [];
$summary          = $dataResult['success'] ? $dataResult['summary'] : ['total' => 0, 'passed' => 0, 'needs_maintenance' => 0, 'failed' => 0];
$filter           = $dataResult['filter'] ?? null;
$eligibleBookings = $dataResult['success'] ? ($dataResult['eligible_bookings'] ?? []) : [];
$ongoingBookings  = $dataResult['success'] ? ($dataResult['ongoing_bookings'] ?? []) : [];
$csrfToken        = AuthHelper::getCsrfToken();
$preselectedBookingId = (int)($_GET['booking_id'] ?? 0);
$preselectedType      = trim($_GET['type'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Inspections - LankaRenters Owner</title>
  <link rel="stylesheet" href="includes/assets/css/owner-style.css">
  <link rel="stylesheet" href="../assets/css/lr-tokens.css">
  <link rel="stylesheet" href="../assets/css/lr-shell.css">
  <link rel="stylesheet" href="../assets/css/lr-components.css">
  <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
      <section class="vehicles-header welcome-container" style="margin-bottom: 24px;">
                    <div class="vehicles-title">
                        <h1>Vehicle Inspections</h1>
                        <p>View inspection records and vehicle condition history for your fleet.</p>
                    </div>
                </section>

                <!-- Alert Notifications -->
                <?php if (!empty($actionError)): ?>
                    <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                        <?php echo htmlspecialchars($actionError); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($actionSuccess)): ?>
                    <div style="background-color: #d1fae5; border: 1px solid #34d399; color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                        <?php echo htmlspecialchars($actionSuccess); ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Metric Cards -->
                <section class="overview-stats" aria-label="Inspection statistics" style="margin-bottom: 28px;">
                    <div class="stats-grid">
                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Total Inspections</p>
                                    <p class="stat-value"><?php echo (int)($summary['total'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-blue">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 11l3 3L22 4"></path>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Logged fleet records</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Passed</p>
                                    <p class="stat-value"><?php echo (int)($summary['passed'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-green">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Optimal condition</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Needs Maintenance</p>
                                    <p class="stat-value"><?php echo (int)($summary['needs_maintenance'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-yellow">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Service required</p>
                        </article>

                        <article class="stat-card">
                            <div class="stat-card-header">
                                <div>
                                    <p class="stat-label">Failed</p>
                                    <p class="stat-value"><?php echo (int)($summary['failed'] ?? 0); ?></p>
                                </div>
                                <div class="stat-icon-bubble bubble-red">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-meta">Action required</p>
                        </article>
                    </div>
                </section>

                <!-- Pre-Rental Inspection Recording Section -->
                <section class="record-inspection-section" style="margin-bottom: 28px;">
                    <div class="form-card" style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #ffffff;">
                        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 14px; margin-bottom: 20px;">
                            <h2 style="margin: 0 0 6px 0; font-size: 1.15rem; color: #0f172a;">Record Pre-Rental Inspection</h2>
                            <p style="margin: 0; font-size: 0.88rem; color: #64748b;">Record vehicle condition, odometer, and fuel readings for confirmed bookings prior to customer handover.</p>
                        </div>

                        <?php if (empty($eligibleBookings)): ?>
                            <div style="padding: 20px; text-align: center; background-color: #f8fafc; border-radius: 6px; color: #64748b;">
                                <p style="margin: 0; font-size: 0.92rem;">No confirmed bookings currently awaiting pre-rental inspection.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data" style="display: grid; gap: 16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="inspection_type" value="pre_rental">

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    <div>
                                        <label for="booking_id" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Confirmed Booking *</label>
                                        <select name="booking_id" id="booking_id" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="">-- Select Confirmed Booking --</option>
                                            <?php foreach ($eligibleBookings as $eb): ?>
                                                <?php
                                                    $isSelected = ($preselectedType !== 'post_rental' && $preselectedBookingId === (int)$eb['id']) ? 'selected' : '';
                                                    $hasInsp = !empty($eb['pre_rental_inspection_id']);
                                                    $inspTag = $hasInsp ? ' [Already Recorded: ' . ucfirst($eb['pre_rental_status']) . ']' : ' [Awaiting Inspection]';
                                                ?>
                                                <option value="<?php echo (int)$eb['id']; ?>" <?php echo $isSelected; ?>>
                                                    Booking #<?php echo (int)$eb['id']; ?> — <?php echo htmlspecialchars($eb['make'] . ' ' . $eb['model'] . ' (' . $eb['license_plate'] . ')'); ?> — <?php echo htmlspecialchars($eb['customer_name']); ?><?php echo $inspTag; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="odometer_reading" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Odometer Reading (km) *</label>
                                        <input type="number" name="odometer_reading" id="odometer_reading" min="0" required placeholder="e.g. 45000" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box;">
                                    </div>

                                    <div>
                                        <label for="fuel_level" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Fuel Level *</label>
                                        <select name="fuel_level" id="fuel_level" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="Full" selected>Full</option>
                                            <option value="3/4">3/4</option>
                                            <option value="1/2">1/2</option>
                                            <option value="1/4">1/4</option>
                                            <option value="Empty">Empty</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="status" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Inspection Status *</label>
                                        <select name="status" id="status" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="pass" selected>Pass (Roadworthy — Ready for Handover)</option>
                                            <option value="needs_maintenance">Needs Maintenance (Requires Attention)</option>
                                            <option value="fail">Fail (Not Roadworthy — Handover Blocked)</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    <div>
                                        <label for="exterior_condition" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Exterior Condition *</label>
                                        <textarea name="exterior_condition" id="exterior_condition" rows="3" required placeholder="Describe paint, bodywork, lights, mirrors, tires condition..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                    </div>

                                    <div>
                                        <label for="interior_condition" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Interior Condition *</label>
                                        <textarea name="interior_condition" id="interior_condition" rows="3" required placeholder="Describe upholstery, AC, dashboard, cleanliness, controls..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                    </div>
                                </div>

                                <div>
                                    <label for="pre_inspection_photos" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Vehicle Inspection Photos (Optional, max 5MB each)</label>
                                    <input type="file" name="inspection_photos[]" id="pre_inspection_photos" multiple accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                    <small style="color: #64748b; font-size: 0.8rem;">Upload clear photos of exterior panels, odometer display, interior cabin, or existing scratches before handover.</small>
                                </div>

                                <div>
                                    <label for="comments" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Additional Comments / Notes (Optional)</label>
                                    <textarea name="comments" id="comments" rows="2" placeholder="Any additional inspection remarks..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                </div>

                                <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                                    <button type="submit" style="padding: 10px 22px; font-size: 0.9rem; font-weight: 600; background-color: #059669; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">
                                        Save Pre-Rental Inspection
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Post-Rental Inspection Recording Section -->
                <section class="record-inspection-section" id="post-rental-inspection" style="margin-bottom: 28px;">
                    <div class="form-card" style="padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #ffffff;">
                        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h2 style="margin: 0 0 6px 0; font-size: 1.15rem; color: #0f172a;">Record Post-Rental Inspection</h2>
                                <p style="margin: 0; font-size: 0.88rem; color: #64748b;">Record vehicle return condition, odometer, and fuel readings for ongoing bookings upon return from customer.</p>
                            </div>
                            <a href="bookings.php" class="button button-outline" style="padding: 6px 14px; font-size: 0.82rem; text-decoration: none; border-radius: 6px;">
                                &larr; Return to Bookings
                            </a>
                        </div>

                        <?php if (empty($ongoingBookings)): ?>
                            <div style="padding: 20px; text-align: center; background-color: #f8fafc; border-radius: 6px; color: #64748b;">
                                <p style="margin: 0; font-size: 0.92rem;">No ongoing bookings currently awaiting post-rental inspection.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data" style="display: grid; gap: 16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="inspection_type" value="post_rental">

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    <div>
                                        <label for="post_booking_id" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Ongoing Booking *</label>
                                        <select name="booking_id" id="post_booking_id" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="">-- Select Ongoing Booking --</option>
                                            <?php foreach ($ongoingBookings as $ob): ?>
                                                <?php
                                                    $isSelected = ($preselectedType === 'post_rental' && $preselectedBookingId === (int)$ob['id']) ? 'selected' : '';
                                                    $hasInsp = !empty($ob['post_rental_inspection_id']);
                                                    $inspTag = $hasInsp ? ' [Already Recorded: ' . ucfirst($ob['post_rental_status']) . ']' : ' [Awaiting Inspection]';
                                                ?>
                                                <option value="<?php echo (int)$ob['id']; ?>" <?php echo $isSelected; ?>>
                                                    Booking #<?php echo (int)$ob['id']; ?> — <?php echo htmlspecialchars($ob['make'] . ' ' . $ob['model'] . ' (' . $ob['license_plate'] . ')'); ?> — <?php echo htmlspecialchars($ob['customer_name']); ?><?php echo $inspTag; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="post_odometer_reading" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Return Odometer Reading (km) *</label>
                                        <input type="number" name="odometer_reading" id="post_odometer_reading" min="0" required placeholder="e.g. 45250" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box;">
                                    </div>

                                    <div>
                                        <label for="post_fuel_level" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Return Fuel Level *</label>
                                        <select name="fuel_level" id="post_fuel_level" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="Full" selected>Full</option>
                                            <option value="3/4">3/4</option>
                                            <option value="1/2">1/2</option>
                                            <option value="1/4">1/4</option>
                                            <option value="Empty">Empty</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="post_status" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Inspection Status *</label>
                                        <select name="status" id="post_status" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                            <option value="pass" selected>Pass (Normal Return — Roadworthy)</option>
                                            <option value="needs_maintenance">Needs Maintenance (Minor Wear / Service Required)</option>
                                            <option value="fail">Fail (Damage Detected / Incident Reported)</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                    <div>
                                        <label for="post_exterior_condition" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Exterior Return Condition *</label>
                                        <textarea name="exterior_condition" id="post_exterior_condition" rows="3" required placeholder="Describe paint, bodywork, lights, mirrors, tires upon return..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                    </div>

                                    <div>
                                        <label for="post_interior_condition" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Interior Return Condition *</label>
                                        <textarea name="interior_condition" id="post_interior_condition" rows="3" required placeholder="Describe cleanliness, upholstery, dashboard, controls upon return..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                    </div>
                                </div>

                                <div>
                                    <label for="post_inspection_photos" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Vehicle Inspection Photos (Optional, max 5MB each)</label>
                                    <input type="file" name="inspection_photos[]" id="post_inspection_photos" multiple accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; background-color: #fff;">
                                    <small style="color: #64748b; font-size: 0.8rem;">Upload return photos showing odometer reading, fuel gauge, and condition upon return.</small>
                                </div>

                                <div>
                                    <label for="post_comments" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">Additional Comments / Return Notes (Optional)</label>
                                    <textarea name="comments" id="post_comments" rows="2" placeholder="Any return observations, note of minor scratches or remarks..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                                </div>

                                <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                                    <button type="submit" style="padding: 10px 22px; font-size: 0.9rem; font-weight: 600; background-color: #2563eb; border: none; color: #ffffff; cursor: pointer; border-radius: 6px;">
                                        Save Post-Rental Inspection
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Inspections Data Table -->
                <section class="inspections-list" aria-label="Inspection Log Table">
                    <div class="form-card" style="padding: 0; overflow: hidden;">
                        <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Vehicle Inspection Records</h2>
                            <div class="filter-tabs" style="display: flex; gap: 8px;">
                                <a href="inspection.php" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo empty($filter) ? 'background-color:#eff6ff; color:#2563eb; border-color:#93c5fd;' : ''; ?>">All</a>
                                <a href="inspection.php?status=pass" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'pass' ? 'background-color:#dcfce7; color:#166534; border-color:#86efac;' : ''; ?>">Passed</a>
                                <a href="inspection.php?status=needs_maintenance" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'needs_maintenance' ? 'background-color:#fef3c7; color:#92400e; border-color:#fde047;' : ''; ?>">Maintenance</a>
                                <a href="inspection.php?status=fail" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'fail' ? 'background-color:#fee2e2; color:#991b1b; border-color:#fca5a5;' : ''; ?>">Failed</a>
                            </div>
                        </div>

                        <?php if (empty($inspections)): ?>
                            <div style="padding: 48px; text-align: center; color: #64748b;">
                                <p style="font-size: 1.05rem; font-weight: 600; color: #0f172a; margin: 0 0 6px;">No Inspection Records Found</p>
                                <p style="margin: 0; font-size: 0.9rem;">Vehicle condition inspection logs recorded by inspectors/drivers will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                    <thead>
                                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">
                                            <th style="padding: 14px 20px;">ID</th>
                                            <th style="padding: 14px 20px;">Vehicle</th>
                                            <th style="padding: 14px 20px;">Type</th>
                                            <th style="padding: 14px 20px;">Inspector</th>
                                            <th style="padding: 14px 20px;">Odometer</th>
                                            <th style="padding: 14px 20px;">Conditions</th>
                                            <th style="padding: 14px 20px;">Date</th>
                                            <th style="padding: 14px 20px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inspections as $item): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 14px 20px; font-weight: 600; color: #1e293b;">
                                                     #INSP-<?php echo (int)$item['id']; ?>
                                                    <?php if (!empty($item['booking_ref'])): ?>
                                                        <br><span style="font-size: 0.78rem; color: #64748b; font-weight: normal;">Book #<?php echo (int)$item['booking_ref']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?></div>
                                                    <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($item['license_plate']); ?></div>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <span style="text-transform: capitalize; font-weight: 500; color: #334155;">
                                                        <?php echo htmlspecialchars(str_replace('_', ' ', $item['inspection_type'])); ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <div style="color: #1e293b;"><?php echo htmlspecialchars($item['inspector_name']); ?></div>
                                                    <div style="font-size: 0.78rem; color: #64748b; text-transform: capitalize;"><?php echo htmlspecialchars($item['inspector_role']); ?></div>
                                                </td>
                                                <td style="padding: 14px 20px; font-weight: 500; color: #0f172a;">
                                                    <?php echo number_format($item['odometer_reading']); ?> km
                                                </td>
                                                <td style="padding: 14px 20px; font-size: 0.84rem; max-width: 240px;">
                                                    <div><strong>Ext:</strong> <?php echo htmlspecialchars($item['exterior_condition']); ?></div>
                                                    <div><strong>Int:</strong> <?php echo htmlspecialchars($item['interior_condition']); ?></div>
                                                    <div><strong>Fuel:</strong> <?php echo htmlspecialchars($item['fuel_level']); ?></div>
                                                    <?php if (!empty($item['photos'])): ?>
                                                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                                                            <?php foreach ($item['photos'] as $p): ?>
                                                                <a href="../<?php echo htmlspecialchars($p['photo_path']); ?>" target="_blank" title="<?php echo htmlspecialchars($p['caption'] ?? 'Inspection photo'); ?>" style="display: inline-block;">
                                                                    <img src="../<?php echo htmlspecialchars($p['photo_path']); ?>" alt="Photo" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;">
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>         </td>
                                                <td style="padding: 14px 20px; color: #64748b; font-size: 0.84rem;">
                                                    <?php echo date('M d, Y', strtotime($item['inspection_date'])); ?>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <?php if ($item['status'] === 'pass'): ?>
                                                        <span class="status-badge badge-success" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #dcfce7; color: #15803d;">Pass</span>
                                                    <?php elseif ($item['status'] === 'needs_maintenance'): ?>
                                                        <span class="status-badge badge-warning" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #fef3c7; color: #b45309;">Maintenance</span>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-danger" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background-color: #fee2e2; color: #b91c1c;">Fail</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>
