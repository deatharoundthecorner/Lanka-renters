<?php
$type = in_array(($type ?? ''), ['primary', 'secondary', 'danger', 'ghost'], true)
    ? (string) $type
    : 'primary';
$text = (string) ($text ?? 'Button');
$buttonType = in_array(($buttonType ?? ''), ['button', 'submit', 'reset'], true)
    ? (string) $buttonType
    : 'button';
?>
<button class="button button--<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($buttonType, ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?>
</button>
