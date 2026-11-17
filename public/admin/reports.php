<?php

require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Report.php';
require_once dirname(__DIR__, 2) . '/app/helpers/Database.php';
require_once __DIR__ . '/config/database.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

$pageTitle = "Reports & Analytics";
$districts = getSriLankanDistricts();
$reportType = (string)($_GET['report_type'] ?? 'payments');
if (!in_array($reportType, ['payments', 'trips', 'role_summary'], true)) $reportType = 'payments';
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$summaryRole = (string)($_GET['role'] ?? 'customer');
if (!in_array($summaryRole, ['customer', 'owner', 'driver'], true)) $summaryRole = 'customer';
$usersForSend = [];
$previewRows = [];
$previewSummary = ['total' => 0, 'completed' => 0, 'pending' => 0, 'failed' => 0, 'refunded' => 0, 'completed_total' => 0.0];

try {
    $db = Database::getInstance()->getConnection();
    $usersForSend = $db->query("SELECT id,name,role FROM users WHERE status='active' AND role IN ('customer','owner','driver') ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    if ($reportType === 'payments') {
        $sql = "SELECT p.id,p.booking_id,p.amount,p.payment_method,p.payment_status,p.created_at,cu.name customer_name,v.make,v.model,ou.name owner_name FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN customers c ON c.id=b.customer_id JOIN users cu ON cu.id=c.user_id JOIN vehicles v ON v.id=b.vehicle_id JOIN vehicle_owners vo ON vo.id=v.owner_id JOIN users ou ON ou.id=vo.user_id WHERE 1=1";
        if ($from !== '') $sql .= ' AND DATE(p.created_at) >= :from_date';
        if ($to !== '') $sql .= ' AND DATE(p.created_at) <= :to_date';
        if (in_array($status, ['pending','completed','failed','refunded'], true)) $sql .= ' AND p.payment_status = :status';
        $sql .= ' ORDER BY p.created_at DESC';
        $stmt = $db->prepare($sql);
        $params = array_filter(['from_date' => $from, 'to_date' => $to, 'status' => $status], static fn($value) => $value !== '');
        $stmt->execute($params);
        $previewRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($previewRows as $row) { $previewSummary['total']++; $key = (string)$row['payment_status']; if (isset($previewSummary[$key])) $previewSummary[$key]++; if ($key === 'completed') $previewSummary['completed_total'] += (float)$row['amount']; }
    } elseif ($reportType === 'trips') {
        $sql = "SELECT b.id,b.start_date,b.end_date,b.status,b.booking_type,cu.name customer_name,ou.name owner_name,v.make,v.model,du.name driver_name FROM bookings b JOIN customers c ON c.id=b.customer_id JOIN users cu ON cu.id=c.user_id JOIN vehicles v ON v.id=b.vehicle_id JOIN vehicle_owners vo ON vo.id=v.owner_id JOIN users ou ON ou.id=vo.user_id LEFT JOIN drivers d ON d.id=b.driver_id LEFT JOIN users du ON du.id=d.user_id WHERE 1=1";
        if ($from !== '') $sql .= ' AND DATE(b.created_at) >= :from_date';
        if ($to !== '') $sql .= ' AND DATE(b.created_at) <= :to_date';
        if (in_array($status, ['pending_payment','confirmed','ongoing','completed','cancelled'], true)) $sql .= ' AND b.status = :status';
        $sql .= ' ORDER BY b.created_at DESC';
        $stmt = $db->prepare($sql);
        $params = array_filter(['from_date' => $from, 'to_date' => $to, 'status' => $status], static fn($value) => $value !== '');
        $stmt->execute($params);
        $previewRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $previewSummary['total'] = count($previewRows);
    } else {
        $sql = 'SELECT status, COUNT(*) total FROM bookings WHERE 1=1';
        $params = [];
        if ($from !== '') { $sql .= ' AND DATE(created_at) >= :from_date'; $params['from_date'] = $from; }
        if ($to !== '') { $sql .= ' AND DATE(created_at) <= :to_date'; $params['to_date'] = $to; }
        $summary = $db->prepare($sql . ' GROUP BY status');
        $summary->execute($params);
        $previewRows = $summary->fetchAll(PDO::FETCH_ASSOC);
        $previewSummary['total'] = array_sum(array_column($previewRows, 'total'));
    }
} catch (Throwable $e) {
    error_log('Report preview error: ' . $e->getMessage());
}

try {
    $reportModel = new Report();
    $financialSummary = $reportModel->getFinancialSummary();
    $statusBreakdown = $reportModel->getBookingsByStatus();
    $categoryPopularity = $reportModel->getVehicleCategoryPopularity();
} catch (Throwable $e) {
    error_log("Reports Error: " . $e->getMessage());
    $financialSummary = ['gross_revenue' => 0, 'total_commission' => 0, 'total_payouts' => 0, 'total_bookings' => 0];
    $statusBreakdown = [];
    $categoryPopularity = [];
}

$escape = static fn(mixed $val): string => htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Reports & Analytics</h1>
                <p class="page-subtitle">Generate operational reports from current Lanka Renters records.</p>
            </div>

            <div class="card" style="margin-bottom:24px;">
                <div class="card-header-clean"><h3 class="card-title-text">Report Center</h3></div>
                <form method="get" action="reports.php" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:end;">
                    <label>Report Type<select name="report_type" class="form-control"><option value="payments" <?= $reportType === 'payments' ? 'selected' : '' ?>>Payment Report</option><option value="trips" <?= $reportType === 'trips' ? 'selected' : '' ?>>Trip / Rental Report</option><option value="role_summary" <?= $reportType === 'role_summary' ? 'selected' : '' ?>>Role Summary Report</option></select></label>
                    <label>Date From<input type="date" name="from" class="form-control" value="<?= $escape($from) ?>"></label>
                    <label>Date To<input type="date" name="to" class="form-control" value="<?= $escape($to) ?>"></label>
                    <?php if ($reportType === 'role_summary'): ?><label>Summary Audience<select name="role" class="form-control"><option value="customer" <?= $summaryRole === 'customer' ? 'selected' : '' ?>>Customers</option><option value="owner" <?= $summaryRole === 'owner' ? 'selected' : '' ?>>Vehicle Owners</option><option value="driver" <?= $summaryRole === 'driver' ? 'selected' : '' ?>>Drivers</option></select></label><?php endif; ?>
                    <label>Status<select name="status" class="form-control"><option value="">All statuses</option><?php foreach (($reportType === 'payments' ? ['pending','completed','failed','refunded'] : ($reportType === 'trips' ? ['pending_payment','confirmed','ongoing','completed','cancelled'] : [])) as $statusOption): ?><option value="<?= $escape($statusOption) ?>" <?= $status === $statusOption ? 'selected' : '' ?>><?= $escape(ucwords(str_replace('_',' ',$statusOption))) ?></option><?php endforeach; ?></select></label>
                    <div style="display:flex;gap:8px;grid-column:1/-1;flex-wrap:wrap;"><button class="btn btn-primary" type="submit">Preview Report</button><a class="btn btn-secondary" href="report-pdf.php?type=<?= $reportType ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($summaryRole) ?>">Download PDF</a></div>
                </form>
            </div>

            <?php if (isset($_GET['sent'])): ?><div class="alert-box" role="status">Report notification sent to <?= (int)$_GET['sent'] ?> recipient(s).</div><?php endif; ?>
            <?php if (isset($_GET['delivery_error'])): ?><div class="alert-box" role="alert"><?= $_GET['delivery_error'] === 'private' ? 'Detailed payment and trip reports can only be sent to a specific authorized user.' : 'The requested report delivery is not permitted.' ?></div><?php endif; ?>

            <div class="card" style="margin-bottom:24px;"><div class="card-header-clean"><h3 class="card-title-text">Send Report Notification</h3></div><form method="post" action="report-send.php" style="display:grid;gap:12px;max-width:720px;"><input type="hidden" name="csrf_token" value="<?= $escape(AuthHelper::getCsrfToken()) ?>"><input type="hidden" name="report_type" value="<?= $escape($reportType) ?>"><input type="hidden" name="from" value="<?= $escape($from) ?>"><input type="hidden" name="to" value="<?= $escape($to) ?>"><input type="hidden" name="status" value="<?= $escape($status) ?>"><label>Recipient<select name="recipient_type" class="form-control"><?php if ($reportType === 'role_summary'): ?><option value="customer">Customers</option><option value="owner">Vehicle Owners</option><option value="driver">Drivers</option><?php else: ?><option value="specific">Specific User</option><?php endif; ?></select></label><?php if ($reportType === 'role_summary'): ?><p>Role-wide delivery is available only for Role Summary reports.</p><?php else: ?><label>Specific User<select name="user_id" class="form-control"><option value="">Choose an active user</option><?php foreach($usersForSend as $recipient): ?><option value="<?= (int)$recipient['id'] ?>"><?= $escape($recipient['name'].' ('.ucfirst($recipient['role']).')') ?></option><?php endforeach; ?></select></label><?php endif; ?><button class="btn btn-primary" type="submit">Send Report</button></form></div>

            <div class="card"><div class="card-header-clean"><h3 class="card-title-text"><?= $reportType === 'payments' ? 'Payment Report' : ($reportType === 'trips' ? 'Trip / Rental Report' : 'Role Summary Report') ?> Preview</h3><span><?= count($previewRows) ?> record(s)</span></div><?php if ($previewRows === []): ?><p>No records match the selected report filters.</p><?php elseif ($reportType === 'payments'): ?><p>Total records: <?= $previewSummary['total'] ?> · Completed: <?= $previewSummary['completed'] ?> · Pending: <?= $previewSummary['pending'] ?> · Failed: <?= $previewSummary['failed'] ?> · Refunded: <?= $previewSummary['refunded'] ?> · Completed value: LKR <?= number_format($previewSummary['completed_total'],2) ?></p><div class="table-responsive"><table class="custom-table"><thead><tr><th>Payment</th><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach($previewRows as $row): ?><tr><td>#<?= (int)$row['id'] ?></td><td>#<?= (int)$row['booking_id'] ?></td><td><?= $escape($row['customer_name']) ?></td><td><?= $escape($row['make'].' '.$row['model']) ?></td><td>LKR <?= number_format((float)$row['amount'],2) ?></td><td><?= $escape(ucfirst($row['payment_status'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php elseif ($reportType === 'trips'): ?><p>Total records: <?= count($previewRows) ?></p><div class="table-responsive"><table class="custom-table"><thead><tr><th>Booking</th><th>Customer</th><th>Owner</th><th>Vehicle</th><th>Dates</th><th>Status</th></tr></thead><tbody><?php foreach($previewRows as $row): ?><tr><td>#<?= (int)$row['id'] ?></td><td><?= $escape($row['customer_name']) ?></td><td><?= $escape($row['owner_name']) ?></td><td><?= $escape($row['make'].' '.$row['model']) ?></td><td><?= $escape(substr($row['start_date'],0,10).' to '.substr($row['end_date'],0,10)) ?></td><td><?= $escape(ucwords(str_replace('_',' ',$row['status']))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Total bookings: <?= $previewSummary['total'] ?></p><div class="table-responsive"><table class="custom-table"><thead><tr><th>Booking Status</th><th>Total</th></tr></thead><tbody><?php foreach($previewRows as $row): ?><tr><td><?= $escape(ucwords(str_replace('_',' ',$row['status']))) ?></td><td><?= (int)$row['total'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>

            <!-- Financial Summary Cards -->
            <div class="grid-4">
                <div class="stat-card" style="border-left: 4px solid var(--primary);">
                    <span class="stat-label">Gross Completed Rental Revenue</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['gross_revenue'], 2) ?></div>
                    <span class="stat-comparison">Total Completed Trips</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <span class="stat-label">Platform Commission (10%)</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['total_commission'], 2) ?></div>
                    <span class="stat-comparison">Platform Revenue</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--info);">
                    <span class="stat-label">Owner Payouts (90%)</span>
                    <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format((float)$financialSummary['total_payouts'], 2) ?></div>
                    <span class="stat-comparison">Paid to Vehicle Owners</span>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--warning);">
                    <span class="stat-label">Completed Bookings</span>
                    <div class="stat-value"><?= number_format((int)$financialSummary['total_bookings']) ?></div>
                    <span class="stat-comparison">Finished Reservations</span>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <!-- Booking Status Breakdown -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Bookings Status Breakdown</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Total Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusBreakdown as $sb): ?>
                                    <tr>
                                        <td><span class="badge badge-blue"><?= $escape(str_replace('_', ' ', ucfirst($sb['status']))) ?></span></td>
                                        <td><span class="cell-primary-text"><?= number_format((int)$sb['count']) ?> Bookings</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vehicle Category Popularity -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Vehicle Category Popularity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Bookings Count</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryPopularity as $cp): ?>
                                    <tr>
                                        <td><span class="cell-primary-text"><?= $escape(ucfirst($cp['vehicle_type'])) ?></span></td>
                                        <td><?= number_format((int)$cp['booking_count']) ?></td>
                                        <td><span class="cell-primary-text">Rs. <?= number_format((float)$cp['total_spent'], 2) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
