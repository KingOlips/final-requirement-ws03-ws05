<?php
require_once 'includes/db_connection.php';
try {
    echo "Cart table structure:\n";
    $q = $pdo->query("DESCRIBE cart");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
