<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/ChatController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();
AuthHelper::requireRole('driver');
$user = AuthHelper::getCurrentUser();

$chatController = new ChatController();
$roomId = (int)($_GET['room_id'] ?? 0);
if ($roomId <= 0) {
    header("Location: messages.php");
    exit();
}
$roomsResult = $chatController->getDriverRooms();
$rooms = $roomsResult['success'] ? $roomsResult['rooms'] : [];

$error = '';
$success = '';

// Send message processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // Validate CSRF token
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $messageText = $_POST['message_text'] ?? '';
        $res = $chatController->sendChatMessage($roomId, $messageText);
        if ($res['success']) {
            header("Location: chat.php?room_id=" . $roomId);
            exit();
        } else {
            $error = $res['error'];
        }
    }
}

// Fetch messages
$msgResult = $chatController->getRoomMessages($roomId);
if (!$msgResult['success']) {
    error_log('Driver chat load failed: ' . ($msgResult['error'] ?? 'Unknown error'));
    die('The page could not be loaded. Please try again later.');
}
$messages = $msgResult['messages'];

// Fetch assigned bookings for picker
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
$driverController = new DriverController();
$tripsResult = $driverController->viewTrips();
$assignedBookings = $tripsResult['success'] ? array_merge($tripsResult['active_trips'], $tripsResult['completed_trips']) : [];

// Fetch other participant through controller
$partResult = $chatController->getOtherParticipant($roomId);
$otherParticipant = $partResult['success'] ? $partResult['participant'] : null;

$roomTitle = $otherParticipant ? htmlspecialchars($otherParticipant['name']) . ' (' . ucfirst($otherParticipant['role']) . ')' : 'Chat Room #' . $roomId;
$pageTitle = "Chat - Lanka Renters";
$activePage = "messages";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container" style="margin-bottom: 20px;">
        <div>
            <h2 class="welcome-title" style="margin:0 0 4px; font-size:1.5rem; color:#0f172a;">Driver Messages</h2>
            <p class="welcome-subtitle" style="margin:0; font-size:0.9rem; color:#64748b;">Assigned-booking conversation &middot; Room #<?php echo $roomId; ?></p>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger lr-message-alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Chat Container -->
    <div class="lr-messages-layout">
        <aside class="lr-messages-panel">
            <h2>Booking conversations</h2>
            <?php if (empty($rooms)): ?>
                <div class="lr-message-empty" style="min-height: 240px;">
                    <div class="lr-message-empty-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3>No conversations yet</h3>
                    <p>Messages will appear when assigned to an eligible rental.</p>
                </div>
            <?php else: ?>
                <ul class="lr-message-rooms">
                    <?php foreach ($rooms as $room): ?>
                        <li class="lr-message-room">
                            <a href="chat.php?room_id=<?php echo (int)$room['room_id']; ?>" <?php echo (int)$room['room_id'] === $roomId ? 'aria-current="page"' : ''; ?>>
                                <div class="chat-room-info">
                                    <div class="chat-room-title">Booking #<?php echo (int)$room['booking_id']; ?></div>
                                    <div class="chat-room-sub"><?php echo htmlspecialchars(($room['make'] ?? '') . ' ' . ($room['model'] ?? '')); ?></div>
                                    <div class="chat-room-meta"><?php echo htmlspecialchars($room['last_message'] ?: 'No messages yet'); ?></div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($assignedBookings)): ?>
                <form class="lr-message-picker" action="messages.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="start_chat">
                    <label for="booking_id">Open conversation for booking</label>
                    <select name="booking_id" id="booking_id" required>
                        <option value="">Choose booking</option>
                        <?php foreach ($assignedBookings as $b): ?>
                            <option value="<?php echo (int)$b['id']; ?>">#<?php echo (int)$b['id']; ?> — <?php echo htmlspecialchars(($b['customer_name'] ?? 'Assigned rental')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button button-primary btn-primary">Open booking chat</button>
                </form>
            <?php endif; ?>
        </aside>

        <section class="lr-messages-window" aria-live="polite">
            <!-- Active Header -->
            <div class="lr-messages-header">
                <div>
                    <p class="eyebrow">Assigned Booking &middot; Room #<?php echo $roomId; ?></p>
                    <h2>Chat with <?php echo $roomTitle; ?></h2>
                </div>
                <a href="trips.php" style="font-size:12px; font-weight:600; color:var(--lr-msg-primary);">Assigned Trips ↗</a>
            </div>

            <!-- Messages Area -->
            <div class="lr-message-history" id="chatBox">
                <?php if (empty($messages)): ?>
                    <div class="lr-message-empty" style="min-height: 200px;">
                        <div class="lr-message-empty-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h3>No messages yet</h3>
                        <p>Send a message below to begin communicating about this trip.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $isSelf = ((int)$msg['sender_id'] === (int)$user['id']); ?>
                        <article class="lr-message-bubble <?php echo $isSelf ? 'is-own' : ''; ?>">
                            <strong><?php echo $isSelf ? 'You' : htmlspecialchars($msg['sender_name'] . ' (' . ucfirst($msg['sender_role']) . ')'); ?></strong>
                            <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                            <time><?php echo date('d M Y, h:i A', strtotime($msg['sent_at'])); ?></time>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Form for sending message -->
            <form action="" method="POST" class="lr-message-composer">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="send_message">
                <label for="message_text" style="display:none;">Message</label>
                <input type="text" name="message_text" id="message_text" class="form-control" placeholder="Type your message here..." required autofocus autocomplete="off">
                <div class="lr-message-composer-row">
                    <span class="form-hint" style="font-size:12px; color:var(--lr-msg-text-muted);">Plain text message limited to assigned booking participants.</span>
                    <button type="submit" class="button button-primary btn-primary">
                        <span>Send Message</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        // Auto scroll to bottom of the chat box
        var chatBox = document.getElementById('chatBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</main>
<?php
include 'includes/footer.php';
?>
