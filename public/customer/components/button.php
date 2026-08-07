<?php

$type = $type ?? "primary";
$text = $text ?? "Button";
$icon = $icon ?? "";
?>

<button class="btn btn-<?php echo $type; ?>">

    <?php if($icon!=""): ?>

        <i class="<?php echo $icon; ?>"></i>

    <?php endif; ?>

    <?php echo htmlspecialchars($text); ?>

</button>