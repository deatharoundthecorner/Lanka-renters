<?php

require_once dirname(__DIR__) . '/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/models/User.php';

class AdminUserController
{
    private User $userModel;
    private const ROLES = ['customer', 'owner', 'driver', 'admin'];
    private const STATUSES = ['active', 'inactive', 'suspended', 'pending'];

    public function __construct()
    {
        AuthHelper::requireRole('admin');
        $this->userModel = new User();
    }

    public function listUsers(): array
    {
        return $this->userModel->getAllManagedUsers();
    }

    public function findUser(int $userId): ?array
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }

    public function createUser(array $data): array
    {
        $validation = $this->validate($data, true);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            if ($this->userModel->findByEmail($validation['data']['email'])) {
                return ['success' => false, 'error' => 'A user with that email already exists.'];
            }
            $userId = $this->userModel->createManagedUser($validation['data']);
            return ['success' => true, 'message' => 'Platform user created successfully.', 'user_id' => $userId];
        } catch (Throwable $exception) {
            $this->log('create', $exception);
            return ['success' => false, 'error' => 'The user could not be created. Check that the email is unique and try again.'];
        }
    }

    public function updateUser(int $userId, array $data): array
    {
        $validation = $this->validate($data, false);
        if (!$validation['success']) {
            return $validation;
        }

        $currentAdmin = AuthHelper::getCurrentUser();
        if ((int)($currentAdmin['id'] ?? 0) === $userId) {
            $existing = $this->userModel->findById($userId);
            if (!$existing) {
                return ['success' => false, 'error' => 'User not found.'];
            }
            if ($validation['data']['role'] !== 'admin' || $validation['data']['status'] !== 'active') {
                return ['success' => false, 'error' => 'You cannot remove your own active Admin access.'];
            }
        }

        try {
            $emailOwner = $this->userModel->findByEmail($validation['data']['email']);
            if ($emailOwner && (int)$emailOwner['id'] !== $userId) {
                return ['success' => false, 'error' => 'A user with that email already exists.'];
            }
            if (!$this->userModel->updateManagedUser($userId, $validation['data'])) {
                return ['success' => false, 'error' => 'User not found.'];
            }
            return ['success' => true, 'message' => 'Platform user updated successfully.'];
        } catch (Throwable $exception) {
            $this->log('update', $exception);
            return ['success' => false, 'error' => 'The user could not be updated because related records may depend on the current role.'];
        }
    }

    public function deleteUser(int $userId): array
    {
        $currentAdmin = AuthHelper::getCurrentUser();
        if ((int)($currentAdmin['id'] ?? 0) === $userId) {
            return ['success' => false, 'error' => 'You cannot delete your own Admin account.'];
        }

        try {
            if (!$this->userModel->findById($userId)) {
                return ['success' => false, 'error' => 'User not found.'];
            }
            if (!$this->userModel->deleteManagedUser($userId)) {
                return ['success' => false, 'error' => 'User could not be deleted.'];
            }
            return ['success' => true, 'message' => 'Platform user deleted successfully.'];
        } catch (Throwable $exception) {
            $this->log('delete', $exception);
            return ['success' => false, 'error' => 'This user cannot be deleted because rental or audit records depend on the account.'];
        }
    }

    private function validate(array $data, bool $requirePassword): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $role = (string)($data['role'] ?? '');
        $status = (string)($data['status'] ?? 'active');
        $password = (string)($data['password'] ?? '');

        if (strlen($name) < 2) {
            return ['success' => false, 'error' => 'Enter a valid user name.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Enter a valid email address.'];
        }
        if ($phone === '') {
            return ['success' => false, 'error' => 'Enter a phone number.'];
        }
        if (!in_array($role, self::ROLES, true)) {
            return ['success' => false, 'error' => 'Select a valid role.'];
        }
        if (!in_array($status, self::STATUSES, true)) {
            return ['success' => false, 'error' => 'Select a valid account status.'];
        }
        if ($requirePassword && strlen($password) < 8) {
            return ['success' => false, 'error' => 'Temporary password must contain at least 8 characters.'];
        }

        return ['success' => true, 'data' => compact('name', 'email', 'phone', 'role', 'status', 'password')];
    }

    private function log(string $operation, Throwable $exception): void
    {
        error_log(sprintf('Admin user %s failed [%s]: %s', $operation, get_class($exception), $exception->getMessage()));
    }
}
