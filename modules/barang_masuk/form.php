<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters']); // Roles yang dapat input barang masuk

$page_title = "Form Barang Masuk";
include '../../includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Generate nomor transaksi
$nomor_transaksi = generateTransactionNumber('BM');

// Ambil data lokasi berdasarkan role user
$user_location_id = getUserLocationId();
$is_global_user = hasGlobalAccess();

if ($is_global_user) {
    // User dengan akses global dapat melihat semua lokasi
    $query = "SELECT * FROM lokasi ORDER BY nama_lokasi";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat lokasi mereka
    $query = "SELECT * FROM lokasi WHERE id = :lokasi_id ORDER BY nama_lokasi";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':lokasi_id', $user_location_id);
    $stmt->execute();
    $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data barang dengan lokasi berdasarkan role user
if ($is_global_user) {
    // User dengan akses global dapat melihat semua barang
    $query = "SELECT b.*, kb.nama_kategori, 
              su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
              GROUP_CONCAT(DISTINCT l.nama_lokasi) as lokasi_names,
              GROUP_CONCAT(DISTINCT l.id) as lokasi_ids
              FROM barang b
              LEFT JOIN kategori_barang kb ON b.kategori_id = kb.id
              LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
              LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
              LEFT JOIN stock s ON b.id = s.barang_id
              LEFT JOIN lokasi l ON s.lokasi_id = l.id
              WHERE b.is_aktif = 1
              GROUP BY b.id
              ORDER BY b.nama_barang ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
} else {
    // User dengan role bartender, kitchen, kasir, waiters hanya dapat melihat barang di lokasi mereka
    $query = "SELECT b.*, kb.nama_kategori, 
              su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
              GROUP_CONCAT(DISTINCT l.nama_lokasi) as lokasi_names,
              GROUP_CONCAT(DISTINCT l.id) as lokasi_ids
              FROM barang b
              LEFT JOIN kategori_barang kb ON b.kategori_id = kb.id
              LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
              LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
              LEFT JOIN stock s ON b.id = s.barang_id
              LEFT JOIN lokasi l ON s.lokasi_id = l.id
              WHERE b.is_aktif = 1
              AND s.lokasi_id = :lokasi_id
              GROUP BY b.id
              ORDER BY b.nama_barang ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':lokasi_id', $user_location_id);
    $stmt->execute();
}
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Barang Masuk - Kopi</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet">
    <style>
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .selected-row {
            background-color: #e3f2fd !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
<div class="row">
    <div class="col-md-12">
                <div class="card">
            <div class="card-header">
                        <h5 class="card-title mb-0">Form Barang Masuk</h5>
            </div>
            <div class="card-body">
                        <form action="proses.php" method="POST" enctype="multipart/form-data" id="formBarangMasuk">
                    <input type="hidden" name="action" value="simpan">
                    
                    <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nomor Transaksi</label>
                                    <input type="text" class="form-control" name="nomor_transaksi"
                                        value="<?php echo $nomor_transaksi; ?>" readonly>
                        </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" class="form-control" name="tanggal"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                                <div class="col-md-4">
                                    <label class="form-label">Lokasi</label>
                                    <select class="form-select" name="lokasi_id" id="lokasi_id" required
                                        onchange="filterBarangByLokasi()">
                                <option value="">Pilih Lokasi</option>
                                <?php foreach ($lokasi_list as $lokasi): ?>
                                <option value="<?php echo $lokasi['id']; ?>">
                                    <?php echo $lokasi['nama_lokasi']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gambar Barang</label>
                                    <input type="file" class="form-control" name="gambar_barang" accept="image/*"
                                        onchange="previewImage(this, 'previewBarang')">
                                    <div class="mt-2">
                                        <img id="previewBarang" class="img-thumbnail"
                                            style="max-width: 200px; display: none;">
                        </div>
                    </div>
                                <div class="col-md-6">
                                    <label class="form-label">Struk</label>
                                    <input type="file" class="form-control" name="struk" accept="image/*"
                                        onchange="previewImage(this, 'previewStruk')">
                                    <div class="mt-2">
                                        <img id="previewStruk" class="img-thumbnail"
                                            style="max-width: 200px; display: none;">
                    </div>
                        </div>
                    </div>
                    
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" rows="2"></textarea>
    </div>
</div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">Daftar Barang</h6>
                                        <div class="input-group" style="width: 300px;">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="searchBarang"
                                                placeholder="Cari barang..." onkeyup="searchBarang()">
            </div>
                    </div>
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover" id="tabelBarang">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th width="50">No</th>
                                                    <th width="100">Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                                    <th>Satuan</th>
                                                    <th>Lokasi</th>
                                                    <th width="150">Jumlah Utuh</th>
                                                    <th width="150">Jumlah Pecahan</th>
                                                    <th width="50">Pilih</th>
                                </tr>
                            </thead>
                            <tbody>
                                                <?php if (empty($barang_list)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">Tidak ada data barang</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php $no = 1;
                                                    foreach ($barang_list as $barang): ?>
                                                        <tr class="barang-row"
                                                            data-lokasi="<?php echo $barang['lokasi_ids']; ?>">
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo $barang['kode_barang']; ?></td>
                                                            <td><?php echo $barang['nama_barang']; ?></td>
                                                            <td><?php echo $barang['nama_kategori']; ?></td>
                                                            <td>
                                                                <?php
                                                                echo $barang['satuan_utuh'];
                                                                if ($barang['satuan_pecahan']) {
                                                                    echo ' / ' . $barang['satuan_pecahan'];
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo $barang['lokasi_names']; ?></td>
                                                            <td>
                                                                <input type="number"
                                                                    class="form-control form-control-sm jumlah-utuh" min="0"
                                                                    step="1" value="0" disabled>
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    class="form-control form-control-sm jumlah-pecahan" min="0"
                                                                    step="0.01" value="0" disabled>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input barang-checkbox"
                                                                        type="checkbox" value="<?php echo $barang['id']; ?>"
                                                                        data-kode="<?php echo $barang['kode_barang']; ?>"
                                                                        data-nama="<?php echo $barang['nama_barang']; ?>"
                                                                        data-satuan-utuh="<?php echo $barang['satuan_utuh']; ?>"
                                                                        data-satuan-pecahan="<?php echo $barang['satuan_pecahan']; ?>"
                                                                        onchange="handleCheckboxChange(this)">
                                                                </div>
                                                            </td>
                                </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                        </div>
                        </div>

                            <input type="hidden" name="detail_barang" id="detailBarang">

                            <div class="text-end">
                                <a href="index.php" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }

        function filterBarangByLokasi() {
            const lokasiId = document.getElementById('lokasi_id').value;
            const rows = document.querySelectorAll('.barang-row');

            rows.forEach(row => {
                const lokasiIds = row.dataset.lokasi ? row.dataset.lokasi.split(',') : [];
                const shouldShow = !lokasiId || lokasiIds.includes(lokasiId);

                if (!shouldShow) {
                    // Uncheck if hidden
                    const checkbox = row.querySelector('.barang-checkbox');
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        handleCheckboxChange(checkbox);
                    }
                }
                row.style.display = shouldShow ? '' : 'none';
            });
        }

        function searchBarang() {
            const input = document.getElementById('searchBarang');
            const filter = input.value.toUpperCase();
            const lokasiId = document.getElementById('lokasi_id').value;
            const rows = document.querySelectorAll('.barang-row');

            rows.forEach(row => {
                const lokasiIds = row.dataset.lokasi ? row.dataset.lokasi.split(',') : [];
                const shouldShowByLokasi = !lokasiId || lokasiIds.includes(lokasiId);
                const text = row.textContent.toUpperCase();
                const matchesSearch = text.includes(filter);

                row.style.display = shouldShowByLokasi && matchesSearch ? '' : 'none';
            });
        }

        function handleCheckboxChange(checkbox) {
            const row = $(checkbox).closest('tr');
            const inputs = row.find('input[type="number"]');

            if (checkbox.checked) {
                row.addClass('selected-row');
                inputs.prop('disabled', false);
                // Move selected row to top
                row.prependTo(row.parent());
            } else {
                row.removeClass('selected-row');
                inputs.prop('disabled', true);
                inputs.val(0);
                // Move row back to original position
                row.appendTo(row.parent());
            }
        }

        $(document).ready(function () {
            // Form submission
            $('#formBarangMasuk').on('submit', function (e) {
                e.preventDefault();

                const detailBarang = [];
                $('.barang-checkbox:checked').each(function () {
                    const row = $(this).closest('tr');
                    const jumlahUtuh = parseFloat(row.find('.jumlah-utuh').val()) || 0;
                    const jumlahPecahan = parseFloat(row.find('.jumlah-pecahan').val()) || 0;

                    if (jumlahUtuh > 0 || jumlahPecahan > 0) {
                        detailBarang.push({
                            barang_id: $(this).val(),
                            jumlah_utuh: jumlahUtuh,
                            jumlah_pecahan: jumlahPecahan
                        });
                    }
                });

                if (detailBarang.length === 0) {
                    alert('Pilih minimal satu barang dan isi jumlahnya!');
                return;
            }
            
                $('#detailBarang').val(JSON.stringify(detailBarang));
                this.submit();
        });
    });
</script>
</body>

</html>
<?php include '../../includes/footer.php'; ?>