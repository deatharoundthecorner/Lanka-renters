<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/OwnerChatController.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$controller = new OwnerChatController();
$actionMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionMessage = $controller->sendMessage();
}

$activeRoomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;
$chatData = $controller->getChatData($activeRoomId);

$rooms      = $chatData['success'] ? $chatData['rooms'] : [];
$activeRoom = $chatData['success'] ? $chatData['activeRoom'] : null;
$messages   = $chatData['success'] ? $chatData['messages'] : [];
$userId     = $chatData['userId'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Messaging & Chat - LankaRenters</title>
  <link rel="stylesheet" href="includes/assets/css/owner-style.css">
  <link rel="stylesheet" href="../assets/css/lr-tokens.css">
  <link rel="stylesheet" href="../assets/css/lr-shell.css">
  <link rel="stylesheet" href="../assets/css/lr-components.css">
    <link rel="stylesheet" href="../assets/css/lr-messages.css">
    <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <main class="main-content">
            <div class="page-header-box welcome-container" style="margin-bottom: 20px;">
                <h1 class="page-title" style="margin:0 0 4px; font-size:1.5rem; color:#0f172a;">Owner Messages</h1>
                <p class="page-subtitle" style="margin:0; font-size:0.9rem; color:#64748b;">Communicate with participants in booking conversations for your vehicles.</p>
            </div>

            <?php if ($actionMessage && !$actionMessage['success']): ?>
                <div class="alert alert-danger lr-message-alert">
                    <?php echo htmlspecialchars($actionMessage['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($rooms)): ?>
                <div class="lr-messages-layout">
                    <aside class="lr-messages-panel">
                        <h2>Booking conversations</h2>
                        <div class="lr-message-empty" style="min-height: 240px;">
                            <div class="lr-message-empty-icon">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <h3>No conversations yet</h3>
                            <p>Booking conversations appear for rentals involving one of your vehicles.</p>
                        </div>
                    </aside>
                    <section class="lr-messages-window" aria-live="polite">
                        <div class="lr-message-empty">
                            <div class="lr-message-empty-icon">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <h2>Select a conversation</h2>
                            <p>Messages are stored in MySQL and limited to verified booking participants.</p>
                        </div>
                    </section>
                </div>
            <?php else: ?>
                <div class="lr-messages-layout">
                    <!-- Left Sidebar (Conversations List & Picker) -->
                    <aside class="lr-messages-panel">
                        <h2>Booking conversations</h2>
                        <ul class="lr-message-rooms">
                            <?php foreach ($rooms as $r): ?>
                                <?php
                                    $isCurActive = $activeRoom && (int)$activeRoom['room_id'] === (int)$r['room_id'];
                                    $otherName = !empty($r['other_participant_name']) ? $r['other_participant_name'] : 'Customer / Driver';
                                    $otherRole = !empty($r['other_participant_role']) ? ucfirst($r['other_participant_role']) : 'User';
                                    $initial = strtoupper(substr($otherName, 0, 1));
                                ?>
                                <li class="lr-message-room">
                                    <a href="chat.php?room_id=<?php echo (int)$r['room_id']; ?>" <?php echo $isCurActive ? 'aria-current="page"' : ''; ?>>
                                        <div class="chat-avatar"><?php echo $initial; ?></div>
                                        <div class="chat-room-info">
                                            <div class="chat-room-title"><?php echo htmlspecialchars($otherName); ?> (<?php echo htmlspecialchars($otherRole); ?>)</div>
                                            <div class="chat-room-sub"><?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?> · Booking #<?php echo (int)$r['booking_id']; ?></div>
                                            <div class="chat-room-meta"><?php echo !empty($r['last_message_time']) ? date('M d, H:i', strtotime($r['last_message_time'])) : 'No messages yet'; ?></div>
                                        </div>
                                        <?php if (!empty($r['unread_count']) && (int)$r['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo (int)$r['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <form class="lr-message-picker" method="get" action="chat.php">
                            <label for="owner-room-picker">Open conversation for booking</label>
                            <select id="owner-room-picker" name="room_id" required>
                                <option value="">Choose booking</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?php echo (int)$r['room_id']; ?>" <?php echo $activeRoom && (int)$activeRoom['room_id'] === (int)$r['room_id'] ? 'selected' : ''; ?>>
                                        #<?php echo (int)$r['booking_id']; ?> — <?php echo htmlspecialchars(($r['make'] ?? '') . ' ' . ($r['model'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="button button-primary btn-primary">Open booking chat</button>
                        </form>
                    </aside>

                    <!-- Right Main Panel -->
                    <section class="lr-messages-window" aria-live="polite">
                        <?php if ($activeRoom): ?>
                            <?php
                                $activeName = !empty($activeRoom['other_participant_name']) ? $activeRoom['other_participant_name'] : 'Customer / Driver';
                                $activeRole = !empty($activeRoom['other_participant_role']) ? ucfirst($activeRoom['other_participant_role']) : 'User';
                                $activeInitial = strtoupper(substr($activeName, 0, 1));
                            ?>
                            <div class="lr-messages-header">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="chat-avatar"><?php echo $activeInitial; ?></div>
                                    <div>
                                        <p class="eyebrow">Booking #<?php echo (int)$activeRoom['booking_id']; ?> · <?php echo htmlspecialchars($activeRoom['make'] . ' ' . $activeRoom['model']); ?></p>
                                        <h2><?php echo htmlspecialchars($activeName); ?> <span style="font-size:13px; font-weight:500; color:var(--lr-msg-text-muted);">(<?php echo htmlspecialchars($activeRole); ?>)</span></h2>
                                    </div>
                                </div>
                                <a href="bookings.php?id=<?php echo (int)$activeRoom['booking_id']; ?>" style="font-size:12px; font-weight:600; color:var(--lr-msg-primary);">View Booking ↗</a>
                            </div>

                            <div class="lr-message-history" id="messagesArea">
                                <?php if (empty($messages)): ?>
                                    <div class="lr-message-empty" style="min-height: 200px;">
                                        <div class="lr-message-empty-icon">
                                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        </div>
                                        <h3>No messages yet</h3>
                                        <p>Send a message below to start communicating with <?php echo htmlspecialchars($activeName); ?>.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <?php $isOutgoing = (int)$msg['sender_id'] === (int)$userId; ?>
                                        <article class="lr-message-bubble <?php echo $isOutgoing ? 'is-own' : ''; ?>">
                                            <strong><?php echo $isOutgoing ? 'You' : htmlspecialchars($msg['sender_name']); ?></strong>
                                            <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                                            <time><?php echo date('d M Y, h:i A', strtotime($msg['sent_at'])); ?></time>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form class="lr-message-composer" action="chat.php?room_id=<?php echo (int)$activeRoom['room_id']; ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>" />
                                <input type="hidden" name="room_id" value="<?php echo (int)$activeRoom['room_id']; ?>" />
                                <label for="message_text" style="display:none;">Message</label>
                                <textarea name="message_text" id="message_text" placeholder="Type your message here..." required rows="2" style="min-height:60px;"></textarea>
                                <div class="lr-message-composer-row">
                                    <span class="form-hint" style="font-size:12px; color:var(--lr-msg-text-muted);">Plain text message limited to booking participants.</span>
                                    <button type="submit" class="button button-primary btn-primary">
                                        <span>Send Message</span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="lr-message-empty">
                                <div class="lr-message-empty-icon">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.15 7.15 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </div>
                                <h2>Select a conversation</h2>
                                <p>Choose an active rental conversation from the list to view messages.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

    <script>
        // Auto scroll chat to bottom on page load
        const msgArea = document.getElementById('messagesArea');
        if (msgArea) {
            msgArea.scrollTop = msgArea.scrollHeight;
        }
    </script>
</body>
</html>
