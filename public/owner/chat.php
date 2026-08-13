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
    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 160px);
            min-height: 520px;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .chat-rooms-sidebar {
            width: 320px;
            border-right: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .chat-rooms-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            font-size: 1.1rem;
            color: #0f172a;
        }

        .chat-rooms-list {
            flex: 1;
            overflow-y: auto;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .chat-room-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s ease;
        }

        .chat-room-item a:hover,
        .chat-room-item.active a {
            background-color: #eff6ff;
        }

        .chat-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .chat-room-info {
            flex: 1;
            min-width: 0;
        }

        .chat-room-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-room-sub {
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-room-meta {
            text-align: right;
            font-size: 0.75rem;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .unread-badge {
            background-color: #ef4444;
            color: #ffffff;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-top: 4px;
            display: inline-block;
        }

        .chat-main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }

        .chat-panel-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 14px;
            background-color: #ffffff;
        }

        .chat-messages-area {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background-color: #f8fafc;
        }

        .chat-bubble {
            max-width: 65%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 0.92rem;
            line-height: 1.45;
            position: relative;
            word-wrap: break-word;
        }

        .chat-bubble.outgoing {
            align-self: flex-end;
            background-color: #2563eb;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .chat-bubble.incoming {
            align-self: flex-start;
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .chat-bubble-meta {
            font-size: 0.72rem;
            margin-top: 4px;
            opacity: 0.8;
            text-align: right;
        }

        .chat-input-bar {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            background-color: #ffffff;
        }

        .chat-input-form {
            display: flex;
            gap: 12px;
        }

        .chat-input-field {
            flex: 1;
            padding: 12px 18px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .chat-input-field:focus {
            border-color: #2563eb;
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-wrapper">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
                <section class="vehicles-header" style="margin-bottom: 20px;">
                    <div class="vehicles-title">
                        <h1>Owner Chat & Communications</h1>
                        <p>Communicate directly with customers, drivers, and support regarding vehicle bookings.</p>
                    </div>
                </section>

                <?php if ($actionMessage && !$actionMessage['success']): ?>
                    <div style="padding: 12px 18px; background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 12px; margin-bottom: 16px;">
                        <?php echo htmlspecialchars($actionMessage['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($rooms)): ?>
                    <div class="form-card" style="padding: 48px; text-align: center; color: #64748b;">
                        <div style="font-size: 3rem; margin-bottom: 14px;">💬</div>
                        <h3 style="margin: 0 0 8px; color: #0f172a;">No Active Conversations</h3>
                        <p style="margin: 0; font-size: 0.92rem;">Conversations automatically start whenever customers or drivers place a booking for your vehicles.</p>
                    </div>
                <?php else: ?>
                    <div class="chat-container">
                        <!-- Left Rooms Sidebar -->
                        <div class="chat-rooms-sidebar">
                            <div class="chat-rooms-header">Conversations</div>
                            <ul class="chat-rooms-list">
                                <?php foreach ($rooms as $r): ?>
                                    <?php
                                        $isCurActive = $activeRoom && (int)$activeRoom['room_id'] === (int)$r['room_id'];
                                        $otherName = !empty($r['other_participant_name']) ? $r['other_participant_name'] : 'Customer / Driver';
                                        $otherRole = !empty($r['other_participant_role']) ? ucfirst($r['other_participant_role']) : 'User';
                                        $initial = strtoupper(substr($otherName, 0, 1));
                                    ?>
                                    <li class="chat-room-item <?php echo $isCurActive ? 'active' : ''; ?>">
                                        <a href="chat.php?room_id=<?php echo (int)$r['room_id']; ?>">
                                            <div class="chat-avatar"><?php echo $initial; ?></div>
                                            <div class="chat-room-info">
                                                <div class="chat-room-title"><?php echo htmlspecialchars($otherName); ?></div>
                                                <div class="chat-room-sub">
                                                    <?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?> (Book #<?php echo (int)$r['booking_id']; ?>)
                                                </div>
                                            </div>
                                            <div class="chat-room-meta">
                                                <div><?php echo !empty($r['last_message_time']) ? date('H:i', strtotime($r['last_message_time'])) : ''; ?></div>
                                                <?php if (!empty($r['unread_count']) && (int)$r['unread_count'] > 0): ?>
                                                    <span class="unread-badge"><?php echo (int)$r['unread_count']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Right Main Chat Panel -->
                        <div class="chat-main-panel">
                            <?php if ($activeRoom): ?>
                                <?php
                                    $activeName = !empty($activeRoom['other_participant_name']) ? $activeRoom['other_participant_name'] : 'Customer / Driver';
                                    $activeRole = !empty($activeRoom['other_participant_role']) ? ucfirst($activeRoom['other_participant_role']) : 'User';
                                    $activeInitial = strtoupper(substr($activeName, 0, 1));
                                ?>
                                <div class="chat-panel-header">
                                    <div class="chat-avatar"><?php echo $activeInitial; ?></div>
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 1.05rem;"><?php echo htmlspecialchars($activeName); ?> (<?php echo htmlspecialchars($activeRole); ?>)</div>
                                        <div style="font-size: 0.84rem; color: #64748b;">
                                            Vehicle: <?php echo htmlspecialchars($activeRoom['make'] . ' ' . $activeRoom['model'] . ' (' . $activeRoom['license_plate'] . ')'); ?> • Booking #<?php echo (int)$activeRoom['booking_id']; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="chat-messages-area" id="messagesArea">
                                    <?php if (empty($messages)): ?>
                                        <div style="text-align: center; color: #94a3b8; margin: auto; font-size: 0.9rem;">
                                            No messages yet. Send a message below to start the conversation!
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $msg): ?>
                                            <?php $isOutgoing = (int)$msg['sender_id'] === (int)$userId; ?>
                                            <div class="chat-bubble <?php echo $isOutgoing ? 'outgoing' : 'incoming'; ?>">
                                                <div style="font-size: 0.76rem; font-weight: 600; margin-bottom: 3px; <?php echo $isOutgoing ? 'color:#dbeafe;' : 'color:#475569;'; ?>">
                                                    <?php echo $isOutgoing ? 'You' : htmlspecialchars($msg['sender_name']); ?>
                                                </div>
                                                <div><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                                <div class="chat-bubble-meta">
                                                    <?php echo date('M d, H:i', strtotime($msg['sent_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="chat-input-bar">
                                    <form class="chat-input-form" action="chat.php?room_id=<?php echo (int)$activeRoom['room_id']; ?>" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>" />
                                        <input type="hidden" name="room_id" value="<?php echo (int)$activeRoom['room_id']; ?>" />
                                        <input type="text" name="message_text" class="chat-input-field" placeholder="Type your message here..." required autocomplete="off" />
                                        <button type="submit" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 14px; padding: 12px 20px;">
                                            <span>Send</span>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div style="margin: auto; text-align: center; color: #64748b;">
                                    <p>Select a room from the left panel to view messages.</p>
                                </div>
                            <?php endif; ?>
                        </div>
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