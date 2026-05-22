<?php
require_once '../auth/init.php';
require_once '../includes/db_connection.php';
require_login();

// Restrict to admins only
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch Summary Stats
try {
    // Total value calculation
    $stmt = $pdo->query("SELECT SUM(quantity * price_per_unit) as total FROM batches");
    $total_value = $stmt->fetch()['total'] ?: 0;

    // Expired batches count
    $stmt = $pdo->query("SELECT COUNT(*) as expired FROM batches WHERE expiry_date < CURDATE()");
    $expired_count = $stmt->fetch()['expired'];

    // Low stock medicines count
    $stmt = $pdo->query("SELECT COUNT(*) as low FROM (SELECT medicine_id, SUM(quantity) as total_qty FROM batches GROUP BY medicine_id HAVING total_qty < 50) as counts");
    $low_stock_count = $stmt->fetch()['low'];
    
    // Category breakdown
    $category_query = "SELECT m.category, SUM(b.quantity * b.price_per_unit) as total_value, COUNT(b.id) as batch_count 
                       FROM medicines m 
                       LEFT JOIN batches b ON m.id = b.medicine_id 
                       GROUP BY m.category 
                       ORDER BY total_value DESC";
    $category_stats = $pdo->query($category_query)->fetchAll();

} catch (PDOException $e) {
    die("Error fetching reports: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | PharmTrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <div class="page-body">
            <!-- TOP NAVBAR -->
            <header class="topnav">
                <div class="topnav-left">
                    <button class="hamburger" onclick="toggleSidebar()" title="Toggle Menu">
                        <i class='bx bx-menu' style='font-size: 1.5rem;'></i>
                    </button>
                    <div class="topnav-breadcrumb">
                        <span class="topnav-title">Analytics</span>
                        <span class="topnav-sub">Inventory Performance Reports</span>
                    </div>
                </div>
                <div class="topnav-right">
                    <button class="hamburger" onclick="toggleTheme()" title="Toggle Dark Mode">
                        <svg id="theme-icon-moon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg id="theme-icon-sun" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <div class="topnav-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
                    <div>
                        <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Inventory Performance</h1>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Comprehensive overview of your pharmacy stock health and value.</p>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <button class="btn" style="background: var(--card-bg); border: 1px solid var(--border); color: var(--text-main);">
                            <i class='bx bx-download' style='margin-right: 0.5rem;'></i>
                            Export PDF
                        </button>
                        <button class="btn btn-primary">
                            <i class='bx bx-plus' style='margin-right: 0.5rem;'></i>
                            Generate custom report
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                            <i class='bx bx-trending-up' style='font-size: 1.5rem;'></i>
                        </div>
                        <div class="stat-value">₱<?php echo number_format($total_value, 2); ?></div>
                        <div class="stat-label">Total Inventory Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            <i class='bx bx-time' style='font-size: 1.5rem;'></i>
                        </div>
                        <div class="stat-value"><?php echo $expired_count; ?></div>
                        <div class="stat-label">Expired Batches</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent);">
                            <i class='bx bx-error' style='font-size: 1.5rem;'></i>
                        </div>
                        <div class="stat-value"><?php echo $low_stock_count; ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">Category Distribution</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Active Batches</th>
                                    <th>Total Value (₱)</th>
                                    <th>Health</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_stats as $stat): 
                                    $health = $stat['total_value'] > 10000 ? 'badge-success' : 'badge-warning';
                                    $label = $stat['total_value'] > 10000 ? 'Optimal' : 'Needs Review';
                                ?>
                                    <tr>
                                        <td><strong><?php echo e($stat['category'] ?: 'Uncategorized'); ?></strong></td>
                                        <td><?php echo (int)$stat['batch_count']; ?> batches</td>
                                        <td>₱<?php echo number_format($stat['total_value'] ?: 0, 2); ?></td>
                                        <td><span class="badge <?php echo $health; ?>"><?php echo $label; ?></span></td>
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
