<?php
require_once 'auth/security.php';
require_once 'includes/db_connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';
if (!in_array($mode, ['login', 'signup', 'forgot'])) $mode = 'login';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $action = $_POST['action'];
    
    if ($action === 'forgot') {
        $username = trim($_POST['username']);
        
        // Generate real reset token
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$token, $user_data['id']]);
            
            $reset_link = "auth/reset_password.php?token=" . $token;
            $success = "A reset link has been generated. <a href='$reset_link' style='color:var(--primary); font-weight:700;'>Click here to reset your password</a> (Simulation).";
        } else {
            $success = "If an account exists for $username, a reset link has been sent.";
        }
        $mode = 'login';
    } elseif ($action === 'signup') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $pw_errors = validate_password_strength($password);
        if (!empty($pw_errors)) {
            $error = implode(' ', $pw_errors);
            $mode = 'signup';
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
            $mode = 'signup';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists.";
                $mode = 'signup';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashed_password])) {
                    $success = "Account created! You can now sign in.";
                    $mode = 'login';
                } else {
                    $error = "Registration failed. Please try again.";
                    $mode = 'signup';
                }
            }
        }
    } else {
        // ── LOGIN ACTION ──
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // 1. CRITICAL FIX: Check if the account is currently locked out FIRST.
            // If it is locked, reject immediately without validating password or adding more errors.
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
                $error = "Account is temporarily locked due to multiple failed attempts. Please try again in {$mins} minute(s).";
            } 
            // 2. Account is NOT locked, check if password is correct
            elseif (password_verify($password, $user['password'])) {
                // Successful login – reset counters completely
                $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")
                    ->execute([$user['id']]);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                setcookie('app_logged_in', '1', 0, '/');
                header("Location: index.php");
                exit();
            } 
            // 3. Password is wrong (and account wasn't already locked)
            else {
                $attempts = $user['failed_attempts'] + 1;
                
                if ($attempts >= 5) {
                    // Lock the account for exactly 15 minutes from right now
                    $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?")
                        ->execute([$attempts, $user['id']]);
                    $error = "Too many failed attempts. Your account has been locked for 15 minutes.";
                } else {
                    // Record the failed attempt normally
                    $remaining = 5 - $attempts;
                    $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?")
                        ->execute([$attempts, $user['id']]);
                    $error = "Invalid username or password. {$remaining} attempt(s) remaining before lockout.";
                }
            }
        } else {
            // Username doesn't exist at all
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        if ($mode === 'signup') echo 'Sign Up';
        elseif ($mode === 'forgot') echo 'Reset Password';
        else echo 'Login'; 
    ?> | PharmTrack</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; font-family: 'Inter', sans-serif; }
        .auth-container { display: flex; height: 100vh; width: 100vw; background: var(--background); }
        .auth-image-panel { flex: 1.2; position: relative; background: url('assets/img/auth-bg.png') center/cover no-repeat; display: none; }
        @media (min-width: 1024px) { .auth-image-panel { display: block; } }
        .auth-image-panel::after { content: ''; position: absolute; inset: 0; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8)); }
        .auth-image-content { position: absolute; bottom: 8%; left: 8%; right: 8%; z-index: 10; color: white; }
        .auth-form-panel { flex: 1; display: flex; flex-direction: column; background: var(--surface); padding: 2rem; overflow-y: auto; }
        .auth-form-inner { width: 100%; max-width: 400px; margin: auto; }
        .auth-logo { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; color: var(--primary); text-decoration: none; }
        .auth-logo-icon { width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(79, 70, 229, 0.2); }
        .auth-logo-text { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; }
        .auth-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.4rem; color: var(--text-main); letter-spacing: -0.01em; }
        .auth-subtitle { color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.875rem; }
        .form-label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem; }
        .auth-input { height: 44px; font-size: 0.9rem; border: 2px solid var(--border); background: transparent; padding: 0 0.875rem; margin-bottom: 1rem; border-radius: 0.625rem; }
        .auth-input:focus { border-color: var(--primary); outline: none; }
        .auth-btn { height: 48px; font-size: 0.95rem; width: 100%; margin-top: 0.5rem; font-weight: 700; border-radius: 0.625rem; }
        .auth-toggle-link { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 0.8125rem; }
        .auth-toggle-link a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .alert { padding: 0.75rem; border-radius: 0.625rem; margin-bottom: 1.25rem; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        label { font-size: 0.75rem; font-weight: 600; color: var(--text-main); }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-image-panel">
            <div class="auth-image-content">
                <h1 style="font-size: 2.75rem; font-weight: 800; line-height: 1.1; margin-bottom: 1rem; color: #f8fafc; text-shadow: 0 2px 10px rgba(0,0,0,0.6);">Intelligent Pharmacy <br> Solutions.</h1>
                <p style="font-size: 1.1rem; opacity: 0.9; max-width: 420px;">The industry standard for medical inventory tracking and batch management. Precise, secure, and modern.</p>
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-form-inner">
                <a href="login.php" class="auth-logo">
                    <div class="auth-logo-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.641.32a2 2 0 01-1.836 0l-.64-.32a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547l-.234.234a2 2 0 000 2.828l.234.234a2 2 0 001.022.547l2.387.477a6 6 0 003.86-.517l.641-.32a2 2 0 011.836 0l.64.32a6 6 0 003.86.517l2.387-.477a2 2 0 001.022-.547l.234-.234a2 2 0 000-2.828l-.234-.234zM6 10a4 4 0 118 0 4 4 0 01-8 0z"/></svg>
                    </div>
                    <span class="auth-logo-text">PharmTrack</span>
                </a>

                <?php if ($mode === 'forgot'): ?>
                    <h2 class="auth-title">Reset Password</h2>
                    <p class="auth-subtitle">Enter your username to receive a reset link.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="forgot">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="auth-input" required placeholder="Enter your username" autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary auth-btn">Send Reset Link</button>
                    </form>
                    <div class="auth-toggle-link">
                        Back to <a href="?mode=login">Sign in</a>
                    </div>

                <?php elseif ($mode === 'signup'): ?>
                    <h2 class="auth-title">Create account</h2>
                    <p class="auth-subtitle">Start managing your medical inventory today.</p>
                    <?php if ($error): ?><div class="alert alert-error"><span><?php echo e($error); ?></span></div><?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="signup">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="form-group"><label for="username">Username</label><input type="text" name="username" id="username" class="auth-input" required placeholder="Enter your username"></div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" name="password" id="password" class="auth-input" required placeholder="Min 8 chars, upper, lower, number, symbol" style="width: 100%; box-sizing: border-box; padding-right: 2.75rem;" oninput="updateStrengthBar(this.value)">
                                <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('password', this)"></i>
                            </div>
                            <!-- Password Strength Bar -->
                            <div style="margin-top: -0.5rem; margin-bottom: 0.75rem;">
                                <div style="height:5px; background:var(--border); border-radius:99px; overflow:hidden;">
                                    <div id="strengthBar" style="height:100%; width:0%; border-radius:99px; transition:width 0.3s, background 0.3s;"></div>
                                </div>
                                <small id="strengthLabel" style="font-size:0.7rem; color:var(--text-muted);"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" name="confirm_password" id="confirm_password" class="auth-input" required placeholder="Confirm your password" style="width: 100%; box-sizing: border-box; padding-right: 2.75rem;">
                                <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('confirm_password', this)"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary auth-btn">Create account</button>
                    </form>
                    <div class="auth-toggle-link">Already have an account? <a href="?mode=login">Sign in here</a></div>
                    <script>
                    function updateStrengthBar(val) {
                        let score = 0;
                        if (val.length >= 8) score++;
                        if (/[A-Z]/.test(val)) score++;
                        if (/[a-z]/.test(val)) score++;
                        if (/[0-9]/.test(val)) score++;
                        if (/[\W_]/.test(val)) score++;
                        const bar = document.getElementById('strengthBar');
                        const label = document.getElementById('strengthLabel');
                        const levels = [
                            { pct: '0%',   color: 'transparent', text: '' },
                            { pct: '20%',  color: '#ef4444',     text: 'Very Weak' },
                            { pct: '40%',  color: '#f97316',     text: 'Weak' },
                            { pct: '60%',  color: '#eab308',     text: 'Fair' },
                            { pct: '80%',  color: '#22c55e',     text: 'Strong' },
                            { pct: '100%', color: '#16a34a',     text: 'Very Strong' },
                        ];
                        bar.style.width = levels[score].pct;
                        bar.style.background = levels[score].color;
                        label.textContent = levels[score].text;
                        label.style.color = levels[score].color;
                    }
                    </script>

                <?php else: ?>
                    <h2 class="auth-title">Sign in</h2>
                    <p class="auth-subtitle">Welcome back! Please enter your details.</p>
                    <?php if ($error): ?><div class="alert alert-error"><span><?php echo e($error); ?></span></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><span><?php echo $success; ?></span></div><?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="form-group"><label for="username">Username</label><input type="text" name="username" id="username" class="auth-input" required placeholder="Enter your username" autofocus></div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" name="password" id="password" class="auth-input" required placeholder="Enter your password" style="width: 100%; box-sizing: border-box; padding-right: 2.75rem;">
                                <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('password', this)"></i>
                            </div>
                            <div style="text-align: right; margin-top: -0.5rem; margin-bottom: 1rem;">
                                <a href="?mode=forgot" style="font-size: 0.75rem; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot password?</a>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary auth-btn">Sign in</button>
                    </form>
                    <div class="auth-toggle-link">New to PharmTrack? <a href="?mode=signup">Create an account</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
