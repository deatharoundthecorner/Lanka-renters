<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/VehicleOwner.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Vehicle.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverOwnerLink.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/Database.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$currentUser = AuthHelper::getCurrentUser();
$ownerModel  = new VehicleOwner();
$owner       = $ownerModel->findByUserId($currentUser['id']);

$totalVehicles    = 0;
$verifiedVehicles = 0;
$linkedDrivers    = 0;
$pendingBookings  = 0;
$totalEarnings    = 0.00;
$recentBookings   = [];

if ($owner) {
    $db = Database::getInstance()->getConnection();

    // Vehicles count
    $stmt = $db->prepare("SELECT COUNT(*) as cnt, SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) as verified FROM vehicles WHERE owner_id = :oid");
    $stmt->execute(['oid' => $owner['id']]);
    $vRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalVehicles    = (int)($vRow['cnt'] ?? 0);
    $verifiedVehicles = (int)($vRow['verified'] ?? 0);

    // Linked (accepted) drivers
    $stmt2 = $db->prepare("SELECT COUNT(*) as cnt FROM driver_owner_links WHERE owner_id = :oid AND status = 'accepted'");
    $stmt2->execute(['oid' => $owner['id']]);
    $linkedDrivers = (int)$stmt2->fetchColumn();

    // Pending bookings (confirmed or ongoing) for owner's vehicles
    $stmt3 = $db->prepare("
        SELECT COUNT(*) as cnt FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE v.owner_id = :oid AND b.status IN ('pending_payment', 'confirmed')
    ");
    $stmt3->execute(['oid' => $owner['id']]);
    $pendingBookings = (int)$stmt3->fetchColumn();

    // Total earnings from completed bookings
    $stmt4 = $db->prepare("
        SELECT COALESCE(SUM(b.total_price), 0) as total FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE v.owner_id = :oid AND b.status = 'completed'
    ");
    $stmt4->execute(['oid' => $owner['id']]);
    $totalEarnings = (float)$stmt4->fetchColumn();

    // Recent bookings
    $stmt5 = $db->prepare("
        SELECT b.id, b.status, b.start_date, b.end_date, b.total_price,
               u.name as customer_name,
               v.make, v.model, v.license_plate
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        JOIN customers c ON b.customer_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE v.owner_id = :oid
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt5->execute(['oid' => $owner['id']]);
    $recentBookings = $stmt5->fetchAll(PDO::FETCH_ASSOC);
}

$csrfToken = AuthHelper::getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - LankaRenters</title>
    <meta name="description" content="Manage your vehicle fleet, track bookings and earnings on LankaRenters owner dashboard.">
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Page Header -->
            <section class="vehicles-header" style="margin-bottom: 24px;">
                <div class="vehicles-title">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>. Here's your fleet overview.</p>
                </div>
                <div class="vehicles-action">
                    <a href="vehicles.php#add-vehicle-form" class="button button-primary">+ Add Vehicle</a>
                </div>
            </section>

            <!-- Statistics Cards -->
            <section class="overview-stats" aria-label="Summary statistics">
                <div class="stats-grid">
                    <article class="stat-card stat-earnings">
                        <p class="stat-label">Total Earnings</p>
                        <p class="stat-value">LKR <?php echo number_format($totalEarnings, 0); ?></p>
                        <p class="stat-meta">From completed rentals</p>
                    </article>

                    <article class="stat-card stat-vehicles">
                        <p class="stat-label">Active Vehicles</p>
                        <p class="stat-value"><?php echo $totalVehicles; ?></p>
                        <p class="stat-meta"><?php echo $verifiedVehicles; ?> verified</p>
                    </article>

                    <article class="stat-card stat-bookings">
                        <p class="stat-label">Pending Bookings</p>
                        <p class="stat-value"><?php echo $pendingBookings; ?></p>
                        <p class="stat-meta"><?php echo $pendingBookings > 0 ? 'Needs attention' : 'All clear'; ?></p>
                    </article>

                    <article class="stat-card stat-drivers">
                        <p class="stat-label">Linked Drivers</p>
                        <p class="stat-value"><?php echo $linkedDrivers; ?></p>
                        <p class="stat-meta">Accepted connections</p>
                    </article>
                </div>
            </section>

            <!-- Recent Bookings -->
            <section class="booking-requests">
                <div class="section-heading">
                    <h2>Recent Booking Activity</h2>
                </div>

                <?php if (empty($recentBookings)): ?>
                    <div style="text-align: center; padding: 32px 0; color: #64748b;">
                        <p style="font-size: 1.1rem; margin: 0 0 8px;">No bookings yet</p>
                        <p style="margin: 0; font-size: 0.95rem;">Once customers book your vehicles, they'll appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="request-list">
                        <?php foreach ($recentBookings as $bk): ?>
                            <?php
                                $statusColors = [
                                    'pending_payment' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Pending Payment'],
                                    'confirmed'       => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Confirmed'],
                                    'ongoing'         => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Ongoing'],
                                    'completed'       => ['bg' => '#f0fdf4', 'color' => '#166534', 'label' => 'Completed'],
                                    'cancelled'       => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Cancelled'],
                                ];
                                $sc = $statusColors[$bk['status']] ?? ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => ucfirst($bk['status'])];
                            ?>
                            <article class="request-card">
                                <div class="request-details">
                                    <p class="request-title"><?php echo htmlspecialchars($bk['make'] . ' ' . $bk['model']); ?> · <?php echo htmlspecialchars($bk['license_plate']); ?></p>
                                    <p class="request-meta"><?php echo htmlspecialchars($bk['customer_name']); ?> · <?php echo date('d M Y', strtotime($bk['start_date'])); ?> – <?php echo date('d M Y', strtotime($bk['end_date'])); ?> · LKR <?php echo number_format($bk['total_price'], 0); ?></p>
                                </div>
                                <div class="request-actions">
                                    <span style="display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 999px; font-size: 0.85rem; font-weight: 700; background-color: <?php echo $sc['bg']; ?>; color: <?php echo $sc['color']; ?>;">
                                        <?php echo $sc['label']; ?>
                                    </span>
                                    <a href="bookings.php" class="button button-secondary" style="text-decoration: none; padding: 8px 14px; font-size: 0.88rem;">View All</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

</body>
</html>