<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola data barang

header('Content-Type: application/json');

if (!isset($_GET['nama_barang'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nama barang tidak ditemukan'
    ]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Ambil inisial dari nama barang (3 huruf pertama)
    $words = explode(' ', strtoupper($_GET['nama_barang']));
    $initials = '';

    // Ambil huruf pertama dari setiap kata sampai 3 huruf
    foreach ($words as $word) {
        if (strlen($initials) < 3) {
            $initials .= substr($word, 0, 1);
        }
    }

    // Jika masih kurang dari 3 huruf, tambahkan X
    while (strlen($initials) < 3) {
        $initials .= 'X';
    }

    // Ambil kode terakhir dengan inisial yang sama
    $query = "SELECT kode_barang FROM barang 
              WHERE kode_barang LIKE :prefix 
              ORDER BY kode_barang DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':prefix', $initials . '-%');
    $stmt->execute();
    $last_code = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last_code) {
        // Ambil angka dari kode terakhir
        $last_number = intval(substr($last_code['kode_barang'], 4)); // 4 = panjang "XXX-"
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }

    // Format kode baru dengan leading zeros
    $new_code = $initials . '-' . str_pad($new_number, 3, '0', STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'kode_barang' => $new_code
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}