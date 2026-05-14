<?php 
require_once 'auth/security.php';
require_once 'auth/auth_check.php';
require_once 'includes/db_connection.php';
require_login();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
$params = [];

if ($search) {
    $where_clause = " WHERE (m.name LIKE ? OR b.batch_no LIKE ?) ";
    $params = ["%$search%", "%$search%"];
}

// Security: Hide expired batches from users
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    if ($where_clause === "") {
        $where_clause = " WHERE b.expiry_date >= CURDATE() ";
    } else {
        $where_clause .= " AND b.expiry_date >= CURDATE() ";
    }
}

$total_meds    = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$expired_count = $pdo->query("SELECT COUNT(*) FROM batches WHERE expiry_date < CURDATE()")->fetchColumn();
$soon_count    = $pdo->query("SELECT COUNT(*) FROM batches WHERE expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)")->fetchColumn();
$total_value   = $pdo->query("SELECT SUM(quantity * price_per_unit) FROM batches")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PharmTrack</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body>
<div class="app-wrapper">
    <?php $base_path = '';
        include 'includes/sidebar.php'; ?>

    <div class="page-body">

        <!-- TOP NAVBAR -->
        <header class="topnav">
            <div class="topnav-left">
                <!-- Hamburger -->
                <button class="hamburger" onclick="toggleSidebar()" title="Toggle Menu">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="topnav-breadcrumb">
                    <span class="topnav-title">Dashboard</span>
                    <span class="topnav-sub">Inventory Overview</span>
                </div>
            </div>
            <div class="topnav-right">
                <!-- Search -->
                <form method="GET" class="topnav-search">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search medicines or batches..." value="<?php echo e($search); ?>">
                </form>
                <button class="hamburger" onclick="toggleTheme()" title="Toggle Dark Mode">
                    <svg id="theme-icon-moon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg id="theme-icon-sun" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
                <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                <a href="actions/add_stock.php" class="topnav-btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Stock
                </a>
                <?php endif; ?>
                <!-- User Avatar -->
                <div class="topnav-avatar" title="<?php echo e($_SESSION['username']); ?>">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(79,70,229,0.1); color: var(--primary);">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="stat-label">Total Medicines</div>
                    <div class="stat-value"><?php echo (int)$total_meds; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: var(--secondary);">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="stat-label">Stock Value</div>
                    <div class="stat-value" style="color: var(--secondary);">&#8369;<?php echo number_format($total_value ?: 0, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239,68,68,0.1); color: var(--danger);">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Expired Batches</div>
                    <div class="stat-value" style="color: var(--danger);"><?php echo (int)$expired_count; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: var(--accent);">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Expiring Soon</div>
                    <div class="stat-value" style="color: var(--accent);"><?php echo (int)$soon_count; ?></div>
                </div>
            </div>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div class="card-header">
                        <h3 class="card-title">Inventory Overview</h3>
                        <?php if ($search): ?>
                            <a href="index.php" style="font-size: 0.8125rem; color: var(--primary); font-weight: 600; text-decoration: none;">Clear Search: "<?php echo e($search); ?>"</a>
                        <?php endif; ?>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Category</th>
                                    <th>Batch No.</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total Value</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT m.name, m.category, b.* FROM batches b JOIN medicines m ON b.medicine_id = m.id $where_clause ORDER BY b.expiry_date ASC";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute($params);

                                while($row = $stmt->fetch()) {
                                    $today        = date('Y-m-d');
                                    $warning_date = date('Y-m-d', strtotime('+3 months'));
                                    $badge_class  = 'badge-success';
                                    $status       = 'Good';

                                    if ($row['expiry_date'] < $today) {
                                        $badge_class = 'badge-danger';
                                        $status = 'EXPIRED';
                                    } elseif ($row['expiry_date'] < $warning_date) {
                                        $badge_class = 'badge-warning';
                                        $status = 'Soon';
                                    }

                                    $total_row_value = $row['quantity'] * $row['price_per_unit'];
                                    $img_html = "";
                                    if (!empty($row['image_url']) && file_exists($row['image_url'])) {
                                        $img_html = "<div style='width: 40px; height: 40px; border-radius: 8px; overflow: hidden; background: #f8fafc; border: 1px solid #e2e8f0;'>
                                                        <img src='{$row['image_url']}' style='width: 100%; height: 100%; object-fit: cover;'>
                                                     </div>";
                                    } else {
                                        $initial = strtoupper(substr($row['name'], 0, 1));
                                        $img_html = "<div style='width: 40px; height: 40px; border-radius: 8px; background: rgba(79,70,229,0.05); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8125rem; border: 1px solid rgba(79,70,229,0.1);'>
                                                        $initial
                                                     </div>";
                                    }

                                    echo "<tr>
                                        <td>
                                            <div style='display: flex; align-items: center; gap: 0.75rem;'>
                                                $img_html
                                                <strong>".e($row['name'])."</strong>
                                            </div>
                                        </td>
                                        <td><span class='category-pill'>".e($row['category'])."</span></td>
                                        <td><code>".e($row['batch_no'])."</code></td>
                                        <td>".number_format($row['quantity'])."</td>
                                        <td>&#8369;".number_format($row['price_per_unit'], 2)."</td>
                                        <td><strong>&#8369;".number_format($total_row_value, 2)."</strong></td>
                                        <td>".e($row['expiry_date'])."</td>
                                        <td><span class='badge $badge_class'>$status</span></td>
                                        <td>";
                                            if ($user_role === 'admin') {
                                                echo "<a href='actions/delete_batch.php?id={$row['id']}&csrf=".get_csrf_token()."' 
                                                        class='btn btn-danger' 
                                                        style='padding: 0.4rem 0.8rem; font-size: 0.75rem; background: rgba(239, 68, 68, 0.1); color: var(--danger);' 
                                                        onclick=\"event.preventDefault(); showConfirm('Delete Batch?', 'Are you sure you want to remove this medicine batch from the inventory?', () => window.location.href = this.href);\">Delete</a>";
                                            } else {
                                                $med_name = addslashes($row['name']);
                                                echo "<button onclick=\"addToCart({$row['id']}, '$med_name')\" 
                                                        class='btn btn-primary' 
                                                        style='padding: 0.4rem 0.8rem; font-size: 0.75rem;' " . 
                                                        ($row['quantity'] <= 0 ? 'disabled' : '') . ">
                                                        Add to Cart
                                                      </button>";
                                            }
                                    echo "</td>
                                    </tr>";
                                }

                                if ($stmt->rowCount() == 0) {
                                    echo "<tr><td colspan='9' style='text-align:center; padding:3rem; color:var(--text-muted);'>
                                        <svg width='48' height='48' fill='none' stroke='currentColor' viewBox='0 0 24 24' style='opacity:0.3; margin-bottom:0.5rem; display:block; margin-left:auto; margin-right:auto;'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'/>
                                        </svg>
                                        No inventory items found.
                                    </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        </main>
    </div>
</div>
    <script src="assets/js/scripts.js"></script>
    <script>
        <?php if (isset($_SESSION['undo_msg'])): ?>
            showUndoToast('<?php echo addslashes($_SESSION['undo_msg']); ?>', 'actions/undo_delete.php');
            <?php unset($_SESSION['undo_msg']); ?>
        <?php endif; ?>

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