<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
AuthHelper::startSession();
AuthHelper::requireRole('owner');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - LankaRenters</title>
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
  <section class="chat-card-wrapper">
    <article class="chat-card">
      <h1>Chat</h1>
      <p>Message customers, drivers and support.</p>
    </article>
  </section>
</main>
    </div>

</body>
</html>