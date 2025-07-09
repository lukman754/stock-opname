<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters']); // Roles yang dapat input barang keluar

$db = new Database();
$conn = $db->getConnection();

// Ambil action dari form
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Mulai transaksi database
$conn->beginTransaction();

try {
    switch ($action) {
        case 'simpan':
            // Ambil data dari form
            $nomor_transaksi = $_POST['nomor_transaksi'];
            $tanggal = $_POST['tanggal'];
            $lokasi_id = $_POST['lokasi_id'];
            $keterangan = $_POST['keterangan'];
            $detail_barang = json_decode($_POST['detail_barang'], true);

            // Validasi input
            if (empty($nomor_transaksi) || empty($tanggal) || empty($lokasi_id)) {
                throw new Exception("Semua field harus diisi!");
            }

            if (empty($detail_barang)) {
                throw new Exception("Pilih minimal satu barang yang akan dikeluarkan!");
            }

            // Simpan data header barang keluar
            $query = "INSERT INTO barang_keluar (nomor_transaksi, tanggal, lokasi_id, keterangan, user_id, created_at)
                      VALUES (:nomor_transaksi, :tanggal, :lokasi_id, :keterangan, :user_id, NOW())";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nomor_transaksi', $nomor_transaksi);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();

            // Ambil ID barang keluar yang baru saja diinsert
            $barang_keluar_id = $conn->lastInsertId();

            // Simpan detail barang keluar
            foreach ($detail_barang as $item) {
                $query = "INSERT INTO barang_keluar_detail (barang_keluar_id, barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan, keterangan)
                          VALUES (:barang_keluar_id, :barang_id, :lokasi_id, :jumlah_utuh, :jumlah_pecahan, :keterangan)";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':barang_keluar_id', $barang_keluar_id);
                $stmt->bindParam(':barang_id', $item['barang_id']);
                $stmt->bindParam(':lokasi_id', $lokasi_id);
                $stmt->bindParam(':jumlah_utuh', $item['jumlah_utuh']);
                $stmt->bindParam(':jumlah_pecahan', $item['jumlah_pecahan']);
                $stmt->bindParam(':keterangan', $item['keterangan']);
                $stmt->execute();

                // Update stok barang
                updateStock($conn, $item['barang_id'], $lokasi_id, $item['jumlah_utuh'], $item['jumlah_pecahan'], 'keluar', $tanggal);
            }

            // Commit transaksi
            $conn->commit();

            showAlert("Transaksi barang keluar berhasil disimpan", "success");
            header("Location: detail.php?id=" . $barang_keluar_id);
            exit;

        case 'update':
            // Ambil data dari form
            $id = $_POST['id'];
            $nomor_transaksi = $_POST['nomor_transaksi'];
            $tanggal = $_POST['tanggal'];
            $lokasi_id = $_POST['lokasi_id'];
            $keterangan = $_POST['keterangan'];
            $detail_barang = json_decode($_POST['detail_barang'], true);

            // Validasi input
            if (empty($id) || empty($nomor_transaksi) || empty($tanggal) || empty($lokasi_id)) {
                throw new Exception("Semua field harus diisi!");
            }

            if (empty($detail_barang)) {
                throw new Exception("Pilih minimal satu barang yang akan dikeluarkan!");
            }

            // Ambil data barang keluar lama
            $query = "SELECT * FROM barang_keluar WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $barang_keluar_lama = $stmt->fetch(PDO::FETCH_ASSOC);

            // Ambil detail barang keluar lama
            $query = "SELECT * FROM barang_keluar_detail WHERE barang_keluar_id = :barang_keluar_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_keluar_id', $id);
            $stmt->execute();
            $detail_lama = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Kembalikan stok barang lama
            foreach ($detail_lama as $item) {
                updateStock(
                    $conn,
                    $item['barang_id'],
                    $barang_keluar_lama['lokasi_id'],
                    $item['jumlah_utuh'],
                    $item['jumlah_pecahan'],
                    'masuk',
                    $barang_keluar_lama['tanggal']
                );
            }

            // Update data header barang keluar
            $query = "UPDATE barang_keluar 
                     SET tanggal = :tanggal, lokasi_id = :lokasi_id, keterangan = :keterangan 
                     WHERE id = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Hapus detail barang keluar lama
            $query = "DELETE FROM barang_keluar_detail WHERE barang_keluar_id = :barang_keluar_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_keluar_id', $id);
            $stmt->execute();

            // Simpan detail barang keluar baru
            foreach ($detail_barang as $item) {
                $query = "INSERT INTO barang_keluar_detail (barang_keluar_id, barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan, keterangan)
                          VALUES (:barang_keluar_id, :barang_id, :lokasi_id, :jumlah_utuh, :jumlah_pecahan, :keterangan)";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':barang_keluar_id', $id);
                $stmt->bindParam(':barang_id', $item['barang_id']);
                $stmt->bindParam(':lokasi_id', $lokasi_id);
                $stmt->bindParam(':jumlah_utuh', $item['jumlah_utuh']);
                $stmt->bindParam(':jumlah_pecahan', $item['jumlah_pecahan']);
                $stmt->bindParam(':keterangan', $item['keterangan']);
                $stmt->execute();

                // Update stok barang
                updateStock(
                    $conn,
                    $item['barang_id'],
                    $lokasi_id,
                    $item['jumlah_utuh'],
                    $item['jumlah_pecahan'],
                    'keluar',
                    $tanggal
                );
            }

            // Commit transaksi
            $conn->commit();

            showAlert("Transaksi barang keluar berhasil diperbarui", "success");
            header("Location: detail.php?id=" . $id);
            exit;

        case 'hapus':
            // Ambil ID barang keluar
            $id = $_GET['id'];

            // Validasi ID
            if (empty($id)) {
                throw new Exception("ID tidak valid!");
            }

            // Cek apakah barang keluar ada
            $query = "SELECT * FROM barang_keluar WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                throw new Exception("Data barang keluar tidak ditemukan!");
            }

            $barang_keluar = $stmt->fetch(PDO::FETCH_ASSOC);
            $lokasi_id = $barang_keluar['lokasi_id'];

            // Ambil detail barang keluar
            $query = "SELECT * FROM barang_keluar_detail WHERE barang_keluar_id = :barang_keluar_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_keluar_id', $id);
            $stmt->execute();
            $detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Kembalikan stok barang
            foreach ($detail_list as $item) {
                updateStock($conn, $item['barang_id'], $lokasi_id, $item['jumlah_utuh'], $item['jumlah_pecahan'], 'masuk', $tanggal);
            }

            // Hapus detail barang keluar
            $query = "DELETE FROM barang_keluar_detail WHERE barang_keluar_id = :barang_keluar_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_keluar_id', $id);
            $stmt->execute();

            // Hapus header barang keluar
            $query = "DELETE FROM barang_keluar WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Commit transaksi
            $conn->commit();

            showAlert("Transaksi barang keluar berhasil dihapus", "success");
            header("Location: index.php");
            exit;

        default:
            throw new Exception("Action tidak valid!");
    }
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollBack();

    showAlert($e->getMessage(), "danger");

    if ($action == 'simpan' || $action == 'update') {
        header("Location: form.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
