<?php
$title = $title ?? "Statistic";
$value = $value ?? "0";
$icon = $icon ?? "📊";
$color = $color ?? "primary";
?>

<div class="stat-card">

    <div class="stat-card__icon <?= htmlspecialchars($color) ?>">
        <?= htmlspecialchars($icon) ?>
    </div>

    <div class="stat-card__content">

        <h3><?= htmlspecialchars($value) ?></h3>

        <p><?= htmlspecialchars($title) ?></p>

    </div>

</div>