<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengedit data barang masuk

$db = new Database();
$conn = $db->getConnection();

// Cek apakah ada ID yang dikirim
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID transaksi tidak ditemukan');
    redirect('index.php');
}

$id = $_GET['id'];

// Ambil data transaksi
$query = "SELECT bm.*, l.nama_lokasi, u.nama_lengkap as nama_petugas
          FROM barang_masuk bm
          JOIN lokasi l ON bm.lokasi_id = l.id
          JOIN users u ON bm.user_id = u.id
          WHERE bm.id = :id";
$stmt = $conn->prepare($query);
$stmt->execute(['id' => $id]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    setFlashMessage('error', 'Transaksi tidak ditemukan');
    redirect('index.php');
}

// Ambil detail barang masuk
$query_detail = "SELECT bmd.*, b.kode_barang, b.nama_barang, 
                kb.nama_kategori, su.nama_satuan as satuan_utuh,
                sp.nama_satuan as satuan_pecahan
                FROM barang_masuk_detail bmd
                JOIN barang b ON bmd.barang_id = b.id
                JOIN kategori_barang kb ON b.kategori_id = kb.id
                LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
                LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                WHERE bmd.barang_masuk_id = :id";
$stmt_detail = $conn->prepare($query_detail);
$stmt_detail->execute(['id' => $id]);
$details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar lokasi
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi";
$stmt_lokasi = $conn->query($query_lokasi);
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update data transaksi
        $query_update = "UPDATE barang_masuk SET 
                        lokasi_id = :lokasi_id,
                        tanggal = :tanggal,
                        keterangan = :keterangan,
                        updated_at = NOW(),
                        updated_by = :user_id
                        WHERE id = :id";

        $stmt_update = $conn->prepare($query_update);
        $stmt_update->execute([
            'lokasi_id' => $_POST['lokasi_id'],
            'tanggal' => $_POST['tanggal'],
            'keterangan' => $_POST['keterangan'],
            'user_id' => $_SESSION['user_id'],
            'id' => $id
        ]);

        // Update gambar jika ada yang diupload
        if (!empty($_FILES['gambar_barang']['name'])) {
            $gambar_barang = uploadImage($_FILES['gambar_barang'], 'barang_masuk');
            if ($gambar_barang) {
                // Hapus gambar lama jika ada
                if ($transaksi['gambar_barang']) {
                    deleteImage($transaksi['gambar_barang']);
                }
                $query_gambar = "UPDATE barang_masuk SET gambar_barang = :gambar_barang WHERE id = :id";
                $stmt_gambar = $conn->prepare($query_gambar);
                $stmt_gambar->execute(['gambar_barang' => $gambar_barang, 'id' => $id]);
            }
        }

        if (!empty($_FILES['struk']['name'])) {
            $struk = uploadImage($_FILES['struk'], 'struk');
            if ($struk) {
                // Hapus gambar lama jika ada
                if ($transaksi['struk']) {
                    deleteImage($transaksi['struk']);
                }
                $query_struk = "UPDATE barang_masuk SET struk = :struk WHERE id = :id";
                $stmt_struk = $conn->prepare($query_struk);
                $stmt_struk->execute(['struk' => $struk, 'id' => $id]);
            }
        }

        // Update detail barang
        // Hapus detail lama
        $query_delete_detail = "DELETE FROM barang_masuk_detail WHERE barang_masuk_id = :id";
        $stmt_delete_detail = $conn->prepare($query_delete_detail);
        $stmt_delete_detail->execute(['id' => $id]);

        // Insert detail baru
        $query_insert_detail = "INSERT INTO barang_masuk_detail 
                              (barang_masuk_id, barang_id, jumlah_utuh, jumlah_pecahan) 
                              VALUES (:barang_masuk_id, :barang_id, :jumlah_utuh, :jumlah_pecahan)";
        $stmt_insert_detail = $conn->prepare($query_insert_detail);

        foreach ($_POST['barang'] as $barang_id => $jumlah) {
            if (!empty($jumlah['utuh']) || !empty($jumlah['pecahan'])) {
                $stmt_insert_detail->execute([
                    'barang_masuk_id' => $id,
                    'barang_id' => $barang_id,
                    'jumlah_utuh' => $jumlah['utuh'] ?? 0,
                    'jumlah_pecahan' => $jumlah['pecahan'] ?? 0
                ]);
            }
        }

        $conn->commit();
        setFlashMessage('success', 'Transaksi berhasil diperbarui');
        redirect('detail.php?id=' . $id);

    } catch (Exception $e) {
        $conn->rollBack();
        setFlashMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

$page_title = "Edit Barang Masuk";
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-edit me-1"></i>
                        Edit Barang Masuk
                    </div>
                    <div>
                        <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Informasi Transaksi -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Informasi Transaksi
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">No. Transaksi</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control"
                                                    value="<?php echo $transaksi['nomor_transaksi']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">Tanggal</label>
                                            <div class="col-sm-8">
                                                <input type="date" class="form-control" name="tanggal"
                                                    value="<?php echo $transaksi['tanggal']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">Lokasi</label>
                                            <div class="col-sm-8">
                                                <select class="form-select" name="lokasi_id" required>
                                                    <?php foreach ($lokasi_list as $lokasi): ?>
                                                        <option value="<?php echo $lokasi['id']; ?>" <?php echo $lokasi['id'] == $transaksi['lokasi_id'] ? 'selected' : ''; ?>>
                                                            <?php echo $lokasi['nama_lokasi']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">Keterangan</label>
                                            <div class="col-sm-8">
                                                <textarea class="form-control" name="keterangan"
                                                    rows="3"><?php echo $transaksi['keterangan']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Gambar -->
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-images me-1"></i>
                                        Upload Gambar
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Gambar Barang</label>
                                                    <?php if ($transaksi['gambar_barang']): 
                                                        $gambar_path = '../../uploads/barang_masuk/' . $transaksi['gambar_barang'];
                                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/kopi/uploads/barang_masuk/' . $transaksi['gambar_barang'])) {
                                                    ?>
                                                        <div class="mb-2">
                                                            <img src="<?php echo $gambar_path; ?>"
                                                                class="img-thumbnail"
                                                                style="max-height: 200px; max-width: 100%;">
                                                            <div class="form-text">Gambar saat ini</div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="alert alert-warning py-1 small">File gambar tidak ditemukan</div>
                                                    <?php } ?>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control" name="gambar_barang"
                                                        accept="image/*">
                                                    <div class="form-text">Upload gambar baru untuk mengganti gambar
                                                        saat ini</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Struk</label>
                                                    <?php if ($transaksi['struk']): 
                                                        $struk_path = '../../uploads/struk/' . $transaksi['struk'];
                                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/kopi/uploads/struk/' . $transaksi['struk'])) {
                                                    ?>
                                                        <div class="mb-2">
                                                            <a href="<?php echo $struk_path; ?>" target="_blank" class="d-inline-block">
                                                                <img src="<?php echo $struk_path; ?>"
                                                                    class="img-thumbnail"
                                                                    style="max-height: 200px; max-width: 100%;">
                                                            </a>
                                                            <div class="form-text">Struk saat ini - Klik untuk memperbesar</div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="alert alert-warning py-1 small">File struk tidak ditemukan</div>
                                                    <?php } ?>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control" name="struk"
                                                        accept="image/*">
                                                    <div class="form-text">Upload struk baru untuk mengganti struk saat
                                                        ini</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Barang -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-list me-1"></i>
                                        Detail Barang
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="30%">Barang</th>
                                                        <th width="20%">Kategori</th>
                                                        <th width="25%">Satuan</th>
                                                        <th width="25%">Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($details as $detail): ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo $detail['nama_barang']; ?>
                                                                <input type="hidden"
                                                                    name="barang[<?php echo $detail['barang_id']; ?>][id]"
                                                                    value="<?php echo $detail['barang_id']; ?>">
                                                            </td>
                                                            <td><?php echo $detail['nama_kategori']; ?></td>
                                                            <td>
                                                                <?php if ($detail['satuan_utuh']): ?>
                                                                    <div class="mb-1"><?php echo $detail['satuan_utuh']; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($detail['satuan_pecahan']): ?>
                                                                    <div><?php echo $detail['satuan_pecahan']; ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($detail['satuan_utuh']): ?>
                                                                    <div class="mb-1">
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" class="form-control"
                                                                                name="barang[<?php echo $detail['barang_id']; ?>][utuh]"
                                                                                value="<?php echo $detail['jumlah_utuh']; ?>"
                                                                                min="0">
                                                                            <span
                                                                                class="input-group-text"><?php echo $detail['satuan_utuh']; ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($detail['satuan_pecahan']): ?>
                                                                    <div>
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" class="form-control"
                                                                                name="barang[<?php echo $detail['barang_id']; ?>][pecahan]"
                                                                                value="<?php echo $detail['jumlah_pecahan']; ?>"
                                                                                min="0" step="0.01">
                                                                            <span
                                                                                class="input-group-text"><?php echo $detail['satuan_pecahan']; ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
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

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>