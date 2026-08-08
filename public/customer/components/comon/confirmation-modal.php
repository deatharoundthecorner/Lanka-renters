<dialog class="confirmation-dialog" id="<?= htmlspecialchars((string) ($modalId ?? 'customer-confirmation'), ENT_QUOTES, 'UTF-8') ?>">
    <h2><?= htmlspecialchars((string) ($modalTitle ?? 'Confirm action'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars((string) ($modalMessage ?? 'Review this action before continuing.'), ENT_QUOTES, 'UTF-8') ?></p>
    <form method="dialog"><button class="button button--secondary" value="cancel">Cancel</button><button class="button button--primary" value="confirm">Confirm</button></form>
</dialog>
