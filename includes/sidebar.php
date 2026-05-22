<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_initial = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));

// Compute base path for root-level links when page lives inside a subfolder.
if (!isset($base_path) || $base_path === '') {
    $script_path = trim($_SERVER['SCRIPT_NAME'], '/');
    $path_parts = explode('/', $script_path);
    if (count($path_parts) > 1) {
        $base_path = '/' . $path_parts[0] . '/';
    } else {
        $base_path = '/';
    }
}
?>
<script>
    if (!/app_logged_in=1/.test(document.cookie)) {
        document.documentElement.style.display = 'none';
        window.location.replace('<?php echo ($base_path ?? ""); ?>login.php');
    }
</script>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="mainSidebar">

    <div class="sidebar-logo">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="sidebar-logo-icon">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                </svg>
            </div>
            <span class="sidebar-logo-text">PharmTrack</span>
        </div>
    </div>

    <!-- User Profile Card -->
    <div class="sidebar-profile" style="margin: 0 0.75rem 1rem 0.75rem;">
        <div class="profile-avatar"><?php echo e($user_initial); ?></div>
        <div class="profile-info">
            <span class="profile-name"><?php echo e($_SESSION['username']); ?></span>
            <span class="profile-role"><?php echo ucfirst($user_role); ?></span>
        </div>
    </div>

    <!-- Nav Section Label -->
    <p class="nav-section-label">Main Menu</p>

    <!-- Navigation Links -->
    <nav class="nav-menu">
        <a href="<?php echo $base_path ?? ''; ?>index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo $base_path ?? ''; ?>product/medicines.php" class="nav-item <?php echo $current_page == 'medicines.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.641.32a2 2 0 01-1.836 0l-.64-.32a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547l-.234.234a2 2 0 000 2.828l.234.234a2 2 0 001.022.547l2.387.477a6 6 0 003.86-.517l.641-.32a2 2 0 011.836 0l.64.32a6 6 0 003.86-.517l2.387-.477a2 2 0 001.022-.547l.234-.234a2 2 0 000-2.828l-.234-.234zM6 10a4 4 0 118 0 4 4 0 01-8 0z"/>
            </svg>
            <span>Medicines</span>
        </a>

        <?php if (($user_role ?? 'user') === 'user'): ?>
        <?php 
            $cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
            $cart_stmt->execute([$_SESSION['user_id'] ?? 0]);
            $cart_count = $cart_stmt->fetchColumn() ?: 0;
        ?>
        <a href="<?php echo $base_path ?? ''; ?>product/cart.php" class="nav-item <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
            <div style="position: relative; display: flex; align-items: center; width: 100%; gap: 0.75rem;">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>My Cart</span>
                <span id="cart-badge" style="margin-left: auto; background: var(--primary); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 6px; font-weight: 700; display: <?php echo $cart_count > 0 ? 'inline-flex' : 'none'; ?>;">
                    <?php echo $cart_count; ?>
                </span>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <a href="<?php echo $base_path ?? ''; ?>actions/add_stock.php" class="nav-item <?php echo $current_page == 'add_stock.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add New Batch</span>
        </a>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <a href="<?php echo $base_path ?? ''; ?>admin/reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Reports</span>
        </a>
        <?php endif; ?>
    </nav>

    <?php if ($user_role === 'admin'): ?>
    <!-- Admin Section -->
    <p class="nav-section-label" style="margin-top: 1rem;">Administration</p>
    <nav class="nav-menu">
        <a href="<?php echo $base_path ?? ''; ?>users/users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>User Management</span>
        </a>
    </nav>
    <?php endif; ?>

    <!-- Section Label -->
    <p class="nav-section-label" style="margin-top: 1rem;">Account</p>

    <nav class="nav-menu">
        <a href="<?php echo $base_path ?? ''; ?>users/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Settings</span>
        </a>
    </nav>

    <!-- Profile & Sign Out at Bottom -->
    <div class="sidebar-footer">
        <a href="<?php echo $base_path ?? ''; ?>logout.php" class="nav-item nav-item-danger">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>

<!-- Global Toast Notifications -->
<div id="toast-container" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 9999; display: flex; flex-direction: column; gap: 1rem;">
    <?php if (isset($_SESSION['undo_msg'])): ?>
        <div class="toast toast-undo">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="toast-icon"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
                <div>
                    <p style="font-size: 0.875rem; font-weight: 500; margin: 0;"><?php echo $_SESSION['undo_msg']; ?></p>
                    <a href="<?php echo $base_path ?? ''; ?>actions/undo_delete.php?csrf=<?php echo $_SESSION['csrf_token']; ?>" class="undo-link">Undo Action</a>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; padding:0.25rem;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['undo_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="toast toast-success">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="toast-icon"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                <p style="font-size: 0.875rem; font-weight: 500; margin: 0;"><?php echo $_SESSION['success_msg']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
</div>

<script>window.BASE_URL = '<?php echo $base_path ?? ""; ?>';</script>
<script src="<?php echo $base_path ?? ''; ?>assets/js/scripts.js"></script>
