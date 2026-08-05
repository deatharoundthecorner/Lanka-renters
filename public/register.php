<?php
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Redirect if already logged in
if (AuthHelper::isLoggedIn()) {
    $user = AuthHelper::getCurrentUser();
    switch ($user['role'] ?? '') {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'owner':
            header("Location: owner/dashboard.php");
            break;
        case 'driver':
            header("Location: driver/dashboard.php");
            break;
        case 'customer':
            header("Location: customer/dashboard.php");
            break;
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $result = $authController->register($_POST);
    
    if (!$result['success']) {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lanka Renters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/driver.css">
    <style>
        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .form-select {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background-color: #ffffff;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.2s;
        }
        .form-select:focus {
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="auth-container" style="max-width: 500px; padding: 40px 20px;">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo" style="color: var(--primary);">Lanka Renters</h1>
                <p class="auth-subtitle">Create a New Account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px; border-radius: 4px; background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 14px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input class="form-control" type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input class="form-control" type="text" id="phone" name="phone" required placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required placeholder="Create a password">
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Register As</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="customer" <?php echo (($_POST['role'] ?? '') === 'customer') ? 'selected' : ''; ?>>Customer (Renting Vehicles)</option>
                        <option value="owner" <?php echo (($_POST['role'] ?? '') === 'owner') ? 'selected' : ''; ?>>Vehicle Owner (Listing Vehicles)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-blue" style="width: 100%; padding: 12px; margin-top: 10px;">Register Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign In here</a>
            </div>
        </div>
    </div>
</body>
</html>
