<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters', 'manager_keuangan']); // Roles yang dapat input barang masuk

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

// Cek apakah user memiliki akses global atau terbatas pada lokasi tertentu
$user_location_id = getUserLocationId();
$is_global_user = hasGlobalAccess();

// Ambil data barang masuk berdasarkan role user
if ($is_global_user) {
    // User dengan akses global dapat melihat semua barang masuk
    $query = "SELECT bm.*, u.nama_lengkap as nama_user, l.nama_lokasi
              FROM barang_masuk bm
              JOIN users u ON bm.user_id = u.id
              JOIN lokasi l ON bm.lokasi_id = l.id
              WHERE bm.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir
              ORDER BY bm.tanggal DESC, bm.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':tanggal_awal', $tanggal_awal);
    $stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
    $stmt->execute();
} else {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat barang masuk di lokasi mereka
    $query = "SELECT bm.*, u.nama_lengkap as nama_user, l.nama_lokasi
              FROM barang_masuk bm
              JOIN users u ON bm.user_id = u.id
              JOIN lokasi l ON bm.lokasi_id = l.id
              WHERE bm.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir
              AND bm.lokasi_id = :lokasi_id
              ORDER BY bm.tanggal DESC, bm.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':tanggal_awal', $tanggal_awal);
    $stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
    $stmt->bindParam(':lokasi_id', $user_location_id);
    $stmt->execute();
}
$barang_masuk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Barang Masuk";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Barang Masuk</h1>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Barang Masuk
    </a>
</div>

<?php displayAlert(); ?>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter me-1"></i>
        Filter Data
    </div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal"
                    value="<?php echo $tanggal_awal; ?>">
            </div>
            <div class="col-md-4">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir"
                    value="<?php echo $tanggal_akhir; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-sync"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Barang Masuk -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-truck-loading me-1"></i>
        Daftar Barang Masuk
    </div>
    <div class="card-body">
        <?php if (empty($barang_masuk_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data barang masuk untuk periode yang dipilih. Silakan
                tambahkan data baru atau ubah filter pencarian.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">No. Transaksi</th>
                        <th width="15%">Tanggal</th>
                        <th width="20%">Lokasi Penerima</th>
                        <th width="20%">Petugas</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barang_masuk_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data barang masuk</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($barang_masuk_list as $barang_masuk): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $barang_masuk['nomor_transaksi']; ?></td>
                                <td><?php echo formatDate($barang_masuk['tanggal']); ?></td>
                                <td><?php echo $barang_masuk['nama_lokasi']; ?></td>
                                <td><?php echo $barang_masuk['nama_user']; ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $barang_masuk['id']; ?>" class="btn btn-info btn-sm"
                                        title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasRole(['admin', 'manager'])): ?>
                                        <a href="cetak.php?id=<?php echo $barang_masuk['id']; ?>" class="btn btn-primary btn-sm"
                                            title="Cetak" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="hapus.php?id=<?php echo $barang_masuk['id']; ?>" class="btn btn-danger btn-sm"
                                            title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>