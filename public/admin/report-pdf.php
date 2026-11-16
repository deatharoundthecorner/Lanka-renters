<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/helpers/Database.php';
require_once dirname(__DIR__, 2) . '/app/helpers/SimplePdf.php';
AuthHelper::startSession();
AuthHelper::requireRole('admin');
$db = Database::getInstance()->getConnection();
$type = (string)($_GET['type'] ?? 'trips');
$type = in_array($type, ['payments', 'trips', 'role_summary'], true) ? $type : 'trips';
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$role = (string)($_GET['role'] ?? 'customer');
$role = in_array($role, ['customer', 'owner', 'driver'], true) ? $role : 'customer';
if ($type === 'role_summary') {
    $sql = 'SELECT status, COUNT(*) total FROM bookings WHERE 1=1';
    $params = [];
    if ($from !== '') { $sql .= ' AND DATE(created_at) >= :from_date'; $params['from_date'] = $from; }
    if ($to !== '') { $sql .= ' AND DATE(created_at) <= :to_date'; $params['to_date'] = $to; }
    $summary = $db->prepare($sql . ' GROUP BY status ORDER BY status');
    $summary->execute($params);
    $rows = $summary->fetchAll(PDO::FETCH_ASSOC);
    $pdf = new SimplePdf('Lanka Renters ' . ucfirst($role) . ' Role Summary');
    $pdf->text('Generated: ' . date('Y-m-d H:i:s'), 36, 752, 9);
    $pdf->text('Report period: ' . ($from !== '' ? $from : 'Beginning') . ' to ' . ($to !== '' ? $to : 'Today'), 36, 736, 9);
    $pdf->heading('Platform Activity Summary');
    $pdf->row(['Booking status', 'Total bookings'], [260, 160]);
    foreach ($rows as $row) $pdf->row([ucwords(str_replace('_', ' ', $row['status'])), (string)$row['total']], [260, 160]);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="lanka-renters-' . $role . '-summary-' . date('Y-m') . '.pdf"');
    echo $pdf->output();
    exit;
}
$dateAlias = $type === 'payments' ? 'p' : 'x';
$fromSql = $from !== '' ? ' AND DATE(' . $dateAlias . '.created_at) >= :from_date' : '';
$toSql = $to !== '' ? ' AND DATE(' . $dateAlias . '.created_at) <= :to_date' : '';
$params = [];
$status = trim((string)($_GET['status'] ?? ''));
if ($from !== '') $params['from_date'] = $from;
if ($to !== '') $params['to_date'] = $to;
$pdf = new SimplePdf($type === 'payments' ? 'Lanka Renters Payment Report' : 'Lanka Renters Trip / Rental Report');
$pdf->text('Generated: ' . date('Y-m-d H:i:s'), 36, 752, 9);
$pdf->spacer(18);
if ($type === 'payments') {
    $sql = "SELECT p.id,p.booking_id,p.amount,p.payment_method,p.payment_status,p.created_at,cu.name customer_name,v.make,v.model FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN customers c ON c.id=b.customer_id JOIN users cu ON cu.id=c.user_id JOIN vehicles v ON v.id=b.vehicle_id WHERE 1=1";
} else {
    $sql = "SELECT x.id,x.created_at,x.status,x.start_date,x.end_date,cu.name customer_name,ou.name owner_name,v.make,v.model,du.name driver_name FROM bookings x JOIN customers c ON c.id=x.customer_id JOIN users cu ON cu.id=c.user_id JOIN vehicles v ON v.id=x.vehicle_id JOIN vehicle_owners vo ON vo.id=v.owner_id JOIN users ou ON ou.id=vo.user_id LEFT JOIN drivers d ON d.id=x.driver_id LEFT JOIN users du ON du.id=d.user_id WHERE 1=1";
}
if ($type === 'payments') {
    if (in_array($status, ['pending', 'completed', 'failed', 'refunded'], true)) $sql .= ' AND p.payment_status = :status';
    $sql .= $fromSql . $toSql . ' ORDER BY p.created_at DESC';
} else {
    if (in_array($status, ['pending_payment', 'confirmed', 'ongoing', 'completed', 'cancelled'], true)) $sql .= ' AND x.status = :status';
    $sql .= $fromSql . $toSql . ' ORDER BY x.created_at DESC';
}
if (in_array($status, ['pending', 'completed', 'failed', 'refunded', 'pending_payment', 'confirmed', 'ongoing', 'cancelled'], true)) $params['status'] = $status;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($type === 'payments') {
    $completed = 0; $completedTotal = 0.0;
    foreach ($rows as $row) { if ($row['payment_status'] === 'completed') { $completed++; $completedTotal += (float)$row['amount']; } }
    $pdf->heading('Payment Summary');
    $pdf->text('Transactions: ' . count($rows) . ' | Completed: ' . $completed . ' | Completed total: LKR ' . number_format($completedTotal, 2), 36, $pdfY = 730, 9);
    $pdf->spacer(20);
    $pdf->row(['Payment','Booking','Customer','Vehicle','Amount','Status'], [60,60,105,125,75,98]);
    foreach ($rows as $row) $pdf->row(['#'.$row['id'],'#'.$row['booking_id'],$row['customer_name'],$row['make'].' '.$row['model'],'LKR '.number_format((float)$row['amount'],2),$row['payment_status']], [60,60,105,125,75,98]);
} else {
    $pdf->heading('Trip / Rental Activity');
    $pdf->text('Bookings: ' . count($rows), 36, 730, 9); $pdf->spacer(20);
    $pdf->row(['Booking','Customer','Owner','Vehicle','Dates','Status'], [60,100,95,115,105,48]);
    foreach ($rows as $row) $pdf->row(['#'.$row['id'],$row['customer_name'],$row['owner_name'],$row['make'].' '.$row['model'],substr($row['start_date'],0,10).' to '.substr($row['end_date'],0,10),$row['status']], [60,100,95,115,105,48]);
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="lanka-renters-' . $type . '-report.pdf"');
echo $pdf->output();
