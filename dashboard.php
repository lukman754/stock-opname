<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Cek hak akses - semua role dapat mengakses dashboard
checkRole(['kepala_toko', 'staf_gudang', 'manager_keuangan', 'bartender', 'kitchen', 'kasir', 'waiters']);

$db = new Database();
$conn = $db->getConnection();

// Get user's location
$user_location_id = $_SESSION['lokasi_id'] ?? null;
$location_filter = $user_location_id ? "WHERE s.lokasi_id = :lokasi_id" : "";

// Get total items
$query_items = "SELECT COUNT(DISTINCT b.id) as total 
                FROM barang b 
                JOIN stock s ON b.id = s.barang_id 
                " . getLocationFilter('s');
$stmt_items = $conn->prepare($query_items);
bindLocationParam($stmt_items);
$stmt_items->execute();
$total_items = $stmt_items->fetch(PDO::FETCH_ASSOC)['total'];

// Get total stock value
$query_stock = "SELECT SUM(s.jumlah_utuh) as total_value 
                FROM stock s 
                " . getLocationFilter('s');
$stmt_stock = $conn->prepare($query_stock);
bindLocationParam($stmt_stock);
$stmt_stock->execute();
$total_stock_value = $stmt_stock->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;

// Get recent transactions
$query_transactions = "SELECT * FROM (
    SELECT 
        'Barang Masuk' as jenis, 
        bm.nomor_transaksi, 
        bm.tanggal, 
        l.nama_lokasi, 
        u.nama_lengkap,
        bm.id as transaksi_id,
        'masuk' as tipe_transaksi
    FROM barang_masuk bm
    JOIN lokasi l ON bm.lokasi_id = l.id
    JOIN users u ON bm.user_id = u.id
    " . (!hasGlobalAccess() ? "WHERE bm.lokasi_id = :lokasi_id1" : "") . "
    
    UNION ALL
    
    SELECT 
        'Barang Keluar' as jenis, 
        bk.nomor_transaksi, 
        bk.tanggal, 
        l.nama_lokasi, 
        u.nama_lengkap,
        bk.id as transaksi_id,
        'keluar' as tipe_transaksi
    FROM barang_keluar bk
    JOIN lokasi l ON bk.lokasi_id = l.id
    JOIN users u ON bk.user_id = u.id
    " . (!hasGlobalAccess() ? "WHERE bk.lokasi_id = :lokasi_id2" : "") . "
) transactions
ORDER BY tanggal DESC LIMIT 5";

$stmt_transactions = $conn->prepare($query_transactions);
if (!hasGlobalAccess()) {
    $stmt_transactions->bindParam(':lokasi_id1', $user_location_id);
    $stmt_transactions->bindParam(':lokasi_id2', $user_location_id);
}
$stmt_transactions->execute();
$recent_transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

// Get low stock items
$query_low_stock = "SELECT b.kode_barang, b.nama_barang, s.jumlah_utuh, s.jumlah_pecahan, 
                    l.nama_lokasi, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
                    s.stok_minimal
                    FROM stock s
                    JOIN barang b ON s.barang_id = b.id
                    JOIN lokasi l ON s.lokasi_id = l.id
                    LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
                    LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                    WHERE b.is_aktif = 1 
                    AND (s.jumlah_utuh <= s.stok_minimal OR s.jumlah_utuh = 0)
                    " . (!hasGlobalAccess() ? "AND s.lokasi_id = :lokasi_id3" : "") . "
                    ORDER BY s.jumlah_utuh ASC, s.jumlah_pecahan ASC
                    LIMIT 5";
$stmt_low_stock = $conn->prepare($query_low_stock);
if (!hasGlobalAccess()) {
    $user_location_id = $_SESSION['lokasi_id'];
    $stmt_low_stock->bindParam(':lokasi_id3', $user_location_id);
}
$stmt_low_stock->execute();
$low_stock_items = $stmt_low_stock->fetchAll(PDO::FETCH_ASSOC);

// Get recent stock opname
$query_stock_opname = "SELECT so.*, l.nama_lokasi, u.nama_lengkap,
                       (SELECT COUNT(*) FROM stock_opname_details WHERE stock_opname_id = so.id) as total_items
                       FROM stock_opname so
                       JOIN lokasi l ON so.lokasi_id = l.id
                       JOIN users u ON so.user_id = u.id
                       " . getLocationFilter('so') . "
                       ORDER BY so.tanggal DESC, so.created_at DESC
                       LIMIT 5";
$stmt_stock_opname = $conn->prepare($query_stock_opname);
bindLocationParam($stmt_stock_opname);
$stmt_stock_opname->execute();
$recent_stock_opname = $stmt_stock_opname->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Dashboard";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card welcome-card border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div>
                            <h4 class="card-title mb-1" style="color: #fff; font-weight: 500;">
                                Selamat <?php echo getGreeting(); ?>, <?php echo $_SESSION['nama_lengkap']; ?>!
                            </h4>
                            <p class="card-text mb-0" style="color: rgba(255,255,255,0.8);">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?php echo date('l, d F Y'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 quick-stats-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="quick-stats-icon me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <h6 class="quick-stats-label mb-1">Total Barang</h6>
                            <div class="quick-stats-value"><?php echo number_format($total_items); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 quick-stats-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="quick-stats-icon me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <h6 class="quick-stats-label mb-1">Total Nilai Stok</h6>
                            <div class="quick-stats-value"><?php echo formatRupiah($total_stock_value); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 quick-stats-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="quick-stats-icon me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h6 class="quick-stats-label mb-1">Stok Menipis</h6>
                            <div class="quick-stats-value"><?php echo count($low_stock_items); ?> Item</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 quick-stats-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="quick-stats-icon me-3">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <h6 class="quick-stats-label mb-1">Stock Opname Terakhir</h6>
                            <div class="quick-stats-value"><?php echo $recent_stock_opname[0]['tanggal'] ?? '-'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-exchange-alt"></i>
                    <span class="card-title">Transaksi Terakhir</span>
                    <a href="modules/barang_masuk/index.php" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>No. Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Lokasi</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $transaction['jenis'] == 'Barang Masuk' ? 'success' : 'primary'; ?>">
                                                <?php echo $transaction['jenis']; ?>
                                            </span>
                                        </td>
                                        <td class="fw-medium"><?php echo $transaction['nomor_transaksi']; ?></td>
                                        <td><?php echo formatDate($transaction['tanggal']); ?></td>
                                        <td><?php echo $transaction['nama_lokasi']; ?></td>
                                        <td class="text-end">
                                            <a href="<?php echo $transaction['tipe_transaksi'] == 'masuk' ? 'modules/barang_masuk/detail.php?id=' : 'modules/barang_keluar/detail.php?id='; ?><?php echo $transaction['transaksi_id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="card-title">Stok Menipis</span>
                    <a href="modules/stock/index.php" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Lokasi</th>
                                    <th class="text-end">Stok</th>
                                    <th class="text-end">Minimal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($low_stock_items)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada stok yang menipis</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($low_stock_items as $item): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo $item['kode_barang']; ?></td>
                                            <td><?php echo $item['nama_barang']; ?></td>
                                            <td><?php echo $item['nama_lokasi']; ?></td>
                                            <td class="text-end">
                                                <?php if ($item['jumlah_utuh'] > 0): ?>
                                                    <span
                                                        class="fw-medium"><?php echo $item['jumlah_utuh'] . ' ' . $item['satuan_utuh']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger">0 <?php echo $item['satuan_utuh']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($item['jumlah_pecahan'] > 0): ?>
                                                    <?php echo ($item['jumlah_utuh'] > 0 ? ' + ' : '') . $item['jumlah_pecahan'] . ' ' . $item['satuan_pecahan']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo $item['stok_minimal'] . ' ' . $item['satuan_utuh']; ?>
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

    <div class="row">
        <!-- Recent Stock Opname -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-check"></i>
                    <span class="card-title">Stock Opname Terakhir</span>
                    <a href="modules/stock_opname/index.php" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Stock Opname</th>
                                    <th>Tanggal</th>
                                    <th>Lokasi</th>
                                    <th>Petugas</th>
                                    <th>Jumlah Item</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_stock_opname as $opname): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo $opname['id']; ?></td>
                                        <td><?php echo formatDate($opname['tanggal']); ?></td>
                                        <td><?php echo $opname['nama_lokasi']; ?></td>
                                        <td><?php echo $opname['nama_lengkap']; ?></td>
                                        <td class="text-center fw-medium"><?php echo $opname['total_items']; ?> item</td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $opname['status'] == 'selesai' ? 'success' :
                                                ($opname['status'] == 'draft' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($opname['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-medium"><?php echo $opname['total_items']; ?> item</td>
                                        <td>
                                            <span class="fw-medium"><?php echo $opname['jam_mulai']; ?></span> -
                                            <span class="fw-medium"><?php echo $opname['jam_selesai'] ?: '-'; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to get greeting based on time of day
function getGreeting()
{
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return 'Pagi';
    } elseif ($hour >= 12 && $hour < 15) {
        return 'Siang';
    } elseif ($hour >= 15 && $hour < 19) {
        return 'Sore';
    } else {
        return 'Malam';
    }
}

include 'includes/footer.php';
?>