<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya admin dan manager yang boleh mengakses halaman ini
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola satuan

$db = new Database();
$conn = $db->getConnection();

// Proses hapus satuan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek apakah satuan digunakan di tabel barang
    $query_check = "SELECT COUNT(*) as total FROM barang WHERE satuan_utuh_id = :id OR satuan_pecahan_id = :id";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();
    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        showAlert('Satuan tidak dapat dihapus karena masih digunakan di data barang', 'danger');
    } else {
        $query = "DELETE FROM satuan WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('Satuan berhasil dihapus', 'success');
        } else {
            showAlert('Gagal menghapus satuan', 'danger');
        }
    }

    redirect('satuan.php');
}

// Proses tambah/edit satuan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nama_satuan = $_POST['nama_satuan'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    // Validasi input
    if (empty($nama_satuan)) {
        showAlert('Nama satuan harus diisi', 'danger');
    } else {
        // Cek apakah nama satuan sudah ada
        $query_check = "SELECT id FROM satuan WHERE nama_satuan = :nama_satuan AND id != :id";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bindParam(':nama_satuan', $nama_satuan);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            showAlert('Nama satuan sudah digunakan', 'danger');
        } else {
            if (empty($id)) { // Tambah satuan baru
                $query = "INSERT INTO satuan (nama_satuan, deskripsi) VALUES (:nama_satuan, :deskripsi)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_satuan', $nama_satuan);
                $stmt->bindParam(':deskripsi', $deskripsi);

                if ($stmt->execute()) {
                    showAlert('Satuan berhasil ditambahkan', 'success');
                } else {
                    showAlert('Gagal menambahkan satuan', 'danger');
                }
            } else { // Edit satuan
                $query = "UPDATE satuan SET nama_satuan = :nama_satuan, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_satuan', $nama_satuan);
                $stmt->bindParam(':deskripsi', $deskripsi);
                $stmt->bindParam(':id', $id);

                if ($stmt->execute()) {
                    showAlert('Satuan berhasil diperbarui', 'success');
                } else {
                    showAlert('Gagal memperbarui satuan', 'danger');
                }
            }
        }
    }

    redirect('satuan.php');
}

// Ambil data satuan untuk ditampilkan
$query = "SELECT * FROM satuan ORDER BY nama_satuan ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$satuan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data satuan untuk edit jika ada parameter id
$satuan_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM satuan WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $satuan_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$page_title = "Master Data Satuan";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Master Data Satuan</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#satuanModal">
        <i class="fas fa-plus"></i> Tambah Satuan
    </button>
</div>

<?php displayAlert(); ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-balance-scale me-1"></i>
        Daftar Satuan
    </div>
    <div class="card-body">
        <?php if (empty($satuan_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data satuan yang tersedia. Silakan tambahkan satuan baru.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($satuan_list) ? 'datatable' : ''; ?>"
                data-default-sort="1" data-default-order="asc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Satuan</th>
                        <th>Deskripsi</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($satuan_list as $satuan): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $satuan['nama_satuan']; ?></td>
                            <td><?php echo $satuan['deskripsi']; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $satuan['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $satuan['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus satuan ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Satuan -->
<div class="modal fade" id="satuanModal" tabindex="-1" aria-labelledby="satuanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="satuanModalLabel">
                    <?php echo $satuan_edit ? 'Edit Satuan' : 'Tambah Satuan'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if ($satuan_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $satuan_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nama_satuan" class="form-label">Nama Satuan</label>
                        <input type="text" class="form-control" id="nama_satuan" name="nama_satuan"
                            value="<?php echo $satuan_edit ? $satuan_edit['nama_satuan'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" id="deskripsi" name="deskripsi"
                            value="<?php echo $satuan_edit ? $satuan_edit['deskripsi'] : ''; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($satuan_edit): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var satuanModal = new bootstrap.Modal(document.getElementById('satuanModal'));
            satuanModal.show();
        });
    </script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>