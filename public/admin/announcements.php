<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Announcement.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Announcements";
$flash = $_SESSION['admin_ann_flash'] ?? null;
unset($_SESSION['admin_ann_flash']);

try {
    $model = new Announcement();
    $announcements = $model->getAll();
} catch (Throwable $e) {
    error_log("Announcements Error: " . $e->getMessage());
    $announcements = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Broadcast Announcements</h1>
                <p class="page-subtitle">Publish system notices, policy updates, and operational warnings to customers, owners, or drivers.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <!-- Create New Announcement Card -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Create New Announcement</h3>
                </div>

                <form method="post" action="announcement_handler.php">
                    <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                    <input type="hidden" name="action" value="create">

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                        <div class="filter-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Announcement Title..." required>
                        </div>
                        <div class="filter-group">
                            <label>Target Audience</label>
                            <select name="target_audience" class="form-control">
                                <option value="all">All Users</option>
                                <option value="customers">Customers Only</option>
                                <option value="owners">Vehicle Owners Only</option>
                                <option value="drivers">Drivers Only</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="important">Important</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-group" style="margin-bottom: 16px;">
                        <label>Message Content</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Write announcement details..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">+ Publish Announcement</button>
                </form>
            </div>

            <!-- Published Announcements List Card -->
            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Published Announcements</h3>
                    <span class="badge badge-approved"><?= count($announcements) ?> Total</span>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Priority</th>
                                <th>Publish Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 24px;">No announcements published yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                    <tr>
                                        <td><span class="cell-secondary-text">ANN-<?= (int)$ann['id'] ?></span></td>
                                        <td>
                                            <div class="cell-stacked">
                                                <span class="cell-primary-text"><?= $escape($ann['title']) ?></span>
                                                <span class="cell-secondary-text" style="max-width: 300px;"><?= $escape($ann['message']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-blue"><?= $escape(ucfirst($ann['target_audience'])) ?></span></td>
                                        <td>
                                            <span class="badge <?= $ann['priority'] === 'urgent' || $ann['priority'] === 'high' ? 'badge-rejected' : 'badge-approved' ?>">
                                                <?= $escape(ucfirst($ann['priority'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $escape($ann['created_at']) ?></td>
                                        <td>
                                            <form method="post" action="announcement_handler.php">
                                                <input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="announcement_id" value="<?= (int)$ann['id'] ?>">
                                                <button type="submit" class="btn btn-reject btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
