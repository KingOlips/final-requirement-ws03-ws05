<?php
require_once '../auth/init.php';
require_once '../includes/db_connection.php';
require_login();

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Prefer POST for destructive actions, fall back to GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }
    $id = (int)$_POST['id'];
} elseif (isset($_GET['id']) && isset($_GET['csrf'])) {
    if (!verify_csrf_token($_GET['csrf'])) {
        die("Security violation: CSRF token mismatch.");
    }
    $id = (int)$_GET['id'];
} else {
    $id = null;
}

if ($id !== null) {
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
