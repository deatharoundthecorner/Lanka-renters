<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'owner') {
    AuthHelper::logout();
    header("Location: ../login.php");
    exit();
}

$ownerController = new OwnerController();

$message = '';
$error = '';

// Handle driver assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    $driverId = (int)($_POST['driver_id'] ?? 0);
    $result = $ownerController->assignDriver($driverId);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['error'];
    }
}

// Get available drivers
$driversResult = $ownerController->viewAvailableDrivers();
if (!$driversResult['success']) {
    die("Error loading owner dashboard: " . htmlspecialchars($driversResult['error']));
}

$owner = $driversResult['owner'];
$drivers = $driversResult['drivers'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Lanka Renters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/driver.css">
    <style>
        /* Custom tweaks to match owner themes */
        :root {
            --primary: #10b981; /* Emerald/Green theme for owners */
            --primary-hover: #059669;
        }
        .btn-assign {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-assign:hover {
            background-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Owner Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand" style="background-color: var(--primary);">
                Lanka Renters
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a >
            </nav>
            <div class="sidebar-footer">
                <form action="../driver/dashboard.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn-sidebar-logout">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Body -->
        <div class="main-body">
            <header class="navbar" style="border-bottom: 3px solid var(--primary);">
                <div style="font-weight: 700; font-size: 18px; color: var(--text-main);">
                    Owner Portal
                </div>
                <div class="navbar-profile">
                    <span class="user-initial" style="background-color: var(--primary);"><?php echo strtoupper(substr($owner['name'], 0, 1)); ?></span>
                    <span class="profile-name"><?php echo htmlspecialchars($owner['name']); ?> (Owner)</span>
                </div>
            </header>

            <main class="main-content">
                <div class="welcome-container">
                    <div>
                        <h2 class="welcome-title">Driver Connection Console</h2>
                        <p class="welcome-subtitle">Assign available, verified drivers to build your renting fleet.</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success" style="border-left: 4px solid var(--primary);"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Drivers List Card -->
                <div class="card">
                    <h2 class="card-title">Available Verified Drivers</h2>
                    <?php if (empty($drivers)): ?>
                        <p style="font-style: italic; color: var(--text-muted);">No available verified drivers found at the moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Driver Name</th>
                                    <th>Rating</th>
                                    <th>Completed Trips</th>
                                    <th>Availability Status</th>
                                    <th>Verification</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($drivers as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                        <td>
                                            <span style="color: #f59e0b; font-weight: bold;">
                                                <?php echo number_format($d['rating'], 1); ?> ⭐
                                            </span>
                                        </td>
                                        <td><?php echo $d['completed_trips']; ?> trips</td>
                                        <td>
                                            <span class="status-pill status-available">
                                                Available
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-pill status-approved">
                                                Verified
                                            </span>
                                        </td>
                                        <td>
                                            <form action="" method="POST" style="margin:0;">
                                                <input type="hidden" name="action" value="assign">
                                                <input type="hidden" name="driver_id" value="<?php echo $d['id']; ?>">
                                                <button type="submit" class="btn-assign">Assign Driver</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
