<?php

if (!defined('CUSTOMER_DASHBOARD_VIEW')) {
    require_once dirname(__DIR__) . '/_bootstrap.php';
    header('Location: ' . customer_url('dashboard.php'), true, 302);
    exit;
}

$pageTitle = $viewData['page_title'];
$dashboard = is_array($viewData['dashboard'] ?? null) ? $viewData['dashboard'] : [];
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
$nameParts = preg_split('/\s+/', trim($customerName)) ?: [];
$firstName = trim((string) ($nameParts[0] ?? 'Customer'));
require dirname(__DIR__) . '/components/layout/header.php';
?>
<div class="customer-shell">
    <?php require dirname(__DIR__) . '/components/layout/sidebar.php'; ?>

    <div class="customer-main">
        <?php require dirname(__DIR__) . '/components/layout/navbar.php'; ?>

        <main class="customer-content" id="main-content">
            <?php require dirname(__DIR__) . '/components/layout/page-header.php'; ?>

            <?php $verificationStatus = (string) ($dashboard['verification_status'] ?? 'pending'); ?>
            <section class="welcome-strip" aria-labelledby="welcome-heading">
                <div>
                    <p class="eyebrow">Good to see you</p>
                    <h2 id="welcome-heading">Welcome back, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>Your summary below uses Customer-owned database records.</p>
                </div>
                <span class="status-badge status-badge--<?= $verificationStatus === 'approved' ? 'success' : 'warning' ?>"><?= customer_icon($verificationStatus === 'approved' ? 'check' : 'clock') ?> <?= htmlspecialchars(ucfirst($verificationStatus) . ' verification', ENT_QUOTES, 'UTF-8') ?></span>
            </section>

            <?php $verification = is_array($dashboard['verification'] ?? null) ? $dashboard['verification'] : []; ?>
            <section class="next-action-card" aria-labelledby="next-action-heading">
                <span class="next-action-card__icon" aria-hidden="true"><?= customer_icon('shield') ?></span>
                <div class="next-action-card__copy">
                    <p class="eyebrow">Recommended next step</p>
                    <h2 id="next-action-heading"><?= htmlspecialchars((string) ($verification['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars((string) ($verification['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="button button--primary" href="<?= htmlspecialchars(customer_url((string) ($verification['action_path'] ?? 'verification/index.php')), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) ($verification['action_label'] ?? 'Continue'), ENT_QUOTES, 'UTF-8') ?>
                    <?= customer_icon('arrow-right') ?>
                </a>
            </section>

            <section aria-labelledby="summary-heading">
                <div class="section-heading"><div><p class="eyebrow">At a glance</p><h2 id="summary-heading">Rental summary</h2></div></div>
                <div class="stats-grid">
                    <?php foreach (($dashboard['stats'] ?? []) as $stat): ?>
                        <?php require dirname(__DIR__) . '/components/dashboard/stat-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="dashboard-grid dashboard-grid--main">
                <section class="card" aria-labelledby="progress-heading">
                    <div class="card__header">
                        <div><p class="eyebrow">Your journey</p><h2 id="progress-heading">Rental progress</h2></div>
                        <?php $completeProgress = count(array_filter($dashboard['progress'] ?? [], static fn (array $item): bool => ($item['state'] ?? '') === 'complete')); ?>
                        <span class="status-badge status-badge--info"><?= $completeProgress ?> of 6 complete</span>
                    </div>
                    <ol class="progress-list">
                        <?php foreach (($dashboard['progress'] ?? []) as $progressItem): ?>
                            <?php $progressState = (string) ($progressItem['state'] ?? 'upcoming'); ?>
                            <li class="progress-list__item progress-list__item--<?= htmlspecialchars($progressState, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="progress-list__marker" aria-hidden="true"><?= $progressState === 'complete' ? customer_icon('check') : '' ?></span>
                                <div>
                                    <strong><?= htmlspecialchars((string) ($progressItem['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <p><?= htmlspecialchars((string) ($progressItem['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="visually-hidden"><?= htmlspecialchars($progressState === 'current' ? 'Current step' : ucfirst($progressState), ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>

                <div class="dashboard-stack">
                    <section class="card" aria-labelledby="recent-booking-heading">
                        <div class="card__header">
                            <div><p class="eyebrow">Bookings</p><h2 id="recent-booking-heading">Recent booking</h2></div>
                            <a class="text-link" href="<?= htmlspecialchars(customer_url('bookings/index.php'), ENT_QUOTES, 'UTF-8') ?>">View all</a>
                        </div>
                        <?php if (empty($dashboard['recent_booking'])): ?>
                            <div class="empty-state empty-state--compact">
                                <span class="empty-state__icon" aria-hidden="true"><?= customer_icon('calendar') ?></span>
                                <h3>No bookings yet</h3>
                                <p>Your latest booking summary will appear here after you request a vehicle.</p>
                                <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('vehicles/index.php'), ENT_QUOTES, 'UTF-8') ?>">Browse vehicles</a>
                            </div>
                        <?php else: ?>
                            <?php $recentBooking = $dashboard['recent_booking']; ?>
                            <div class="recent-booking-summary">
                                <p class="eyebrow">Booking #<?= (int) $recentBooking['id'] ?></p>
                                <h3><?= htmlspecialchars($recentBooking['make'] . ' ' . $recentBooking['model'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars(date('d M Y', strtotime($recentBooking['start_date'])) . ' – ' . date('d M Y', strtotime($recentBooking['end_date'])), ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="status-badge status-badge--info"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $recentBooking['status'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url('bookings/details.php?id=' . (int) $recentBooking['id']), ENT_QUOTES, 'UTF-8') ?>">View Details</a>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="card reminder-card" aria-labelledby="reminders-heading">
                        <div class="card__header">
                            <div><p class="eyebrow">Before you rent</p><h2 id="reminders-heading">Essential reminders</h2></div>
                            <span class="card__header-icon" aria-hidden="true"><?= customer_icon('info') ?></span>
                        </div>
                        <ul class="check-list">
                            <?php foreach (($dashboard['reminders'] ?? []) as $reminder): ?>
                                <li><span aria-hidden="true"><?= customer_icon('check') ?></span><?= htmlspecialchars((string) $reminder, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </div>

            <section aria-labelledby="quick-links-heading">
                <div class="section-heading"><div><p class="eyebrow">Shortcuts</p><h2 id="quick-links-heading">Quick links</h2></div></div>
                <div class="quick-links-grid">
                    <?php foreach (($dashboard['quick_links'] ?? []) as $quickLink): ?>
                        <?php require dirname(__DIR__) . '/components/dashboard/quick-action-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
<?php require dirname(__DIR__) . '/components/layout/footer.php'; ?>
