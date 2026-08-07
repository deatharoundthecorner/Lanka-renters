<header class="page-header">
    <div class="page-header__copy">
        <p class="eyebrow"><?= htmlspecialchars((string) ($viewData['page_kicker'] ?? 'Customer module'), ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= htmlspecialchars((string) ($viewData['page_title'] ?? 'Customer'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) ($viewData['page_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <?php if (isset($viewData['page_action']) && is_array($viewData['page_action'])): ?>
        <a class="button button--primary" href="<?= htmlspecialchars(customer_url((string) ($viewData['page_action']['path'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
            <?= customer_icon('search') ?>
            <?= htmlspecialchars((string) ($viewData['page_action']['label'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endif; ?>
</header>
