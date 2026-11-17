<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AdminUserController.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = 'Users';
$flash = $_SESSION['admin_user_flash'] ?? null;
unset($_SESSION['admin_user_flash']);
$users = [];
$editUser = null;
$databaseError = false;

try {
    $controller = new AdminUserController();
    $users = $controller->listUsers();
    $editId = (int)($_GET['edit_id'] ?? 0);
    if ($editId > 0) {
        $editUser = $controller->findUser($editId);
    }
} catch (Throwable $exception) {
    error_log(sprintf('Admin user list failed [%s]: %s', get_class($exception), $exception->getMessage()));
    $databaseError = true;
}

$currentAdmin = AuthHelper::getCurrentUser();
$csrfToken = AuthHelper::getCsrfToken();
$escape = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$roles = ['customer' => 'Customer', 'owner' => 'Vehicle Owner', 'driver' => 'Driver', 'admin' => 'Admin'];
$statuses = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>
        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Platform Users</h1>
                <p class="page-subtitle">Create and manage authenticated Customer, Owner, Driver, and Admin accounts.</p>
            </div>

            <?php if (is_array($flash)): ?>
                <div class="alert-box" role="alert" style="margin-bottom: 16px;">
                    <?= $escape($flash['message'] ?? 'Action completed.') ?>
                </div>
            <?php endif; ?>

            <?php if ($databaseError): ?>
                <div class="card"><p>User records are temporarily unavailable. Please try again later.</p></div>
            <?php else: ?>
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-header-clean"><h3 class="card-title-text">Create Platform User</h3></div>
                    <form method="post" action="user_handler.php">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="action" value="create">
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px;">
                            <div class="filter-group"><label for="create-name">Name</label><input id="create-name" class="form-control" name="name" required></div>
                            <div class="filter-group"><label for="create-email">Email</label><input id="create-email" class="form-control" type="email" name="email" required></div>
                            <div class="filter-group"><label for="create-phone">Phone</label><input id="create-phone" class="form-control" name="phone" required></div>
                            <div class="filter-group"><label for="create-password">Temporary Password</label><input id="create-password" class="form-control" type="password" name="password" minlength="8" required></div>
                            <div class="filter-group"><label for="create-role">Role</label><select id="create-role" class="form-control" name="role" required><?php foreach($roles as $value=>$label):?><option value="<?= $escape($value) ?>"><?= $escape($label) ?></option><?php endforeach;?></select></div>
                            <div class="filter-group"><label for="create-status">Status</label><select id="create-status" class="form-control" name="status" required><?php foreach($statuses as $value=>$label):?><option value="<?= $escape($value) ?>"><?= $escape($label) ?></option><?php endforeach;?></select></div>
                        </div>
                        <button class="btn btn-primary" type="submit">Create User</button>
                    </form>
                </div>

                <?php if (is_array($editUser)): ?>
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header-clean"><h3 class="card-title-text">Edit User #<?= (int)$editUser['id'] ?></h3><a class="btn btn-secondary btn-sm" href="users.php">Cancel</a></div>
                        <form method="post" action="user_handler.php">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
                            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px;">
                                <div class="filter-group"><label for="edit-name">Name</label><input id="edit-name" class="form-control" name="name" value="<?= $escape($editUser['name']) ?>" required></div>
                                <div class="filter-group"><label for="edit-email">Email</label><input id="edit-email" class="form-control" type="email" name="email" value="<?= $escape($editUser['email']) ?>" required></div>
                                <div class="filter-group"><label for="edit-phone">Phone</label><input id="edit-phone" class="form-control" name="phone" value="<?= $escape($editUser['phone']) ?>" required></div>
                                <div class="filter-group"><label for="edit-role">Role</label><select id="edit-role" class="form-control" name="role" required><?php foreach($roles as $value=>$label):?><option value="<?= $escape($value) ?>" <?= $editUser['role']===$value?'selected':'' ?>><?= $escape($label) ?></option><?php endforeach;?></select></div>
                                <div class="filter-group"><label for="edit-status">Status</label><select id="edit-status" class="form-control" name="status" required><?php foreach($statuses as $value=>$label):?><option value="<?= $escape($value) ?>" <?= $editUser['status']===$value?'selected':'' ?>><?= $escape($label) ?></option><?php endforeach;?></select></div>
                            </div>
                            <button class="btn btn-primary" type="submit">Update User</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header-clean"><h3 class="card-title-text">Registered Users</h3><span class="badge badge-approved"><?= count($users) ?> Total</span></div>
                    <div class="lr-table-wrap table-responsive" style="overflow-x: auto;">
                        <table class="custom-table" style="min-width: 680px; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 75px; white-space: nowrap;">ID</th>
                                    <th style="min-width: 130px;">User</th>
                                    <th style="min-width: 180px;">Contact</th>
                                    <th style="width: 90px;">Role</th>
                                    <th style="width: 90px;">Status</th>
                                    <th style="width: 105px; white-space: nowrap;">Registered</th>
                                    <th style="width: 115px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($users === []): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="lr-empty-state">
                                            <div class="lr-empty-icon">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                            </div>
                                            <h3>No platform users found</h3>
                                            <p>No user accounts matched the system query.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: foreach ($users as $user): ?>
                                <tr>
                                    <td style="font-weight: 600; font-family: monospace; white-space: nowrap;">USR-<?= (int)$user['id'] ?></td>
                                    <td><span class="cell-primary-text"><?= $escape($user['name']) ?></span></td>
                                    <td>
                                        <div class="cell-stacked" style="word-break: break-word; overflow-wrap: anywhere;">
                                            <span class="cell-secondary-text" style="color: var(--text-main); font-weight: 500;"><?= $escape($user['email']) ?></span>
                                            <span class="cell-secondary-text"><?= $escape($user['phone']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-blue"><?= $escape($roles[$user['role']] ?? ucfirst($user['role'])) ?></span></td>
                                    <td><span class="badge <?= $user['status']==='active'?'badge-approved':'badge-pending' ?>"><?= $escape($statuses[$user['status']] ?? ucfirst($user['status'])) ?></span></td>
                                    <td style="white-space: nowrap; font-size: 12px; color: var(--text-muted);"><?= $escape(date('Y-m-d', strtotime($user['created_at']))) ?></td>
                                    <td style="text-align: right;">
                                        <div class="btn-group" style="justify-content: flex-end;">
                                            <a class="btn btn-primary btn-sm" href="users.php?edit_id=<?= (int)$user['id'] ?>">Edit</a>
                                            <?php if ((int)$user['id'] !== (int)($currentAdmin['id'] ?? 0)): ?>
                                                <form method="post" action="user_handler.php" onsubmit="return confirm('Delete this platform user? Related rental records may prevent deletion.');">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                    <button class="btn btn-reject btn-sm" type="submit">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
