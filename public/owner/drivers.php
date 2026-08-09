<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/Database.php';
require_once dirname(dirname(__DIR__)) . '/app/models/VehicleOwner.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverOwnerLink.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Driver.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$currentUser = AuthHelper::getCurrentUser();
$ownerModel = new VehicleOwner();
$owner = $ownerModel->findByUserId($currentUser['id']);

if (!$owner) {
    die("Vehicle Owner profile not found.");
}

$linkModel = new DriverOwnerLink();
$driverModel = new Driver();

$error = '';
$success = '';

// Handle connection request submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_driver') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $driverId = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : 0;
        
        // Validate selected driver exists, is active and is verified/available
        $availableDrivers = $driverModel->getAvailableVerifiedDrivers();
        $isValid = false;
        foreach ($availableDrivers as $d) {
            if ((int)$d['id'] === $driverId) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $error = "Selected driver is not eligible or available.";
        } else {
            // Check if there is already an active or pending link to prevent duplicate requests
            $db = Database::getInstance()->getConnection();
            $stmtCheck = $db->prepare("SELECT status FROM `driver_owner_links` WHERE `driver_id` = ? AND `owner_id` = ? LIMIT 1");
            $stmtCheck->execute([$driverId, $owner['id']]);
            $existingStatus = $stmtCheck->fetchColumn();

            if ($existingStatus === 'accepted') {
                $error = "You are already connected with this driver.";
            } elseif ($existingStatus === 'pending') {
                $error = "A connection request is already pending with this driver.";
            } else {
                $linkModel->requestLink($driverId, $owner['id']);
                $success = "Connection request sent successfully.";
            }
        }
    }
}

// Retrieve active/accepted drivers linked to this owner
$acceptedDrivers = $linkModel->getAcceptedDriversByOwner($owner['id']);

// Retrieve pending requests sent from this owner
$db = Database::getInstance()->getConnection();
$stmtPending = $db->prepare("
    SELECT dol.*, d.rating_avg as rating, u.name, u.email, u.phone 
    FROM `driver_owner_links` dol
    JOIN `drivers` d ON dol.driver_id = d.id
    JOIN `users` u ON d.user_id = u.id
    WHERE dol.owner_id = :owner_id AND dol.status = 'pending'
    ORDER BY dol.created_at DESC
");
$stmtPending->execute(['owner_id' => $owner['id']]);
$pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

// Retrieve verified and available drivers for discovery
$availableDrivers = $driverModel->getAvailableVerifiedDrivers();

$availabilityMap = [
    'available' => ['class' => 'status-linked', 'label' => 'Available'],
    'busy'      => ['class' => 'status-pending', 'label' => 'Busy'],
    'off_duty'  => ['class' => 'status-outline', 'label' => 'Off Duty']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - LankaRenters</title>
    <!-- Link CSS File - Corrected stylesheet path link reference -->
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <section class="drivers-header">
                <div class="drivers-heading">
                    <h1>Driver management</h1>
                    <p>Search, request and manage verified drivers.</p>
                </div>
            </section>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
                <div style="background-color: #FEF2F2; border: 1px solid #FCA5A5; color: #EF4444; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; color: #15803D; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <section class="drivers-layout">
                <div class="accepted-drivers">
                    <h2>Accepted drivers</h2>

                    <div class="driver-list">
                        <?php if (empty($acceptedDrivers)): ?>
                            <p class="no-data" style="color: var(--text-muted); font-style: italic; padding: 20px 0;">No connected drivers found.</p>
                        <?php else: ?>
                            <?php foreach ($acceptedDrivers as $d): ?>
                                <?php
                                $initial = htmlspecialchars(substr($d['name'] ?? 'D', 0, 1));
                                $avail = $availabilityMap[$d['availability_status']] ?? ['class' => 'status-pending', 'label' => ucfirst($d['availability_status'])];
                                ?>
                                <article class="driver-card">
                                    <div class="driver-avatar"><?php echo $initial; ?></div>
                                    <div class="driver-text">
                                        <p class="driver-name"><?php echo htmlspecialchars($d['name']); ?></p>
                                        <p class="driver-details">★ <?php echo number_format($d['rating_avg'], 1); ?> · <?php echo htmlspecialchars($d['phone']); ?></p>
                                    </div>
                                    <span class="status-badge <?php echo $avail['class']; ?>"><?php echo $avail['label']; ?></span>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="driver-panels">
                    <!-- Send Connection Request Section -->
                    <article class="request-panel" style="margin-bottom: 20px;">
                        <h2>Request Connection</h2>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                            <input type="hidden" name="action" value="request_driver">
                            
                            <div style="margin-bottom: 15px;">
                                <label for="driver_id" style="display: block; font-size: 13px; font-weight: 600; color: var(--dark-blue); margin-bottom: 8px; text-transform: uppercase;">Select Driver</label>
                                <select id="driver_id" name="driver_id" required style="width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 6px; outline: none; font-size: 14px; background-color: white;">
                                    <option value="">-- Select Verified Available Driver --</option>
                                    <?php foreach ($availableDrivers as $d): ?>
                                        <option value="<?php echo $d['id']; ?>">
                                            <?php echo htmlspecialchars($d['name']); ?> (Rating: <?php echo number_format($d['rating'], 1); ?> ★)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="button button-primary" style="width: 100%; border: none; padding: 10px; font-weight: 600; border-radius: 6px; cursor: pointer;">Send Request</button>
                        </form>
                    </article>

                    <!-- Pending Connection Requests Section -->
                    <article class="request-panel">
                        <h2>Pending requests</h2>
                        
                        <div class="request-list" style="display: flex; flex-direction: column; gap: 15px;">
                            <?php if (empty($pendingRequests)): ?>
                                <p class="no-data" style="color: var(--text-muted); font-style: italic; padding: 10px 0;">No pending connection requests.</p>
                            <?php else: ?>
                                <?php foreach ($pendingRequests as $r): ?>
                                    <div class="request-item" style="border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 10px;">
                                        <div class="request-info">
                                            <p class="request-name" style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;"><?php echo htmlspecialchars($r['name']); ?></p>
                                            <p class="request-meta" style="font-size: 12px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></p>
                                        </div>
                                        <span class="status-badge status-pending" style="margin-left: auto;">Pending</span>
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