<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/ChatController.php';

AuthHelper::startSession();

// Localized driver auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'driver') {
    AuthHelper::logout();
    header("Location: login.php");
    exit();
}

$chatController = new ChatController();
$error = '';
$success = '';

// Handle creating room for a booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_chat') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $result = $chatController->createBookingRoom($bookingId);
    if ($result['success']) {
        header("Location: chat.php?room_id=" . $result['room_id']);
        exit();
    } else {
        $error = $result['error'];
    }
}

// Fetch active rooms
$roomsResult = $chatController->getDriverRooms();
$rooms = $roomsResult['success'] ? $roomsResult['rooms'] : [];

// Fetch assigned bookings
$driverModel = new Driver();
$driver = $driverModel->findByUserId($user['id']);
$assignedBookings = $driver ? $driverModel->getBookings($driver['id']) : [];

// Page config
$pageTitle = "Conversations - Lanka Renters";
$activePage = "messages";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Driver Messages</h2>
            <p class="welcome-subtitle">Chat directly with customers, vehicle owners, or system administrators.</p>
        </div>
    </div>

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

    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 30px; align-items: flex-start;">
        <!-- Left: Conversations List -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Conversations</h2>
            <?php if (empty($rooms)): ?>
                <p style="font-style: italic; color: var(--text-muted);">No active conversations found.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($rooms as $room): ?>
                        <div style="border: 1px solid var(--border); border-radius: 6px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; background-color: #ffffff;">
                            <div>
                                <div style="font-weight: 700; font-size: 15px; color: var(--text-main); margin-bottom: 4px;">
                                    Booking #<?php echo htmlspecialchars($room['booking_id']); ?> 
                                    (Chat with <?php echo htmlspecialchars($room['other_participant_name'] ?? 'System'); ?> - <span style="text-transform: capitalize; font-size:12px; color: var(--text-muted);"><?php echo htmlspecialchars($room['other_participant_role'] ?? 'Participant'); ?></span>)
                                </div>
                                <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 4px;">
                                    <?php echo !empty($room['last_message']) ? htmlspecialchars($room['last_message']) : '<em>No messages yet.</em>'; ?>
                                </div>
                                <div style="font-size: 11px; color: #999;">
                                    <?php echo !empty($room['last_message_time']) ? date('Y-m-d H:i', strtotime($room['last_message_time'])) : ''; ?>
                                </div>
                            </div>
                            <div>
                                <a href="chat.php?room_id=<?php echo (int)$room['room_id']; ?>" class="btn-blue" style="font-size: 13px; padding: 8px 16px;">Open Chat</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Start a New Chat -->
        <div class="card" style="margin: 0; background-color: #f8fafc;">
            <h2 class="card-title">Start New Chat</h2>
            <?php if (empty($assignedBookings)): ?>
                <p style="font-style: italic; color: var(--text-muted); font-size: 13px;">No assigned bookings found to initiate conversations.</p>
            <?php else: ?>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="start_chat">
                    
                    <div class="form-group">
                        <label for="booking_id" class="form-label">Choose Booking Trip</label>
                        <select name="booking_id" id="booking_id" class="form-control" required>
                            <option value="">-- Choose Booking --</option>
                            <?php foreach ($assignedBookings as $b): ?>
                                <option value="<?php echo (int)$b['id']; ?>">
                                    Booking #<?php echo htmlspecialchars($b['id']); ?> (<?php echo htmlspecialchars($b['customer_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 10px;">Initialize Chat</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
