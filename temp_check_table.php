<?php
require_once 'includes/config.php';

$db = new Database();
$conn = $db->getConnection();

// Check stock_opname_details table structure
$query = "DESCRIBE stock_opname_details";
$stmt = $conn->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Stock Opname Details Table Structure</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
