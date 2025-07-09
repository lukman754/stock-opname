<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat mengedit data barang keluar

$db = new Database();
$conn = $db->getConnection();

// Ambil ID barang keluar
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Validasi ID
if (empty($id)) {
    showAlert("ID tidak valid!", "danger");
    header("Location: index.php");
    exit;
}

// Ambil data header barang keluar
$query = "SELECT bk.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM barang_keluar bk
          JOIN lokasi l ON bk.lokasi_id = l.id
          JOIN users u ON bk.user_id = u.id
          WHERE bk.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    showAlert("Data barang keluar tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}

$barang_keluar = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail barang keluar
$query = "SELECT bkd.*, b.kode_barang, b.nama_barang, b.kategori_id,
          kb.nama_kategori, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM barang_keluar_detail bkd
          JOIN barang b ON bkd.barang_id = b.id
          JOIN kategori_barang kb ON b.kategori_id = kb.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE bkd.barang_keluar_id = :barang_keluar_id
          ORDER BY b.nama_barang ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':barang_keluar_id', $id);
$stmt->execute();
$detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar lokasi
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi";
$stmt_lokasi = $conn->query($query_lokasi);
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar barang dengan stok
$query_barang = "SELECT b.*, kb.nama_kategori, 
                su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
                GROUP_CONCAT(DISTINCT CONCAT(s.lokasi_id, ':', s.jumlah_utuh, ':', s.jumlah_pecahan)) as stok_lokasi
                FROM barang b
                JOIN kategori_barang kb ON b.kategori_id = kb.id
                LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
                LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                LEFT JOIN stock s ON b.id = s.barang_id
                GROUP BY b.id
                ORDER BY b.nama_barang ASC";
$stmt_barang = $conn->query($query_barang);
$barang_list = $stmt_barang->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Edit Barang Keluar";
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-edit me-1"></i>
                        Edit Barang Keluar
                    </div>
                    <div>
                        <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="proses.php" method="POST" id="formEditBarangKeluar">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="detail_barang" id="detail_barang">
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
                                                <input type="text" class="form-control" name="nomor_transaksi"
                                                    value="<?php echo $barang_keluar['nomor_transaksi']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">Tanggal</label>
                                            <div class="col-sm-8">
                                                <input type="date" class="form-control" name="tanggal"
                                                    value="<?php echo $barang_keluar['tanggal']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-sm-4 col-form-label">Lokasi Asal</label>
                                            <div class="col-sm-8">
                                                <select class="form-select" name="lokasi_id" id="lokasi_id" required>
                                                    <option value="">Pilih Lokasi</option>
                                                    <?php foreach ($lokasi_list as $lokasi): ?>
                                                        <option value="<?php echo $lokasi['id']; ?>" <?php echo ($lokasi['id'] == $barang_keluar['lokasi_id']) ? 'selected' : ''; ?>>
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
                                                    rows="3"><?php echo $barang_keluar['keterangan']; ?></textarea>
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
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="searchBarang"
                                                    placeholder="Cari barang...">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="btnClearSearch">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th width="5%">#</th>
                                                        <th width="20%">Barang</th>
                                                        <th width="15%">Kategori</th>
                                                        <th width="15%">Stok</th>
                                                        <th width="25%">Jumlah</th>
                                                        <th width="20%">Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="barangTableBody">
                                                    <?php foreach ($barang_list as $barang):
                                                        // Cari detail barang yang sudah ada
                                                        $detail = array_filter($detail_list, function ($d) use ($barang) {
                                                            return $d['barang_id'] == $barang['id'];
                                                        });
                                                        $detail = reset($detail); // Ambil item pertama jika ada
                                                        ?>
                                                        <tr class="barang-row" data-barang-id="<?php echo $barang['id']; ?>"
                                                            data-lokasi="<?php echo $barang['stok_lokasi']; ?>">
                                                            <td>
                                                                <input type="checkbox"
                                                                    class="form-check-input barang-checkbox"
                                                                    name="barang[<?php echo $barang['id']; ?>][selected]"
                                                                    <?php echo $detail ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td>
                                                                <?php echo $barang['nama_barang']; ?>
                                                                <input type="hidden"
                                                                    name="barang[<?php echo $barang['id']; ?>][id]"
                                                                    value="<?php echo $barang['id']; ?>">
                                                            </td>
                                                            <td><?php echo $barang['nama_kategori']; ?></td>
                                                            <td class="stok-info">
                                                                <span class="stok-utuh"></span>
                                                                <span class="stok-pecahan"></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($barang['satuan_utuh']): ?>
                                                                    <div class="mb-1">
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number"
                                                                                class="form-control jumlah-utuh"
                                                                                name="barang[<?php echo $barang['id']; ?>][utuh]"
                                                                                min="0"
                                                                                value="<?php echo $detail ? $detail['jumlah_utuh'] : ''; ?>"
                                                                                <?php echo $detail ? '' : 'disabled'; ?>>
                                                                            <span
                                                                                class="input-group-text"><?php echo $barang['satuan_utuh']; ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($barang['satuan_pecahan']): ?>
                                                                    <div>
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number"
                                                                                class="form-control jumlah-pecahan"
                                                                                name="barang[<?php echo $barang['id']; ?>][pecahan]"
                                                                                min="0" step="0.01"
                                                                                value="<?php echo $detail ? $detail['jumlah_pecahan'] : ''; ?>"
                                                                                <?php echo $detail ? '' : 'disabled'; ?>>
                                                                            <span
                                                                                class="input-group-text"><?php echo $barang['satuan_pecahan']; ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    class="form-control form-control-sm keterangan-item"
                                                                    name="barang[<?php echo $barang['id']; ?>][keterangan]"
                                                                    value="<?php echo $detail ? $detail['keterangan'] : ''; ?>"
                                                                    <?php echo $detail ? '' : 'disabled'; ?>>
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
                            <button type="submit" class="btn btn-primary" id="btnSimpan">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lokasiSelect = document.getElementById('lokasi_id');
        const searchInput = document.getElementById('searchBarang');
        const btnClearSearch = document.getElementById('btnClearSearch');
        const barangRows = document.querySelectorAll('.barang-row');
        const formEditBarangKeluar = document.getElementById('formEditBarangKeluar');

        // Fungsi untuk memfilter barang berdasarkan lokasi
        function filterBarangByLokasi() {
            const selectedLokasi = lokasiSelect.value;
            barangRows.forEach(row => {
                const lokasiData = row.dataset.lokasi;
                if (!lokasiData) {
                    row.style.display = 'none';
                    return;
                }

                const stokLokasi = lokasiData.split(',');
                let hasStok = false;
                let stokUtuh = 0;
                let stokPecahan = 0;

                stokLokasi.forEach(stok => {
                    const [lokasiId, utuh, pecahan] = stok.split(':');
                    if (lokasiId === selectedLokasi) {
                        hasStok = true;
                        stokUtuh = parseFloat(utuh) || 0;
                        stokPecahan = parseFloat(pecahan) || 0;
                    }
                });

                if (hasStok) {
                    row.style.display = '';
                    const stokInfo = row.querySelector('.stok-info');
                    const stokUtuhSpan = stokInfo.querySelector('.stok-utuh');
                    const stokPecahanSpan = stokInfo.querySelector('.stok-pecahan');

                    stokUtuhSpan.textContent = stokUtuh > 0 ? `${stokUtuh} utuh` : '';
                    stokPecahanSpan.textContent = stokPecahan > 0 ?
                        (stokUtuh > 0 ? ` + ${stokPecahan} pecahan` : `${stokPecahan} pecahan`) : '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Fungsi untuk memfilter barang berdasarkan pencarian
        function searchBarang() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedLokasi = lokasiSelect.value;

            barangRows.forEach(row => {
                const namaBarang = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const lokasiData = row.dataset.lokasi;
                let showByLokasi = true;

                if (selectedLokasi && lokasiData) {
                    const stokLokasi = lokasiData.split(',');
                    showByLokasi = stokLokasi.some(stok => stok.split(':')[0] === selectedLokasi);
                }

                const showBySearch = namaBarang.includes(searchTerm);
                row.style.display = (showBySearch && showByLokasi) ? '' : 'none';
            });
        }

        // Event listener untuk checkbox
        function handleCheckboxChange(checkbox) {
            const row = checkbox.closest('tr');
            const inputs = row.querySelectorAll('input[type="number"], input.keterangan-item');

            inputs.forEach(input => {
                input.disabled = !checkbox.checked;
                if (!checkbox.checked) {
                    input.value = '';
                }
            });

            // Pindahkan baris yang dipilih ke atas
            if (checkbox.checked) {
                const tbody = row.parentNode;
                tbody.insertBefore(row, tbody.firstChild);
            }
        }

        // Event listeners
        lokasiSelect.addEventListener('change', function () {
            filterBarangByLokasi();
            searchBarang(); // Re-apply search filter
        });

        searchInput.addEventListener('input', searchBarang);

        btnClearSearch.addEventListener('click', function () {
            searchInput.value = '';
            searchBarang();
        });

        barangRows.forEach(row => {
            const checkbox = row.querySelector('.barang-checkbox');
            checkbox.addEventListener('change', function () {
                handleCheckboxChange(this);
            });
        });

        // Form validation
        formEditBarangKeluar.addEventListener('submit', function (e) {
            e.preventDefault();
            const checkedBoxes = document.querySelectorAll('.barang-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Pilih minimal satu barang');
                return;
            }

            let hasQuantity = false;
            let detailBarang = [];

            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const barangId = row.dataset.barangId;
                const utuhInput = row.querySelector('.jumlah-utuh');
                const pecahanInput = row.querySelector('.jumlah-pecahan');
                const keteranganInput = row.querySelector('.keterangan-item');
                const jumlahUtuh = utuhInput ? parseFloat(utuhInput.value) || 0 : 0;
                const jumlahPecahan = pecahanInput ? parseFloat(pecahanInput.value) || 0 : 0;
                const keterangan = keteranganInput ? keteranganInput.value : '';

                if (jumlahUtuh > 0 || jumlahPecahan > 0) {
                    hasQuantity = true;
                    detailBarang.push({
                        barang_id: barangId,
                        jumlah_utuh: jumlahUtuh,
                        jumlah_pecahan: jumlahPecahan,
                        keterangan: keterangan
                    });
                }
            });

            if (!hasQuantity) {
                alert('Masukkan jumlah untuk minimal satu barang');
                return;
            }

            // Set detail barang ke input hidden
            document.getElementById('detail_barang').value = JSON.stringify(detailBarang);

            // Submit form
            this.submit();
        });

        // Inisialisasi tampilan
        filterBarangByLokasi();
    });
</script>

<?php include '../../includes/footer.php'; ?>