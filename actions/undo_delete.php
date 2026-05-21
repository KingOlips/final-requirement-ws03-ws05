<?php
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';
require_login();

if (!isset($_GET['csrf']) || !verify_csrf_token($_GET['csrf'])) {
    die("Security violation: CSRF token mismatch.");
}

if (isset($_SESSION['last_deleted_batch'])) {
    $batch = $_SESSION['last_deleted_batch'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO batches (medicine_id, batch_no, expiry_date, quantity, price_per_unit) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $batch['medicine_id'],
            $batch['batch_no'],
            $batch['expiry_date'],
            $batch['quantity'],
            $batch['price_per_unit']
        ]);
        
        unset($_SESSION['last_deleted_batch']);
        unset($_SESSION['undo_msg']);
        $_SESSION['success_msg'] = "Batch successfully restored!";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Error restoring batch: " . $e->getMessage();
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit();
?>
