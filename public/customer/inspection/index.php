<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData=(new CustomerPortalController())->inspectionIndexPage(); require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if($viewData['database_error']):?><section class="empty-state"><h2>Inspections are temporarily unavailable</h2><p>Please try again later.</p></section>
<?php elseif($viewData['bookings']===[]):?><section class="empty-state"><span class="empty-state__icon"><?=customer_icon('clipboard')?></span><h2>No booking inspections</h2><p>Read-only reports will appear after authorized staff link an inspection to your booking.</p></section>
<?php else:?><div class="feature-card-list"><?php foreach($viewData['bookings'] as $booking):?><article class="card feature-list-card"><div><p class="eyebrow">Booking #<?=(int)$booking['booking_id']?></p><h2><?=htmlspecialchars($booking['make'].' '.$booking['model'],ENT_QUOTES,'UTF-8')?></h2><p><?=htmlspecialchars($booking['license_plate'],ENT_QUOTES,'UTF-8')?> · <?=htmlspecialchars(ucwords(str_replace('_',' ',$booking['booking_status'])),ENT_QUOTES,'UTF-8')?></p><p><?=(int)$booking['inspection_count']?> linked inspection report(s)</p></div><div class="feature-list-card__actions"><a class="button button--secondary" href="<?=htmlspecialchars(customer_url('bookings/inspection.php?id='.(int)$booking['booking_id']),ENT_QUOTES,'UTF-8')?>">View Inspection</a></div></article><?php endforeach;?></div><?php endif;?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
