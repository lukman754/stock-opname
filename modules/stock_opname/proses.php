<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['admin', 'manager', 'bartender', 'kitchen', 'kasir', 'staf_gudang']);

$db = new Database();
$conn = $db->getConnection();

// Ambil action dari form
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Jika tidak ada action, redirect ke halaman index
if (empty($action)) {
    redirect('index.php');
}

try {
    $conn->beginTransaction();

    // Proses berdasarkan action
    switch ($action) {
        case 'create':
            // Validasi input
            if (empty($_POST['tanggal']) || empty($_POST['lokasi_id'])) {
                showAlert('Tanggal dan lokasi harus diisi', 'danger');
                redirect('form.php');
            }

            // Cek apakah ada barang yang dipilih
            if (empty($_POST['barang']) || !is_array($_POST['barang'])) {
                showAlert('Tidak ada barang yang dipilih', 'danger');
                redirect('form.php?lokasi_id=' . $_POST['lokasi_id']);
            }

            // Ambil data dari form
            $tanggal = $_POST['tanggal'];
            $lokasi_id = $_POST['lokasi_id'];
            $keterangan = $_POST['keterangan'] ?? '';
            $user_id = $_SESSION['user_id'];
            $jam_mulai = date('H:i:s');

            // Insert data ke tabel stock_opname
            $query = "INSERT INTO stock_opname (tanggal, lokasi_id, user_id, status, keterangan, jam_mulai, created_by) 
                      VALUES (:tanggal, :lokasi_id, :user_id, 'draft', :keterangan, :jam_mulai, :created_by)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':jam_mulai', $jam_mulai);
            $stmt->bindParam(':created_by', $user_id);
            $stmt->execute();

            $stock_opname_id = $conn->lastInsertId();

            // Insert detail barang
            foreach ($_POST['barang'] as $barang_id => $data) {
                // Validasi data
                $jumlah_sistem_utuh = isset($data['jumlah_sistem_utuh']) ? floatval($data['jumlah_sistem_utuh']) : 0;
                $jumlah_sistem_pecahan = isset($data['jumlah_sistem_pecahan']) ? floatval($data['jumlah_sistem_pecahan']) : 0;
                $actual_qty_whole = isset($data['actual_qty_whole']) ? floatval($data['actual_qty_whole']) : 0;
                $actual_qty_fraction = isset($data['actual_qty_fraction']) ? floatval($data['actual_qty_fraction']) : 0;
                $keterangan_item = isset($data['keterangan']) ? $data['keterangan'] : '';

                // Insert ke tabel stock_opname_details
                $query = "INSERT INTO stock_opname_details (stock_opname_id, barang_id, jumlah_sistem_utuh, jumlah_sistem_pecahan, 
                          actual_qty_whole, actual_qty_fraction, keterangan) 
                          VALUES (:stock_opname_id, :barang_id, :jumlah_sistem_utuh, :jumlah_sistem_pecahan, 
                          :actual_qty_whole, :actual_qty_fraction, :keterangan)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':stock_opname_id', $stock_opname_id);
                $stmt->bindParam(':barang_id', $barang_id);
                $stmt->bindParam(':jumlah_sistem_utuh', $jumlah_sistem_utuh);
                $stmt->bindParam(':jumlah_sistem_pecahan', $jumlah_sistem_pecahan);
                $stmt->bindParam(':actual_qty_whole', $actual_qty_whole);
                $stmt->bindParam(':actual_qty_fraction', $actual_qty_fraction);
                $stmt->bindParam(':keterangan', $keterangan_item);
                $stmt->execute();
            }

            $conn->commit();
            showAlert('Stock opname berhasil disimpan sebagai draft', 'success');
            redirect('detail.php?id=' . $stock_opname_id);
            break;

        case 'update':
            // Validasi input
            if (empty($_POST['id']) || empty($_POST['tanggal']) || empty($_POST['lokasi_id'])) {
                showAlert('ID, tanggal, dan lokasi harus diisi', 'danger');
                redirect('index.php');
            }

            // Ambil data dari form
            $id = $_POST['id'];
            $tanggal = $_POST['tanggal'];
            $lokasi_id = $_POST['lokasi_id'];
            $keterangan = $_POST['keterangan'] ?? '';
            $user_id = $_SESSION['user_id'];

            // Update data di tabel stock_opname
            $query = "UPDATE stock_opname SET tanggal = :tanggal, lokasi_id = :lokasi_id, keterangan = :keterangan, 
                      updated_by = :updated_by WHERE id = :id AND status = 'draft'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':updated_by', $user_id);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Hapus detail lama jika ada barang yang dihapus
            if (!empty($_POST['barang']) && is_array($_POST['barang'])) {
                $barang_ids = array_keys($_POST['barang']);
                $placeholders = implode(',', array_fill(0, count($barang_ids), '?'));
                
                $query = "DELETE FROM stock_opname_details WHERE stock_opname_id = ? AND barang_id NOT IN ($placeholders)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(1, $id);
                
                $i = 2;
                foreach ($barang_ids as $barang_id) {
                    $stmt->bindParam($i, $barang_id);
                    $i++;
                }
                
                $stmt->execute();

                // Update atau insert detail barang
                foreach ($_POST['barang'] as $barang_id => $data) {
                    // Validasi data
                    $jumlah_sistem_utuh = isset($data['jumlah_sistem_utuh']) ? floatval($data['jumlah_sistem_utuh']) : 0;
                    $jumlah_sistem_pecahan = isset($data['jumlah_sistem_pecahan']) ? floatval($data['jumlah_sistem_pecahan']) : 0;
                    $actual_qty_whole = isset($data['actual_qty_whole']) ? floatval($data['actual_qty_whole']) : 0;
                    $actual_qty_fraction = isset($data['actual_qty_fraction']) ? floatval($data['actual_qty_fraction']) : 0;
                    $keterangan_item = isset($data['keterangan']) ? $data['keterangan'] : '';

                    // Cek apakah detail sudah ada
                    $query = "SELECT id FROM stock_opname_details WHERE stock_opname_id = :stock_opname_id AND barang_id = :barang_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':stock_opname_id', $id);
                    $stmt->bindParam(':barang_id', $barang_id);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        // Update detail yang sudah ada
                        $query = "UPDATE stock_opname_details SET jumlah_sistem_utuh = :jumlah_sistem_utuh, 
                                  jumlah_sistem_pecahan = :jumlah_sistem_pecahan, actual_qty_whole = :actual_qty_whole, 
                                  actual_qty_fraction = :actual_qty_fraction, keterangan = :keterangan 
                                  WHERE stock_opname_id = :stock_opname_id AND barang_id = :barang_id";
                    } else {
                        // Insert detail baru
                        $query = "INSERT INTO stock_opname_details (stock_opname_id, barang_id, jumlah_sistem_utuh, 
                                  jumlah_sistem_pecahan, actual_qty_whole, actual_qty_fraction, keterangan) 
                                  VALUES (:stock_opname_id, :barang_id, :jumlah_sistem_utuh, :jumlah_sistem_pecahan, 
                                  :actual_qty_whole, :actual_qty_fraction, :keterangan)";
                    }

                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':stock_opname_id', $id);
                    $stmt->bindParam(':barang_id', $barang_id);
                    $stmt->bindParam(':jumlah_sistem_utuh', $jumlah_sistem_utuh);
                    $stmt->bindParam(':jumlah_sistem_pecahan', $jumlah_sistem_pecahan);
                    $stmt->bindParam(':actual_qty_whole', $actual_qty_whole);
                    $stmt->bindParam(':actual_qty_fraction', $actual_qty_fraction);
                    $stmt->bindParam(':keterangan', $keterangan_item);
                    $stmt->execute();
                }
            }

            $conn->commit();
            showAlert('Stock opname berhasil diperbarui', 'success');
            redirect('detail.php?id=' . $id);
            break;

        case 'finish':
            // Validasi input
            if (empty($_POST['id'])) {
                showAlert('ID stock opname tidak valid', 'danger');
                redirect('index.php');
            }

            $id = $_POST['id'];
            $user_id = $_SESSION['user_id'];
            $jam_selesai = date('H:i:s');

            // Update status stock opname menjadi selesai
            $query = "UPDATE stock_opname SET status = 'selesai', jam_selesai = :jam_selesai, updated_by = :updated_by 
                      WHERE id = :id AND status = 'draft'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':jam_selesai', $jam_selesai);
            $stmt->bindParam(':updated_by', $user_id);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Ambil detail stock opname
            $query = "SELECT sod.*, so.lokasi_id 
                      FROM stock_opname_details sod 
                      JOIN stock_opname so ON sod.stock_opname_id = so.id 
                      WHERE sod.stock_opname_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Update stok barang berdasarkan hasil stock opname
            foreach ($details as $detail) {
                $barang_id = $detail['barang_id'];
                $lokasi_id = $detail['lokasi_id'];
                $actual_qty_whole = $detail['actual_qty_whole'];
                $actual_qty_fraction = $detail['actual_qty_fraction'];
                $tanggal_update = date('Y-m-d');

                // Cek apakah stok sudah ada
                $query = "SELECT id FROM stock WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':barang_id', $barang_id);
                $stmt->bindParam(':lokasi_id', $lokasi_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    // Update stok yang sudah ada
                    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
                    $query = "UPDATE stock SET jumlah_utuh = :jumlah_utuh, jumlah_pecahan = :jumlah_pecahan, 
                              tanggal_update = :tanggal_update WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':jumlah_utuh', $actual_qty_whole);
                    $stmt->bindParam(':jumlah_pecahan', $actual_qty_fraction);
                    $stmt->bindParam(':tanggal_update', $tanggal_update);
                    $stmt->bindParam(':id', $stock['id']);
                } else {
                    // Insert stok baru
                    $query = "INSERT INTO stock (barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan, tanggal_update) 
                              VALUES (:barang_id, :lokasi_id, :jumlah_utuh, :jumlah_pecahan, :tanggal_update)";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':barang_id', $barang_id);
                    $stmt->bindParam(':lokasi_id', $lokasi_id);
                    $stmt->bindParam(':jumlah_utuh', $actual_qty_whole);
                    $stmt->bindParam(':jumlah_pecahan', $actual_qty_fraction);
                    $stmt->bindParam(':tanggal_update', $tanggal_update);
                }

                $stmt->execute();
            }

            $conn->commit();
            showAlert('Stock opname berhasil diselesaikan dan stok telah diperbarui', 'success');
            redirect('detail.php?id=' . $id);
            break;

        case 'cancel':
            // Validasi input
            if (empty($_GET['id'])) {
                showAlert('ID stock opname tidak valid', 'danger');
                redirect('index.php');
            }

            $id = $_GET['id'];
            $user_id = $_SESSION['user_id'];

            // Update status stock opname menjadi batal
            $query = "UPDATE stock_opname SET status = 'batal', updated_by = :updated_by 
                      WHERE id = :id AND status = 'draft'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':updated_by', $user_id);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $conn->commit();
            showAlert('Stock opname berhasil dibatalkan', 'success');
            redirect('index.php');
            break;

        case 'delete':
            // Validasi input
            if (empty($_GET['id'])) {
                showAlert('ID stock opname tidak valid', 'danger');
                redirect('index.php');
            }

            $id = $_GET['id'];

            // Cek status stock opname
            $query = "SELECT status FROM stock_opname WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $stock_opname = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stock_opname['status'] != 'batal') {
                showAlert('Hanya stock opname dengan status batal yang dapat dihapus', 'danger');
                redirect('index.php');
            }

            // Hapus detail stock opname
            $query = "DELETE FROM stock_opname_details WHERE stock_opname_id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Hapus stock opname
            $query = "DELETE FROM stock_opname WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $conn->commit();
            showAlert('Stock opname berhasil dihapus', 'success');
            redirect('index.php');
            break;

        default:
            showAlert('Action tidak valid', 'danger');
            redirect('index.php');
            break;
    }
} catch (Exception $e) {
    $conn->rollBack();
    showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'danger');
    
    if ($action == 'create') {
        redirect('form.php');
    } elseif ($action == 'update' || $action == 'finish') {
        redirect('form.php?id=' . $_POST['id']);
    } else {
        redirect('index.php');
    }
}
