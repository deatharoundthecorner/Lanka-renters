<header class="page-header">
    <p class="eyebrow">Customer Module</p>
    <h1><?= htmlspecialchars((string) ($viewData['page_title'] ?? 'Customer'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($viewData['page_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
</header>
