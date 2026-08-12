<article class="message-bubble <?= !empty($message['is_own']) ? 'is-own' : '' ?>">
    <strong><?= htmlspecialchars((string) ($message['sender_label'] ?? 'Participant'), ENT_QUOTES, 'UTF-8') ?></strong>
    <p><?= nl2br(htmlspecialchars((string) ($message['message_text'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    <?php if (!empty($message['sent_at'])): ?><time><?= htmlspecialchars((string) $message['sent_at'], ENT_QUOTES, 'UTF-8') ?></time><?php endif; ?>
</article>
