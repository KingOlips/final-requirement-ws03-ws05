<?php 
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';
require_login();

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
$params = [];

if ($search) {
    $where_clause = " WHERE (m.name LIKE ? OR m.category LIKE ?) ";
    $params = ["%$search%", "%$search%"];
}

// Security: Hide expired medicines from users
if ($user_role !== 'admin') {
    if ($where_clause === "") {
        $where_clause = " WHERE b.expiry_date >= CURDATE() ";
    } else {
        $where_clause .= " AND b.expiry_date >= CURDATE() ";
    }
}

// Query to get unique medicines and their total stock
$query = "SELECT m.id, m.name, m.category, 
          COUNT(b.id) as batch_count, 
          SUM(b.quantity) as total_qty,
          MIN(b.expiry_date) as nearest_expiry
          FROM medicines m 
          LEFT JOIN batches b ON m.id = b.medicine_id 
          $where_clause
          GROUP BY m.id 
          ORDER BY m.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory List | PharmTrack</title>
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
                        <span class="topnav-title">Inventory</span>
                        <span class="topnav-sub">Full Medicine Catalog</span>
                    </div>
                </div>
                <div class="topnav-right">
                    <button class="hamburger" onclick="toggleTheme()" title="Toggle Dark Mode">
                        <svg id="theme-icon-moon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg id="theme-icon-sun" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                    <a href="../actions/add_stock.php" class="topnav-btn">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Stock
                    </a>
                    <?php endif; ?>
                    <div class="topnav-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div style="margin-bottom: 2rem;">
                    <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Inventory Catalog</h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Manage and track your entire pharmacy medicine collection.</p>
                </div>

                <!-- Search Bar -->
                <div class="card" style="padding: 1.25rem; margin-bottom: 2rem;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <div style="position: relative; flex: 1;">
                            <svg style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" name="search" placeholder="Search medicines or categories..." value="<?php echo e($search); ?>" style="padding-left: 2.75rem; background: var(--background);">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding-left: 1.5rem; padding-right: 1.5rem;">Search Catalog</button>
                    </form>
                </div>

                <div class="card" style="padding: 0;">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Category</th>
                                    <th>Batches</th>
                                    <th>Total Quantity</th>
                                    <th>Nearest Expiry</th>
                                    <th>Stock Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicines as $med): 
                                    $status = 'In Stock';
                                    $badge = 'badge-success';
                                    if ($med['total_qty'] <= 0) {
                                        $status = 'Out of Stock';
                                        $badge = 'badge-danger';
                                    } elseif ($med['total_qty'] < 50) {
                                        $status = 'Low Stock';
                                        $badge = 'badge-warning';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 48px; height: 48px; background: rgba(79, 70, 229, 0.05); border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                    <?php if (!empty($med['image_url']) && file_exists($med['image_url'])): ?>
                                                        <img src="<?php echo $med['image_url']; ?>" alt="<?php echo e($med['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="color: var(--primary); font-weight: 700; font-size: 0.875rem;">
                                                            <?php echo strtoupper(substr($med['name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <strong style="color: var(--text-main); font-size: 0.95rem;"><?php echo e($med['name']); ?></strong>
                                            </div>
                                        </td>
                                        <td><span class="category-pill"><?php echo e($med['category']); ?></span></td>
                                        <td><span style="font-size: 0.875rem; color: var(--text-muted); font-weight: 500;"><?php echo (int)$med['batch_count']; ?> batches active</span></td>
                                        <td style="font-weight: 700; color: var(--text-main);"><?php echo number_format($med['total_qty'] ?: 0); ?> pcs</td>
                                        <td>
                                            <?php if($med['nearest_expiry']): ?>
                                                <div style="font-size: 0.875rem; font-weight: 500;"><?php echo e($med['nearest_expiry']); ?></div>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.8125rem;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
                                        <td>
                                            <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                                                <a href="../actions/delete_medicine.php?id=<?php echo $med['id']; ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; background: rgba(239, 68, 68, 0.1); color: var(--danger);" onclick="event.preventDefault(); showConfirm('Delete Medicine?', 'Are you sure you want to remove this medicine and all its associated batches?', () => window.location.href = this.href);">Delete</a>
                                            <?php else: ?>
                                                <button onclick="addToCart(<?php echo $med['id']; ?>, '<?php echo addslashes($med['name']); ?>')" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" <?php echo $med['total_qty'] <= 0 ? 'disabled' : ''; ?>>
                                                    Add to Cart
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($medicines)): ?>
                                    <tr><td colspan="7" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                                        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.2; margin-bottom: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <p>No medicines found matching your criteria.</p>
                                    </td></tr>
                                <?php endif; ?>
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
