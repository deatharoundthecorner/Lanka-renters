<?php $quickLink = is_array($quickLink ?? null) ? $quickLink : []; ?>
<a href="<?= htmlspecialchars(customer_url((string) ($quickLink['path'] ?? 'dashboard.php')), ENT_QUOTES, 'UTF-8') ?>" class="quick-action">
    <span class="quick-action__icon" aria-hidden="true"><?= customer_icon((string) ($quickLink['icon'] ?? 'arrow-right')) ?></span>
    <span class="quick-action__copy">
        <strong><?= htmlspecialchars((string) ($quickLink['label'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?></strong>
        <small><?= htmlspecialchars((string) ($quickLink['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
    </span>
    <span class="quick-action__arrow" aria-hidden="true"><?= customer_icon('arrow-right') ?></span>
</a>
