<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/helpers/Database.php';
require_once dirname(__DIR__, 2) . '/app/models/Notification.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: reports.php?sent=0');
    exit;
}

$type = (string)($_POST['report_type'] ?? '');
$recipientType = (string)($_POST['recipient_type'] ?? 'specific');
$from = trim((string)($_POST['from'] ?? ''));
$to = trim((string)($_POST['to'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$db = Database::getInstance()->getConnection();
$recipients = [];

if (!in_array($type, ['payments', 'trips', 'role_summary'], true)) {
    header('Location: reports.php?delivery_error=invalid');
    exit;
}

if ($type !== 'role_summary' && $recipientType !== 'specific') {
    header('Location: reports.php?delivery_error=private');
    exit;
}

if ($type !== 'role_summary' && $recipientType === 'specific') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($userId !== false) {
        $stmt = $db->prepare("SELECT id,name,role FROM users WHERE id=:id AND status='active' AND role IN ('customer','owner','driver') LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $recipients[] = $row;
    }
    if ($row && $type === 'payments' && $row['role'] === 'driver') {
        header('Location: reports.php?delivery_error=driver_payment');
        exit;
    }
} elseif ($type === 'role_summary' && in_array($recipientType, ['customer', 'owner', 'driver'], true)) {
    $stmt = $db->prepare("SELECT id,name,role FROM users WHERE status='active' AND role=:role ORDER BY name");
    $stmt->execute(['role' => $recipientType]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($recipients === []) {
    header('Location: reports.php?sent=0');
    exit;
}

$label = $type === 'payments' ? 'Payment Report' : ($type === 'trips' ? 'Trip / Rental Report' : 'Role Summary Report');
$range = ($from !== '' || $to !== '') ? ' for ' . ($from !== '' ? $from : 'the beginning') . ' to ' . ($to !== '' ? $to : 'today') : '';
$notification = new Notification();
foreach ($recipients as $recipient) {
    $message = $type === 'role_summary'
        ? 'A new aggregate ' . $label . $range . ' is available in your signed-in workspace.'
        : 'A private ' . $label . $range . ' is available in your signed-in workspace.';
    $notification->create((int)$recipient['id'], $label . ' available', $message, $type === 'payments' ? 'payment' : 'booking');
}

header('Location: reports.php?sent=' . count($recipients));
exit;
