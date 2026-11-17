<?php if (($booking['agreement_version'] ?? null) !== null && ($booking['agreement_accepted_at'] ?? null) !== null): ?>
    <section class="card accepted-booking-agreement" aria-labelledby="accepted-booking-agreement-title">
        <div class="card__header"><div><p class="eyebrow">Accepted</p><h2 id="accepted-booking-agreement-title">Accepted Booking Agreement</h2></div><span class="status-badge status-badge--success"><?= customer_icon('check') ?> Accepted</span></div>
        <ul>
            <li><?= customer_icon('check') ?><span>I understand that the platform fee is non refundable.</span></li>
            <li><?= customer_icon('check') ?><span>I understand that a replacement vehicle is not guaranteed.</span></li>
            <li><?= customer_icon('check') ?><span>I understand that the refundable security payment will be released after the rental is completed.</span></li>
            <li><?= customer_icon('check') ?><span>I agree to inspect the vehicle and confirm the inspection before vehicle pickup.</span></li>
        </ul>
        <dl class="booking-record-dates">
            <div><dt>Accepted</dt><dd><?= htmlspecialchars(date('d M Y, h:i A', strtotime((string) $booking['agreement_accepted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Agreement version</dt><dd><?= htmlspecialchars((string) $booking['agreement_version'], ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </section>
<?php endif; ?>
