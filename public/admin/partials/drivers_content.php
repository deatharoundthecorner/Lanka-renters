<?php
// Drivers page content (partial)
?>
<header class="admin-header">
    <div>
        <h1>Drivers</h1>
        <p>Review driver submissions and confirm identity before dispatch.</p>
    </div>
    <div class="admin-userbubble">
        <button class="btn-outline">Open approval queue</button>
        <div class="user-initial">D</div>
    </div>
</header>
<div class="dashboard-cards">
    <section class="panel-card">
        <h2>Driver approvals</h2>
        <div class="list-card">
            <div class="incident-card">
                <div class="incident-main">
                    <div class="incident-header">
                        <div>
                            <div class="incident-title">Ruwan Bandara · Pending license review</div>
                            <div class="incident-meta">Review evidence, apply the rule, then keep a clear record.</div>
                        </div>
                    </div>
                    <div class="incident-detail">
                        <strong>Submitted documents</strong>
                        <p class="muted">Driver license, ID proof, and vehicle registration received.</p>
                    </div>
                </div>
                <div class="incident-actions">
                    <button class="btn-primary">Approve</button>
                    <button class="btn-secondary">Reject</button>
                </div>
            </div>
            <div class="incident-card">
                <div class="incident-main">
                    <div class="incident-header">
                        <div>
                            <div class="incident-title">Dilani Weerasinghe · Pending background check</div>
                            <div class="incident-meta">Confirm customer reviews and vehicle familiarity.</div>
                        </div>
                    </div>
                    <div class="incident-detail">
                        <strong>Submitted documents</strong>
                        <p class="muted">Background report and driving history attached.</p>
                    </div>
                </div>
                <div class="incident-actions">
                    <button class="btn-primary">Approve</button>
                    <button class="btn-secondary">Reject</button>
                </div>
            </div>
        </div>
    </section>
    <aside class="panel-card">
        <h2>Admin essentials</h2>
        <div style="display:grid; gap:10px;">
            <div style="color:#64748b;">The rules that matter most today.</div>
            <ul style="padding-left:18px; margin:0; color:#0f172a;">
                <li>Verify drivers before assigning rentals.</li>
                <li>Check ID proof and license validity.</li>
                <li>Approve only after owning or admin confirmation.</li>
            </ul>
        </div>
        <div style="margin-top:18px; background:#eef2ff; border-radius:12px; padding:12px; color:#2563eb;">
            Driver verification is required before covering vehicles under insurance.
        </div>
    </aside>
</div>
