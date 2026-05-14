<?php
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

if ((isset($_GET['id']) || isset($_POST['id'])) && (isset($_GET['action']) || isset($_POST['action']))) {
    $cart_id = (int)($_GET['id'] ?? $_POST['id']);
    $action = $_GET['action'] ?? $_POST['action'];
    $user_id = $_SESSION['user_id'];
    $is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

    if ($action === 'add') {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    } elseif ($action === 'sub') {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = GREATEST(1, quantity - 1) WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    }

    if ($is_ajax) {
        try {
            // Fetch new state for the item and the whole cart
            $stmt = $pdo->prepare("SELECT c.quantity, 
                                   (SELECT price_per_unit FROM batches WHERE medicine_id = c.medicine_id ORDER BY expiry_date ASC LIMIT 1) as price 
                                   FROM cart c 
                                   WHERE c.id = ?");
            $stmt->execute([$cart_id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                throw new Exception("Item not found in cart.");
            }

            $stmt = $pdo->prepare("SELECT SUM(c.quantity * (SELECT price_per_unit FROM batches WHERE medicine_id = c.medicine_id ORDER BY expiry_date ASC LIMIT 1)) as total 
                                   FROM cart c 
                                   WHERE c.user_id = ?");
            $stmt->execute([$user_id]);
            $subtotal = $stmt->fetch()['total'] ?: 0;
            $grand_total = $subtotal * 1.12; // Adding 12% VAT to match cart.php
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'new_qty' => (int)$item['quantity'],
                'item_total' => number_format($item['quantity'] * ($item['price'] ?: 0), 2),
                'grand_total' => number_format($grand_total, 2)
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    header("Location: ../product/cart.php");
    exit();
}
?>
