<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat input stock opname

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan lokasi dan status
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil data lokasi untuk filter
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$stmt_lokasi = $conn->prepare($query_lokasi);
$stmt_lokasi->execute();
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Ambil data stock opname
$query = "SELECT so.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM stock_opname so
          JOIN lokasi l ON so.lokasi_id = l.id
          JOIN users u ON so.user_id = u.id";

$where_clauses = [];

if (!empty($lokasi_id)) {
    $where_clauses[] = "so.lokasi_id = :lokasi_id";
}

if (!empty($status)) {
    $where_clauses[] = "so.status = :status";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY so.tanggal DESC, so.id DESC";

$stmt = $conn->prepare($query);

if (!empty($lokasi_id)) {
    $stmt->bindParam(':lokasi_id', $lokasi_id);
}

if (!empty($status)) {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$stock_opname_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Stock Opname";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Stock Opname</h1>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Stock Opname Baru
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
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="selesai" <?php echo $status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    <option value="batal" <?php echo $status == 'batal' ? 'selected' : ''; ?>>Batal</option>
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

<!-- Tabel Stock Opname -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-clipboard-check me-1"></i>
        Daftar Stock Opname
    </div>
    <div class="card-body">
        <?php if (empty($stock_opname_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data stock opname untuk periode yang dipilih. Silakan
                tambahkan data baru atau ubah filter pencarian.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($stock_opname_list) ? 'datatable' : ''; ?>"
                data-default-sort="1" data-default-order="desc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stock_opname_list)): ?>
                        <tr>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($stock_opname_list as $so): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo formatDate($so['tanggal']); ?></td>
                                <td><?php echo $so['nama_lokasi']; ?></td>
                                <td><?php echo $so['nama_user']; ?></td>
                                <td>
                                    <?php if ($so['status'] == 'draft'): ?>
                                        <span class="badge bg-warning">Draft</span>
                                    <?php elseif ($so['status'] == 'selesai'): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Batal</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $so['jam_mulai'] ? date('H:i', strtotime($so['jam_mulai'])) : '-'; ?></td>
                                <td><?php echo $so['jam_selesai'] ? date('H:i', strtotime($so['jam_selesai'])) : '-'; ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $so['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($so['status'] == 'draft'): ?>
                                        <a href="form.php?id=<?php echo $so['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="proses.php?action=cancel&id=<?php echo $so['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin membatalkan stock opname ini?')" title="Batalkan">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php elseif ($so['status'] == 'selesai' && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                                        <a href="edit_completed.php?id=<?php echo $so['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php elseif ($so['status'] == 'batal'): ?>
                                        <a href="proses.php?action=delete&id=<?php echo $so['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus data stock opname ini? Data yang dihapus tidak dapat dikembalikan.')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="cetak.php?id=<?php echo $so['id']; ?>" class="btn btn-sm btn-secondary"
                                        target="_blank" title="Cetak">
                                        <i class="fas fa-print"></i>
                                    </a>
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