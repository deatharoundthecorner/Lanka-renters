<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData=(new CustomerPortalController())->profilePage($_POST,strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET')));
if(isset($viewData['redirect'])){header('Location: '.customer_url($viewData['redirect']),true,303);exit;}
$profile=$viewData['profile']??null; require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if($viewData['database_error']||!is_array($profile)):?><section class="empty-state"><h2>Profile unavailable</h2><p>Please try again later.</p></section>
<?php else:?><div class="feature-detail-grid"><form class="card feature-form" method="post" action="<?=htmlspecialchars(customer_url('profile/index.php'),ENT_QUOTES,'UTF-8')?>" data-submit-once><?=CustomerCsrf::field()?><span class="demo-label demo-label--database">Database profile</span><h2>Permitted account details</h2>
<label for="profile-name">Full name</label><input id="profile-name" name="name" maxlength="100" value="<?=htmlspecialchars($viewData['form']['name'],ENT_QUOTES,'UTF-8')?>" required><?php if(isset($viewData['errors']['name'])):?><p class="field-error"><?=htmlspecialchars($viewData['errors']['name'],ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<label for="profile-email">Email address</label><input id="profile-email" value="<?=htmlspecialchars($profile['email'],ENT_QUOTES,'UTF-8')?>" readonly><p class="form-hint">Email changes require coordinated authentication work.</p>
<label for="profile-phone">Phone number</label><input id="profile-phone" name="phone" maxlength="20" value="<?=htmlspecialchars($viewData['form']['phone'],ENT_QUOTES,'UTF-8')?>" required><?php if(isset($viewData['errors']['phone'])):?><p class="field-error"><?=htmlspecialchars($viewData['errors']['phone'],ENT_QUOTES,'UTF-8')?></p><?php endif;?>
<label for="profile-nic">NIC number</label><input id="profile-nic" name="nic_number" maxlength="20" value="<?=htmlspecialchars($viewData['form']['nic_number'],ENT_QUOTES,'UTF-8')?>">
<label for="profile-license">Driving licence number</label><input id="profile-license" name="driving_license_number" maxlength="30" value="<?=htmlspecialchars($viewData['form']['driving_license_number'],ENT_QUOTES,'UTF-8')?>">
<button class="button button--primary" type="submit">Save profile details</button></form>
<aside class="card"><h2>Account controls</h2><dl class="feature-detail-list"><div><dt>Account</dt><dd><?=htmlspecialchars(ucfirst($profile['user_status']),ENT_QUOTES,'UTF-8')?></dd></div><div><dt>Verification</dt><dd><?=htmlspecialchars(ucfirst($profile['verification_status']),ENT_QUOTES,'UTF-8')?></dd></div></dl><div class="alert alert--info"><p>Role, account status, verification decision, email, and password are not Customer-editable here. No insecure prototype password form is provided.</p></div><a class="button button--secondary" href="<?=htmlspecialchars(customer_url('verification/index.php'),ENT_QUOTES,'UTF-8')?>">Open Verification</a></aside></div><?php endif;?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>
