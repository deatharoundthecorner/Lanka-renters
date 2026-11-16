<section class="card chat-window" aria-labelledby="chat-window-title">
    <h2 id="chat-window-title"><?= htmlspecialchars((string) ($chatTitle ?? 'Booking conversation'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="message-list"><?php foreach (($chatMessages ?? []) as $message): require __DIR__ . '/message-bubble.php'; endforeach; ?></div>
</section>
