<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya staff gudang yang boleh mengakses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengatur stok minimal

$db = new Database();
$conn = $db->getConnection();

// Ambil data stok
$id = $_GET['id'] ?? '';
if (empty($id)) {
    showAlert('ID stok tidak valid', 'danger');
    redirect('index.php');
}

$query = "SELECT s.*, b.kode_barang, b.nama_barang, k.nama_kategori, 
          l.nama_lokasi, su.nama_satuan as satuan_utuh
          FROM stock s
          JOIN barang b ON s.barang_id = b.id
          JOIN kategori_barang k ON b.kategori_id = k.id
          JOIN lokasi l ON s.lokasi_id = l.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          WHERE s.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    showAlert('Data stok tidak ditemukan', 'danger');
    redirect('index.php');
}

$stock = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses update stok minimal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stok_minimal = $_POST['stok_minimal'] ?? 0;

    if ($stok_minimal < 0) {
        showAlert('Stok minimal tidak boleh negatif', 'danger');
    } else {
        $query = "UPDATE stock SET stok_minimal = :stok_minimal WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':stok_minimal', $stok_minimal);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('Stok minimal berhasil diperbarui', 'success');
            redirect('index.php');
        } else {
            showAlert('Gagal memperbarui stok minimal', 'danger');
        }
    }
}

$page_title = "Edit Stok Minimal";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Stok Minimal</h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<?php displayAlert(); ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="row mb-3">
                <label class="col-sm-3 form-label">Kode Barang</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" value="<?php echo $stock['kode_barang']; ?>" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 form-label">Nama Barang</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" value="<?php echo $stock['nama_barang']; ?>" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 form-label">Kategori</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" value="<?php echo $stock['nama_kategori']; ?>" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 form-label">Lokasi</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" value="<?php echo $stock['nama_lokasi']; ?>" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 form-label">Stok Saat Ini</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control"
                        value="<?php echo $stock['jumlah_utuh'] . ' ' . $stock['satuan_utuh']; ?>" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label for="stok_minimal" class="col-sm-3 form-label">Stok Minimal</label>
                <div class="col-sm-9">
                    <div class="input-group">
                        <input type="number" class="form-control" id="stok_minimal" name="stok_minimal"
                            value="<?php echo $stock['stok_minimal']; ?>" min="0" step="0.01" required>
                        <span class="input-group-text"><?php echo $stock['satuan_utuh']; ?></span>
                    </div>
                    <div class="form-text">Jumlah minimal stok yang harus tersedia di lokasi ini</div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>