<?php
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
$nameParts = preg_split('/\s+/', trim($customerName)) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $initials .= strtoupper(substr($namePart, 0, 1));
}
$initials = $initials !== '' ? $initials : 'C';
?>
<header class="customer-navbar top-navbar">
    <div class="navbar-left navbar-context">
        <button class="icon-button menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="customer-sidebar" data-sidebar-open>
            <?= customer_icon('menu') ?>
        </button>
        <nav class="lr-breadcrumb" aria-label="Breadcrumb">
            <span class="lr-breadcrumb-home">Customer</span>
            <span class="lr-breadcrumb-separator" aria-hidden="true">/</span>
            <span class="lr-breadcrumb-current"><?= htmlspecialchars((string) ($viewData['page_title'] ?? 'Customer'), ENT_QUOTES, 'UTF-8') ?></span>
        </nav>
    </div>

    <div class="navbar-right navbar-actions">
        <?php
        $notificationViewAllUrl = customer_url('notifications/index.php');
        include dirname(__DIR__, 3) . '/includes/notification-dropdown.php';
        ?>
        <div class="profile-menu" data-profile-menu>
            <button class="profile-menu__trigger" type="button" aria-expanded="false" aria-controls="customer-profile-menu" data-profile-trigger>
                <span class="lr-header-avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="profile-info">
                    <span class="profile-menu__name profile-name"><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-role">Customer</span>
                </span>
                <?= customer_icon('chevron-down') ?>
            </button>
            <div class="profile-menu__panel" id="customer-profile-menu" hidden data-profile-panel>
                <p><strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong><small>Customer account</small></p>
                <a href="<?= htmlspecialchars(customer_url('profile/index.php'), ENT_QUOTES, 'UTF-8') ?>"><?= customer_icon('user') ?> Profile settings</a>
                <form method="post" action="<?= htmlspecialchars(customer_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="<?= htmlspecialchars((string) $viewData['csrf_field_name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $viewData['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit"><?= customer_icon('logout') ?> Log out</button>
                </form>
            </div>
        </div>
    </div>
</header>
