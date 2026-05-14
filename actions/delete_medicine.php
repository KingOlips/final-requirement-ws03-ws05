<?php
require_once '../auth/security.php';
require_once '../auth/auth_check.php';
require_once '../includes/db_connection.php';
require_login();

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete all batches associated with this medicine first (foreign key constraints)
        $stmt = $pdo->prepare("DELETE FROM batches WHERE medicine_id = ?");
        $stmt->execute([$id]);
        
        // Delete the medicine
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $_SESSION['success_msg'] = "Medicine and all associated batches deleted successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Error deleting medicine: " . $e->getMessage();
    }
}

header("Location: ../product/medicines.php");
exit();
?>
