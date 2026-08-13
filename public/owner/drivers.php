<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerDriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$driverController = new OwnerDriverController();

$error   = '';
$success = '';

// Handle POST: request a driver connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_driver') {
    $result = $driverController->requestDriverConnection($_POST);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}

// Fetch all driver data
$data             = $driverController->getDriverData();
$acceptedDrivers  = $data['accepted_drivers']  ?? [];
$pendingRequests  = $data['pending_requests']   ?? [];
$availableDrivers = $data['available_drivers']  ?? [];

$csrfToken = AuthHelper::getCsrfToken();

$availabilityMap = [
    'available' => ['class' => 'status-linked',   'label' => 'Available'],
    'busy'      => ['class' => 'status-pending',   'label' => 'Busy'],
    'off_duty'  => ['class' => 'status-outline',   'label' => 'Off Duty'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - LankaRenters</title>
    <meta name="description" content="Search, request and manage verified drivers for your vehicle fleet on LankaRenters.">
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Page Header -->
            <section class="drivers-header">
                <div class="drivers-heading">
                    <h1>Driver Management</h1>
                    <p>Search, request and manage verified drivers for your vehicles.</p>
                </div>
            </section>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
                <div style="background-color:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="background-color:#f0fdf4; border:1px solid #86efac; color:#166534; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-weight:600;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <section class="drivers-layout">
                <!-- Left: Accepted Drivers -->
                <div class="accepted-drivers">
                    <h2>Accepted Drivers (<?php echo count($acceptedDrivers); ?>)</h2>

                    <div class="driver-list">
                        <?php if (empty($acceptedDrivers)): ?>
                            <p style="color:#64748b; font-style:italic; padding:20px 0;">No accepted drivers yet. Send a connection request to get started.</p>
                        <?php else: ?>
                            <?php foreach ($acceptedDrivers as $d): ?>
                                <?php
                                    $initial = htmlspecialchars(strtoupper(mb_substr($d['name'] ?? 'D', 0, 1)));
                                    $avail   = $availabilityMap[$d['availability_status']] ?? ['class' => 'status-pending', 'label' => ucfirst($d['availability_status'])];
                                ?>
                                <article class="driver-card">
                                    <div class="driver-avatar" aria-hidden="true"><?php echo $initial; ?></div>
                                    <div class="driver-text">
                                        <p class="driver-name"><?php echo htmlspecialchars($d['name']); ?></p>
                                        <p class="driver-details">
                                            ★ <?php echo number_format($d['rating_avg'], 1); ?>
                                            · <?php echo htmlspecialchars($d['phone']); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge <?php echo $avail['class']; ?>"><?php echo $avail['label']; ?></span>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Panels -->
                <div class="driver-panels">
                    <!-- Send Connection Request -->
                    <article class="request-panel">
                        <h2>Request a Driver</h2>

                        <?php if (empty($availableDrivers)): ?>
                            <p style="color:#64748b; font-size:0.95rem;">No verified drivers are currently available for new connections.</p>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action"     value="request_driver">

                                <div style="margin-bottom:16px;">
                                    <label for="driver_id" style="display:block; font-size:12px; font-weight:700; color:#0b3a82; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">Select Verified Driver</label>
                                    <select id="driver_id" name="driver_id" required style="width:100%; padding:12px 14px; border:2px solid #e5e7eb; border-radius:8px; font-size:14px; background-color:#f8fafc; color:#0f172a; outline:none;">
                                        <option value="">— Select a verified, available driver —</option>
                                        <?php foreach ($availableDrivers as $d): ?>
                                            <option value="<?php echo (int)$d['id']; ?>">
                                                <?php echo htmlspecialchars($d['name']); ?>
                                                (★ <?php echo number_format($d['rating'], 1); ?> · <?php echo (int)$d['completed_trips']; ?> trips)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="button button-primary" style="width:100%; border:none; padding:12px; font-weight:700; border-radius:8px; cursor:pointer;">
                                    Send Connection Request
                                </button>
                            </form>
                        <?php endif; ?>
                    </article>

                    <!-- Pending Requests -->
                    <article class="request-panel">
                        <h2>Pending Requests (<?php echo count($pendingRequests); ?>)</h2>

                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <?php if (empty($pendingRequests)): ?>
                                <p style="color:#64748b; font-style:italic; font-size:0.92rem;">No pending connection requests.</p>
                            <?php else: ?>
                                <?php foreach ($pendingRequests as $r): ?>
                                    <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                                        <div>
                                            <p style="font-weight:700; color:#0f172a; margin:0 0 2px;"><?php echo htmlspecialchars($r['name']); ?></p>
                                            <p style="font-size:0.82rem; color:#64748b; margin:0;">
                                                ★ <?php echo number_format($r['rating'], 1); ?>
                                                · Sent <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge status-pending">Pending</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>

</body>
</html>