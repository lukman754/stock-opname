<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola data barang

$db = new Database();
$conn = $db->getConnection();

// Proses toggle status barang
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Ambil status barang saat ini
    $query_check = "SELECT is_aktif FROM barang WHERE id = :id";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        $barang = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $new_status = $barang['is_aktif'] ? 0 : 1;

        $query = "UPDATE barang SET is_aktif = :is_aktif WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':is_aktif', $new_status);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $status_text = $new_status ? 'diaktifkan' : 'dinonaktifkan';
            showAlert("Barang berhasil $status_text", 'success');
        } else {
            showAlert('Gagal mengubah status barang', 'danger');
        }
    } else {
        showAlert('Data barang tidak ditemukan', 'danger');
    }

    redirect('index.php');
}

// Proses hapus barang
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek apakah barang masih digunakan di tabel lain
    $check_queries = [
        "SELECT COUNT(*) as count FROM barang_masuk_detail WHERE barang_id = :id",
        "SELECT COUNT(*) as count FROM barang_keluar_detail WHERE barang_id = :id"
    ];

    $is_used = false;
    foreach ($check_queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $is_used = true;
            break;
        }
    }

    if ($is_used) {
        showAlert('Barang tidak dapat dihapus karena masih digunakan dalam transaksi barang masuk atau barang keluar', 'danger');
    } else {
        // Hapus barang
        $query = "DELETE FROM barang WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('Barang berhasil dihapus', 'success');
        } else {
            showAlert('Gagal menghapus barang', 'danger');
        }
    }

    redirect('index.php');
}

// Proses insert/update barang
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Debug: Log POST data
    error_log("POST data: " . print_r($_POST, true));

    $nama_barang = $_POST['nama_barang'] ?? '';
    $kode_barang = $_POST['kode_barang'] ?? '';
    $kategori_id = $_POST['kategori_id'] ?? '';
    $satuan_utuh_id = $_POST['satuan_utuh_id'] ?? '';
    $satuan_pecahan_id = $_POST['satuan_pecahan_id'] ?? '';
    $lokasi_ids = $_POST['lokasi_id'] ?? [];
    $is_aktif = isset($_POST['is_aktif']) ? 1 : 0;

    // Validasi input
    if (
        empty($nama_barang) || empty($kode_barang) || empty($kategori_id) ||
        empty($satuan_utuh_id) || empty($lokasi_ids)
    ) {
        showAlert('Nama barang, kode barang, kategori, satuan utuh, dan lokasi harus diisi', 'danger');
        redirect('form.php' . ($action == 'update' && isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    }

    // Cek apakah kode barang sudah ada
    $id = $_POST['id'] ?? '';
    $query_check = "SELECT id FROM barang WHERE kode_barang = :kode_barang AND id != :id";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':kode_barang', $kode_barang);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        showAlert('Kode barang sudah digunakan', 'danger');
        redirect('form.php' . ($action == 'update' && isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    }

    // Jika satuan pecahan kosong, set ke NULL
    if (empty($satuan_pecahan_id)) {
        $satuan_pecahan_id = null;
    }

    try {
        $conn->beginTransaction();

        if ($action == 'create') {  // Ubah dari 'insert' ke 'create'
            // Debug: Log sebelum insert
            error_log("Attempting to insert new item: " . $kode_barang);

            // Tambah barang baru
            $query = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, satuan_utuh_id, 
                     satuan_pecahan_id, is_aktif) 
                 VALUES (:kode_barang, :nama_barang, :kategori_id, :satuan_utuh_id, 
                     :satuan_pecahan_id, :is_aktif)";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kode_barang', $kode_barang);
            $stmt->bindParam(':nama_barang', $nama_barang);
            $stmt->bindParam(':kategori_id', $kategori_id);
            $stmt->bindParam(':satuan_utuh_id', $satuan_utuh_id);
            $stmt->bindParam(':satuan_pecahan_id', $satuan_pecahan_id);
            $stmt->bindParam(':is_aktif', $is_aktif);

            if (!$stmt->execute()) {
                throw new Exception('Gagal menambahkan barang: ' . implode(" ", $stmt->errorInfo()));
            }

            $barang_id = $conn->lastInsertId();
            error_log("New item inserted with ID: " . $barang_id);

            // Tambah data stock untuk setiap lokasi
            $query_stock = "INSERT INTO stock (barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan) 
                           VALUES (:barang_id, :lokasi_id, 0, 0)";
            $stmt_stock = $conn->prepare($query_stock);

            foreach ($lokasi_ids as $lokasi_id) {
                $stmt_stock->bindParam(':barang_id', $barang_id);
                $stmt_stock->bindParam(':lokasi_id', $lokasi_id);
                if (!$stmt_stock->execute()) {
                    throw new Exception('Gagal menambahkan data stock: ' . implode(" ", $stmt_stock->errorInfo()));
                }
            }

            $conn->commit();
            showAlert('Barang berhasil ditambahkan', 'success');
            redirect('index.php');

        } elseif ($action == 'update' && isset($_POST['id'])) {
            // Update barang
            $id = $_POST['id'];

            $query = "UPDATE barang SET 
                     nama_barang = :nama_barang,
                     kategori_id = :kategori_id,
                     satuan_utuh_id = :satuan_utuh_id,
                     satuan_pecahan_id = :satuan_pecahan_id,
                     is_aktif = :is_aktif
                 WHERE id = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nama_barang', $nama_barang);
            $stmt->bindParam(':kategori_id', $kategori_id);
            $stmt->bindParam(':satuan_utuh_id', $satuan_utuh_id);
            $stmt->bindParam(':satuan_pecahan_id', $satuan_pecahan_id);
            $stmt->bindParam(':is_aktif', $is_aktif);
            $stmt->bindParam(':id', $id);

            if (!$stmt->execute()) {
                throw new Exception('Gagal memperbarui barang');
            }

            // Update data stock
            // 1. Hapus lokasi yang tidak dipilih lagi
            $placeholders = array_fill(0, count($lokasi_ids), ':lokasi_id_' . implode(',:lokasi_id_', array_keys($lokasi_ids)));
            $query_delete = "DELETE FROM stock WHERE barang_id = :barang_id AND lokasi_id NOT IN (" . implode(',', $placeholders) . ")";
            $stmt_delete = $conn->prepare($query_delete);
            $stmt_delete->bindParam(':barang_id', $id);
            foreach ($lokasi_ids as $key => $lokasi_id) {
                $stmt_delete->bindValue(':lokasi_id_' . $key, $lokasi_id);
            }
            if (!$stmt_delete->execute()) {
                throw new Exception('Gagal menghapus data stock lama');
            }

            // 2. Tambah lokasi baru yang belum ada
            $query_check_stock = "SELECT lokasi_id FROM stock WHERE barang_id = :barang_id";
            $stmt_check_stock = $conn->prepare($query_check_stock);
            $stmt_check_stock->bindParam(':barang_id', $id);
            $stmt_check_stock->execute();
            $existing_lokasi_ids = $stmt_check_stock->fetchAll(PDO::FETCH_COLUMN);

            $query_insert_stock = "INSERT INTO stock (barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan) 
                                 VALUES (:barang_id, :lokasi_id, 0, 0)";
            $stmt_insert_stock = $conn->prepare($query_insert_stock);

            foreach ($lokasi_ids as $lokasi_id) {
                if (!in_array($lokasi_id, $existing_lokasi_ids)) {
                    $stmt_insert_stock->bindParam(':barang_id', $id);
                    $stmt_insert_stock->bindParam(':lokasi_id', $lokasi_id);
                    if (!$stmt_insert_stock->execute()) {
                        throw new Exception('Gagal menambahkan data stock baru');
                    }
                }
            }

            $conn->commit();
            showAlert('Barang berhasil diperbarui', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Action tidak valid: ' . $action);
        }
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error in proses.php: " . $e->getMessage());
        showAlert('Error: ' . $e->getMessage(), 'danger');
        redirect('form.php' . ($action == 'update' && isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    }
}

// Jika tidak ada action yang valid, redirect ke halaman index
redirect('index.php');
