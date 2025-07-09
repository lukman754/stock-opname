<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya admin dan manager yang boleh mengakses halaman ini
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengelola kategori

$db = new Database();
$conn = $db->getConnection();

// Proses hapus kategori
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek apakah kategori digunakan di tabel barang
    $query_check = "SELECT COUNT(*) as total FROM barang WHERE kategori_id = :id";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();
    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        showAlert('Kategori tidak dapat dihapus karena masih digunakan di data barang', 'danger');
    } else {
        $query = "DELETE FROM kategori_barang WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('Kategori berhasil dihapus', 'success');
        } else {
            showAlert('Gagal menghapus kategori', 'danger');
        }
    }

    redirect('kategori.php');
}

// Proses tambah/edit kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nama_kategori = $_POST['nama_kategori'] ?? '';
    $lokasi_id = $_POST['lokasi_id'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    // Validasi input
    if (empty($nama_kategori) || empty($lokasi_id)) {
        showAlert('Nama kategori dan lokasi harus diisi', 'danger');
    } else {
        // Cek apakah nama kategori sudah ada
        $query_check = "SELECT id FROM kategori_barang WHERE nama_kategori = :nama_kategori AND id != :id";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bindParam(':nama_kategori', $nama_kategori);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            showAlert('Nama kategori sudah digunakan', 'danger');
        } else {
            if (empty($id)) { // Tambah kategori baru
                $query = "INSERT INTO kategori_barang (nama_kategori, lokasi_id, deskripsi) 
                          VALUES (:nama_kategori, :lokasi_id, :deskripsi)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_kategori', $nama_kategori);
                $stmt->bindParam(':lokasi_id', $lokasi_id);
                $stmt->bindParam(':deskripsi', $deskripsi);

                if ($stmt->execute()) {
                    showAlert('Kategori berhasil ditambahkan', 'success');
                } else {
                    showAlert('Gagal menambahkan kategori', 'danger');
                }
            } else { // Edit kategori
                $query = "UPDATE kategori_barang SET nama_kategori = :nama_kategori, 
                          lokasi_id = :lokasi_id, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nama_kategori', $nama_kategori);
                $stmt->bindParam(':lokasi_id', $lokasi_id);
                $stmt->bindParam(':deskripsi', $deskripsi);
                $stmt->bindParam(':id', $id);

                if ($stmt->execute()) {
                    showAlert('Kategori berhasil diperbarui', 'success');
                } else {
                    showAlert('Gagal memperbarui kategori', 'danger');
                }
            }
        }
    }

    redirect('kategori.php');
}

// Ambil data lokasi untuk dropdown
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$stmt_lokasi = $conn->prepare($query_lokasi);
$stmt_lokasi->execute();
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Ambil data kategori untuk ditampilkan
$query = "SELECT kb.*, l.nama_lokasi 
          FROM kategori_barang kb
          JOIN lokasi l ON kb.lokasi_id = l.id
          ORDER BY kb.nama_kategori ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data kategori untuk edit jika ada parameter id
$kategori_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM kategori_barang WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $kategori_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$page_title = "Master Data Kategori Barang";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Master Data Kategori Barang</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kategoriModal">
        <i class="fas fa-plus"></i> Tambah Kategori
    </button>
</div>

<?php displayAlert(); ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-tags me-1"></i>
        Daftar Kategori Barang
    </div>
    <div class="card-body">
        <?php if (empty($kategori_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data kategori yang tersedia. Silakan tambahkan kategori
                baru.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($kategori_list) ? 'datatable' : ''; ?>"
                data-default-sort="1" data-default-order="asc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Kategori</th>
                        <th>Lokasi</th>
                        <th>Deskripsi</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($kategori_list as $kategori): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $kategori['nama_kategori']; ?></td>
                            <td><?php echo $kategori['nama_lokasi']; ?></td>
                            <td><?php echo $kategori['deskripsi']; ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $kategori['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $kategori['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
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

<!-- Modal Tambah/Edit Kategori -->
<div class="modal fade" id="kategoriModal" tabindex="-1" aria-labelledby="kategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kategoriModalLabel">
                    <?php echo $kategori_edit ? 'Edit Kategori' : 'Tambah Kategori'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if ($kategori_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $kategori_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                            value="<?php echo $kategori_edit ? $kategori_edit['nama_kategori'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="lokasi_id" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id" name="lokasi_id" required>
                            <option value="">Pilih Lokasi</option>
                            <?php foreach ($lokasi_list as $lokasi): ?>
                                <option value="<?php echo $lokasi['id']; ?>" <?php echo $kategori_edit && $kategori_edit['lokasi_id'] == $lokasi['id'] ? 'selected' : ''; ?>>
                                    <?php echo $lokasi['nama_lokasi']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi"
                            rows="3"><?php echo $kategori_edit ? $kategori_edit['deskripsi'] : ''; ?></textarea>
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

<?php if ($kategori_edit): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var kategoriModal = new bootstrap.Modal(document.getElementById('kategoriModal'));
            kategoriModal.show();
        });
    </script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>