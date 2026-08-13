<article class="card"><p class="eyebrow"><?= htmlspecialchars((string) ($ratingLabel ?? 'Rating'), ENT_QUOTES, 'UTF-8') ?></p><h3><?= (int) ($ratingValue ?? 0) ?> / 5</h3></article>
