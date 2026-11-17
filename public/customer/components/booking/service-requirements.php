<?php
$requirementIcon = static function (string $state): string {
    return match ($state) {
        'complete' => customer_icon('check'),
        'failed' => customer_icon('alert'),
        default => customer_icon('clock'),
    };
};
?>
<section class="booking-requirements" aria-labelledby="service-requirements-title" data-service-requirements>
    <div class="card__header">
        <div><p class="eyebrow">Before you request</p><h2 id="service-requirements-title">Check Service Type and Requirements</h2></div>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('shield') ?></span>
    </div>
    <ul class="booking-requirements__list">
        <?php foreach (($viewData['requirements'] ?? []) as $requirement): ?>
            <?php
            $action = (string) ($requirement['action'] ?? '');
            $state = (string) ($requirement['state'] ?? 'pending');
            $appliesTo = (string) ($requirement['applies_to'] ?? 'all');
            $isVisible = $appliesTo === 'all' || $appliesTo === (string) ($viewData['form']['booking_type'] ?? 'self_drive');
            ?>
            <li class="booking-requirements__item booking-requirements__item--<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>" data-requirement-service="<?= htmlspecialchars($appliesTo, ENT_QUOTES, 'UTF-8') ?>"<?= $isVisible ? '' : ' hidden' ?><?= isset($requirement['key']) ? ' data-requirement-key="' . htmlspecialchars((string) $requirement['key'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <span class="booking-requirements__icon" aria-hidden="true" data-requirement-icon>
                    <span data-requirement-icon-state="complete"<?= $state === 'complete' ? '' : ' hidden' ?>><?= customer_icon('check') ?></span>
                    <span data-requirement-icon-state="failed"<?= $state === 'failed' ? '' : ' hidden' ?>><?= customer_icon('alert') ?></span>
                    <span data-requirement-icon-state="pending"<?= $state === 'pending' ? '' : ' hidden' ?>><?= customer_icon('clock') ?></span>
                </span>
                <div><strong><?= htmlspecialchars((string) $requirement['label'], ENT_QUOTES, 'UTF-8') ?></strong><small data-requirement-description><?= htmlspecialchars((string) $requirement['description'], ENT_QUOTES, 'UTF-8') ?></small></div>
                <?php if ($action !== ''): ?>
                    <a class="text-link" href="<?= htmlspecialchars(str_starts_with($action, '#') ? $action : customer_url($action), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($requirement['action_label'] ?? (str_starts_with($action, '#') ? 'Review' : 'Complete')), ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
