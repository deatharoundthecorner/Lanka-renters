<?php

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';
require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/helpers/Database.php';

/**
 * Lanka Renters - Authentication Controller
 * Orchestrates user registration, logins, and logouts by validating inputs,
 * querying models, starting sessions, and handling role-based routing.
 */
class AuthController {
    // Hold instance of User model
    private $userModel;

    /**
     * Constructor initializes the User model.
     */
    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Handles new user registration.
     * Performs validations, duplicates check, registers the user,
     * logs the user in automatically, and redirects them to their dashboard.
     * 
     * @param array $data Input form submission array
     * @return array Associative array status (success, error)
     */
    public function register($data) {
        // 1. Validate required fields
        $requiredFields = ['name', 'email', 'phone', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => ucfirst($field) . " is required."
                ];
            }
        }

        // Validate that role is valid for public registration (only customer or owner allowed)
        $allowedRoles = ['customer', 'owner'];
        if (!in_array($data['role'], $allowedRoles)) {
            return [
                'success' => false,
                'error' => "Invalid registration role selected."
            ];
        }

        // 2. Check if email already exists
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser) {
            return [
                'success' => false,
                'error' => "An account with this email already exists."
            ];
        }

        // Get database instance to handle transaction
        $db = Database::getInstance()->getConnection();

        try {
            // Begin transaction
            $db->beginTransaction();

            // 3. Create user account (the User model handles password hashing internally)
            $userId = $this->userModel->create([
                'name'     => trim($data['name']),
                'email'    => trim($data['email']),
                'password' => $data['password'],
                'phone'    => trim($data['phone']),
                'role'     => $data['role'],
                'status'   => 'active'
            ]);

            if (!$userId) {
                throw new Exception("Failed to create user account record.");
            }

            // 4. Create role-specific profile records
            if ($data['role'] === 'customer') {
                $customerModel = new Customer();
                $customerProfileId = $customerModel->create($userId);
                if (!$customerProfileId) {
                    throw new Exception("Failed to create customer profile record.");
                }
            } elseif ($data['role'] === 'owner') {
                $ownerModel = new VehicleOwner();
                $ownerProfileId = $ownerModel->create($userId);
                if (!$ownerProfileId) {
                    throw new Exception("Failed to create vehicle owner profile record.");
                }
            }

            // Commit transaction on success of all operations
            $db->commit();

            // Retrieve the newly created user record to initiate session
            $user = $this->userModel->findById($userId);
            if ($user) {
                // 5. Create session using AuthHelper
                AuthHelper::login($user);

                // 6. Redirect based on role
                $this->redirectByRole($user['role']);
            }

            return ['success' => true];

        } catch (Exception $e) {
            // Roll back all changes if any error occurs
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'success' => false,
                'error' => "Registration failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Handles user login credentials verification.
     * Logs the user in on credentials match and redirects based on role.
     * 
     * @param string $email User-entered email address
     * @param string $password User-entered plaintext password
     * @return array Associative array status (success, error)
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'error' => "Email and password are required."
            ];
        }

        // 1. Find user by email
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return [
                'success' => false,
                'error' => "Invalid email or password."
            ];
        }

        // 2. Verify password
        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => "Invalid email or password."
            ];
        }

        // 3. Check user status
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'error' => "Your account is " . $user['status'] . ". Please contact support."
            ];
        }

        // 4. Create session using AuthHelper
        AuthHelper::login($user);

        // 5. Redirect according to role
        $this->redirectByRole($user['role']);

        return ['success' => true];
    }

    /**
     * Handles user logout by destroying session data.
     * Redirects back to the login screen.
     */
    public function logout() {
        // 1. Destroy session using AuthHelper
        AuthHelper::logout();

        // 2. Redirect to login page
        $this->redirect('login.php');
    }

    /**
     * Directs the user to their role-specific dashboard page.
     * 
     * @param string $role User role
     */
    private function redirectByRole($role) {
        switch ($role) {
            case 'admin':
                $this->redirect('admin/dashboard.php');
                break;
            case 'owner':
                $this->redirect('owner/dashboard.php');
                break;
            case 'driver':
                $this->redirect('driver/dashboard.php');
                break;
            case 'customer':
                $this->redirect('customer/dashboard.php');
                break;
            default:
                $this->redirect('index.php');
                break;
        }
    }

    /**
     * Safe redirection helper. Supports subfolder-scoped routes.
     * 
     * @param string $path Target relative path
     */
    private function redirect($path) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = '/';
        
        if (strpos($script, '/public/') !== false) {
            $base = substr($script, 0, strpos($script, '/public/') + 8);
        }
        
        $targetUrl = $base . ltrim($path, '/');
        header("Location: " . $targetUrl);
        exit();
    }
}
