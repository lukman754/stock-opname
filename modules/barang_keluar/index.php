<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters']); // Roles yang dapat input barang keluar

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

// Cek apakah user memiliki akses global atau terbatas pada lokasi tertentu
$user_location_id = getUserLocationId();
$is_global_user = hasGlobalAccess();

// Ambil data barang keluar berdasarkan role user
if ($is_global_user) {
    // User dengan akses global dapat melihat semua barang keluar
    $query = "SELECT bk.*, u.nama_lengkap as nama_user,
              (SELECT GROUP_CONCAT(DISTINCT l.nama_lokasi SEPARATOR ', ') 
               FROM barang_keluar_detail bkd
               JOIN lokasi l ON bkd.lokasi_id = l.id
               WHERE bkd.barang_keluar_id = bk.id) as nama_lokasi
              FROM barang_keluar bk
              JOIN users u ON bk.user_id = u.id
              WHERE bk.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir
              ORDER BY bk.tanggal DESC, bk.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':tanggal_awal', $tanggal_awal);
    $stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
    $stmt->execute();
} else {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat barang keluar di lokasi mereka
    $query = "SELECT bk.*, u.nama_lengkap as nama_user,
              (SELECT GROUP_CONCAT(DISTINCT l.nama_lokasi SEPARATOR ', ') 
               FROM barang_keluar_detail bkd
               JOIN lokasi l ON bkd.lokasi_id = l.id
               WHERE bkd.barang_keluar_id = bk.id) as nama_lokasi
              FROM barang_keluar bk
              JOIN users u ON bk.user_id = u.id
              JOIN barang_keluar_detail bkd ON bk.id = bkd.barang_keluar_id
              WHERE bk.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir
              AND bkd.lokasi_id = :lokasi_id
              GROUP BY bk.id
              ORDER BY bk.tanggal DESC, bk.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':tanggal_awal', $tanggal_awal);
    $stmt->bindParam(':tanggal_akhir', $tanggal_akhir);
    $stmt->bindParam(':lokasi_id', $user_location_id);
    $stmt->execute();
}
$barang_keluar_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Barang Keluar";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Barang Keluar</h1>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Barang Keluar
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

<!-- Tabel Barang Keluar -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-truck me-1"></i>
        Daftar Barang Keluar
    </div>
    <div class="card-body">
        <?php if (empty($barang_keluar_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data barang keluar untuk periode yang dipilih. Silakan
                tambahkan data baru atau ubah filter pencarian.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table
                class="table table-bordered table-hover <?php echo !empty($barang_keluar_list) ? 'datatable' : ''; ?>"
                data-default-sort="2" data-default-order="desc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">No. Transaksi</th>
                        <th width="15%">Tanggal</th>
                        <th width="20%">Lokasi</th>
                        <th width="15%">Petugas</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barang_keluar_list)): ?>
                        <tr>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($barang_keluar_list as $bk): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $bk['nomor_transaksi']; ?></td>
                                <td><?php echo formatDate($bk['tanggal']); ?></td>
                                <td><?php echo $bk['nama_lokasi']; ?></td>
                                <td><?php echo $bk['nama_user']; ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $bk['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="cetak.php?id=<?php echo $bk['id']; ?>" class="btn btn-sm btn-secondary"
                                        target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                                        <a href="hapus.php?id=<?php echo $bk['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
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