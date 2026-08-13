<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/AdminIncidentManagement.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: incidents.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthHelper::validateCsrfToken($csrfToken)) {
    $_SESSION['admin_incident_flash'] = ['message' => 'CSRF verification failed.'];
    header('Location: incidents.php');
    exit;
}

$incidentId = (int)($_POST['incident_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($incidentId <= 0 || !in_array($status, ['reported', 'investigating', 'resolved'], true)) {
    $_SESSION['admin_incident_flash'] = ['message' => 'Invalid parameters.'];
    header('Location: incidents.php');
    exit;
}

try {
    $model = new AdminIncidentManagement();
    $model->updateStatus($incidentId, $status);
    $_SESSION['admin_incident_flash'] = ['message' => "Incident #$incidentId status updated to $status."];
} catch (Throwable $e) {
    $_SESSION['admin_incident_flash'] = ['message' => 'Error: ' . $e->getMessage()];
}

header('Location: incidents.php');
exit;
