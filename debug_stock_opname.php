<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Check if ID is provided
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    echo "Please provide a stock opname ID in the URL parameter (e.g., ?id=1)";
    exit;
}

// Get stock opname header
$query = "SELECT * FROM stock_opname WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$stock_opname = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stock_opname) {
    echo "Stock opname with ID $id not found";
    exit;
}

// Get stock opname details
$query_detail = "SELECT sod.*, b.nama_barang 
                FROM stock_opname_details sod
                JOIN barang b ON sod.barang_id = b.id
                WHERE sod.stock_opname_id = :stock_opname_id
                ORDER BY b.nama_barang ASC";
$stmt_detail = $conn->prepare($query_detail);
$stmt_detail->bindParam(':stock_opname_id', $id);
$stmt_detail->execute();
$stock_opname_details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Debug Stock Opname #$id</h1>";
echo "<h2>Stock Opname Header</h2>";
echo "<pre>";
print_r($stock_opname);
echo "</pre>";

echo "<h2>Stock Opname Details</h2>";
echo "<pre>";
print_r($stock_opname_details);
echo "</pre>";
?>
