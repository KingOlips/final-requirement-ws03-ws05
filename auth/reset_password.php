<?php
require_once 'init.php';
require_once '../includes/db_connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$token) {
    header("Location: ../login.php");
    exit();
}

// Verify Token
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired reset token. Please request a new one from the login page.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $pw_errors = validate_password_strength($password);
    if (!empty($pw_errors)) {
        $error = implode(' ', $pw_errors);
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL, failed_attempts = 0, locked_until = NULL WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = "Password reset successful! You can now <a href='../login.php' style='color:var(--primary); font-weight:700;'>Sign in</a>.";
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | PharmTrack</title>
    <script>const savedTheme = localStorage.getItem('theme') || 'light'; document.documentElement.setAttribute('data-theme', savedTheme);</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body, html { height: 100%; margin: 0; background: var(--background); font-family: 'Inter', sans-serif; transition: background 0.3s; }
        .reset-container { display: flex; align-items: center; justify-content: center; height: 100vh; padding: 2rem; }
        .reset-card { width: 100%; max-width: 400px; background: var(--surface); padding: 2.5rem; border-radius: 1.5rem; box-shadow: var(--shadow-lg); border: 1px solid var(--border); }
        .auth-logo { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem; color: var(--primary); text-decoration: none; justify-content: center; }
        .auth-logo-icon { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(79, 70, 229, 0.2); }
        .auth-logo-text { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; }
        .auth-input { height: 44px; font-size: 0.9rem; border: 2px solid var(--border); background: transparent; padding: 0 0.875rem; border-radius: 0.625rem; width: 100%; box-sizing: border-box; color: var(--text-main); transition: all 0.3s; }
        .auth-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        .auth-btn { height: 48px; width: 100%; margin-top: 0.5rem; font-weight: 700; border-radius: 0.625rem; }
        .form-group { margin-bottom: 1.25rem; }
        .alert { padding: 0.75rem; border-radius: 0.625rem; margin-bottom: 1.25rem; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        label { font-size: 0.75rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.3rem; display: block; }
        
        .password-container { position: relative; width: 100%; }
        .password-toggle { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 1.25rem; transition: color 0.2s; z-index: 10; }
        .password-toggle:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <a href="../login.php" class="auth-logo">
                <div class="auth-logo-icon">
                    <i class='bx bxs-capsule' style='font-size: 1.5rem;'></i>
                </div>
                <span class="auth-logo-text">PharmTrack</span>
            </a>

            <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; text-align: center; color: var(--text-main);">New Password</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem; text-align: center; margin-bottom: 2rem;">Please choose a strong password to protect your account.</p>

            <?php if ($error): ?><div class="alert alert-error"><span><?php echo e($error); ?></span></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><span><?php echo $success; ?></span></div><?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo e($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="auth-input" required placeholder="Min 8 chars, upper, lower, number, symbol" autofocus oninput="updateStrengthBar(this.value)">
                        <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('password', this)"></i>
                    </div>
                    <!-- Password Strength Bar -->
                    <div style="margin-top:0.4rem;">
                        <div style="height:5px; background:var(--border); border-radius:99px; overflow:hidden;">
                            <div id="strengthBar" style="height:100%; width:0%; border-radius:99px; transition:width 0.3s, background 0.3s;"></div>
                        </div>
                        <small id="strengthLabel" style="font-size:0.7rem; color:var(--text-muted);"></small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirm_password" class="auth-input" required placeholder="Repeat your new password">
                        <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-btn">Reset Password</button>
            </form>
            <?php endif; ?>

            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem;">
                <a href="../login.php" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Back to Login</a>
            </div>
        </div>
    </div>
    <script src="../assets/js/scripts.js"></script>
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
</body>
</html>
