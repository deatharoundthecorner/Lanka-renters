<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
AuthHelper::startSession();
AuthHelper::logout();
header('Location: login.php');
exit;
