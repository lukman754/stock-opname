<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'staf_gudang', 'manager_keuangan', 'bartender', 'kitchen', 'kasir', 'waiters']); // Semua roles dapat melihat data stok

$db = new Database();
$conn = $db->getConnection();

// Cek apakah user memiliki akses global atau terbatas pada lokasi tertentu
$user_location_id = getUserLocationId();
$is_global_user = hasGlobalAccess();

// Filter berdasarkan lokasi
if (!$is_global_user) {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat lokasi mereka
    $lokasi_id = $user_location_id;
} else {
    // User dengan akses global dapat melihat semua lokasi atau memfilter berdasarkan lokasi
    $lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';
}

// Ambil data lokasi untuk filter berdasarkan role user
if ($is_global_user) {
    // User dengan akses global dapat melihat semua lokasi
    $query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
    $stmt_lokasi = $conn->prepare($query_lokasi);
    $stmt_lokasi->execute();
    $lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);
} else {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat lokasi mereka
    $query_lokasi = "SELECT * FROM lokasi WHERE id = :lokasi_id ORDER BY nama_lokasi ASC";
    $stmt_lokasi = $conn->prepare($query_lokasi);
    $stmt_lokasi->bindParam(':lokasi_id', $user_location_id);
    $stmt_lokasi->execute();
    $lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data stok barang
$query = "SELECT s.*, b.kode_barang, b.nama_barang, k.nama_kategori, 
          l.nama_lokasi, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM stock s
          JOIN barang b ON s.barang_id = b.id
          JOIN kategori_barang k ON b.kategori_id = k.id
          JOIN lokasi l ON s.lokasi_id = l.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE b.is_aktif = 1";

if (!empty($lokasi_id)) {
    $query .= " AND s.lokasi_id = :lokasi_id";
}

$query .= " ORDER BY l.nama_lokasi, k.nama_kategori, b.nama_barang ASC";

$stmt = $conn->prepare($query);

if (!empty($lokasi_id)) {
    $stmt->bindParam(':lokasi_id', $lokasi_id);
}

$stmt->execute();
$stock_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Stok Barang";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Stok Barang</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="cetak.php<?php echo !empty($lokasi_id) ? '?lokasi_id=' . $lokasi_id : ''; ?>"
                class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="fas fa-print"></i> Cetak
            </a>
        </div>
    </div>
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
                <label for="lokasi_id" class="form-label">Lokasi</label>
                <select class="form-select" id="lokasi_id" name="lokasi_id">
                    <option value="">Semua Lokasi</option>
                    <?php foreach ($lokasi_list as $lokasi): ?>
                        <option value="<?php echo $lokasi['id']; ?>" <?php echo $lokasi_id == $lokasi['id'] ? 'selected' : ''; ?>>
                            <?php echo $lokasi['nama_lokasi']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

<!-- Tabel Stok Barang -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-cubes me-1"></i>
        Daftar Stok Barang
    </div>
    <div class="card-body">
        <?php if (empty($stock_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data stok untuk filter yang dipilih. Silakan ubah filter
                pencarian.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($stock_list) ? 'datatable' : ''; ?>"
                data-default-sort="2" data-default-order="asc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Stok Minimal</th>
                        <th>Status</th>
                        <th>Stok Utuh</th>
                        <th>Stok Pecahan</th>
                        <th>Tanggal Update</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_list)): ?>
                        <tr>
                            <td colspan="11" class="text-center">Tidak ada data stok untuk filter yang dipilih. Silakan ubah
                                filter pencarian.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($stock_list as $stock):
                            $status_class = ($stock['jumlah_utuh'] <= $stock['stok_minimal']) ? 'danger' : 'success';
                            $status_text = ($stock['jumlah_utuh'] <= $stock['stok_minimal']) ? 'Stok Rendah' : 'Stok Aman';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $stock['kode_barang']; ?></td>
                                <td><?php echo $stock['nama_barang']; ?></td>
                                <td><?php echo $stock['nama_kategori']; ?></td>
                                <td><?php echo $stock['nama_lokasi']; ?></td>
                                <td><?php echo isset($stock['stok_minimal']) ? $stock['stok_minimal'] . ' ' . $stock['satuan_utuh'] : '-'; ?>
                                </td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo $stock['jumlah_utuh'] . ' ' . $stock['satuan_utuh']; ?></td>
                                <td>
                                    <?php if ($stock['jumlah_pecahan'] > 0 && $stock['satuan_pecahan']): ?>
                                        <?php echo $stock['jumlah_pecahan'] . ' ' . $stock['satuan_pecahan']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($stock['tanggal_update']); ?></td>
                                <td>
                                    <?php if (hasRole(['staf_gudang', 'admin', 'manager'])): ?>
                                        <a href="edit_stok_minimal.php?id=<?php echo $stock['id']; ?>"
                                            class="btn btn-sm btn-warning" title="Edit Stok Minimal">
                                            <i class="fas fa-edit"></i>
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