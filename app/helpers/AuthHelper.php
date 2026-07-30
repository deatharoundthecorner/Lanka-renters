<?php

/**
 * Lanka Renters - Authentication and Session Management Helper
 * Provides static methods for managing sessions, user logins, logouts, 
 * authentication status, and role-based access control.
 */
class AuthHelper {
    
    /**
     * Starts the PHP session safely if it has not already been started.
     * Sets security configuration parameters for session cookies.
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Determine if HTTPS is active to secure cookie delivery
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                      || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
            
            // Set secure session cookie configurations
            session_set_cookie_params([
                'lifetime' => 0,             // Expires when browser closes
                'path'     => '/',           // Accessible across entire domain
                'domain'   => '',            // Active domain
                'secure'   => $secure,       // True only if HTTPS is enabled
                'httponly' => true,         // Mitigates XSS cookie theft
                'samesite' => 'Lax'          // CSRF protection
            ]);
            
            session_start();
        }
    }

    /**
     * Logs the user in by saving user data in the session.
     * Regenerates the session ID to protect against Session Fixation.
     * 
     * @param array $user Associative array of user data (id, name, email, role, etc.)
     */
    public static function login($user) {
        self::startSession();
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    /**
     * Logs the current user out by clearing session variables, 
     * deleting the session cookie, and destroying the session.
     */
    public static function logout() {
        self::startSession();
        
        // Unset all session variables
        $_SESSION = [];

        // Clear session cookies in the browser
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy the session context on the server
        session_destroy();
    }

    /**
     * Checks whether a user session exists and is active.
     * 
     * @return bool True if logged in, false otherwise
     */
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Returns the active user data stored in the session.
     * 
     * @return array|null User details array, or null if guest
     */
    public static function getCurrentUser() {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Checks if a user is authenticated. 
     * If they are not, redirects them immediately to the login page.
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::redirect('login.php');
        }
    }

    /**
     * Enforces role-based permissions access.
     * If the authenticated user does not have the specified role:
     * - Re-routes them to their role-specific dashboard (if logged in under a different role)
     * - Re-routes them to the login page if session is missing.
     * 
     * @param string $role The required role ('customer', 'owner', 'driver', 'admin')
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        $user = self::getCurrentUser();
        $userRole = $user['role'] ?? '';

        if ($userRole !== $role) {
            // Redirect the user to their appropriate landing dashboard based on their actual role
            switch ($userRole) {
                case 'admin':
                    self::redirect('admin/dashboard.php');
                    break;
                case 'owner':
                    self::redirect('owner/dashboard.php');
                    break;
                case 'driver':
                    self::redirect('driver/dashboard.php');
                    break;
                case 'customer':
                    self::redirect('customer/dashboard.php');
                    break;
                default:
                    // If the role is invalid/unregistered, force a logout and redirect
                    self::logout();
                    self::redirect('login.php');
                    break;
            }
        }
    }

    /**
     * Helper method to handle safe redirections regardless of whether the project is 
     * hosted on a server root, local development folder, or a subfolder subdirectory.
     * 
     * @param string $path Target relative path to redirect to
     */
    private static function redirect($path) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = '/';
        
        // Find if the project contains a /public/ folder segments inside the URL to resolve subfolders
        if (strpos($script, '/public/') !== false) {
            $base = substr($script, 0, strpos($script, '/public/') + 8);
        }
        
        $targetUrl = $base . ltrim($path, '/');
        
        header("Location: " . $targetUrl);
        exit();
    }
}
