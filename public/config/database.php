<?php
// config/database.php
// Lanka Renters Admin Dashboard - Database Connection & Data Provider Helper

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lanka_renters');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Fallback gracefully to dummy mode if DB connection fails
            return null;
        }
    }
    return $pdo;
}

// Sri Lankan Districts List (All 25 Districts)
function getSriLankanDistricts() {
    return [
        "Ampara", "Anuradhapura", "Badulla", "Batticaloa", "Colombo", 
        "Galle", "Gampaha", "Hambantota", "Jaffna", "Kalutara", 
        "Kandy", "Kegalle", "Kilinochchi", "Kurunegala", "Mannar", 
        "Matale", "Matara", "Monaragala", "Mullaitivu", "Nuwara Eliya", 
        "Polonnaruwa", "Puttalam", "Ratnapura", "Trincomalee", "Vavuniya"
    ];
}

// Vehicle Types List
function getVehicleTypes() {
    return [
        "SUV (6 Seater)",
        "Car (4 Seater)",
        "Minivan (6 Seater)",
        "Van (8 Seater)"
    ];
}

// Security Helper: Protect Admin Access
function requireAdminLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Sanitization Helper
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
