<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - LankaRenters</title>
    <!-- Link CSS File -->
    <link rel="stylesheet" href="assets/css/owner-style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
       <main class="main-content">
  <section class="drivers-header">
    <div class="drivers-heading">
      <h1>Driver management</h1>
      <p>Search, request and manage verified drivers.</p>
    </div>
    <div class="drivers-header-action">
      <button type="button" class="button button-primary">+ Send Driver Request</button>
    </div>
  </section>

  <section class="drivers-filters">
    <div class="filter-group">
      <label for="search-driver">Search verified drivers</label>
      <input id="search-driver" type="search" placeholder="Driver name..." />
    </div>

    <div class="filter-group">
      <label for="filter-district">District</label>
      <select id="filter-district">
        <option>All</option>
        <option>Colombo</option>
        <option>Kandy</option>
        <option>Gampaha</option>
      </select>
    </div>

    <div class="filter-group">
      <label for="filter-availability">Availability</label>
      <select id="filter-availability">
        <option>Any</option>
        <option>Available</option>
        <option>Busy</option>
      </select>
    </div>
  </section>

  <section class="drivers-layout">
    <div class="accepted-drivers">
      <h2>Accepted drivers</h2>

      <div class="driver-list">
        <article class="driver-card">
          <div class="driver-avatar">N</div>
          <div class="driver-text">
            <p class="driver-name">Nimal Perera</p>
            <p class="driver-details">★ 4.9 · 8 years</p>
          </div>
          <span class="status-badge status-linked">Linked</span>
        </article>

        <article class="driver-card">
          <div class="driver-avatar">S</div>
          <div class="driver-text">
            <p class="driver-name">Sunil Fernando</p>
            <p class="driver-details">★ 4.7 · 5 years</p>
          </div>
          <span class="status-badge status-linked">Linked</span>
        </article>
      </div>
    </div>

    <div class="driver-panels">
      <article class="request-panel">
        <h2>Pending requests</h2>
        <div class="request-item">
          <div class="request-info">
            <p class="request-name">Kamal Silva</p>
            <p class="request-meta">Request sent 2 days ago</p>
          </div>
          <span class="status-badge status-pending">Pending</span>
        </div>
      </article>

      <article class="request-panel">
        <h2>Leave requests</h2>
        <div class="request-item">
          <div class="request-info">
            <p class="request-name">Sunil Fernando</p>
            <p class="request-meta">20 Jul – 22 Jul 2026</p>
          </div>
          <div class="request-actions">
            <button type="button" class="button button-primary">Approve</button>
            <button type="button" class="button button-outline">Deny</button>
          </div>
        </div>
      </article>
    </div>
  </section>
</main>
    </div>

</body>
</html>