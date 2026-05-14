<?php
require_once 'includes/db_connection.php';
try {
    // Default tablets
    $pdo->exec("UPDATE medicines SET image_url = 'assets/img/products/tablets.png'");
    
    // Capsules for specific names
    $pdo->exec("UPDATE medicines SET image_url = 'assets/img/products/capsules.png' WHERE name LIKE '%Capsule%' OR name LIKE '%Amoxicillin%' OR name LIKE '%Antibiotic%'");
    
    echo "Database image paths updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
