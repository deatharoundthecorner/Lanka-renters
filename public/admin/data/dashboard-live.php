<?php
require_once dirname(__DIR__, 3) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 3) . '/app/controllers/AdminDashboardController.php';
AuthHelper::startSession();
AuthHelper::requireRole('admin');
header('Content-Type: application/json; charset=utf-8');
try {
    $data = (new AdminDashboardController())->liveAnalytics($_GET['year'] ?? null);
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Admin dashboard analytics error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
