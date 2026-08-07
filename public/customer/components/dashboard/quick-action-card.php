<?php
$title = $title ?? "Action";
$link = $link ?? "#";
$icon = $icon ?? "➡";
?>

<a href="<?= htmlspecialchars($link) ?>" class="quick-action">

    <span class="quick-action__icon">

        <?= htmlspecialchars($icon) ?>

    </span>

    <span>

        <?= htmlspecialchars($title) ?>

    </span>

</a>