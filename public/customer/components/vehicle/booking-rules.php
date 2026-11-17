<section class="card vehicle-booking-rules" aria-labelledby="booking-rules-title">
    <div class="card__header">
        <div><p class="eyebrow">Before you request</p><h2 id="booking-rules-title">Booking Rules</h2></div>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('info') ?></span>
    </div>
    <ul class="vehicle-booking-rules__list">
        <li><?= customer_icon('check') ?><span>The minimum rental period is <strong>28 days</strong>, and the maximum rental period is <strong>6 months</strong>.</span></li>
        <li><?= customer_icon('check') ?><span>For the With Driver service, you can choose only an available, Admin-verified Driver linked to the vehicle's Owner.</span></li>
        <li><?= customer_icon('clock') ?><span>The Owner must approve or reject the booking request within <strong>12 hours</strong>.</span></li>
        <li><?= customer_icon('check') ?><span>After a booking request is submitted, the vehicle is reserved for the requested rental period and cannot receive conflicting booking requests.</span></li>
        <li><?= customer_icon('clock') ?><span>After Owner approval, payment must be completed within <strong>12 hours</strong>.</span></li>
        <li><?= customer_icon('shield') ?><span>The booking becomes active only after the payment is verified by the Admin.</span></li>
        <li><?= customer_icon('check') ?><span>The vehicle becomes available again after the rental is completed, provided no other valid booking or maintenance restriction exists.</span></li>
        <li><?= customer_icon('info') ?><span>If the request is rejected or payment is not completed, the vehicle can appear as available again <strong>24 hours after the original request time</strong>, provided no other restriction exists.</span></li>
    </ul>
</section>
