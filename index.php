<?php
session_start();
require_once 'db.php';
 
if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit;
}
 
$errors = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
 
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
 
    if ($action === 'signup') {
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if (!$errors) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = 'Email already registered.';
            }
            $stmt->close();
        }
 
        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            if ($stmt->execute()) {
                $success = 'Account created. You can now log in.';
            } else {
                $errors[] = 'Signup failed. Please try again.';
            }
            $stmt->close();
        }
    } elseif ($action === 'login') {
        if (!$errors) {
            $stmt = $conn->prepare('SELECT id, password, name FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
 
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: chat.php');
                exit;
            } else {
                $errors[] = 'Invalid credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rehan Chat Bot - Login / Signup</title>
    <style>
        :root {
            --bg: #0a0f1f;
            --card: rgba(255, 255, 255, 0.05);
            --stroke: rgba(255, 215, 0, 0.7);
            --accent: #2196F3;
            --text: #e9f1ff;
            --muted: #8ea2c0;
            --success: #33e2a0;
            --error: #ff6b6b;
            --glass: rgba(255, 255, 255, 0.08);
            --shadow: 0 25px 80px rgba(0, 0, 0, 0.55);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(255, 215, 0, 0.12), transparent 25%),
                        radial-gradient(circle at 80% 0%, rgba(33, 150, 243, 0.16), transparent 22%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            align-items: center;
        }
        .hero {
            padding: 32px;
        }
        .hero h1 {
            margin: 0 0 12px;
            font-size: 40px;
            background: linear-gradient(90deg, #FFD700, #2196F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }
        .hero p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.6;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: 1px solid var(--stroke);
            border-radius: 999px;
            color: var(--text);
            background: linear-gradient(120deg, rgba(255, 215, 0, 0.08), rgba(33, 150, 243, 0.08));
            box-shadow: 0 0 12px rgba(255, 215, 0, 0.25);
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        .card {
            position: relative;
            padding: 28px;
            background: var(--glass);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
            overflow: hidden;
        }
        .card::before, .card::after {
            content: "";
            position: absolute;
            inset: 0;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 18px;
            pointer-events: none;
        }
        .card::before { filter: blur(12px); }
        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid var(--stroke);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .tab {
            padding: 12px;
            text-align: center;
            cursor: pointer;
            background: transparent;
            color: var(--muted);
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .tab.active {
            color: var(--text);
            background: linear-gradient(120deg, rgba(255, 215, 0, 0.15), rgba(33, 150, 243, 0.2));
            box-shadow: inset 0 0 25px rgba(255, 215, 0, 0.25);
        }
        form {
            display: none;
            animation: fade 0.25s ease;
        }
        form.active { display: block; }
        label {
            display: block;
            margin: 14px 0 6px;
            color: var(--muted);
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            outline: none;
            transition: all 0.2s ease;
        }
        input:focus {
            border-color: var(--stroke);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.18);
        }
        .btn {
            width: 100%;
            margin-top: 18px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--stroke);
            background: linear-gradient(120deg, #FFD700, #2196F3);
            color: #061020;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 12px 30px rgba(255, 215, 0, 0.35);
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .alert {
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--error);
        }
        .alert.success { color: var(--success); }
        .floating {
            position: absolute;
            width: 140px;
            height: 140px;
            border: 1px solid rgba(255, 215, 0, 0.35);
            border-radius: 32px;
            filter: blur(1px);
            animation: float 6s ease-in-out infinite;
            opacity: 0.3;
        }
        .float-1 { top: -30px; right: -30px; animation-delay: 0s; }
        .float-2 { bottom: -40px; left: -40px; animation-delay: 1.5s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(2deg); }
            50% { transform: translateY(-12px) rotate(-2deg); }
        }
        @keyframes fade { from { opacity: 0; transform: translateY(6px);} to { opacity: 1; transform: translateY(0);} }
        @media (max-width: 720px) {
            .hero h1 { font-size: 32px; }
            .hero { text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <div class="badge">Neon Stroke UI • Local AI • Secure Auth</div>
            <h1>Chat with a Local AI</h1>
            <p>Sign up or log in to start talking with the on-device rule-based assistant. No external APIs, just pure PHP logic with a sleek ChatGPT-style interface.</p>
            <p style="color: var(--muted); font-size: 14px;">Responsive • Dark Neon • Smooth transitions</p>
        </div>
        <div class="card">
            <div class="floating float-1"></div>
            <div class="floating float-2"></div>
 
            <div class="tabs">
                <button class="tab active" data-target="login">Login</button>
                <button class="tab" data-target="signup">Signup</button>
            </div>
 
            <?php if ($errors): ?>
                <div class="alert">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
 
            <form id="login" class="active" method="POST" novalidate>
                <input type="hidden" name="action" value="login">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com">
                <label>Password</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••">
                <button class="btn" type="submit">Login</button>
            </form>
 
            <form id="signup" method="POST" novalidate>
                <input type="hidden" name="action" value="signup">
                <label>Name</label>
                <input type="text" name="name" required placeholder="Your name">
                <label>Email</label>
                <input type="email" name="email" required placeholder="you@example.com">
                <label>Password</label>
                <input type="password" name="password" required minlength="6" placeholder="Create a password">
                <button class="btn" type="submit">Create account</button>
            </form>
        </div>
    </div>
 
    <script>
        const tabs = document.querySelectorAll('.tab');
        const forms = document.querySelectorAll('form');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                forms.forEach(f => f.classList.remove('active'));
                document.getElementById(tab.dataset.target).classList.add('active');
            });
        });
    </script>
</body>
</html>
 
 
