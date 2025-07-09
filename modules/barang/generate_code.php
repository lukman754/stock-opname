<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola data barang

// Pastikan request adalah AJAX dan metode POST
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Ambil data dari request
    $nama_barang = $_POST['nama_barang'] ?? '';
    $is_edit = isset($_POST['is_edit']) ? (bool) $_POST['is_edit'] : false;
    $is_copy = isset($_POST['is_copy']) ? (bool) $_POST['is_copy'] : false;
    $current_code = $_POST['current_code'] ?? '';

    if (empty($nama_barang)) {
        echo json_encode(['success' => false, 'message' => 'Nama barang tidak boleh kosong']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Generate kode barang baru
    $kode_barang = generateItemCode($nama_barang);

    // Jika dalam mode edit dan bukan mode copy, cek apakah kode baru berbeda dengan kode lama
    if ($is_edit && !$is_copy && $kode_barang === $current_code) {
        echo json_encode(['success' => true, 'kode_barang' => $current_code]);
        exit;
    }

    // Cek apakah kode barang sudah digunakan
    $query = "SELECT id FROM barang WHERE kode_barang = :kode_barang";
    $params = [':kode_barang' => $kode_barang];

    // Jika dalam mode edit (bukan copy), abaikan kode saat ini
    if ($is_edit && !$is_copy && !empty($current_code)) {
        $query .= " AND kode_barang != :current_code";
        $params[':current_code'] = $current_code;
    }

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Jika kode sudah ada, tambahkan angka di belakang
        $counter = 1;
        do {
            $new_kode = $kode_barang . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $stmt->bindValue(':kode_barang', $new_kode);
            $stmt->execute();
            $counter++;
        } while ($stmt->rowCount() > 0);

        $kode_barang = $new_kode;
    }

    // Kirim response
    echo json_encode(['success' => true, 'kode_barang' => $kode_barang]);
} else {
    // Jika bukan AJAX request, redirect ke halaman form
    redirect('form.php');
}
