<?php
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';

// Unified Sign In Page

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
    
    if ($result['success']) {
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
    <title>Sign In - Lanka Renters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-blue: #0B3A82;
            --primary-blue: #1357C8;
            --light-blue: #DBEAFE;
            --bg-color: #F8FAFC;
            --white-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        /* Vehicle Themed Background Graphics */
        .bg-decor {
            position: absolute;
            z-index: 1;
            color: var(--light-blue);
            opacity: 0.25;
            pointer-events: none;
        }
        .decor-left {
            bottom: -50px;
            left: -50px;
            width: 400px;
            height: 400px;
        }
        .decor-right {
            top: -50px;
            right: -50px;
            width: 400px;
            height: 400px;
        }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background-color: var(--white-card);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(11, 58, 130, 0.08), 0 10px 10px -5px rgba(11, 58, 130, 0.04);
            border: 1px solid rgba(11, 58, 130, 0.05);
            padding: 40px;
            width: 100%;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark-blue);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            outline: none;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(19, 87, 200, 0.12);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary-blue);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(19, 87, 200, 0.2);
        }

        .btn-submit:hover {
            background-color: var(--dark-blue);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(11, 58, 130, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-box {
            background-color: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #EF4444;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-box svg {
            flex-shrink: 0;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .footer-links a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--dark-blue);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Background Decors (Vehicle themed SVGs) -->
    <svg class="bg-decor decor-left" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 50h80M20 50l15-20h30l15 20M30 50a10 10 0 1020 0M60 50a10 10 0 1020 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <svg class="bg-decor decor-right" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5 60h90M15 60l10-25h50l10 25M25 60a12 12 0 1024 0M60 60a12 12 0 1024 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <h1 class="logo-text">Lanka Renters</h1>
                <p class="subtitle">Unified Sign In Portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-box">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input class="form-input" type="email" id="email" name="email" required placeholder="name@example.com" autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-input" type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="footer-links">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

</body>
</html>

