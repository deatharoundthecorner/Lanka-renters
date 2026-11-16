<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AdminUserController.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    header('Location: users.php');
    exit;
}

$redirect = 'users.php';
if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['admin_user_flash'] = ['success' => false, 'message' => 'Security verification failed. Please try again.'];
    header('Location: ' . $redirect);
    exit;
}

$controller = new AdminUserController();
$action = (string)($_POST['action'] ?? '');
if ($action === 'create') {
    $result = $controller->createUser($_POST);
} elseif ($action === 'update') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $result = $controller->updateUser($userId, $_POST);
    if (!$result['success'] && $userId > 0) {
        $redirect .= '?edit_id=' . $userId;
    }
} elseif ($action === 'delete') {
    $result = $controller->deleteUser((int)($_POST['user_id'] ?? 0));
} else {
    $result = ['success' => false, 'error' => 'Unsupported user action.'];
}

$_SESSION['admin_user_flash'] = [
    'success' => (bool)($result['success'] ?? false),
    'message' => (string)($result['message'] ?? $result['error'] ?? 'The request could not be completed.'),
];
header('Location: ' . $redirect, true, 303);
exit;
