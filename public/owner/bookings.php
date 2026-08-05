<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - LankaRenters</title>
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
  <section class="booking-header">
    <div class="booking-title">
      <h1>Booking management</h1>
      <p>Approve or reject requests within 12 hours.</p>
    </div>
  </section>

  <section class="booking-alert" role="status">
    <span class="alert-icon" aria-hidden="true">⏰</span>
    <p>You must respond to each booking request within <strong>12 hours</strong> or it expires.</p>
  </section>

  <section class="booking-table-section">
    <table class="booking-table">
      <caption class="sr-only">Booking requests</caption>
      <thead>
        <tr>
          <th scope="col">Booking</th>
          <th scope="col">Customer</th>
          <th scope="col">Vehicle</th>
          <th scope="col">Status</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>BK-1042</td>
          <td>Amaya Jayasuriya</td>
          <td>Toyota Aqua</td>
          <td><span class="badge badge-active">Active</span></td>
          <td>—</td>
        </tr>
        <tr>
          <td>BK-1051</td>
          <td>Ruwan Bandara</td>
          <td>Suzuki Wagon R</td>
          <td><span class="badge badge-pending-owner">Pending Owner Approval</span></td>
          <td>
            <button type="button" class="button button-primary">Approve</button>
            <button type="button" class="button button-outline">Reject</button>
          </td>
        </tr>
        <tr>
          <td>BK-1033</td>
          <td>Dilani Weerasinghe</td>
          <td>Toyota KDH Van</td>
          <td><span class="badge badge-payment">Payment Pending</span></td>
          <td>—</td>
        </tr>
        <tr>
          <td>BK-1020</td>
          <td>Tharindu Alwis</td>
          <td>Toyota Premio</td>
          <td><span class="badge badge-completed">Completed</span></td>
          <td>—</td>
        </tr>
      </tbody>
    </table>
  </section>
</main>
    </div>

</body>
</html>