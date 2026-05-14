<?php
require_once 'includes/db_connection.php';
try {
    $pdo->exec("ALTER TABLE medicines ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error or already exists: " . $e->getMessage();
}
?>
