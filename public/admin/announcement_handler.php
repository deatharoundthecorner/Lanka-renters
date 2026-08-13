<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Announcement.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: announcements.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_ann_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: announcements.php');
    exit;
}

$action = $_POST['action'] ?? 'create';

try {
    $model = new Announcement();
    if ($action === 'delete') {
        $id = (int)($_POST['announcement_id'] ?? 0);
        if ($id > 0) {
            $model->delete($id);
            $_SESSION['admin_ann_flash'] = ['message' => 'Announcement deleted.'];
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($title) || empty($message)) {
            $_SESSION['admin_ann_flash'] = ['message' => 'Title and message are required.'];
            header('Location: announcements.php');
            exit;
        }

        $admin = AuthHelper::getCurrentUser();

        $model->create([
            'title' => $title,
            'message' => $message,
            'announcement_type' => $_POST['announcement_type'] ?? 'general',
            'target_audience' => $_POST['target_audience'] ?? 'all',
            'priority' => $_POST['priority'] ?? 'normal',
            'publish_date' => $_POST['publish_date'] ?? date('Y-m-d'),
            'publish_time' => $_POST['publish_time'] ?? date('H:i:s'),
            'expiry_date' => $_POST['expiry_date'] ?? null,
            'status' => 'published',
            'created_by' => $admin['id'] ?? null
        ]);

        $_SESSION['admin_ann_flash'] = ['message' => 'Announcement created and published successfully.'];
    }
} catch (Throwable $e) {
    $_SESSION['admin_ann_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: announcements.php');
exit;
