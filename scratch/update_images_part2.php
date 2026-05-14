<?php
require_once 'includes/db_connection.php';
try {
    $stmt = $pdo->prepare("UPDATE medicines SET image_url = 'assets/img/products/cough_syrup.png' WHERE name LIKE '%Cough Syrup%'");
    $stmt->execute();
    echo "Cough Syrup image updated.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
