<?php
// Shared admin layout — include this from page wrappers.
// Expects: $page (string, page key like 'vehicles'), $pageContent (path to content partial)
if (!isset($page)) { $page = basename($_SERVER['PHP_SELF'], '.php'); }
$menuItems = [
    ['name' => 'Dashboard', 'href' => 'dashboard.php'],
    ['name' => 'Users', 'href' => 'users.php'],
    ['name' => 'Vehicle Owners', 'href' => 'owners.php'],
    ['name' => 'Drivers', 'href' => 'drivers.php'],
    ['name' => 'Vehicles', 'href' => 'vehicles.php'],
    ['name' => 'Bookings', 'href' => 'bookings.php'],
    ['name' => 'Payments', 'href' => 'payment.php'],
    ['name' => 'Incidents', 'href' => 'incident.php'],
    ['name' => 'Replacement Requests', 'href' => 'replacement_requests.php'],
    ['name' => 'Settlements', 'href' => 'settlements.php'],
    ['name' => 'Reports', 'href' => 'reports.php'],
    ['name' => 'Email Logs', 'href' => 'email_logs.php'],
];
function isActive($href, $page) {
    $key = basename($href, '.php');
    return $key === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - LankaRenters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand">
                <div class="brand-logo">L</div>
                <div>
                    <div class="brand-title">LankaRenters</div>
                    <div class="brand-subtitle">Admin</div>
                </div>
            </div>
            <nav class="admin-nav">
                <?php foreach ($menuItems as $item): ?>
                    <a href="<?= $item['href'] ?>" class="nav-item <?= isActive($item['href'], $page) ?>"><?= $item['name'] ?></a>
                <?php endforeach; ?>
            </nav>
            <a href="#" class="logout-link">Log out</a>
        </aside>

        <main class="admin-content">
            <?php
                if (isset($pageContent) && file_exists($pageContent)) {
                    include $pageContent;
                } else {
                    echo "<div class='panel-card'>Content not found.</div>";
                }
            ?>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
