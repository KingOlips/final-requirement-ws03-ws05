<?php
require_once '../auth/init.php';
require_once '../includes/db_connection.php';
require_login();

// Handle deletion (prefer POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $delete_id = (int)$_POST['delete'];

    if ($delete_id === $_SESSION['user_id']) {
        // Self-deletion logic
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: ../logout.php");
        exit();
    } else {
        // Admin deleting another user - restrict to admin
        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            header("Location: ../index.php");
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_msg'] = "User deleted successfully.";
        header("Location: users.php");
        exit();
    }
} elseif (isset($_GET['delete'])) {
    // Backwards compatible GET handling (still requires CSRF)
    if (!isset($_GET['csrf']) || !verify_csrf_token($_GET['csrf'])) {
        die("Security violation: CSRF token mismatch.");
    }
    $delete_id = (int)$_GET['delete'];
    if ($delete_id === $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: ../logout.php");
        exit();
    } else {
        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            header("Location: ../index.php");
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_msg'] = "User deleted successfully.";
        header("Location: users.php");
        exit();
    }
}

// Restrict access to other users to admins only
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';

// Fetch all users
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | PharmTrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>
<body>
    <div class="app-wrapper">
        <?php $base_path = '../'; include '../includes/sidebar.php'; ?>

        <div class="page-body">
            <!-- TOP NAVBAR -->
            <header class="topnav">
                <div class="topnav-left">
                    <button class="hamburger" onclick="toggleSidebar()" title="Toggle Menu">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="topnav-breadcrumb">
                        <span class="topnav-title">Administration</span>
                        <span class="topnav-sub">User Management Dashboard</span>
                    </div>
                </div>
                <div class="topnav-right">
                    <button class="hamburger" onclick="toggleTheme()" title="Toggle Dark Mode">
                        <svg id="theme-icon-moon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg id="theme-icon-sun" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>
            </header>

            <main class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Users</h3>
                        <span class="badge badge-success"><?php echo count($users); ?> Total</span>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User Info</th>
                                    <th>Role</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div class="profile-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                                <div style="font-weight: 600; color: var(--text-main);"><?php echo e($user['username']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-success' : 'badge-primary'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border: none; cursor: pointer;" onclick="event.preventDefault(); showConfirm('Delete User?', 'Are you sure you want to remove this user account?', () => postWithCsrf('users.php', { delete: <?php echo $user['id']; ?> }, function(){ window.location.reload(); }));">Delete</button>
                                            <?php else: ?>
                                                <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/scripts.js"></script>
    <script>
        <?php if (isset($_SESSION['success_msg'])): ?>
            showToast('Success', '<?php echo addslashes($_SESSION['success_msg']); ?>', 'success');
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            showToast('Error', '<?php echo addslashes($_SESSION['error_msg']); ?>', 'danger');
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
