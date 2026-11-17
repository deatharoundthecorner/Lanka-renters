<section class="booking-precheck" aria-labelledby="pre-booking-checklist-title">
    <div class="card__header">
        <h2 id="pre-booking-checklist-title">Pre booking checklist</h2>
        <span class="card__header-icon" aria-hidden="true"><?= customer_icon('check') ?></span>
    </div>
    <p>Your request is sent only after the server checks the selected vehicle, dates, verification, service, Driver, and Booking Rules again.</p>
    <p class="form-hint"><?= ($viewData['can_send_request'] ?? false) ? 'All current requirements are complete. You can send your booking request.' : 'Complete the outstanding requirements above, then send the request.' ?></p>
</section>
