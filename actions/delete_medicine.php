<?php
require_once '../auth/init.php';
require_once '../includes/db_connection.php';
require_login();

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Prefer POST for destructive actions, fall back to GET for compatibility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }
    $id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    if (!isset($_GET['csrf']) || !verify_csrf_token($_GET['csrf'])) {
        die("Security violation: CSRF token mismatch.");
    }
    $id = (int)$_GET['id'];
} else {
    $id = null;
}

if ($id !== null) {
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
