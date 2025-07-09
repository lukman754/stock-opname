<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya admin dan manager yang boleh mengakses halaman ini
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola lokasi

$db = new Database();
$conn = $db->getConnection();

// Proses hapus lokasi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek apakah lokasi digunakan di tabel lain
    $query_check = "SELECT COUNT(*) as total FROM kategori_barang WHERE lokasi_id = :id";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();
    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        showAlert('Lokasi tidak dapat dihapus karena masih digunakan di kategori barang', 'danger');
    } else {
        $query = "DELETE FROM lokasi WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('Lokasi berhasil dihapus', 'success');
        } else {
            showAlert('Gagal menghapus lokasi', 'danger');
        }
    }

    redirect('lokasi.php');
}

// Proses tambah/edit lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nama_lokasi = $_POST['nama_lokasi'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    // Validasi input
    if (empty($nama_lokasi)) {
        showAlert('Nama lokasi harus diisi', 'danger');
    } else {
        // Cek apakah nama lokasi sudah ada
        $query_check = "SELECT id FROM lokasi WHERE nama_lokasi = :nama_lokasi AND id != :id";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bindParam(':nama_lokasi', $nama_lokasi);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            showAlert('Nama lokasi sudah digunakan', 'danger');
        } else {
            if (empty($id)) { // Tambah lokasi baru
                $query = "INSERT INTO lokasi (nama_lokasi, deskripsi) VALUES (:nama_lokasi, :deskripsi)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_lokasi', $nama_lokasi);
                $stmt->bindParam(':deskripsi', $deskripsi);

                if ($stmt->execute()) {
                    showAlert('Lokasi berhasil ditambahkan', 'success');
                } else {
                    showAlert('Gagal menambahkan lokasi', 'danger');
                }
            } else { // Edit lokasi
                $query = "UPDATE lokasi SET nama_lokasi = :nama_lokasi, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_lokasi', $nama_lokasi);
                $stmt->bindParam(':deskripsi', $deskripsi);
                $stmt->bindParam(':id', $id);

                if ($stmt->execute()) {
                    showAlert('Lokasi berhasil diperbarui', 'success');
                } else {
                    showAlert('Gagal memperbarui lokasi', 'danger');
                }
            }
        }
    }

    redirect('lokasi.php');
}

// Ambil data lokasi untuk ditampilkan
$query = "SELECT * FROM lokasi ORDER BY id ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data lokasi untuk edit jika ada parameter id
$lokasi_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM lokasi WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $lokasi_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$page_title = "Master Data Lokasi";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Master Data Lokasi</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lokasiModal">
        <i class="fas fa-plus"></i> Tambah Lokasi
    </button>
</div>

<?php displayAlert(); ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-map-marker-alt me-1"></i>
        Daftar Lokasi
    </div>
    <div class="card-body">
        <?php if (empty($lokasi_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data lokasi yang tersedia. Silakan tambahkan lokasi baru.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($lokasi_list) ? 'datatable' : ''; ?>"
                data-default-sort="1" data-default-order="asc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Lokasi</th>
                        <th>Deskripsi</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($lokasi_list as $lokasi): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $lokasi['nama_lokasi']; ?></td>
                            <td><?php echo $lokasi['deskripsi']; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $lokasi['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $lokasi['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus lokasi ini?')">
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

<!-- Modal Tambah/Edit Lokasi -->
<div class="modal fade" id="lokasiModal" tabindex="-1" aria-labelledby="lokasiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lokasiModalLabel">
                    <?php echo $lokasi_edit ? 'Edit Lokasi' : 'Tambah Lokasi'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if ($lokasi_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $lokasi_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nama_lokasi" class="form-label">Nama Lokasi</label>
                        <input type="text" class="form-control" id="nama_lokasi" name="nama_lokasi"
                            value="<?php echo $lokasi_edit ? $lokasi_edit['nama_lokasi'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi"
                            rows="3"><?php echo $lokasi_edit ? $lokasi_edit['deskripsi'] : ''; ?></textarea>
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

<?php if ($lokasi_edit): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var lokasiModal = new bootstrap.Modal(document.getElementById('lokasiModal'));
            lokasiModal.show();
        });
    </script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>