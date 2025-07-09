<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'manager_keuangan', 'kasir']); // Hanya roles ini yang dapat mencetak laporan

$page_title = "Laporan";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan</h1>
</div>

<?php displayAlert(); ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-cubes me-1"></i>
                Laporan Stok Barang
            </div>
            <div class="card-body">
                <p class="card-text">Laporan stok barang saat ini berdasarkan lokasi.</p>
                <form action="stok.php" method="get" target="_blank">
                    <div class="mb-3">
                        <label for="lokasi_id" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id" name="lokasi_id">
                            <option value="">Semua Lokasi</option>
                            <?php
                            $db = new Database();
                            $conn = $db->getConnection();

                            $query = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
                            $stmt = $conn->prepare($query);
                            $stmt->execute();
                            $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($lokasi_list as $lokasi) {
                                echo "<option value='{$lokasi['id']}'>{$lokasi['nama_lokasi']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_id" name="kategori_id">
                            <option value="">Semua Kategori</option>
                            <?php
                            $query = "SELECT * FROM kategori_barang ORDER BY nama_kategori ASC";
                            $stmt = $conn->prepare($query);
                            $stmt->execute();
                            $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($kategori_list as $kategori) {
                                echo "<option value='{$kategori['id']}'>{$kategori['nama_kategori']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-truck-loading me-1"></i>
                Laporan Barang Masuk
            </div>
            <div class="card-body">
                <p class="card-text">Laporan barang masuk berdasarkan periode tanggal.</p>
                <form action="barang_masuk.php" method="get" target="_blank">
                    <div class="mb-3">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal"
                            value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir"
                            value="<?php echo date('Y-m-t'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lokasi_id_bm" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id_bm" name="lokasi_id">
                            <option value="">Semua Lokasi</option>
                            <?php
                            foreach ($lokasi_list as $lokasi) {
                                echo "<option value='{$lokasi['id']}'>{$lokasi['nama_lokasi']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-dolly-flatbed me-1"></i>
                Laporan Barang Keluar
            </div>
            <div class="card-body">
                <p class="card-text">Laporan barang keluar berdasarkan periode tanggal.</p>
                <form action="barang_keluar.php" method="get" target="_blank">
                    <div class="mb-3">
                        <label for="tanggal_awal_bk" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal_bk" name="tanggal_awal"
                            value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_akhir_bk" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir_bk" name="tanggal_akhir"
                            value="<?php echo date('Y-m-t'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lokasi_id_bk" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id_bk" name="lokasi_id">
                            <option value="">Semua Lokasi</option>
                            <?php
                            foreach ($lokasi_list as $lokasi) {
                                echo "<option value='{$lokasi['id']}'>{$lokasi['nama_lokasi']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-boxes me-1"></i>
                Laporan Stock Opname
            </div>
            <div class="card-body">
                <p class="card-text">Laporan stock opname berdasarkan periode tanggal.</p>
                <form action="stock_opname.php" method="get" target="_blank">
                    <div class="mb-3">
                        <label for="tanggal_awal_so" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal_so" name="tanggal_awal"
                            value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_akhir_so" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir_so" name="tanggal_akhir"
                            value="<?php echo date('Y-m-t'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lokasi_id_so" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id_so" name="lokasi_id">
                            <option value="">Semua Lokasi</option>
                            <?php
                            foreach ($lokasi_list as $lokasi) {
                                echo "<option value='{$lokasi['id']}'>{$lokasi['nama_lokasi']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>