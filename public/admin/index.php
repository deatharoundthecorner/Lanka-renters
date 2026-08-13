<?php
// index.php - Lanka Renters Admin Workspace Entry Point
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('admin');

header("Location: dashboard.php");
exit;
