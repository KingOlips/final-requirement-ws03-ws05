<?php
require_once '../auth/init.php';
require_once '../includes/db_connection.php';
require_login();

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Fetch medicines for the dropdown
try {
    $medicines = $pdo->query("SELECT id, name, category FROM medicines ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Error fetching medicines: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $medicine_id = (int)$_POST['medicine_id'];
    $batch_no = trim($_POST['batch_no']);
    $expiry_date = $_POST['expiry_date'];
    $quantity = (int)$_POST['quantity'];
    $price_per_unit = (float)$_POST['price_per_unit'];

    if (empty($medicine_id) || empty($batch_no) || empty($expiry_date) || $quantity <= 0 || $price_per_unit <= 0) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO batches (medicine_id, batch_no, expiry_date, quantity, price_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$medicine_id, $batch_no, $expiry_date, $quantity, $price_per_unit]);
            $success = "Batch $batch_no successfully added to inventory!";
        } catch (PDOException $e) {
            $error = "Error adding batch: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Batch | PharmTrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <script>
        function calculateTotal() {
            const qty = document.getElementById('quantity').value || 0;
            const price = document.getElementById('price_per_unit').value || 0;
            const total = qty * price;
            document.getElementById('total-value-display').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    </script>
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
                        <span class="topnav-sub">Stock Intake</span>
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
                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card-header" style="background: linear-gradient(to right, var(--background), var(--card-bg)); border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <h3 class="card-title" style="font-size: 1.25rem;">Register New Batch</h3>
                                <p style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 400;">Input detailed stock information for incoming medicine batches</p>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 2.5rem;">
                        <?php if ($success): ?>
                            <div class="badge-success" style="padding: 1.25rem; border-radius: 1rem; margin-bottom: 2rem; font-size: 0.9rem; border-left: 5px solid var(--success); display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow-sm); background: rgba(34, 197, 94, 0.1);">
                                <div style="background: var(--success); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span><?php echo e($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="badge-danger" style="padding: 1.25rem; border-radius: 1rem; margin-bottom: 2rem; font-size: 0.9rem; border-left: 5px solid var(--danger); display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow-sm); background: rgba(239, 68, 68, 0.1);">
                                <div style="background: var(--danger); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <span><?php echo e($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div class="form-section">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Medicine Details</h4>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="medicine_id">Select Medicine</label>
                                        <select name="medicine_id" id="medicine_id" required style="background: var(--background); color: var(--text-main);">
                                            <option value="">Choose Medicine</option>
                                            <?php foreach ($medicines as $med): ?>
                                                <option value="<?php echo $med['id']; ?>"><?php echo e($med['name']); ?> (<?php echo e($med['category']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batch_no">Batch Number / ID</label>
                                        <input type="text" name="batch_no" id="batch_no" required placeholder="e.g. BATCH-2024-001" style="background: var(--background); color: var(--text-main);">
                                    </div>

                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date</label>
                                        <input type="date" name="expiry_date" id="expiry_date" required min="<?php echo date('Y-m-d'); ?>" style="background: var(--background); color: var(--text-main);">
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
                                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Stock & Pricing</h4>
                                    </div>

                                    <div class="form-group">
                                        <label for="quantity">Quantity (Units)</label>
                                        <div style="position: relative;">
                                            <input type="number" name="quantity" id="quantity" required min="1" placeholder="0" oninput="calculateTotal()" style="background: var(--background); color: var(--text-main); padding-right: 3rem;">
                                            <span style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">PCS</span>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="price_per_unit">Price per Unit (₱)</label>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--text-main);">₱</span>
                                            <input type="number" name="price_per_unit" id="price_per_unit" step="0.01" required min="0" placeholder="0.00" oninput="calculateTotal()" style="background: var(--background); color: var(--text-main); padding-left: 2.5rem;">
                                        </div>
                                    </div>

                                    <div style="background: rgba(79, 70, 229, 0.05); border: 1px dashed var(--primary); border-radius: 12px; padding: 1.25rem; margin-top: 2rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <span style="font-size: 0.8125rem; color: var(--text-muted);">Estimated Total Value</span>
                                            <span id="total-value-display" style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">₱0.00</span>
                                        </div>
                                        <p style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.4;">This value represents the total cost of this batch based on the quantity and unit price provided.</p>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 3.5rem; display: flex; justify-content: flex-end; gap: 1.25rem; border-top: 1px solid var(--border); padding-top: 2rem;">
                                <a href="../index.php" class="btn" style="background: var(--background); color: var(--text-main); font-weight: 600; padding-left: 1.5rem; padding-right: 1.5rem;">Discard</a>
                                <button type="submit" class="btn btn-primary" style="padding-left: 2.5rem; padding-right: 2.5rem; box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Confirm & Save Batch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>