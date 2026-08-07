<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - LankaRenters</title>
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
  <section class="vehicles-header">
    <div class="vehicles-title">
      <h1>My vehicles</h1>
      <p>Manage your listed vehicles.</p>
    </div>
    <div class="vehicles-action">
      <button type="button" class="button button-primary">+ Add Vehicle</button>
    </div>
  </section>

  <section class="vehicle-grid" aria-label="Vehicle listing">
    <article class="vehicle-card">
      <div class="vehicle-image">
        <img src="path/to/toyota-aqua.jpg" alt="Toyota Aqua" />
      </div>
      <div class="vehicle-card-body">
        <div class="vehicle-card-meta">
          <span class="vehicle-title">Toyota Aqua</span>
          <span class="vehicle-badge">Verified</span>
        </div>
        <p class="vehicle-location">Colombo · Rs. 95,000/mo</p>
        <div class="vehicle-card-actions">
          <button type="button" class="button button-secondary">Edit</button>
          <button type="button" class="button button-outline">Bookings</button>
        </div>
      </div>
    </article>

    <article class="vehicle-card">
      <div class="vehicle-image">
        <img src="path/to/toyota-kdh-van.jpg" alt="Toyota KDH Van" />
      </div>
      <div class="vehicle-card-body">
        <div class="vehicle-card-meta">
          <span class="vehicle-title">Toyota KDH Van</span>
          <span class="vehicle-badge">Verified</span>
        </div>
        <p class="vehicle-location">Gampaha · Rs. 165,000/mo</p>
        <div class="vehicle-card-actions">
          <button type="button" class="button button-secondary">Edit</button>
          <button type="button" class="button button-outline">Bookings</button>
        </div>
      </div>
    </article>

    <article class="vehicle-card">
      <div class="vehicle-image">
        <img src="path/to/suzuki-wagon-r.jpg" alt="Suzuki Wagon R" />
      </div>
      <div class="vehicle-card-body">
        <div class="vehicle-card-meta">
          <span class="vehicle-title">Suzuki Wagon R</span>
          <span class="vehicle-badge">Verified</span>
        </div>
        <p class="vehicle-location">Kandy · Rs. 78,000/mo</p>
        <div class="vehicle-card-actions">
          <button type="button" class="button button-secondary">Edit</button>
          <button type="button" class="button button-outline">Bookings</button>
        </div>
      </div>
    </article>

    <article class="vehicle-card">
      <div class="vehicle-image">
        <img src="path/to/toyota-premio.jpg" alt="Toyota Premio" />
      </div>
      <div class="vehicle-card-body">
        <div class="vehicle-card-meta">
          <span class="vehicle-title">Toyota Premio</span>
          <span class="vehicle-badge">Verified</span>
        </div>
        <p class="vehicle-location">Colombo · Rs. 135,000/mo</p>
        <div class="vehicle-card-actions">
          <button type="button" class="button button-secondary">Edit</button>
          <button type="button" class="button button-outline">Bookings</button>
        </div>
      </div>
    </article>
  </section>

  <section class="vehicle-form-section">
    <div class="form-card">
      <div class="form-card-header">
        <h2>Add a vehicle</h2>
        <p>Upload the required documents for admin verification.</p>
      </div>

      <form class="vehicle-form" enctype="multipart/form-data">
        <div class="form-row">
          <label class="form-field">
            <span>Vehicle name</span>
            <input type="text" name="vehicle_name" placeholder="e.g. Toyota Aqua" />
          </label>

          <label class="form-field">
            <span>Vehicle type</span>
            <input type="text" name="vehicle_type" placeholder="Car" />
          </label>
        </div>

        <div class="form-row">
          <label class="form-field">
            <span>District</span>
            <input type="text" name="district" placeholder="Colombo" />
          </label>

          <label class="form-field">
            <span>Monthly price</span>
            <input type="number" name="monthly_price" placeholder="95000" />
          </label>
        </div>

        <div class="document-grid">
          <label class="upload-zone">
            <span>Registration Certificate</span>
            <input type="file" name="document_registration" />
          </label>

          <label class="upload-zone">
            <span>Insurance</span>
            <input type="file" name="document_insurance" />
          </label>

          <label class="upload-zone">
            <span>Revenue License</span>
            <input type="file" name="document_revenue" />
          </label>

          <label class="upload-zone">
            <span>Vehicle images</span>
            <input type="file" name="document_images" multiple />
          </label>

          <label class="upload-zone">
            <span>Inspection report</span>
            <input type="file" name="document_inspection" />
          </label>
        </div>

        <div class="form-actions">
          <button type="submit" class="button button-primary">Submit for Verification</button>
        </div>
      </form>
    </div>
  </section>
</main>
    </div>

</body>
</html>