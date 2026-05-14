<?php
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';
require_login();

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['csrf'])) {
    if (!verify_csrf_token($_GET['csrf'])) {
        die("Security violation: CSRF token mismatch.");
    }
    
    $id = $_GET['id'];
    
    // Fetch details before deleting for Undo feature
    $stmt = $pdo->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt->execute([$id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($batch) {
        $_SESSION['last_deleted_batch'] = $batch;
        $_SESSION['undo_msg'] = "Batch " . $batch['batch_no'] . " has been deleted.";
        
        $stmt = $pdo->prepare("DELETE FROM batches WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header("Location: ../index.php");
exit();
?>
