<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

$viewData = [
    'page_kicker' => 'Customer',
    'page_title' => 'Incidents',
    'page_description' => 'Report evidence, choose a replacement or refund path, and follow the decision.',
    'active_nav' => 'incidents',
];

$workflowSteps = [
    ['label' => 'Incident reported', 'state' => 'complete'],
    ['label' => 'Evidence review', 'state' => 'current'],
    ['label' => 'Replacement or refund decision', 'state' => 'upcoming'],
    ['label' => 'Settlement', 'state' => 'settlement'],
];

$settlementRows = [
    ['label' => 'Rental price received', 'amount' => 'LKR 168,000'],
    ['label' => 'Damage deduction', 'amount' => '− LKR 0'],
    ['label' => 'Extra mileage charge', 'amount' => '− LKR 2,500'],
    ['label' => 'Refund amount', 'amount' => 'LKR 27,500'],
    ['label' => 'Owner earning', 'amount' => 'LKR 165,000'],
];

require dirname(__DIR__) . '/components/layout/feature-start.php';
?>

<section class="incident-demo" aria-label="Incident report demo">
    <div class="incident-demo__topbar">
        <div class="incident-demo__badges" aria-label="Account status">
            <span class="incident-demo__badge incident-demo__badge--customer">Customer</span>
            <span class="incident-demo__badge incident-demo__badge--pending">Pending verification</span>
        </div>
        <button class="incident-demo__verify" type="button" aria-disabled="true" title="Demo preview only">
            Complete verification <span aria-hidden="true">›</span>
        </button>
    </div>

    <p class="demo-label">Demo preview</p>

    <article class="card incident-demo__card">
        <div class="incident-demo__card-heading">
            <span class="incident-demo__icon incident-demo__icon--alert" aria-hidden="true">!</span>
            <div>
                <h2>Report an incident</h2>
                <p>Add clear details to help us act quickly.</p>
            </div>
        </div>

        <div class="incident-demo__field">
            <label for="incident-description">What happened?</label>
            <textarea id="incident-description" rows="4" placeholder="Briefly describe the incident..." readonly></textarea>
        </div>

        <div class="incident-demo__upload-row">
            <button class="incident-demo__outline-button" type="button" aria-disabled="true">Add incident photos</button>
            <button class="incident-demo__outline-button" type="button" aria-disabled="true">Add Google Maps link</button>
        </div>
        <button class="incident-demo__submit" type="button" aria-disabled="true">Submit incident report</button>
    </article>

    <article class="card incident-demo__card">
        <div class="incident-demo__section-heading">
            <h2>Do you need another vehicle?</h2>
            <p>Choose a simple path after your incident report.</p>
        </div>
        <div class="incident-demo__choices">
            <button class="incident-demo__choice" type="button" aria-disabled="true">
                <span class="incident-demo__choice-icon incident-demo__choice-icon--vehicle" aria-hidden="true">⌁</span>
                <span><strong>Yes, request another vehicle</strong><small>We will seek a suitable replacement.</small></span>
            </button>
            <button class="incident-demo__choice" type="button" aria-disabled="true">
                <span class="incident-demo__choice-icon incident-demo__choice-icon--refund" aria-hidden="true">↻</span>
                <span><strong>No, request refund</strong><small>Review the refund for remaining days.</small></span>
            </button>
        </div>
    </article>

    <article class="card incident-demo__card">
        <div class="incident-demo__section-heading incident-demo__section-heading--split">
            <div><h2>Incident workflow</h2></div>
            <span>1/4 complete</span>
        </div>
        <ol class="incident-demo__timeline">
            <?php foreach ($workflowSteps as $step): ?>
                <li class="incident-demo__timeline-step incident-demo__timeline-step--<?= htmlspecialchars($step['state'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="incident-demo__timeline-marker" aria-hidden="true"><?= $step['state'] === 'complete' ? '✓' : '' ?></span>
                    <strong><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                </li>
            <?php endforeach; ?>
        </ol>
    </article>

    <article class="card incident-demo__card incident-demo__settlement">
        <div class="incident-demo__card-heading">
            <span class="incident-demo__icon incident-demo__icon--settlement" aria-hidden="true">▣</span>
            <div>
                <h2>Settlement breakdown</h2>
                <p>Review before you continue.</p>
            </div>
        </div>
        <dl class="incident-demo__amounts">
            <?php foreach ($settlementRows as $row): ?>
                <div><dt><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></dt><dd><?= htmlspecialchars($row['amount'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <div class="incident-demo__total"><strong>Total</strong><strong>LKR 192,500</strong></div>
        <p class="incident-demo__notice">Platform fee is non-refundable.</p>
    </article>
</section>

<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
