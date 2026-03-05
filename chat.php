<?php
session_start();
require_once 'db.php';
 
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
 
$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'You';
 
$stmt = $conn->prepare('SELECT message, response, created_at FROM chats WHERE user_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rehan Chat Bot - Conversation</title>
    <style>
        :root {
            --bg: #050915;
            --panel: rgba(255, 255, 255, 0.04);
            --bubble: rgba(255, 255, 255, 0.06);
            --stroke: rgba(255, 215, 0, 0.7);
            --accent: #2196F3;
            --text: #eaf4ff;
            --muted: #8ea2c0;
            --shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(255, 215, 0, 0.08), transparent 24%),
                        radial-gradient(circle at 80% 0%, rgba(33, 150, 243, 0.12), transparent 22%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
        }
        .title {
            font-weight: 700;
            letter-spacing: 0.6px;
            background: linear-gradient(120deg, #FFD700, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .user {
            color: var(--muted);
            font-size: 14px;
        }
        .logout {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--stroke);
            background: transparent;
            color: var(--text);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .logout:hover { background: rgba(255, 215, 0, 0.08); }
        .chat-wrap {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .chat-card {
            position: relative;
            width: 100%;
            max-width: 1100px;
            background: var(--panel);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            display: grid;
            grid-template-rows: 1fr auto;
        }
        .chat-card::after {
            content: "";
            position: absolute;
            inset: 0;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 18px;
            pointer-events: none;
        }
        .messages {
            padding: 22px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .bubble {
            max-width: 75%;
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 12px;
            position: relative;
            background: var(--bubble);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            animation: fade 0.2s ease;
        }
        .bubble.me { margin-left: auto; background: linear-gradient(120deg, rgba(255,215,0,0.18), rgba(33,150,243,0.25)); border-color: var(--stroke); }
        .bubble.bot { margin-right: auto; }
        .meta { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .input-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(0, 0, 0, 0.2);
        }
        textarea {
            width: 100%;
            min-height: 52px;
            max-height: 120px;
            resize: none;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            outline: none;
            line-height: 1.5;
        }
        textarea:focus { border-color: var(--stroke); box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.18); }
        .send {
            padding: 0 18px;
            border-radius: 14px;
            border: 1px solid var(--stroke);
            background: linear-gradient(120deg, #FFD700, #2196F3);
            color: #061020;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.1s ease, box-shadow 0.15s ease;
            box-shadow: 0 12px 30px rgba(255, 215, 0, 0.35);
        }
        .send:active { transform: translateY(1px); }
        .typing {
            display: inline-flex;
            gap: 4px;
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--stroke);
            animation: blink 1s infinite ease-in-out;
        }
        .dot:nth-child(2) { animation-delay: 0.15s; }
        .dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes blink { 0%, 80%, 100% { opacity: 0.3; } 40% { opacity: 1; } }
        @keyframes fade { from {opacity: 0; transform: translateY(6px);} to {opacity:1; transform:translateY(0);} }
        @media (max-width: 720px) {
            .bubble { max-width: 90%; }
            header { flex-direction: column; gap: 8px; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <div class="title">Rehan Chat Bot</div>
            <div class="user">Signed in as <?php echo htmlspecialchars($userName); ?></div>
        </div>
        <a href="logout.php"><button class="logout">Logout</button></a>
    </header>
 
    <div class="chat-wrap">
        <div class="chat-card">
            <div class="messages" id="messages"></div>
            <div class="input-bar">
                <textarea id="input" placeholder="Type your message..." required></textarea>
                <button class="send" id="sendBtn">Send</button>
            </div>
        </div>
    </div>
 
    <script>
        const history = <?php echo json_encode($history, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const messagesEl = document.getElementById('messages');
        const inputEl = document.getElementById('input');
        const sendBtn = document.getElementById('sendBtn');
 
        const scrollToBottom = () => messagesEl.scrollTop = messagesEl.scrollHeight;
 
        const addBubble = (text, role) => {
            const wrap = document.createElement('div');
            wrap.className = `bubble ${role}`;
            wrap.innerHTML = text;
            messagesEl.appendChild(wrap);
            scrollToBottom();
            return wrap;
        };
 
        const renderHistory = () => {
            if (!history || !history.length) {
                addBubble('Hello! I\'m Rehan Chat Bot, your friendly assistant. It\'s nice to meet you! I\'m here to help you with whatever you need - whether that\'s learning something new, solving problems, coding questions, or just having a good conversation. What would you like to talk about today?', 'bot');
                return;
            }
            history.forEach(row => {
                addBubble(sanitize(row.message), 'me');
                addBubble(sanitize(row.response), 'bot');
            });
        };
 
        const sanitize = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };
 
        const showTyping = () => {
            const wrap = document.createElement('div');
            wrap.className = 'bubble bot';
            wrap.innerHTML = '<div class="typing"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>';
            messagesEl.appendChild(wrap);
            scrollToBottom();
            return wrap;
        };
 
        const sendMessage = async () => {
            const text = inputEl.value.trim();
            if (!text) return;
            addBubble(sanitize(text), 'me');
            inputEl.value = '';
            const typing = showTyping();
            sendBtn.disabled = true;
 
            try {
                const res = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message=' + encodeURIComponent(text)
                });
                const data = await res.json();
                typing.remove();
                addBubble(sanitize(data.response || '...'), 'bot');
            } catch (e) {
                typing.remove();
                addBubble('Sorry, something went wrong.', 'bot');
            } finally {
                sendBtn.disabled = false;
                scrollToBottom();
            }
        };
 
        sendBtn.addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
 
        renderHistory();
        scrollToBottom();
    </script>
</body>
</html>
 
 
