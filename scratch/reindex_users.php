<?php
require_once 'includes/db_connection.php';

try {
    $pdo->beginTransaction();

    // 1. Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Fetch all users ordered by creation date
    $stmt = $pdo->query("SELECT id FROM users ORDER BY created_at ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $new_id = 1;
    foreach ($users as $user) {
        $old_id = $user['id'];
        
        // 3. Update the user ID
        $updateUser = $pdo->prepare("UPDATE users SET id = ? WHERE id = ?");
        $updateUser->execute([$new_id, $old_id]);

        // 4. Update the cart mapping
        $updateCart = $pdo->prepare("UPDATE cart SET user_id = ? WHERE user_id = ?");
        $updateCart->execute([$new_id, $old_id]);

        $new_id++;
    }

    // 5. Reset the auto-increment
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = $new_id");

    // 6. Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->commit();
    echo "Successfully re-indexed all users and updated related data.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>
