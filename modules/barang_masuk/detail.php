<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses

checkRole(['kepala_toko', 'staf_gudang', 'manager_keuangan', 'bartender', 'kitchen', 'kasir', 'waiters']); // Semua roles dapat melihat detail

$db = new Database();
$conn = $db->getConnection();

// Ambil ID barang masuk
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Validasi ID
if (empty($id)) {
    showAlert("ID tidak valid!", "danger");
    header("Location: index.php");
    exit;
}

// Ambil data header barang masuk
$query = "SELECT bm.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM barang_masuk bm
          JOIN lokasi l ON bm.lokasi_id = l.id
          JOIN users u ON bm.user_id = u.id
          WHERE bm.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    showAlert("Data barang masuk tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}

$barang_masuk = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail barang masuk
$query = "SELECT bmd.*, b.kode_barang, b.nama_barang, 
          su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM barang_masuk_detail bmd
          JOIN barang b ON bmd.barang_id = b.id
          LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE bmd.barang_masuk_id = :barang_masuk_id
          ORDER BY b.nama_barang ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':barang_masuk_id', $id);
$stmt->execute();
$detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Detail Barang Masuk";
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-truck-loading me-1"></i>
                        Detail Barang Masuk
                    </div>
                    <?php if (in_array($_SESSION['role'], ['admin', 'manager', 'staf_gudang'])): ?>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">No. Transaksi</label>
                                <div class="col-sm-8">
                                    <p class="form-control-plaintext"><?php echo $barang_masuk['nomor_transaksi']; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Tanggal</label>
                                <div class="col-sm-8">
                                    <p class="form-control-plaintext">
                                        <?php echo formatDate($barang_masuk['tanggal']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Lokasi Penerima</label>
                                <div class="col-sm-8">
                                    <p class="form-control-plaintext"><?php echo $barang_masuk['nama_lokasi']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Petugas</label>
                                <div class="col-sm-8">
                                    <p class="form-control-plaintext"><?php echo $barang_masuk['nama_user']; ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Keterangan</label>
                                <div class="col-sm-8">
                                    <p class="form-control-plaintext"><?php echo $barang_masuk['keterangan'] ?: '-'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($barang_masuk['gambar_barang'] || $barang_masuk['struk']): ?>
                        <div class="row mb-3">
                            <?php if ($barang_masuk['gambar_barang']): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Gambar Barang</label>
                                    <div>
                                        <img src="/kopi/<?php echo $barang_masuk['gambar_barang']; ?>" class="img-thumbnail"
                                            style="max-width: 300px;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($barang_masuk['struk']): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Struk</label>
                                    <div>
                                        <img src="/kopi/<?php echo $barang_masuk['struk']; ?>" class="img-thumbnail"
                                            style="max-width: 300px;">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-list me-1"></i>
                        Detail Barang
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Kode Barang</th>
                                    <th width="25%">Nama Barang</th>
                                    <th width="15%">Jumlah Utuh</th>
                                    <th width="15%">Jumlah Pecahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detail_list)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data detail barang</td>
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
</div>

<?php include '../../includes/footer.php'; ?>