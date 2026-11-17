<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/VehicleOwner.php';
require_once dirname(__DIR__) . '/models/User.php';

/**
 * Lanka Renters - Profile Controller
 * Manages the authenticated Vehicle Owner's contact details and payout info.
 *
 * Note: `users` and `vehicle_owners` currently only carry name/phone (users)
 * and owner_type/bank_name/bank_account_no/bank_branch (vehicle_owners).
 * There's no business_name/address/profile_photo column yet - if the team
 * wants those, they need a migration first, then add the field to
 * VehicleOwner::update()'s $allowedFields before wiring it up here.
 */
class ProfileController {
    private $ownerModel;
    private $userModel;

    public function __construct() {
        $this->ownerModel = new VehicleOwner();
        $this->userModel = new User();
    }

    /**
     * Resolves the authenticated Vehicle Owner profile from the session.
     * Enforces strict role-based access control.
     *
     * @return array Returns array with 'user' and 'owner' data or throws Exception
     */
    private function getAuthenticatedOwner() {
        AuthHelper::requireRole('owner');
        $user = AuthHelper::getCurrentUser();
        if (!$user) {
            throw new Exception("Unauthorized session access.");
        }

        $ownerProfile = $this->ownerModel->findByUserId($user['id']);
        if (!$ownerProfile) {
            throw new Exception("Vehicle Owner profile not found for this account.");
        }

        return [
            'user'  => $user,
            'owner' => $ownerProfile
        ];
    }

    /**
     * Fetches the current owner's combined profile (user + vehicle_owners fields).
     *
     * @return array
     */
    public function getProfile() {
        try {
            $auth = $this->getAuthenticatedOwner();
            $profile = array_merge($auth['user'], $auth['owner']);

            return [
                'success' => true,
                'profile' => $profile
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'profile' => null
            ];
        }
    }

    /**
     * Updates the owner's contact details (name, phone) and payout details
     * (owner_type, bank_name, bank_account_no, bank_branch).
     *
     * @param array $data Input POST data
     * @return array Status array
     */
    public function updateProfile($data) {
        try {
            $auth = $this->getAuthenticatedOwner();
            $userId = $auth['user']['id'];
            $ownerId = $auth['owner']['id'];

            // 1. CSRF Validation
            if (!AuthHelper::validateCsrfToken($data['csrf_token'] ?? '')) {
                return ['success' => false, 'error' => 'Security verification failed (Invalid CSRF token).'];
            }

            // 2. Server-side Field Validation
            $validation = $this->validateProfileInput($data);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // 3. Update users table (name, phone)
            $userUpdated = $this->userModel->updateContactInfo($userId, [
                'name'  => trim($data['name']),
                'phone' => trim($data['phone']),
            ]);

            // 4. Update vehicle_owners table (owner_type, bank details)
            $ownerUpdated = $this->ownerModel->update($ownerId, [
                'owner_type'      => $data['owner_type'],
                'bank_name'       => trim($data['bank_name'] ?? ''),
                'bank_account_no' => trim($data['bank_account_no'] ?? ''),
                'bank_branch'     => trim($data['bank_branch'] ?? ''),
            ]);

            if (!$userUpdated && !$ownerUpdated) {
                return ['success' => false, 'error' => 'Failed to update profile.'];
            }

            // Refresh the session copy of the user so the header/sidebar show new name immediately
            $refreshedUser = $this->userModel->findById($userId);
            if ($refreshedUser) {
                $_SESSION['user'] = $refreshedUser;
            }

            return ['success' => true, 'message' => 'Profile updated successfully!'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validates profile input fields.
     *
     * @param array $data
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateProfileInput($data) {
        if (empty(trim($data['name'] ?? ''))) {
            return ['valid' => false, 'error' => 'Name is required.'];
        }
        if (empty(trim($data['phone'] ?? ''))) {
            return ['valid' => false, 'error' => 'Phone number is required.'];
        }
        $allowedOwnerTypes = ['individual', 'company'];
        if (!in_array($data['owner_type'] ?? '', $allowedOwnerTypes, true)) {
            return ['valid' => false, 'error' => 'Invalid owner type selected.'];
        }
        return ['valid' => true, 'error' => null];
    }
}
