<?php
$base_path = '';
require_once 'auth/security.php';
require_once 'auth/auth_check.php';
require_once 'includes/db_connection.php';
require_login();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token mismatch");
    }

    $new_username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_username, $hashed, $_SESSION['user_id']]);
                $_SESSION['username'] = $new_username;
                $success = "Profile and password updated successfully!";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            $_SESSION['username'] = $new_username;
            $success = "Username updated successfully!";
        }
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | PharmTrack</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <div class="app-wrapper">
        <?php
$base_path = '';
include 'includes/sidebar.php'; ?>

        <div class="page-body">
            <!-- TOP NAVBAR -->
            <header class="topnav">
                <div class="topnav-left">
                    <button class="hamburger" onclick="toggleSidebar()" title="Toggle Menu">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="topnav-breadcrumb">
                        <span class="topnav-title">Settings</span>
                        <span class="topnav-sub">User Profile Management</span>
                    </div>
                </div>
                <div class="topnav-right">
                    <button class="hamburger" onclick="toggleTheme()" title="Toggle Dark Mode">
                        <svg id="theme-icon-moon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg id="theme-icon-sun" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <div class="topnav-avatar">
                        <?php
$base_path = '';
echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="card" style="max-width: 650px; margin: 0 auto;">
                    <div class="card-header" style="background: linear-gradient(to right, var(--background), var(--card-bg)); border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 42px; height: 42px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="card-title" style="font-size: 1.25rem;">Account Profile</h3>
                                <p style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 400;">Update your personal information and login credentials</p>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 2rem;">
                        <?php
$base_path = '';
if ($success): ?>
                            <div class="badge-success" style="padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-size: 0.875rem; border-left: 4px solid var(--success); display: flex; align-items: center; gap: 0.75rem; background: rgba(34, 197, 94, 0.1);">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <?php
$base_path = '';
echo e($success); ?>
                            </div>
                        <?php
$base_path = '';
endif; ?>
                        <?php
$base_path = '';
if ($error): ?>
                            <div class="badge-danger" style="padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-size: 0.875rem; border-left: 4px solid var(--danger); display: flex; align-items: center; gap: 0.75rem; background: rgba(239, 68, 68, 0.1);">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?php
$base_path = '';
echo e($error); ?>
                            </div>
                        <?php
$base_path = '';
endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php
$base_path = '';
echo get_csrf_token(); ?>">
                            
                            <div class="form-section" style="margin-bottom: 2.5rem;">
                                <div class="form-group">
                                    <label for="username" style="font-weight: 600; color: var(--text-main);">Display Username</label>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">This name will be visible on your dashboard and profile header.</p>
                                    <input type="text" name="username" id="username" value="<?php
$base_path = '';
echo e($_SESSION['username']); ?>" required style="background: var(--background); border: 1px solid var(--border);">
                                </div>
                            </div>

                            <hr style="margin: 2.5rem 0; border: none; border-top: 1px dashed var(--border);">

                            <div class="form-section">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-main);">Security & Password</h4>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="new_password" style="font-weight: 600; color: var(--text-main);">New Password</label>
                                        <div class="password-container">
                                            <input type="password" name="new_password" id="new_password" placeholder="Put your New Password" style="background: var(--background); border: 1px solid var(--border); width: 100%; box-sizing: border-box; padding-right: 2.75rem;">
                                            <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('new_password', this)"></i>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password" style="font-weight: 600; color: var(--text-main);">Confirm Password</label>
                                        <div class="password-container">
                                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your Password" style="background: var(--background); border: 1px solid var(--border); width: 100%; box-sizing: border-box; padding-right: 2.75rem;">
                                            <i class='bx bx-hide password-toggle' onclick="togglePasswordVisibility('confirm_password', this)"></i>
                                        </div>
                                    </div>
                                </div>
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Leave password fields blank if you do not wish to change your current password.</p>
                            </div>

                            <div style="margin-top: 3rem; display: flex; justify-content: flex-end; gap: 1rem;">
                                <a href="index.php" class="btn" style="background: var(--background); color: var(--text-main); font-weight: 500;">Cancel</a>
                                <button type="submit" class="btn btn-primary" style="padding-left: 2.5rem; padding-right: 2.5rem; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);">Save Changes</button>
                            </div>
                        </form>

                        <!-- Danger Zone (Customers Only) -->
                        <?php
$base_path = '';
if ($user_role !== 'admin'): ?>
                        <div style="margin-top: 3.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border);">
                            <div style="display: flex; align-items: flex-start; gap: 1.25rem; background: rgba(239, 68, 68, 0.03); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(239, 68, 68, 0.1);">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); color: var(--danger); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--danger); margin-bottom: 0.25rem;">Delete My Account</h3>
                                    <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.4;">
                                        This will permanently remove your profile and all selection history. This action is irreversible.
                                    </p>
                                    <a href="users/users.php?delete=<?php echo $_SESSION['user_id']; ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" 
                                       class="btn btn-danger" 
                                       style="background: var(--danger); color: white; padding: 0.6rem 1.25rem; font-size: 0.8125rem; font-weight: 600;"
                                       onclick="return confirm('PERMANENT DELETION: Are you absolutely sure?')">
                                        Delete Account
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
$base_path = '';
endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
