<?php
// announcements.php - Lanka Renters System Announcements & Notifications Page
require_once __DIR__ . '/config/database.php';
requireAdminLogin();

$pageTitle = "Announcements";
$announcementTypes = getAnnouncementTypes();
$targetAudiences = getTargetAudiences();
$priorities = getAnnouncementPriorities();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main class="page-container">
            <div class="page-header-box" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 class="page-title">Announcements</h1>
                    <p class="page-subtitle">Create and manage system-wide notices, maintenance alerts, and policy notifications.</p>
                </div>
                <button class="btn btn-primary" onclick="openCreateAnnouncementModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Create Announcement</span>
                </button>
            </div>

            <!-- TOP STATISTICS CARDS (4 CARDS) -->
            <div class="grid-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Announcements</span>
                        <div class="stat-icon-box">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">24</div>
                    <div class="stat-comparison neutral">All announcements created</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Published</span>
                        <div class="stat-icon-box green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">18</div>
                    <div class="stat-comparison">Currently visible notices</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Drafts</span>
                        <div class="stat-icon-box amber">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">4</div>
                    <div class="stat-comparison neutral">Waiting to be published</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Scheduled</span>
                        <div class="stat-icon-box purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                        </div>
                    </div>
                    <div class="stat-value">2</div>
                    <div class="stat-comparison neutral">Upcoming scheduled notices</div>
                </div>
            </div>

            <!-- ANNOUNCEMENT LIVE PREVIEW & RECENT TIMELINE SECTION -->
            <div class="grid-2">
                <!-- Dynamic Live Preview Card -->
                <div>
                    <h3 class="card-title-text" style="margin-bottom: 12px;">Live Announcement Preview</h3>
                    <div class="announcement-preview-card" id="livePreviewContainer">
                        <div class="preview-header">
                            <div class="preview-title-box">
                                <div class="preview-bell">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                </div>
                                <div>
                                    <h4 class="preview-title" id="livePrevTitle">Scheduled System Maintenance</h4>
                                    <span class="cell-secondary-text" id="livePrevType">Maintenance</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <span class="badge badge-high" id="livePrevPriority">High Priority</span>
                                <span class="badge badge-blue" id="livePrevAudience">All Users</span>
                            </div>
                        </div>

                        <div class="preview-body" id="livePrevBody">
                            Lanka Renters will be temporarily unavailable on Sunday from 10:00 PM to 12:00 AM due to scheduled database maintenance. Please complete any important bookings before the maintenance period.
                        </div>

                        <div class="preview-footer">
                            <span>10 Aug 2026 • 10:00 PM</span>
                            <button class="btn btn-doc btn-sm" onclick="showToast('Previewing announcement details...', 'info')">View Details</button>
                        </div>
                    </div>
                </div>

                <!-- Recent Announcement Activity Timeline -->
                <div class="card">
                    <div class="card-header-clean">
                        <h3 class="card-title-text">Recent Announcement Activity</h3>
                    </div>
                    <div class="timeline-container">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <strong>Admin created "Scheduled Maintenance"</strong>
                                <div class="timeline-time">5 minutes ago</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background: var(--success);"></div>
                            <div class="timeline-content">
                                <strong>"Payment System Update" was published</strong>
                                <div class="timeline-time">1 hour ago</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background: var(--warning);"></div>
                            <div class="timeline-content">
                                <strong>Admin edited "Booking Notice"</strong>
                                <div class="timeline-time">3 hours ago</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-dot" style="background: var(--text-muted);"></div>
                            <div class="timeline-content">
                                <strong>"Holiday Service Notice" expired</strong>
                                <div class="timeline-time">Yesterday</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANNOUNCEMENT MANAGEMENT TABLE & FILTERS -->
            <div class="card">
                <div class="card-header-clean">
                    <h3 class="card-title-text">Announcement Management</h3>
                </div>

                <!-- Filter Card -->
                <div class="filter-card">
                    <div class="filter-group">
                        <label for="filterSearch">Search Announcement</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Search title or ID...">
                    </div>
                    <div class="filter-group">
                        <label for="filterType">Type</label>
                        <select id="filterType" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach ($announcementTypes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterStatus">Status</label>
                        <select id="filterStatus" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Published">Published</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterPriority">Priority</label>
                        <select id="filterPriority" class="form-control">
                            <option value="">All Priorities</option>
                            <?php foreach ($priorities as $p): ?>
                                <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 8px; align-self: flex-end;">
                        <button class="btn btn-primary" onclick="initTableFilters()">Search</button>
                        <button class="btn btn-secondary" id="filterResetBtn">Reset</button>
                    </div>
                </div>

                <!-- Announcements Table -->
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Announcement ID</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Target Audience</th>
                                <th>Priority</th>
                                <th>Published Date</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="cell-secondary-text">ANN-001</span></td>
                                <td><div class="cell-primary-text">Scheduled System Maintenance</div></td>
                                <td>Maintenance</td>
                                <td><span class="badge badge-blue">All Users</span></td>
                                <td><span class="badge badge-high">High</span></td>
                                <td>10 Aug 2026</td>
                                <td><span class="badge badge-approved">Published</span></td>
                                <td>Admin</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openViewAnnouncement('ANN-001', 'Scheduled System Maintenance', 'Maintenance', 'All Users', 'High', 'Published', '10 Aug 2026', 'Admin', 'Lanka Renters will be temporarily unavailable on Sunday from 10:00 PM to 12:00 AM due to scheduled database maintenance.')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditAnnouncement('ANN-001', 'Scheduled System Maintenance', 'Maintenance', 'All Users', 'High', 'Published', 'Lanka Renters will be temporarily unavailable on Sunday from 10:00 PM to 12:00 AM due to scheduled database maintenance.')">Edit</button>
                                        <button class="btn btn-secondary btn-sm" onclick="togglePublishStatus('ANN-001', 'Published')">Unpublish</button>
                                        <button class="btn btn-reject btn-sm" onclick="openDeleteAnnouncement('ANN-001', 'Scheduled System Maintenance')">Delete</button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td><span class="cell-secondary-text">ANN-002</span></td>
                                <td><div class="cell-primary-text">Payment Gateway Integration Upgrade</div></td>
                                <td>Payment Notice</td>
                                <td><span class="badge badge-blue">Customers</span></td>
                                <td><span class="badge badge-normal">Important</span></td>
                                <td>09 Aug 2026</td>
                                <td><span class="badge badge-approved">Published</span></td>
                                <td>Admin</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openViewAnnouncement('ANN-002', 'Payment Gateway Integration Upgrade', 'Payment Notice', 'Customers', 'Important', 'Published', '09 Aug 2026', 'Admin', 'We have added new online banking options for faster payment verification on rental bookings.')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditAnnouncement('ANN-002', 'Payment Gateway Integration Upgrade', 'Payment Notice', 'Customers', 'Important', 'Published', 'We have added new online banking options for faster payment verification on rental bookings.')">Edit</button>
                                        <button class="btn btn-secondary btn-sm" onclick="togglePublishStatus('ANN-002', 'Published')">Unpublish</button>
                                        <button class="btn btn-reject btn-sm" onclick="openDeleteAnnouncement('ANN-002', 'Payment Gateway Integration Upgrade')">Delete</button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td><span class="cell-secondary-text">ANN-003</span></td>
                                <td><div class="cell-primary-text">Owner Commission Payout Guidelines</div></td>
                                <td>Important Notice</td>
                                <td><span class="badge badge-blue">Vehicle Owners</span></td>
                                <td><span class="badge badge-normal">Normal</span></td>
                                <td>12 Aug 2026</td>
                                <td><span class="badge badge-scheduled">Scheduled</span></td>
                                <td>Admin</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openViewAnnouncement('ANN-003', 'Owner Commission Payout Guidelines', 'Important Notice', 'Vehicle Owners', 'Normal', 'Scheduled', '12 Aug 2026', 'Admin', 'Settlement calculations are processed every Monday. Please ensure bank details are up to date.')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditAnnouncement('ANN-003', 'Owner Commission Payout Guidelines', 'Important Notice', 'Vehicle Owners', 'Normal', 'Scheduled', 'Settlement calculations are processed every Monday. Please ensure bank details are up to date.')">Edit</button>
                                        <button class="btn btn-approve btn-sm" onclick="togglePublishStatus('ANN-003', 'Scheduled')">Publish</button>
                                        <button class="btn btn-reject btn-sm" onclick="openDeleteAnnouncement('ANN-003', 'Owner Commission Payout Guidelines')">Delete</button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td><span class="cell-secondary-text">ANN-004</span></td>
                                <td><div class="cell-primary-text">Driver Verification Policy Update</div></td>
                                <td>System Update</td>
                                <td><span class="badge badge-blue">Drivers</span></td>
                                <td><span class="badge badge-normal">Normal</span></td>
                                <td>08 Aug 2026</td>
                                <td><span class="badge badge-draft">Draft</span></td>
                                <td>Admin</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-doc btn-sm" onclick="openViewAnnouncement('ANN-004', 'Driver Verification Policy Update', 'System Update', 'Drivers', 'Normal', 'Draft', '08 Aug 2026', 'Admin', 'All drivers must submit updated commercial driving license copies before the end of the month.')">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditAnnouncement('ANN-004', 'Driver Verification Policy Update', 'System Update', 'Drivers', 'Normal', 'Draft', 'All drivers must submit updated commercial driving license copies before the end of the month.')">Edit</button>
                                        <button class="btn btn-approve btn-sm" onclick="togglePublishStatus('ANN-004', 'Draft')">Publish</button>
                                        <button class="btn btn-reject btn-sm" onclick="openDeleteAnnouncement('ANN-004', 'Driver Verification Policy Update')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    <span class="pagination-info">Showing 1 to 4 of 24 entries</span>
                    <div class="pagination-controls">
                        <button class="page-btn" disabled>Previous</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/modals.php'; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
