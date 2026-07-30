<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/AuthController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Redirect to dashboard if driver is already logged in
if (AuthHelper::isLoggedIn()) {
    $user = AuthHelper::getCurrentUser();
    if (($user['role'] ?? '') === 'driver') {
        header("Location: dashboard.php");
        exit();
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $authController = new AuthController();
    $result = $authController->login($email, $password);
    
    if ($result['success']) {
        $user = AuthHelper::getCurrentUser();
        if (($user['role'] ?? '') !== 'driver') {
            // Restrict access only to driver role
            AuthHelper::logout();
            $error = "Access denied. Only registered drivers can sign in here.";
        } else {
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login - Lanka Renters</title>
    <link rel="stylesheet" href="../assets/css/driver.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo">Lanka Renters</h1>
                <p class="auth-subtitle">Driver Portal Sign In</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink: 0;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Driver Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-submit">Sign In to Portal</button>
            </form>
        </div>
    </div>
</body>
</html>
