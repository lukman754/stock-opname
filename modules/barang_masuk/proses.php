<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters']); // Roles yang dapat input barang masuk

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
                throw new Exception("Nomor transaksi, tanggal, dan lokasi harus diisi!");
            }

            if (empty($detail_barang)) {
                throw new Exception("Tidak ada barang yang ditambahkan!");
            }

            // Handle upload gambar barang
            $gambar_barang = '';
            if (isset($_FILES['gambar_barang']) && $_FILES['gambar_barang']['error'] == 0) {
                $upload_dir = '../../uploads/barang_masuk/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['gambar_barang']['name'], PATHINFO_EXTENSION));
                $new_filename = $nomor_transaksi . '_barang.' . $file_extension;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['gambar_barang']['tmp_name'], $target_file)) {
                    $gambar_barang = 'uploads/barang_masuk/' . $new_filename;
                }
            }

            // Handle upload struk
            $struk = '';
            if (isset($_FILES['struk']) && $_FILES['struk']['error'] == 0) {
                $upload_dir = '../../uploads/barang_masuk/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['struk']['name'], PATHINFO_EXTENSION));
                $new_filename = $nomor_transaksi . '_struk.' . $file_extension;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['struk']['tmp_name'], $target_file)) {
                    $struk = 'uploads/barang_masuk/' . $new_filename;
                }
            }

            // Simpan data header barang masuk
            $query = "INSERT INTO barang_masuk (nomor_transaksi, tanggal, lokasi_id, keterangan, gambar_barang, struk, user_id, created_at)
                      VALUES (:nomor_transaksi, :tanggal, :lokasi_id, :keterangan, :gambar_barang, :struk, :user_id, NOW())";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nomor_transaksi', $nomor_transaksi);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':gambar_barang', $gambar_barang);
            $stmt->bindParam(':struk', $struk);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();

            // Ambil ID barang masuk yang baru saja diinsert
            $barang_masuk_id = $conn->lastInsertId();

            // Simpan detail barang masuk
            foreach ($detail_barang as $item) {
                $query = "INSERT INTO barang_masuk_detail (barang_masuk_id, barang_id, jumlah_utuh, jumlah_pecahan)
                          VALUES (:barang_masuk_id, :barang_id, :jumlah_utuh, :jumlah_pecahan)";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':barang_masuk_id', $barang_masuk_id);
                $stmt->bindParam(':barang_id', $item['barang_id']);
                $stmt->bindParam(':jumlah_utuh', $item['jumlah_utuh']);
                $stmt->bindParam(':jumlah_pecahan', $item['jumlah_pecahan']);
                $stmt->execute();

                // Update stok barang
                updateStockTransaction($conn, $item['barang_id'], $lokasi_id, $item['jumlah_utuh'], $item['jumlah_pecahan'], 'masuk');
            }

            // Commit transaksi
            $conn->commit();

            showAlert("Transaksi barang masuk berhasil disimpan", "success");
            header("Location: detail.php?id=" . $barang_masuk_id);
            exit;

        case 'hapus':
            // Ambil ID barang masuk
            $id = $_GET['id'];

            // Validasi ID
            if (empty($id)) {
                throw new Exception("ID tidak valid!");
            }

            // Cek apakah barang masuk ada
            $query = "SELECT * FROM barang_masuk WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                throw new Exception("Data barang masuk tidak ditemukan!");
            }

            $barang_masuk = $stmt->fetch(PDO::FETCH_ASSOC);
            $lokasi_id = $barang_masuk['lokasi_id'];

            // Ambil detail barang masuk
            $query = "SELECT * FROM barang_masuk_detail WHERE barang_masuk_id = :barang_masuk_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_masuk_id', $id);
            $stmt->execute();
            $detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Kembalikan stok barang
            foreach ($detail_list as $item) {
                updateStockTransaction($conn, $item['barang_id'], $lokasi_id, $item['jumlah_utuh'], $item['jumlah_pecahan'], 'keluar');
            }

            // Hapus detail barang masuk
            $query = "DELETE FROM barang_masuk_detail WHERE barang_masuk_id = :barang_masuk_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_masuk_id', $id);
            $stmt->execute();

            // Hapus header barang masuk
            $query = "DELETE FROM barang_masuk WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Commit transaksi
            $conn->commit();

            showAlert("Transaksi barang masuk berhasil dihapus", "success");
            header("Location: index.php");
            exit;

        default:
            throw new Exception("Action tidak valid!");
    }
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollBack();

    showAlert($e->getMessage(), "danger");

    if ($action == 'simpan') {
        header("Location: form.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

/**
 * Fungsi untuk mengupdate stok barang
 * 
 * @param PDO $conn Koneksi database
 * @param int $barang_id ID barang
 * @param int $lokasi_id ID lokasi
 * @param float $jumlah_utuh Jumlah utuh
 * @param float $jumlah_pecahan Jumlah pecahan
 * @param string $jenis Jenis transaksi (masuk/keluar)
 */
function updateStockTransaction($conn, $barang_id, $lokasi_id, $jumlah_utuh, $jumlah_pecahan, $jenis = 'masuk')
{
    // Cek apakah stok sudah ada
    $query = "SELECT * FROM stock WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':barang_id', $barang_id);
    $stmt->bindParam(':lokasi_id', $lokasi_id);
    $stmt->execute();

    $multiplier = ($jenis == 'masuk') ? 1 : -1;

    if ($stmt->rowCount() > 0) {
        // Update stok yang sudah ada
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_jumlah_utuh = $stock['jumlah_utuh'] + ($jumlah_utuh * $multiplier);
        $new_jumlah_pecahan = $stock['jumlah_pecahan'] + ($jumlah_pecahan * $multiplier);

        $query = "UPDATE stock SET 
                  jumlah_utuh = :jumlah_utuh, 
                  jumlah_pecahan = :jumlah_pecahan, 
                  tanggal_update = CURDATE(),
                  updated_at = NOW() 
                  WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':jumlah_utuh', $new_jumlah_utuh);
        $stmt->bindParam(':jumlah_pecahan', $new_jumlah_pecahan);
        $stmt->bindParam(':barang_id', $barang_id);
        $stmt->bindParam(':lokasi_id', $lokasi_id);
        $stmt->execute();
    } else {
        // Insert stok baru
        $query = "INSERT INTO stock (barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan, tanggal_update, created_at)
                  VALUES (:barang_id, :lokasi_id, :jumlah_utuh, :jumlah_pecahan, CURDATE(), NOW())";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':barang_id', $barang_id);
        $stmt->bindParam(':lokasi_id', $lokasi_id);
        $stmt->bindParam(':jumlah_utuh', $jumlah_utuh);
        $stmt->bindParam(':jumlah_pecahan', $jumlah_pecahan);
        $stmt->execute();
    }
}
?>