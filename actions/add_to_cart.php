<?php
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = $_POST['medicine_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$medicine_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
        exit();
    }

    try {
        // Check if already in cart
        $check = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND medicine_id = ?");
        $check->execute([$user_id, $medicine_id]);
        $existing = $check->fetch();

        if ($existing) {
            $update = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
            $update->execute([$existing['id']]);
        } else {
            $insert = $pdo->prepare("INSERT INTO cart (user_id, medicine_id, quantity) VALUES (?, ?, 1)");
            $insert->execute([$user_id, $medicine_id]);
        }

        echo json_encode(['success' => true, 'message' => 'Added to cart successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding to cart: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
