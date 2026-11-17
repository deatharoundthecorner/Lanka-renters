<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
AuthHelper::startSession();
AuthHelper::requireRole('admin');
$pageTitle = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/lr-messages.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>
        <main class="page-container">
            <div class="page-header-box">
                <h1 class="page-title">Messages</h1>
                <p class="page-subtitle">Manage customer support tickets and escalated booking communications.</p>
            </div>
            
            <div class="lr-messages-layout">
                <aside class="card lr-messages-panel" aria-labelledby="admin-rooms-heading">
                    <h2 id="admin-rooms-heading">Support & Escalations</h2>
                    <div class="lr-message-empty" style="padding: 24px 12px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <h3 style="font-size: 14px; font-weight: 600; margin: 0 0 4px; color: var(--lr-msg-text);">No pending tickets</h3>
                        <p style="font-size: 12px; color: var(--lr-msg-text-muted); margin: 0; line-height: 1.4;">Escalated booking disputes and support inquiries will be queued here.</p>
                    </div>
                </aside>

                <section class="card lr-messages-window" aria-live="polite">
                    <div class="card__header">
                        <div>
                            <p class="eyebrow">Support Helpdesk</p>
                            <h2>Administrative Workspace</h2>
                        </div>
                    </div>
                    <div class="lr-message-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <h2>No Conversation Selected</h2>
                        <p>Private rental conversations are strictly isolated to booking participants.</p>
                        <p class="form-hint" style="margin-top: 6px;">When a user flags an issue or requests administrative escalation, their support thread will appear here.</p>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>