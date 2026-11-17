<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->reviewDetailsPage(
    $_GET,
    $_POST,
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))
);
if (isset($viewData['redirect'])) {
    header('Location: ' . customer_url($viewData['redirect']), true, 303);
    exit;
}
$review = $viewData['review'];
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<p><a class="text-link" href="<?=htmlspecialchars(customer_url('reviews/index.php'),ENT_QUOTES,'UTF-8')?>">&larr; Back to reviews</a></p>
<?php if(!is_array($review)):?>
    <section class="empty-state"><h2>Review not found</h2><p>This review does not exist or does not belong to you.</p></section>
<?php elseif($viewData['database_error']):?>
    <section class="empty-state"><h2>Review unavailable</h2><p>Please try again later.</p></section>
<?php else:?>
    <section class="card">
        <div class="card__header"><div><p class="eyebrow">Review #<?=(int)$review['id']?></p><h2><?=htmlspecialchars($review['make'].' '.$review['model'],ENT_QUOTES,'UTF-8')?></h2></div><span class="status-badge status-badge--success">Saved</span></div>
        <dl class="feature-detail-list"><div><dt>Booking</dt><dd>#<?=(int)$review['booking_id']?></dd></div><div><dt>Vehicle rating</dt><dd><?=(int)$review['vehicle_rating']?> / 5</dd></div><div><dt>Driver rating</dt><dd><?=$review['driver_rating']!==null?(int)$review['driver_rating'].' / 5':'Not applicable'?></dd></div><div><dt>Created</dt><dd><?=htmlspecialchars(date('d M Y, h:i A',strtotime($review['created_at'])),ENT_QUOTES,'UTF-8')?></dd></div><div class="feature-detail-list__wide"><dt>Review</dt><dd><?=nl2br(htmlspecialchars($review['review_text']?:'No written comment.',ENT_QUOTES,'UTF-8'))?></dd></div></dl>
        <a class="button button--secondary" href="<?=htmlspecialchars(customer_url('bookings/details.php?id='.(int)$review['booking_id']),ENT_QUOTES,'UTF-8')?>">Booking Details</a>
    </section>

    <form class="card feature-form" method="post" action="<?=htmlspecialchars(customer_url('reviews/details.php?id='.(int)$review['id']),ENT_QUOTES,'UTF-8')?>" data-submit-once>
        <?=CustomerCsrf::field()?>
        <input type="hidden" name="action" value="update_review">
        <h2>Edit Review</h2>
        <?php if(isset($viewData['errors']['form'])):?><div class="alert alert--error"><p><?=htmlspecialchars($viewData['errors']['form'],ENT_QUOTES,'UTF-8')?></p></div><?php endif;?>
        <label for="vehicle-rating">Vehicle rating</label>
        <select id="vehicle-rating" name="vehicle_rating" required><?php for($rating=5;$rating>=1;$rating--):?><option value="<?=$rating?>" <?=$viewData['form']['vehicle_rating']===(string)$rating?'selected':''?>><?=$rating?> / 5</option><?php endfor;?></select>
        <?php if(isset($viewData['errors']['vehicle_rating'])):?><span class="form-error"><?=htmlspecialchars($viewData['errors']['vehicle_rating'],ENT_QUOTES,'UTF-8')?></span><?php endif;?>
        <?php if($review['driver_id']!==null):?>
            <label for="driver-rating">Driver rating</label>
            <select id="driver-rating" name="driver_rating" required><?php for($rating=5;$rating>=1;$rating--):?><option value="<?=$rating?>" <?=$viewData['form']['driver_rating']===(string)$rating?'selected':''?>><?=$rating?> / 5</option><?php endfor;?></select>
            <?php if(isset($viewData['errors']['driver_rating'])):?><span class="form-error"><?=htmlspecialchars($viewData['errors']['driver_rating'],ENT_QUOTES,'UTF-8')?></span><?php endif;?>
        <?php endif;?>
        <label for="review-text">Review text <span class="form-hint">Plain text, maximum 1500 characters</span></label>
        <textarea id="review-text" name="review_text" maxlength="1500" data-character-input><?=htmlspecialchars($viewData['form']['review_text'],ENT_QUOTES,'UTF-8')?></textarea>
        <span class="form-hint" data-character-count>0 / 1500</span>
        <button class="button button--primary" type="submit">Update Review</button>
    </form>

    <form class="card feature-form" method="post" action="<?=htmlspecialchars(customer_url('reviews/details.php?id='.(int)$review['id']),ENT_QUOTES,'UTF-8')?>" onsubmit="return confirm('Delete this review permanently?');">
        <?=CustomerCsrf::field()?>
        <input type="hidden" name="action" value="delete_review">
        <h2>Delete Review</h2>
        <p>This removes your saved review from the database.</p>
        <button class="button button--secondary" type="submit">Delete Review</button>
    </form>
<?php endif;?>
<?php require dirname(__DIR__).'/components/layout/feature-end.php';?>
