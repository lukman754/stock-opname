<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Ambil parameter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';

// Validasi input
if (empty($search) || strlen($search) < 2 || empty($lokasi_id)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Query untuk mencari barang berdasarkan nama atau kode dengan stok di lokasi tertentu
$query = "SELECT b.id, b.kode_barang, b.nama_barang, 
          k.nama_kategori, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
          COALESCE(s.jumlah_utuh, 0) as qty_whole, COALESCE(s.jumlah_pecahan, 0) as qty_fraction
          FROM barang b
          JOIN kategori_barang k ON b.kategori_id = k.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          LEFT JOIN stock s ON b.id = s.barang_id AND s.lokasi_id = :lokasi_id
          WHERE (b.nama_barang LIKE :search OR b.kode_barang LIKE :search)
          AND b.is_aktif = 1
          AND (s.jumlah_utuh > 0 OR s.jumlah_pecahan > 0 OR s.barang_id IS NULL)
          ORDER BY b.nama_barang ASC LIMIT 20";

$stmt = $conn->prepare($query);
$searchParam = "%{$search}%";
$stmt->bindParam(':search', $searchParam);
$stmt->bindParam(':lokasi_id', $lokasi_id);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kirim respons dalam format JSON
header('Content-Type: application/json');
echo json_encode($result);
?>