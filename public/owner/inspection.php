<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
AuthHelper::startSession();
AuthHelper::requireRole('owner');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Reports - LankaRenters</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <section class="vehicles-header" style="margin-bottom: 24px;">
                <div class="vehicles-title">
                    <h1>Inspection Reports</h1>
                    <p>Review vehicle inspection reports logged by drivers.</p>
                </div>
            </section>

            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:24px; padding:48px; text-align:center; color:#64748b;">
                <div style="font-size:3rem; margin-bottom:16px;">🔍</div>
                <p style="font-size:1.1rem; font-weight:600; color:#0f172a; margin:0 0 8px;">Inspection Reports</p>
                <p style="margin:0;">Inspection report details will be available once vehicle inspections are recorded by drivers.</p>
            </div>
        </main>
    </div>

</body>
</html>