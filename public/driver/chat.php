<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/ChatController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

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
$roomId = (int)($_GET['room_id'] ?? 0);

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
    die("Error loading chat: " . htmlspecialchars($msgResult['error']));
}
$messages = $msgResult['messages'];

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
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Chat with <?php echo $roomTitle; ?></h2>
            <p class="welcome-subtitle">Direct conversation for booking room #<?php echo $roomId; ?>.</p>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Chat Container -->
    <div class="chat-container">
        <!-- Messages Area -->
        <div class="chat-box" id="chatBox">
            <?php if (empty($messages)): ?>
                <p style="text-align: center; color: var(--text-muted); font-style: italic; margin-top:20px; font-size:14px;">No messages exchanged yet in this room. Send a message to start the conversation.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php $isSelf = ((int)$msg['sender_id'] === (int)$user['id']); ?>
                    <div class="msg-bubble <?php echo $isSelf ? 'self' : 'other'; ?>">
                        <div class="msg-header">
                            <?php echo htmlspecialchars($msg['sender_name']); ?> (<?php echo ucfirst($msg['sender_role']); ?>)
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                        <div class="msg-time"><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Form for sending message -->
        <form action="" method="POST" style="display: flex; gap: 10px; margin: 0; padding: 15px; background: #fff; border-top: 1px solid var(--border);">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
            <input type="hidden" name="action" value="send_message">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label for="message_text" class="form-label" style="display:none;">Message</label>
                <input type="text" name="message_text" id="message_text" class="form-control" placeholder="Type your message here..." required autofocus autocomplete="off" style="width: 100%;">
            </div>
            <button type="submit" class="btn-blue" style="height: 42px; padding: 0 25px; border-radius: 4px;">Send</button>
        </form>
    </div>

    <script>
        // Auto scroll to bottom of the chat box
        var chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</main>
<?php
include 'includes/footer.php';
?>
