<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/ChatController.php';

AuthHelper::startSession();
AuthHelper::requireRole('driver');

$chatController = new ChatController();
$error = '';
$success = '';

// Handle creating room for a booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_chat') {
    // Validate CSRF token
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $result = $chatController->createBookingRoom($bookingId);
        if ($result['success']) {
            header("Location: chat.php?room_id=" . $result['room_id']);
            exit();
        } else {
            $error = $result['error'];
        }
    }
}

// Fetch active rooms
$roomsResult = $chatController->getDriverRooms();
$rooms = $roomsResult['success'] ? $roomsResult['rooms'] : [];

// Fetch assigned bookings via controller
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
$driverController = new DriverController();
$tripsResult = $driverController->viewTrips();
$assignedBookings = $tripsResult['success'] ? array_merge($tripsResult['active_trips'], $tripsResult['completed_trips']) : [];

// Page config
$pageTitle = "Conversations - Lanka Renters";
$activePage = "messages";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container"><div><h2 class="welcome-title">Messages</h2><p class="welcome-subtitle">Chat with customers about bookings currently assigned to you.</p></div></div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="lr-messages-layout">
        <aside class="lr-messages-panel">
            <h2>Booking conversations</h2>
            <?php if (empty($rooms)): ?>
                <div class="lr-message-empty" style="min-height: 240px;">
                    <div class="lr-message-empty-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3>No eligible conversations</h3>
                    <p>Messages become available when you are assigned to an eligible rental.</p>
                </div>
            <?php else: ?>
                <ul class="lr-message-rooms">
                    <?php foreach ($rooms as $room): ?>
                        <li class="lr-message-room">
                            <a href="chat.php?room_id=<?php echo (int)$room['room_id']; ?>">
                                <div class="chat-room-info">
                                    <div class="chat-room-title">Booking #<?php echo (int)$room['booking_id']; ?></div>
                                    <div class="chat-room-sub"><?php echo htmlspecialchars(($room['make'] ?? '') . ' ' . ($room['model'] ?? '')); ?></div>
                                    <div class="chat-room-meta"><?php echo !empty($room['last_message']) ? htmlspecialchars($room['last_message']) : 'No messages yet'; ?></div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form class="lr-message-picker" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="start_chat">
                <label for="booking_id">Open conversation for booking</label>
                <select name="booking_id" id="booking_id" required>
                    <option value="">Choose booking</option>
                    <?php foreach ($assignedBookings as $b): ?>
                        <option value="<?php echo (int)$b['id']; ?>">#<?php echo (int)$b['id']; ?> — <?php echo htmlspecialchars(($b['customer_name'] ?? 'Assigned rental')); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button button-primary btn-primary" <?php echo empty($assignedBookings) ? 'disabled' : ''; ?>>Open booking chat</button>
                <?php if (empty($assignedBookings)): ?>
                    <p class="form-hint">No eligible assigned rental is available.</p>
                <?php endif; ?>
            </form>
        </aside>
        <section class="lr-messages-window" aria-live="polite">
            <div class="lr-message-empty">
                <div class="lr-message-empty-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h2>Select a conversation</h2>
                <p>Messages are stored in MySQL and limited to assigned booking participants.</p>
            </div>
        </section>
    </div>
</main>
