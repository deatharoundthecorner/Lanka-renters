<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - LankaRenters</title>
    <!-- Link CSS File -->
    <link rel="stylesheet" href="assets/css/owner-style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area (Dashboard content will go here) -->
        <main class="main-content">
  <section class="overview-stats" aria-label="Summary statistics">
    <div class="stats-grid">
      <article class="stat-card stat-earnings">
        <p class="stat-label">Total earnings</p>
        <p class="stat-value">Rs. 1,240,000</p>
        <p class="stat-meta">This year</p>
      </article>

      <article class="stat-card stat-vehicles">
        <p class="stat-label">Active vehicles</p>
        <p class="stat-value">6</p>
        <p class="stat-meta">4 verified</p>
      </article>

      <article class="stat-card stat-bookings">
        <p class="stat-label">Pending bookings</p>
        <p class="stat-value">2</p>
        <p class="stat-meta">Respond in 12h</p>
      </article>

      <article class="stat-card stat-drivers">
        <p class="stat-label">Linked drivers</p>
        <p class="stat-value">3</p>
        <p class="stat-meta">All verified</p>
      </article>
    </div>
  </section>

  <section class="booking-requests">
    <div class="section-heading">
      <h2>Booking requests awaiting response</h2>
    </div>

    <div class="request-list">
      <article class="request-card">
        <div class="request-details">
          <p class="request-title">Suzuki Wagon R · BK-1051</p>
          <p class="request-meta">Ruwan Bandara · Kandy · Rs. 78,000</p>
        </div>
        <div class="request-actions">
          <button type="button" class="button button-primary">Approve</button>
          <button type="button" class="button button-secondary">Reject</button>
        </div>
      </article>

      <article class="request-card">
        <div class="request-details">
          <p class="request-title">Toyota KDH Van · BK-1033</p>
          <p class="request-meta">Dilani Weerasinghe · Gampaha · Rs. 165,000</p>
        </div>
        <div class="request-actions">
          <button type="button" class="button button-primary">Approve</button>
          <button type="button" class="button button-secondary">Reject</button>
        </div>
      </article>
    </div>
  </section>
</main>
    </div>

</body>
</html>