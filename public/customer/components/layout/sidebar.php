<?php
$customerLinks = [
    'Dashboard' => 'dashboard.php',
    'Vehicles' => 'vehicles/index.php',
    'Bookings' => 'bookings/index.php',
    'Payments' => 'payments/index.php',
    'Incidents' => 'incidents/index.php',
    'Reviews' => 'reviews/index.php',
    'Notifications' => 'notifications.php',
    'Chat' => 'chat/index.php',
    'Profile' => 'profile/index.php',
];
?>
<aside class="customer-sidebar" aria-label="Customer navigation">
    <nav>
        <ul>
            <?php foreach ($customerLinks as $label => $path): ?>
                <li>
                    <a href="<?= htmlspecialchars(customer_url($path), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
