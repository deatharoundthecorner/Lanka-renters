<section class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"><?= customer_icon((string) ($emptyIcon ?? 'info')) ?></span>
    <h2><?= htmlspecialchars((string) ($emptyTitle ?? 'Nothing to show yet'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars((string) ($emptyMessage ?? 'Relevant Customer records will appear here.'), ENT_QUOTES, 'UTF-8') ?></p>
</section>
