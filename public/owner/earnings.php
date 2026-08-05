<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - LankaRenters</title>
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
  <section class="earnings-header">
    <div class="earnings-title">
      <h1>Earnings</h1>
      <p>Track income, deductions and settlements.</p>
    </div>
  </section>

  <section class="earnings-stats" aria-label="Earnings summary">
    <div class="stats-grid">
      <article class="stat-card">
        <p class="stat-label">Total earnings</p>
        <p class="stat-value">Rs. 1,185,000</p>
        <p class="stat-meta">This year</p>
      </article>

      <article class="stat-card">
        <p class="stat-label">Pending earnings</p>
        <p class="stat-value">Rs. 165,000</p>
        <p class="stat-meta">Awaiting settlement</p>
      </article>

      <article class="stat-card">
        <p class="stat-label">Completed earnings</p>
        <p class="stat-value">Rs. 1,020,000</p>
        <p class="stat-meta">Settled</p>
      </article>
    </div>
  </section>

  <section class="vehicle-income section-card" aria-labelledby="vehicle-income-heading">
    <header class="section-header">
      <h2 id="vehicle-income-heading">Vehicle-wise income</h2>
    </header>

    <div class="table-wrap">
      <table class="income-table" summary="Vehicle, income, deductions and net amounts">
        <thead>
          <tr>
            <th scope="col">Vehicle</th>
            <th scope="col">Income</th>
            <th scope="col">Deductions</th>
            <th scope="col">Net</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Toyota Aqua</td>
            <td>Rs. 285,000</td>
            <td class="deduction">-Rs. 12,000</td>
            <td>Rs. 273,000</td>
          </tr>
          <tr>
            <td>Toyota Premio</td>
            <td>Rs. 405,000</td>
            <td class="deduction">-Rs. 0</td>
            <td>Rs. 405,000</td>
          </tr>
          <tr>
            <td>Toyota KDH Van</td>
            <td>Rs. 495,000</td>
            <td class="deduction">-Rs. 25,000</td>
            <td>Rs. 470,000</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="settlement-history section-card" aria-labelledby="settlement-history-heading">
    <header class="section-header">
      <h2 id="settlement-history-heading">Settlement history</h2>
    </header>

    <ul class="settlement-list">
      <li class="settlement-item">
        <div class="settlement-info">
          <p class="settlement-title">Settlement · Jun 2026</p>
          <p class="settlement-sub">Paid to bank account</p>
        </div>
        <div class="settlement-meta">
          <p class="settlement-amount">Rs. 340,000</p>
          <span class="badge badge-paid">Paid</span>
        </div>
      </li>

      <li class="settlement-item">
        <div class="settlement-info">
          <p class="settlement-title">Settlement · May 2026</p>
          <p class="settlement-sub">Paid to bank account</p>
        </div>
        <div class="settlement-meta">
          <p class="settlement-amount">Rs. 320,000</p>
          <span class="badge badge-paid">Paid</span>
        </div>
      </li>

      <li class="settlement-item">
        <div class="settlement-info">
          <p class="settlement-title">Settlement · Apr 2026</p>
          <p class="settlement-sub">Paid to bank account</p>
        </div>
        <div class="settlement-meta">
          <p class="settlement-amount">Rs. 300,000</p>
          <span class="badge badge-paid">Paid</span>
        </div>
      </li>
    </ul>
  </section>
</main>
    </div>

</body>
</html>