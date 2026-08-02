<?php

$image = $image ?? "../assets/images/no-image.png";

$name = $name ?? "Toyota Prius";

$price = $price ?? "Rs. 12,000 / day";

$type = $type ?? "Self Drive";

$rating = $rating ?? "4.8";

?>

<div class="vehicle-card">

    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>">

    <div class="vehicle-card__body">

        <h3><?= htmlspecialchars($name) ?></h3>

        <p><?= htmlspecialchars($price) ?></p>

        <p><?= htmlspecialchars($type) ?></p>

        <p>⭐ <?= htmlspecialchars($rating) ?></p>

        <button class="btn btn-primary">

            View Details

        </button>

    </div>

</div>