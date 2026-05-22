<?php 
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';
require_login();

// Handle Remove Action
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    header("Location: cart.php?removed=1");
    exit();
}

// Fetch Cart Items
$query = "SELECT c.*, m.name, m.category, m.image_url, 
          (SELECT price_per_unit FROM batches WHERE medicine_id = m.id ORDER BY expiry_date ASC LIMIT 1) as price
          FROM cart c 
          JOIN medicines m ON c.medicine_id = m.id 
          WHERE c.user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['quantity'] * ($item['price'] ?: 0);
}
$tax = $subtotal * 0.12; // 12% VAT
$grand_total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Selection | PharmTrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .cart-grid { grid-template-columns: 1fr; }
        }

        .cart-item-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideInUp 0.4s ease-out backwards;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cart-item-card:hover {
            /* Hover effect removed per UX request */
            transform: none;
            border-color: inherit;
            box-shadow: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 1rem;
            overflow: hidden;
            background: var(--background);
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .item-info { flex: 1; }
        .item-name { font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem; }
        .item-meta { font-size: 0.8125rem; color: var(--text-muted); display: flex; gap: 1rem; }

        .qty-controls {
            display: flex;
            align-items: center;
            background: var(--background);
            border-radius: 0.75rem;
            padding: 0.25rem;
            border: 1px solid var(--border);
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .qty-btn:hover { background: var(--card-bg); color: var(--primary); }
        .qty-val { width: 40px; text-align: center; font-weight: 700; font-size: 0.95rem; }

        .price-section { text-align: right; min-width: 120px; }
        .unit-price { font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem; }
        .total-price { font-size: 1.125rem; font-weight: 800; color: var(--primary); }

        .summary-card {
            position: sticky;
            top: 6rem;
            background: var(--card-bg);
            border-radius: 1.5rem;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .summary-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(to right, rgba(79, 70, 229, 0.05), transparent);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .summary-total {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px dashed var(--border);
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .btn-checkout {
            width: 100%;
            padding: 1.25rem;
            border-radius: 1rem;
            background: var(--primary);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px var(--primary);
            filter: brightness(1.1);
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php $base_path = '../'; include '../includes/sidebar.php'; ?>

        <div class="page-body">
            <header class="topnav">
                <div class="topnav-left">
                    <button class="hamburger" onclick="toggleSidebar()">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="topnav-breadcrumb">
                        <span class="topnav-title">My Selection</span>
                        <span class="topnav-sub"><?php echo count($cart_items); ?> Medicines selected</span>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <?php if (empty($cart_items)): ?>
                    <div class="card" style="text-align: center; padding: 5rem 2rem;">
                        <div style="width: 80px; height: 80px; background: rgba(79, 70, 229, 0.05); color: var(--primary); border-radius: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class='bx bx-shopping-bag' style='font-size: 2.5rem;'></i>
                        </div>
                        <h2 style="font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">Your cart is empty</h2>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">Looks like you haven't added any medicines to your selection yet.</p>
                        <a href="medicines.php" class="btn btn-primary" style="padding: 1rem 2.5rem;">Browse Medicines</a>
                    </div>
                <?php else: ?>
                    <div class="cart-grid">
                        <div class="cart-items-list">
                            <?php foreach ($cart_items as $index => $item): 
                                $item_total = $item['quantity'] * ($item['price'] ?: 0);
                            ?>
                                <div class="cart-item-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="item-image">
                                        <img src="../<?php echo $item['image_url'] ?: 'assets/img/placeholder.png'; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="item-info">
                                        <h3 class="item-name"><?php echo e($item['name']); ?></h3>
                                        <div class="item-meta">
                                            <span><i class='bx bx-category-alt'></i> <?php echo e($item['category']); ?></span>
                                            <span><i class='bx bx-check-shield'></i> Batch Verified</span>
                                        </div>
                                        <div style="margin-top: 1rem; display: flex; align-items: center; gap: 1rem;">
                                            <div class="qty-controls">
                                                <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'sub', this)" class="qty-btn" style="background:none; border:none; padding:0;"><i class='bx bx-minus'></i></button>
                                                <div class="qty-val"><?php echo (int)$item['quantity']; ?></div>
                                                <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'add', this)" class="qty-btn" style="background:none; border:none; padding:0;"><i class='bx bx-plus'></i></button>
                                            </div>
                                            <a href="#" onclick="removeFromCart(<?php echo $item['id']; ?>, this); return false;" style="color: var(--danger); font-size: 0.8125rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 0.25rem;">
                                                <i class='bx bx-trash'></i> Remove
                                            </a>
                                        </div>
                                    </div>
                                    <div class="price-section">
                                        <div class="unit-price">&#8369;<?php echo number_format($item['price'] ?: 0, 2); ?> / pc</div>
                                        <div class="total-price">&#8369;<?php echo number_format($item_total, 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-card">
                            <div class="summary-header">
                                <h3 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); margin: 0;">Order Summary</h3>
                            </div>
                            <div style="padding: 1.5rem;">
                                <div class="summary-row">
                                    <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                                    <span style="color: var(--text-main); font-weight: 600;">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Estimated Tax (VAT 12%)</span>
                                    <span style="color: var(--text-main); font-weight: 600;">&#8369;<?php echo number_format($tax, 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Processing Fee</span>
                                    <span style="color: var(--success); font-weight: 700;">FREE</span>
                                </div>

                                <div class="summary-total">
                                    <span style="font-weight: 700; color: var(--text-main);">Order Total</span>
                                    <div style="text-align: right;">
                                        <div id="grand-total-val" style="font-size: 1.75rem; font-weight: 900; color: var(--primary);">&#8369;<?php echo number_format($grand_total, 2); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Inc. all taxes</div>
                                    </div>
                                </div>

                                <button onclick="showToast('Order Placed', 'Your request has been sent to processing.', 'success')" class="btn-checkout">
                                    <i class='bx bx-lock-alt'></i> Secure Checkout
                                </button>
                                
                                <div style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.75rem;">
                                    <i class='bx bxs-check-shield' style='color: var(--success); font-size: 1rem;'></i>
                                    Guaranteed safe & secure checkout
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
