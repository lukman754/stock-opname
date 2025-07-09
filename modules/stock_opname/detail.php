<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['admin', 'manager', 'bartender', 'kitchen', 'kasir', 'staf_gudang']);

if (!isset($_GET['id'])) {
    showAlert('ID stock opname tidak valid', 'danger');
    redirect('index.php');
}

$id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();

// Ambil data stock opname
$query = "SELECT so.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM stock_opname so
          JOIN lokasi l ON so.lokasi_id = l.id
          JOIN users u ON so.user_id = u.id
          WHERE so.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    showAlert('Data stock opname tidak ditemukan', 'danger');
    redirect('index.php');
}

$stock_opname = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail stock opname
$query_detail = "SELECT sod.*, b.kode_barang, b.nama_barang, k.nama_kategori,
                su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
                so.lokasi_id, l.nama_lokasi
                FROM stock_opname_details sod
                JOIN stock_opname so ON sod.stock_opname_id = so.id
                JOIN barang b ON sod.barang_id = b.id
                JOIN kategori_barang k ON b.kategori_id = k.id
                JOIN satuan su ON b.satuan_utuh_id = su.id
                LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                JOIN lokasi l ON so.lokasi_id = l.id
                WHERE sod.stock_opname_id = :stock_opname_id
                ORDER BY k.nama_kategori, b.nama_barang ASC";
$stmt_detail = $conn->prepare($query_detail);
$stmt_detail->bindParam(':stock_opname_id', $id);
$stmt_detail->execute();
$details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

// Hitung total item dan selisih
$total_items = count($details);
$total_selisih_plus = 0;
$total_selisih_minus = 0;

foreach ($details as $detail) {
    if ($detail['selisih_utuh'] > 0) {
        $total_selisih_plus++;
    } elseif ($detail['selisih_utuh'] < 0) {
        $total_selisih_minus++;
    }

    if ($detail['selisih_pecahan'] > 0) {
        $total_selisih_plus++;
    } elseif ($detail['selisih_pecahan'] < 0) {
        $total_selisih_minus++;
    }
}

$page_title = "Detail Stock Opname";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Stock Opname</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (hasRole(['staf_gudang', 'admin', 'manager']) && $stock_opname['status'] == 'draft'): ?>
            <a href="form.php?id=<?php echo $stock_opname['id']; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> Edit
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php displayAlert(); ?>

<!-- Informasi Stock Opname -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-1"></i>
                Informasi Stock Opname
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="30%">Tanggal</td>
                        <td width="5%">:</td>
                        <td><?php echo formatDate($stock_opname['tanggal']); ?></td>
                    </tr>
                    <tr>
                        <td>Lokasi</td>
                        <td>:</td>
                        <td><?php echo $stock_opname['nama_lokasi']; ?></td>
                    </tr>
                    <tr>
                        <td>Petugas</td>
                        <td>:</td>
                        <td><?php echo $stock_opname['nama_user']; ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>:</td>
                        <td>
                            <?php if ($stock_opname['status'] == 'draft'): ?>
                                <span class="badge bg-warning">Draft</span>
                            <?php elseif ($stock_opname['status'] == 'selesai'): ?>
                                <span class="badge bg-success">Selesai</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Batal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Jam Mulai</td>
                        <td>:</td>
                        <td><?php echo $stock_opname['jam_mulai'] ? date('H:i', strtotime($stock_opname['jam_mulai'])) : '-'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Jam Selesai</td>
                        <td>:</td>
                        <td><?php echo $stock_opname['jam_selesai'] ? date('H:i', strtotime($stock_opname['jam_selesai'])) : '-'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Keterangan</td>
                        <td>:</td>
                        <td><?php echo $stock_opname['keterangan'] ?: '-'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Ringkasan
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1"><?php echo $total_items; ?></div>
                        <div>Total Item</div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1 text-success"><?php echo $total_selisih_plus; ?></div>
                        <div>Selisih Lebih</div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="h1 text-danger"><?php echo $total_selisih_minus; ?></div>
                        <div>Selisih Kurang</div>
                    </div>
                </div>

                <div class="progress mt-3" style="height: 25px;">
                    <?php
                    $percent_plus = $total_items > 0 ? ($total_selisih_plus / $total_items) * 100 : 0;
                    $percent_minus = $total_items > 0 ? ($total_selisih_minus / $total_items) * 100 : 0;
                    $percent_equal = 100 - $percent_plus - $percent_minus;
                    ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent_plus; ?>%"
                        aria-valuenow="<?php echo $percent_plus; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo round($percent_plus); ?>%
                    </div>
                    <div class="progress-bar bg-secondary" role="progressbar"
                        style="width: <?php echo $percent_equal; ?>%" aria-valuenow="<?php echo $percent_equal; ?>"
                        aria-valuemin="0" aria-valuemax="100">
                        <?php echo round($percent_equal); ?>%
                    </div>
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $percent_minus; ?>%"
                        aria-valuenow="<?php echo $percent_minus; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo round($percent_minus); ?>%
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-success">Lebih</small>
                    <small class="text-secondary">Sesuai</small>
                    <small class="text-danger">Kurang</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Stock Opname -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list me-1"></i>
        Detail Item Stock Opname
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="stock-opname-detail" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Stok Sistem (Utuh)</th>
                        <th>Stok Sistem (Pecahan)</th>
                        <th>Stok Fisik (Utuh)</th>
                        <th>Stok Fisik (Pecahan)</th>
                        <th>Selisih (Utuh)</th>
                        <th>Selisih (Pecahan)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($details)): ?>
                        <tr>
                            <td colspan="12" class="text-center">Tidak ada data detail stock opname</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($details as $detail): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $detail['kode_barang']; ?></td>
                                <td><?php echo $detail['nama_barang']; ?></td>
                                <td><?php echo $detail['nama_kategori']; ?></td>
                                <td><?php echo $detail['nama_lokasi']; ?></td>
                                <td><?php echo $detail['jumlah_sistem_utuh'] . ' ' . $detail['satuan_utuh']; ?></td>
                                <td>
                                    <?php if ($detail['jumlah_sistem_pecahan'] > 0 && $detail['satuan_pecahan']): ?>
                                        <?php echo $detail['jumlah_sistem_pecahan'] . ' ' . $detail['satuan_pecahan']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $detail['actual_qty_whole'] . ' ' . $detail['satuan_utuh']; ?></td>
                                <td>
                                    <?php if ($detail['satuan_pecahan']): ?>
                                        <?php echo $detail['actual_qty_fraction'] . ' ' . $detail['satuan_pecahan']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td
                                    class="<?php echo $detail['selisih_utuh'] < 0 ? 'text-danger' : ($detail['selisih_utuh'] > 0 ? 'text-success' : ''); ?>">
                                    <?php echo $detail['selisih_utuh'] . ' ' . $detail['satuan_utuh']; ?>
                                </td>
                                <td
                                    class="<?php echo $detail['selisih_pecahan'] < 0 ? 'text-danger' : ($detail['selisih_pecahan'] > 0 ? 'text-success' : ''); ?>">
                                    <?php if ($detail['satuan_pecahan']): ?>
                                        <?php echo $detail['selisih_pecahan'] . ' ' . $detail['satuan_pecahan']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
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

<?php include '../../includes/footer.php'; ?>