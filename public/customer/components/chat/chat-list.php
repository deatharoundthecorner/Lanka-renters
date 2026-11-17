<ul class="chat-room-list">
    <?php foreach (($chatRooms ?? []) as $chatRoom): ?>
        <li><a href="<?= htmlspecialchars(customer_url('chat/index.php?room=' . (int) $chatRoom['room_id']), ENT_QUOTES, 'UTF-8') ?>"><strong>Booking #<?= (int) $chatRoom['booking_id'] ?></strong><span><?= htmlspecialchars(trim(($chatRoom['make'] ?? '') . ' ' . ($chatRoom['model'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></a></li>
    <?php endforeach; ?>
</ul>
