<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerInspectionController.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$controller = new OwnerInspectionController();
$dataResult = $controller->getInspectionsData();

$inspections = $dataResult['success'] ? $dataResult['inspections'] : [];
$summary     = $dataResult['success'] ? $dataResult['summary'] : ['total' => 0, 'passed' => 0, 'needs_maintenance' => 0, 'failed' => 0];
$filter      = $dataResult['filter'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Inspections - LankaRenters Owner</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <section class="vehicles-header" style="margin-bottom: 24px;">
                    <div class="vehicles-title">
                        <h1>Vehicle Inspections</h1>
                        <p>View inspection records and vehicle condition history for your fleet.</p>
                    </div>
                </section>

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

                <!-- Inspections Data Table -->
                <section class="inspections-list" aria-label="Inspection Log Table">
                    <div class="form-card" style="padding: 0; overflow: hidden;">
                        <div class="form-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Inspection Log History</h2>
                            <div class="filter-tabs" style="display: flex; gap: 8px;">
                                <a href="inspection.php" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo empty($filter) ? 'background-color:#eff6ff; color:#2563eb; border-color:#93c5fd;' : ''; ?>">All</a>
                                <a href="inspection.php?status=pass" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'pass' ? 'background-color:#dcfce7; color:#166534; border-color:#86efac;' : ''; ?>">Passed</a>
                                <a href="inspection.php?status=needs_maintenance" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'needs_maintenance' ? 'background-color:#fef3c7; color:#92400e; border-color:#fde047;' : ''; ?>">Maintenance</a>
                                <a href="inspection.php?status=fail" class="button button-outline" style="padding: 6px 12px; font-size: 0.82rem; <?php echo $filter === 'fail' ? 'background-color:#fee2e2; color:#991b1b; border-color:#fca5a5;' : ''; ?>">Failed</a>
                            </div>
                        </div>

                        <?php if (empty($inspections)): ?>
                            <div style="padding: 48px; text-align: center; color: #64748b;">
                                <div style="font-size: 2.8rem; margin-bottom: 12px;">🔍</div>
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
                                                </td>
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