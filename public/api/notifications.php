<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Notification.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function notificationJsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function notificationPayload(Notification $model, int $userId): array
{
    $notifications = array_map(static function (array $notification): array {
        $timestamp = strtotime((string)$notification['created_at']);
        return [
            'id' => (int)$notification['id'],
            'title' => (string)$notification['title'],
            'message' => (string)$notification['message'],
            'type' => (string)$notification['notification_type'],
            'is_read' => (bool)$notification['is_read'],
            'created_at' => (string)$notification['created_at'],
            'display_time' => $timestamp ? date('M j, g:i A', $timestamp) : '',
        ];
    }, $model->getRecentByUserId($userId, 5));

    return [
        'success' => true,
        'unread_count' => $model->getUnreadCount($userId),
        'notifications' => $notifications,
    ];
}

try {
    AuthHelper::startSession();
    $user = AuthHelper::getCurrentUser();
    $userId = (int)($user['id'] ?? 0);
    $role = (string)($user['role'] ?? '');

    if ($userId <= 0 || !in_array($role, ['customer', 'owner', 'driver', 'admin'], true)) {
        notificationJsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        notificationJsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

    $model = new Notification();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!AuthHelper::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            notificationJsonResponse(['success' => false, 'message' => 'Security verification failed.'], 403);
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'mark_read') {
            $notificationId = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($notificationId === false || !$model->markAsRead((int)$notificationId, $userId)) {
                notificationJsonResponse(['success' => false, 'message' => 'Notification not found.'], 404);
            }
        } elseif ($action === 'mark_all') {
            $model->markAllAsRead($userId);
        } else {
            notificationJsonResponse(['success' => false, 'message' => 'Invalid notification action.'], 400);
        }
    }

    notificationJsonResponse(notificationPayload($model, $userId));
} catch (Throwable $error) {
    error_log('Notification API error: ' . $error->getMessage());
    notificationJsonResponse(['success' => false, 'message' => 'Notifications are temporarily unavailable.'], 500);
}
