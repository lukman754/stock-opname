<?php
require_once 'includes/config.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Periksa struktur tabel stock
    $query = "DESCRIBE stock";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Struktur Tabel Stock</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
