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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $authController = new AuthController();
    $result = $authController->login($email, $password);
    
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
    <title>Sign In - Lanka Renters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/driver.css">
    <style>
        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo" style="color: var(--primary);">Lanka Renters</h1>
                <p class="auth-subtitle">Unified Sign In Portal</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px; border-radius: 4px; background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 14px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-blue" style="width: 100%; padding: 12px; margin-top: 10px;">Sign In</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</body>
</html>
