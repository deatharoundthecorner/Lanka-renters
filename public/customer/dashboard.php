<?php
$pageTitle = "Dashboard";
include 'components/layout/header.php';
?>

<div class="dashboard">

    <?php include 'components/layout/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'components/layout/navbar.php'; ?>

        <main class="page-content">

            <div class="content-wrapper">

                <?php
                $pageHeading = "Dashboard";
                $pageDescription = "Welcome back to Lanka Renters.";
                include 'components/layout/page-header.php';
                ?>

                <!-- Statistics -->
                <section class="stat-grid">

                    <?php

                    $title="Current Bookings";
                    $value="1";
                    $icon="booking";
                    include 'components/dashboard/stat-card.php';

                    $title="Upcoming";
                    $value="2";
                    $icon="calendar";
                    include 'components/dashboard/stat-card.php';

                    $title="Completed";
                    $value="15";
                    $icon="success";
                    include 'components/dashboard/stat-card.php';

                    $title="Pending Payments";
                    $value="LKR 18,500";
                    $icon="payment";
                    include 'components/dashboard/stat-card.php';

                    ?>

                </section>

                <!-- Quick Actions -->

                <section class="quick-actions">

                    <?php

                    $title="Search Vehicle";
                    include 'components/dashboard/quick-action-card.php';

                    $title="Book Vehicle";
                    include 'components/dashboard/quick-action-card.php';

                    $title="Payments";
                    include 'components/dashboard/quick-action-card.php';

                    $title="Chat";
                    include 'components/dashboard/quick-action-card.php';

                    ?>

                </section>

            </div>

        </main>

    </div>

</div>

<?php include 'components/layout/footer.php'; ?>