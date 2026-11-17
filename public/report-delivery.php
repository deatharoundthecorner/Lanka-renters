<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/helpers/Database.php';
require_once dirname(__DIR__) . '/app/helpers/SimplePdf.php';

AuthHelper::startSession();
AuthHelper::requireLogin();
$user = AuthHelper::getCurrentUser();
$role = (string)($user['role'] ?? '');
if (!in_array($role, ['customer', 'owner', 'driver'], true)) {
    http_response_code(403);
    exit('Report delivery is not available for this account.');
}

$type = (string)($_GET['type'] ?? 'trips');
$type = in_array($type, ['payments', 'trips', 'role_summary'], true) ? $type : 'trips';
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$db = Database::getInstance()->getConnection();
$userId = (int)($user['id'] ?? 0);

if ($type === 'payments' && $role === 'driver') {
    http_response_code(403);
    exit('Payment report delivery is not available for driver accounts.');
}

if ($type === 'role_summary') {
    $sql = 'SELECT status, COUNT(*) total FROM bookings WHERE 1=1';
    $params = [];
    if ($from !== '') { $sql .= ' AND DATE(created_at) >= :from_date'; $params['from_date'] = $from; }
    if ($to !== '') { $sql .= ' AND DATE(created_at) <= :to_date'; $params['to_date'] = $to; }
    $stmt = $db->prepare($sql . ' GROUP BY status ORDER BY status');
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdf = new SimplePdf('Lanka Renters ' . ucfirst($role) . ' Role Summary');
    $pdf->text('Report period: ' . ($from !== '' ? $from : 'Beginning') . ' to ' . ($to !== '' ? $to : 'Today'), 36, 752, 9);
    $pdf->heading('Platform Activity Summary');
    $pdf->row(['Booking status', 'Total bookings'], [260, 160]);
    foreach ($rows as $row) $pdf->row([ucwords(str_replace('_', ' ', $row['status'])), (string)$row['total']], [260, 160]);
    $filename = 'lanka-renters-' . $role . '-summary-' . date('Y-m') . '.pdf';
} elseif ($type === 'payments') {
    if ($role === 'customer') {
        $scope = 'c.user_id = :user_id';
    } else {
        $scope = 'vo.user_id = :user_id';
    }
    $sql = "SELECT p.amount,p.payment_status,p.created_at,v.make,v.model FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN vehicles v ON v.id=b.vehicle_id JOIN customers c ON c.id=b.customer_id JOIN vehicle_owners vo ON vo.id=v.owner_id WHERE $scope";
    if ($from !== '') $sql .= ' AND DATE(p.created_at) >= :from_date';
    if ($to !== '') $sql .= ' AND DATE(p.created_at) <= :to_date';
    $sql .= ' ORDER BY p.created_at DESC';
    $params = ['user_id' => $userId];
    if ($from !== '') $params['from_date'] = $from;
    if ($to !== '') $params['to_date'] = $to;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdf = new SimplePdf('Lanka Renters Payment Report');
    $pdf->heading('Your Authorized Payment Activity');
    $pdf->row(['Vehicle', 'Amount', 'Status', 'Created'], [180, 110, 110, 120]);
    foreach ($rows as $row) $pdf->row([$row['make'] . ' ' . $row['model'], 'LKR ' . number_format((float)$row['amount'], 2), $row['payment_status'], substr($row['created_at'], 0, 10)], [180, 110, 110, 120]);
    $filename = 'lanka-renters-payment-report-' . date('Y-m') . '.pdf';
} else {
    $scope = $role === 'customer' ? 'c.user_id = :user_id' : ($role === 'owner' ? 'vo.user_id = :user_id' : 'd.user_id = :user_id');
    $sql = "SELECT b.start_date,b.end_date,b.status,v.make,v.model FROM bookings b JOIN vehicles v ON v.id=b.vehicle_id JOIN customers c ON c.id=b.customer_id JOIN vehicle_owners vo ON vo.id=v.owner_id LEFT JOIN drivers d ON d.id=b.driver_id WHERE $scope";
    if ($from !== '') $sql .= ' AND DATE(b.created_at) >= :from_date';
    if ($to !== '') $sql .= ' AND DATE(b.created_at) <= :to_date';
    $sql .= ' ORDER BY b.created_at DESC';
    $params = ['user_id' => $userId];
    if ($from !== '') $params['from_date'] = $from;
    if ($to !== '') $params['to_date'] = $to;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdf = new SimplePdf('Lanka Renters Trip / Rental Report');
    $pdf->heading('Your Authorized Trip Activity');
    $pdf->row(['Vehicle', 'Dates', 'Status'], [200, 200, 120]);
    foreach ($rows as $row) $pdf->row([$row['make'] . ' ' . $row['model'], substr($row['start_date'], 0, 10) . ' to ' . substr($row['end_date'], 0, 10), $row['status']], [200, 200, 120]);
    $filename = 'lanka-renters-trip-report-' . date('Y-m') . '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $pdf->output();