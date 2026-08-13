<?php
// config/database.php
// Lanka Renters Admin Dashboard - Database Connection & Data Provider Helper

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'lanka_renters');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        // Try utilizing the central Database helper if available
        $centralDbFile = dirname(__DIR__, 2) . '/app/helpers/Database.php';
        if (file_exists($centralDbFile)) {
            require_once $centralDbFile;
            try {
                $pdo = Database::getInstance()->getConnection();
                if ($pdo !== null) {
                    return $pdo;
                }
            } catch (Throwable $e) {
                // Fall back to direct PDO connection below if central helper throws
            }
        }

        try {
            $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $dbname = defined('DB_NAME') ? DB_NAME : 'lanka_renters';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
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

// Announcement Types List
function getAnnouncementTypes() {
    return [
        "Maintenance",
        "System Update",
        "Important Notice",
        "Payment Notice",
        "Booking Notice",
        "Emergency",
        "General"
    ];
}

// Announcement Target Audiences List
function getTargetAudiences() {
    return [
        "All Users",
        "Customers",
        "Vehicle Owners",
        "Drivers"
    ];
}

// Announcement Priorities List
function getAnnouncementPriorities() {
    return [
        "Normal",
        "Important",
        "High",
        "Urgent"
    ];
}

// Security Helper: Protect Admin Access using canonical AuthHelper
function requireAdminLogin() {
    AuthHelper::startSession();
    AuthHelper::requireRole('admin');
}

// Sanitization Helper
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
