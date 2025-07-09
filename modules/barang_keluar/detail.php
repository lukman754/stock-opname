<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'staf_gudang', 'manager_keuangan', 'bartender', 'kitchen', 'kasir', 'waiters']); // Semua roles dapat melihat detail

$db = new Database();
$conn = $db->getConnection();

// Ambil ID barang keluar
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Validasi ID
if (empty($id)) {
    showAlert("ID tidak valid!", "danger");
    header("Location: index.php");
    exit;
}

// Ambil data header barang keluar
$query = "SELECT bk.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM barang_keluar bk
          JOIN lokasi l ON bk.lokasi_id = l.id
          JOIN users u ON bk.user_id = u.id
          WHERE bk.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    showAlert("Data barang keluar tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}

$barang_keluar = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail barang keluar
$query = "SELECT bkd.*, b.kode_barang, b.nama_barang, 
          b.satuan_utuh_id, b.satuan_pecahan_id,
          su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM barang_keluar_detail bkd
          JOIN barang b ON bkd.barang_id = b.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE bkd.barang_keluar_id = :barang_keluar_id
          ORDER BY b.nama_barang ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':barang_keluar_id', $id);
$stmt->execute();
$detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Detail Barang Keluar";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Barang Keluar</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            <a href="cetak.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="fas fa-print"></i> Cetak
            </a>
        </div>
    </div>
</div>

<?php displayAlert(); ?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Informasi Transaksi
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">No. Transaksi</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo $barang_keluar['nomor_transaksi']; ?></p>
                    </div>
                    <label class="col-sm-2 col-form-label">Tanggal</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo formatDate($barang_keluar['tanggal']); ?></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Lokasi Asal</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo $barang_keluar['nama_lokasi']; ?></p>
                    </div>
                    <label class="col-sm-2 col-form-label">Petugas</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo $barang_keluar['nama_user']; ?></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Waktu Input</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo formatDateTime($barang_keluar['created_at']); ?>
                        </p>
                    </div>
                    <label class="col-sm-2 col-form-label">Keterangan</label>
                    <div class="col-sm-4">
                        <p class="form-control-plaintext"><?php echo $barang_keluar['keterangan'] ?: '-'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list me-1"></i>
                Detail Barang
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Kode Barang</th>
                                <th width="30%">Nama Barang</th>
                                <th width="15%">Jumlah Utuh</th>
                                <th width="15%">Jumlah Pecahan</th>
                                <th width="20%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detail_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data detail barang</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($detail_list as $detail): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $detail['kode_barang']; ?></td>
                                        <td><?php echo $detail['nama_barang']; ?></td>
                                        <td><?php echo $detail['jumlah_utuh'] . ' ' . $detail['satuan_utuh']; ?></td>
                                        <td><?php echo $detail['jumlah_pecahan'] > 0 && $detail['satuan_pecahan'] ? $detail['jumlah_pecahan'] . ' ' . $detail['satuan_pecahan'] : '-'; ?>
                                        </td>
                                        <td><?php echo $detail['keterangan'] ?: '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>