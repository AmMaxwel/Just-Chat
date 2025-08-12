<?php
session_start();
require('../db.php');

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];

// === Handle message send ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message'] ?? '');
    $message = mysqli_real_escape_string($conn, $message);

    $media_path = '';
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'msg_' . time() . '_' . basename($_FILES['media']['name']);
            $target = '../uploads/' . $filename;
            if (move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
                $media_path = 'uploads/' . $filename;
                $media_path = mysqli_real_escape_string($conn, $media_path);
            }
        }
    }

    if (!empty($message) || !empty($media_path)) {
        $stmt = $conn->prepare("INSERT INTO messages (username, message, media_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $message, $media_path);
        $stmt->execute();
        $message_id = $conn->insert_id;
        $stmt->close();

        // Notify mentions
        preg_match_all('/@(\w+)/', $message, $matches);
        foreach ($matches[1] as $mentioned) {
            $check = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $check->bind_param("s", $mentioned);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $notify = $conn->prepare("INSERT INTO notifications (username, sender, message_id, type) VALUES (?, ?, ?, 'mention')");
                $notify->bind_param("ssi", $mentioned, $username, $message_id);
                $notify->execute();
                $notify->close();
            }
            $check->close();
        }
    }
    header("Location: index.php");
    exit();
}

// === Handle reaction (with notification) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reaction'])) {
    $msg_id = (int)$_POST['message_id'];
    $reaction = $_POST['reaction'];

    if (!in_array($reaction, ['❤️', '👍', '😂'])) {
        header("Location: index.php");
        exit();
    }

    // Remove old
    $stmt = $conn->prepare("DELETE FROM reactions WHERE message_id = ? AND username = ?");
    $stmt->bind_param("is", $msg_id, $username);
    $stmt->execute();
    $stmt->close();

    // Add new
    $stmt = $conn->prepare("INSERT INTO reactions (message_id, username, reaction) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $msg_id, $username, $reaction);
    $stmt->execute();
    $stmt->close();

    // Get message owner
    $owner_query = "SELECT username FROM messages WHERE id = ?";
    $stmt = $conn->prepare($owner_query);
    $stmt->bind_param("i", $msg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $msg_owner = $result->fetch_assoc()['username'] ?? null;
    $stmt->close();

    // Notify if reacting to someone else's message
    if ($msg_owner && $msg_owner !== $username) {
        $notify = $conn->prepare("INSERT INTO notifications (username, sender, message_id, type, reaction) VALUES (?, ?, ?, 'reaction', ?)");
        $notify->bind_param("ssis", $msg_owner, $username, $msg_id, $reaction);
        $notify->execute();
        $notify->close();
    }

    header("Location: index.php");
    exit();
}

// === AJAX: Return messages ===
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $query = "
        SELECT 
            m.*, 
            u.fullname,
            (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '❤️') as heart_count,
            (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '👍') as like_count,
            (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '😂') as laugh_count
        FROM messages m
        JOIN users u ON m.username = u.username
        ORDER BY m.created_at DESC
        LIMIT 100
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = array_reverse($result->fetch_all(MYSQLI_ASSOC));
    $stmt->close();

    echo '<div id="chatArea">';
    foreach ($messages as $msg) {
        $isMine = $msg['username'] === $username;
        $content = htmlspecialchars($msg['message'] ?? '');
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $content = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', $content);
        $content = preg_replace('/#(\w+)/', '<a href="#" class="hashtag" style="color:#9c27b0;">#$1</a>', $content);
        ?>
        <div class="message <?= $isMine ? 'sent' : 'received' ?>" data-id="<?= $msg['id'] ?>">
            <div class="sender"><?= htmlspecialchars($msg['fullname']) ?> (@<?= htmlspecialchars($msg['username']) ?>)</div>
            <div class="content"><?= $content ?></div>
            <?php if ($msg['media_path']): ?>
                <div class="media">
                    <img src="/new_chat/<?= htmlspecialchars($msg['media_path']) ?>" 
                         class="chat-image" 
                         onclick="openModal('/new_chat/<?= htmlspecialchars($msg['media_path']) ?>')"
                         style="max-width:200px;max-height:150px;border-radius:4px;cursor:zoom-in;">
                </div>
            <?php endif; ?>
            <div class="meta"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
            <div class="reactions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                    <input type="hidden" name="reaction" value="❤️">
                    <button type="submit" name="reaction" style="background:none;border:none;cursor:pointer;padding:0;font-size:14px;">
                        ❤️ <?= (int)$msg['heart_count'] > 0 ? (int)$msg['heart_count'] : '' ?>
                    </button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                    <input type="hidden" name="reaction" value="👍">
                    <button type="submit" name="reaction" style="background:none;border:none;cursor:pointer;padding:0;font-size:14px;">
                        👍 <?= (int)$msg['like_count'] > 0 ? (int)$msg['like_count'] : '' ?>
                    </button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                    <input type="hidden" name="reaction" value="😂">
                    <button type="submit" name="reaction" style="background:none;border:none;cursor:pointer;padding:0;font-size:14px;">
                        😂 <?= (int)$msg['laugh_count'] > 0 ? (int)$msg['laugh_count'] : '' ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    exit();
}

// === AJAX: Search users for @mentions ===
if (isset($_GET['action']) && $_GET['action'] === 'search_users') {
    $term = $_GET['q'] ?? '';
    $term = mysqli_real_escape_string($conn, $term);
    $query = "SELECT username, fullname FROM users WHERE username LIKE ? OR fullname LIKE ? LIMIT 5";
    $stmt = $conn->prepare($query);
    $like = "%$term%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($users);
    exit();
}

// === AJAX: Get notifications ===
if (isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    $query = "
        SELECT n.*, u.fullname 
        FROM notifications n 
        JOIN users u ON n.sender = u.username 
        WHERE n.username = ? AND n.read_status = 0 
        ORDER BY n.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifs = [];
    while ($row = $result->fetch_assoc()) {
        $notifs[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($notifs);
    exit();
}

// Fetch messages for initial load
$query = "
    SELECT 
        m.*, 
        u.fullname,
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '❤️') as heart_count,
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '👍') as like_count,
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '😂') as laugh_count
    FROM messages m
    JOIN users u ON m.username = u.username
    ORDER BY m.created_at DESC
    LIMIT 100
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$messages = array_reverse($result->fetch_all(MYSQLI_ASSOC));
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Just Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f3e6d0;
            --text: #8b4000;
            --accent: #ff6f31;
            --border: #c85e0d;
            --msg-bg: #fff4e6;
            --msg-self: #ff6f31;
            --msg-other: #9c27b0;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 14px;
            line-height: 1.4;
            text-align: center;
            background-image: 
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, transparent 0),
                radial-gradient(circle at 1px 1px, #d9c4a5 1px, var(--bg) 0);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            background: repeating-linear-gradient(0deg, transparent 0px, transparent 1px, rgba(0,0,0,0.05) 1px, rgba(0,0,0,0.05) 2px);
            z-index: 1;
            opacity: 0.3;
        }

        .container {
            max-width: 100%;
            width: 90%;
            margin: 0 auto;
            padding: 20px 10px;
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .marquee-container {
            font-size: 6vw;
            font-weight: bold;
            color: var(--text);
            text-shadow: 3px 3px 0 var(--accent), 5px 5px 0 rgba(0,0,0,0.7);
            letter-spacing: 0.05em;
            margin: 20px 0;
            padding: 15px 20px;
            background-color: #fff8ee;
            border: 4px solid var(--border);
            border-radius: 0;
            box-shadow: -6px 6px 0 var(--accent);
            max-width: 95vw;
            overflow: hidden;
            white-space: nowrap;
        }

        .chat-container {
            width: 100%;
            max-width: 600px;
            height: 60vh;
            overflow-y: auto;
            border: 4px solid var(--border);
            border-radius: 0;
            margin: 20px 0;
            padding: 15px;
            background-color: var(--msg-bg);
            box-shadow: -6px 6px 0 var(--accent);
            text-align: left;
            display: flex;
            flex-direction: column;
        }

        .message {
            margin: 10px 0;
            padding: 12px 15px;
            border: 3px solid var(--border);
            border-radius: 0;
            background-color: white;
            box-shadow: -3px 3px 0 var(--accent);
            max-width: 80%;
            word-wrap: break-word;
        }

        .message.sent {
            align-self: flex-end;
            background-color: #fff0e0;
            border-color: var(--msg-self);
            color: #8b4000;
            box-shadow: -3px 3px 0 var(--msg-self);
        }

        .message.received {
            align-self: flex-start;
            background-color: #f0f8ff;
            border-color: var(--msg-other);
            color: #5a189a;
            box-shadow: -3px 3px 0 var(--msg-other);
        }

        .sender {
            font-weight: bold;
            color: var(--text);
            font-size: 13px;
            margin-bottom: 4px;
        }

        .content .mention {
            color: var(--msg-other);
            font-weight: bold;
        }

        .meta {
            font-size: 10px;
            color: #555;
            margin-top: 5px;
        }

        .reactions {
            display: flex;
            gap: 6px;
            margin-top: 6px;
            font-size: 14px;
        }

        .reaction {
            background: white;
            border: 1px solid var(--border);
            padding: 2px 6px;
            border-radius: 0;
            cursor: pointer;
            box-shadow: -1px 1px 0 #000;
        }

        .input-area {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 600px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 3px solid var(--border);
            background-color: white;
            color: var(--text);
            font-family: 'Press Start 2P', cursive;
            font-size: 14px;
            resize: none;
            border-radius: 0;
            box-shadow: inset -2px 2px 0 var(--accent);
            transition: box-shadow 0.3s, transform 0.2s;
        }

        /* Glow when typing */
        textarea:focus {
            outline: none;
            box-shadow: inset -2px 2px 0 var(--accent), 0 0 10px rgba(255, 111, 49, 0.4);
            transform: scale(1.02);
        }

        /* File input styling */
        input[type="file"] {
            border: 3px solid var(--border);
            background-color: white;
            padding: 10px;
            border-radius: 0;
            font-family: 'Press Start 2P', cursive;
            font-size: 12px;
            cursor: pointer;
            box-shadow: inset -2px 2px 0 var(--accent);
        }

        /* Send button - Retro Game Style */
        .input-area button {
            background-color: var(--accent);
            color: white;
            border: 4px solid #000;
            font-family: 'Press Start 2P', cursive;
            font-size: 16px;
            cursor: pointer;
            padding: 10px 16px;
            border-radius: 0;
            box-shadow: -4px 4px 0 #000;
            transition: transform 0.1s, box-shadow 0.1s;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        .input-area button:hover {
            transform: translate(2px, -2px);
            box-shadow: -2px 2px 0 #000;
        }

        .input-area button:active {
            transform: translate(4px, -4px);
            box-shadow: -1px 1px 0 #000;
        }

        .suggestions {
            position: absolute;
            background: white;
            border: 2px solid var(--border);
            border-radius: 0;
            max-height: 150px;
            overflow-y: auto;
            width: 220px;
            z-index: 1000;
            box-shadow: -4px 4px 0 var(--accent);
            display: none;
        }

        .suggestion-item {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .suggestion-item:hover {
            background-color: #ffebcd;
        }

        .notification-bell {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: -2px 2px 0 #000;
            z-index: 100;
            overflow: visible;
        }

        #notifDot {
            position: absolute;
            top: 18px;
            right: 18px;
            background: red;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: none;
        }

        .notification-list {
            position: fixed;
            top: 60px;
            right: 20px;
            width: 300px;
            background: white;
            border: 4px solid var(--border);
            border-radius: 0;
            box-shadow: -6px 6px 0 var(--accent);
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 100;
        }

        .notif-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            cursor: pointer;
        }

        .notif-item:hover {
            background-color: #f9f9f9;
        }

        /* Image Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            justify-content: center;
            align-items: center;
        }

        .modal img {
            max-width: 90%;
            max-height: 90%;
            border: 4px solid white;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="marquee-container">JUST CHAT</div>

    <!-- Chat Area -->
    <div class="chat-container" id="chatArea">
        <!-- Messages loaded via JS -->
    </div>

    <!-- Message Form -->
    <div class="input-area">
        <textarea id="messageInput" placeholder="Type a message..." rows="2"></textarea>
        <div id="suggestions" class="suggestions"></div>
        <input type="file" name="media" accept="image/*">
        <button id="sendBtn">📤</button>
    </div>

    <p style="margin-top: 20px;">
        <a href="../logout.php" style="color: #d32f2f;">Logout</a>
    </p>
</div>

<!-- Notification Bell -->
<div class="notification-bell" onclick="toggleNotifications()">
    🔔
    <span id="notifDot"></span>
</div>
<div class="notification-list" id="notificationList"></div>

<!-- Image Modal -->
<div class="modal" id="imageModal" onclick="closeModal()">
    <img id="modalImage">
</div>

<!-- Audio for new message -->
<audio id="messageSound" src="https://www.soundjay.com/buttons/sounds/button-09.mp3" preload="auto"></audio>
<!-- Audio for send click -->
<audio id="clickSound" src="https://www.soundjay.com/misc/sounds/interface-10.mp3" preload="auto"></audio>

<script>
const currentUser = "<?php echo addslashes($username); ?>";
let lastMessageCount = 0;

// Load messages
function loadMessages() {
    fetch("index.php?action=get_messages")
        .then(r => r.text())
        .then(html => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newContent = tempDiv.querySelector('#chatArea');
            if (newContent) {
                const chatArea = document.getElementById('chatArea');
                const isScrolledToBottom = chatArea.scrollHeight - chatArea.clientHeight <= chatArea.scrollTop + 10;

                const temp = document.createElement('div');
                temp.innerHTML = newContent.innerHTML;
                const newMessages = temp.querySelectorAll('.message');
                const newCount = newMessages.length;

                if (newCount > lastMessageCount) {
                    const lastSender = newMessages[newMessages.length - 1]?.querySelector('.sender')?.textContent || '';
                    if (!lastSender.includes(`(@${currentUser})`)) {
                        document.getElementById('messageSound').play().catch(() => {});
                    }
                }

                chatArea.innerHTML = newContent.innerHTML;
                lastMessageCount = newCount;

                if (isScrolledToBottom) {
                    chatArea.scrollTop = chatArea.scrollHeight;
                }
            }
        });
}

// Image modal
function openModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// @mention suggestions
const messageInput = document.getElementById('messageInput');
const suggestionsBox = document.getElementById('suggestions');

messageInput.addEventListener('input', function() {
    const value = this.value;
    const atIndex = value.lastIndexOf('@');
    if (atIndex === -1) {
        suggestionsBox.style.display = 'none';
        return;
    }
    const term = value.slice(atIndex + 1);
    if (term.length < 1) return;

    fetch(`index.php?action=search_users&q=${encodeURIComponent(term)}`)
        .then(r => r.json())
        .then(users => {
            suggestionsBox.innerHTML = '';
            if (users.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }
            users.forEach(user => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.textContent = `${user.fullname} (@${user.username})`;
                div.onclick = () => {
                    messageInput.setRangeText(`@${user.username} `, atIndex, value.length, 'end');
                    suggestionsBox.style.display = 'none';
                };
                suggestionsBox.appendChild(div);
            });
            const rect = messageInput.getBoundingClientRect();
            suggestionsBox.style.top = (rect.bottom + window.scrollY) + 'px';
            suggestionsBox.style.left = (rect.left + window.scrollX) + 'px';
            suggestionsBox.style.display = 'block';
        });
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#messageInput') && !e.target.closest('.suggestions')) {
        suggestionsBox.style.display = 'none';
    }
});

// Send message
document.getElementById('sendBtn').onclick = function() {
    const msg = messageInput.value.trim();
    const formData = new FormData();
    formData.append('message', msg);
    formData.append('send_message', '1');

    if (document.querySelector('[name="media"]')?.files[0]) {
        formData.append('media', document.querySelector('[name="media"]').files[0]);
    }

    // Play click sound
    document.getElementById('clickSound').play().catch(e => console.log('Sound blocked:', e));

    fetch('index.php', { method: 'POST', body: formData })
        .then(() => {
            messageInput.value = '';
            document.querySelector('[name="media"]').value = '';
            loadMessages();
        });
};

// Notifications
function toggleNotifications() {
    const list = document.getElementById('notificationList');
    if (list.style.display === 'block') {
        list.style.display = 'none';
        return;
    }
    fetch('index.php?action=get_notifications')
        .then(r => r.json())
        .then(notifs => {
            list.innerHTML = '';
            if (notifs.length === 0) {
                list.innerHTML = '<div class="notif-item">No new notifications</div>';
            } else {
                notifs.forEach(n => {
                    let text = '';
                    if (n.type === 'mention') {
                        text = `<b>@${n.sender}</b> mentioned you`;
                    } else if (n.type === 'reaction') {
                        text = `<b>@${n.sender}</b> reacted with <span style="font-size:16px">${n.reaction}</span>`;
                    }
                    const item = document.createElement('div');
                    item.className = 'notif-item';
                    item.innerHTML = text;
                    item.onclick = (e) => {
                        e.stopPropagation();
                        const msgEl = document.querySelector(`.message[data-id="${n.message_id}"]`);
                        if (msgEl) {
                            msgEl.style.scrollMargin = '100px';
                            msgEl.style.transition = 'background-color 0.5s';
                            msgEl.style.backgroundColor = '#ffecb3';
                            msgEl.scrollIntoView({ behavior: 'smooth' });
                            setTimeout(() => msgEl.style.backgroundColor = '', 2000);
                        }
                        list.style.display = 'none';
                    };
                    list.appendChild(item);
                });
            }
            list.style.display = 'block';
            document.getElementById('notifDot').style.display = 'none';
        });
}

// Update notification dot
function updateNotificationDot() {
    fetch('index.php?action=get_notifications')
        .then(r => r.json())
        .then(notifs => {
            const dot = document.getElementById('notifDot');
            if (notifs.length > 0) {
                dot.style.display = 'block';
            } else {
                dot.style.display = 'none';
            }
        });
}

// Load on start
loadMessages();
updateNotificationDot();
setInterval(loadMessages, 2000);
setInterval(updateNotificationDot, 3000);

// Hide notifications on outside click
document.addEventListener('click', function(e) {
    const bell = document.querySelector('.notification-bell');
    const list = document.getElementById('notificationList');
    if (!e.target.closest('.notification-bell') && !e.target.closest('.notification-list')) {
        list.style.display = 'none';
    }
});
</script>

</body>
</html>