<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/ProfileController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$profileController = new ProfileController();

$successMessage = '';
$errorMessage = '';

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $result = $profileController->updateProfile($_POST);
    if ($result['success']) {
        $successMessage = $result['message'];
    } else {
        $errorMessage = $result['error'];
    }
}

// Fetch Owner's Profile from Database
$dataResult = $profileController->getProfile();
$profile = $dataResult['success'] ? $dataResult['profile'] : [];
$csrfToken = AuthHelper::getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Profile - LankaRenters</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
    <style>
        .alert-banner {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
            <section class="profile-card-wrapper">
                <article class="profile-card">
                    <h1>Profile</h1>
                    <p>Manage your owner account details.</p>
                </article>
            </section>

            <?php if ($successMessage): ?>
                <div class="alert-banner alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert-banner alert-error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <section class="vehicle-form-section">
                <div class="form-card">
                    <div class="form-card-header">
                        <h2>Account details</h2>
                        <p>Your name and phone are shown to customers and admins.</p>
                    </div>

                    <form class="vehicle-form" method="post" action="profile.php">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="form-row">
                            <label class="form-field">
                                <span>Name</span>
                                <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
                            </label>
                            <label class="form-field">
                                <span>Phone</span>
                                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" required>
                            </label>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Email</span>
                                <input type="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled>
                            </label>
                            <label class="form-field">
                                <span>Owner type</span>
                                <select name="owner_type" required>
                                    <option value="individual" <?= ($profile['owner_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Individual</option>
                                    <option value="company" <?= ($profile['owner_type'] ?? '') === 'company' ? 'selected' : '' ?>>Company</option>
                                </select>
                            </label>
                        </div>

                        <div class="form-card-header">
                            <h2>Payout details</h2>
                            <p>Used for settlement transfers.</p>
                        </div>

                        <div class="form-row">
                            <label class="form-field">
                                <span>Bank name</span>
                                <input type="text" name="bank_name" value="<?= htmlspecialchars($profile['bank_name'] ?? '') ?>">
                            </label>
                            <label class="form-field">
                                <span>Bank account number</span>
                                <input type="text" name="bank_account_no" value="<?= htmlspecialchars($profile['bank_account_no'] ?? '') ?>">
                            </label>
                        </div>
                        <div class="form-row">
                            <label class="form-field">
                                <span>Bank branch</span>
                                <input type="text" name="bank_branch" value="<?= htmlspecialchars($profile['bank_branch'] ?? '') ?>">
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</div>

</body>
</html>
