<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses - hanya staf_gudang yang dapat mengelola data barang
checkRole(['staf_gudang']);

$db = new Database();
$conn = $db->getConnection();

// Ambil data untuk dropdown
$query_kategori = "SELECT * FROM kategori_barang ORDER BY nama_kategori ASC";
$stmt_kategori = $conn->prepare($query_kategori);
$stmt_kategori->execute();
$kategori_list = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

$query_satuan = "SELECT * FROM satuan ORDER BY nama_satuan ASC";
$stmt_satuan = $conn->prepare($query_satuan);
$stmt_satuan->execute();
$satuan_list = $stmt_satuan->fetchAll(PDO::FETCH_ASSOC);

$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$stmt_lokasi = $conn->prepare($query_lokasi);
$stmt_lokasi->execute();
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Generate kode barang otomatis
function generateKodeBarang($conn, $nama_barang)
{
    // Ambil inisial dari nama barang (3 huruf pertama)
    $words = explode(' ', strtoupper($nama_barang));
    $initials = '';

    // Ambil huruf pertama dari setiap kata sampai 3 huruf
    foreach ($words as $word) {
        if (strlen($initials) < 3) {
            $initials .= substr($word, 0, 1);
        }
    }

    // Jika masih kurang dari 3 huruf, tambahkan X
    while (strlen($initials) < 3) {
        $initials .= 'X';
    }

    // Ambil kode terakhir dengan inisial yang sama
    $query = "SELECT kode_barang FROM barang 
              WHERE kode_barang LIKE :prefix 
              ORDER BY kode_barang DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':prefix', $initials . '-%');
    $stmt->execute();
    $last_code = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last_code) {
        // Ambil angka dari kode terakhir
        $last_number = intval(substr($last_code['kode_barang'], 4)); // 4 = panjang "XXX-"
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }

    // Format kode baru dengan leading zeros
    return $initials . '-' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
}

// Ambil data barang untuk edit jika ada parameter id
$barang = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM barang WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        showAlert('Data barang tidak ditemukan', 'danger');
        redirect('index.php');
    }
}

$page_title = $barang ? "Edit Barang" : "Tambah Barang Baru";
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-box me-1"></i>
                        <?php echo $page_title; ?>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="proses.php" method="POST" id="formBarang">
                        <input type="hidden" name="action" value="<?php echo $barang ? 'update' : 'create'; ?>">
                        <?php if ($barang): ?>
                            <input type="hidden" name="id" value="<?php echo $barang['id']; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <!-- Informasi Barang -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Informasi Barang
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Kode Barang</label>
                                            <input type="text" class="form-control" name="kode_barang" id="kode_barang"
                                                value="<?php echo $barang ? $barang['kode_barang'] : ''; ?>" readonly
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nama Barang</label>
                                            <input type="text" class="form-control" name="nama_barang" id="nama_barang"
                                                value="<?php echo $barang ? $barang['nama_barang'] : ''; ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select class="form-select" name="kategori_id" required>
                                                <option value="">Pilih Kategori</option>
                                                <?php foreach ($kategori_list as $kategori): ?>
                                                    <option value="<?php echo $kategori['id']; ?>" 
                                                        <?php echo ($barang && $barang['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $kategori['nama_kategori']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Lokasi</label>
                                            <select class="form-select" name="lokasi_id[]" multiple required>
                                                <?php 
                                                // Ambil lokasi yang sudah dipilih untuk barang ini
                                                $selected_lokasi = [];
                                                if ($barang) {
                                                    $query = "SELECT lokasi_id FROM stock WHERE barang_id = :barang_id";
                                                    $stmt = $conn->prepare($query);
                                                    $stmt->bindParam(':barang_id', $barang['id']);
                                                    $stmt->execute();
                                                    $selected_lokasi = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                }
                                                ?>
                                                <?php foreach ($lokasi_list as $lokasi): ?>
                                                    <option value="<?php echo $lokasi['id']; ?>" 
                                                        <?php echo in_array($lokasi['id'], $selected_lokasi) ? 'selected' : ''; ?>>
                                                        <?php echo $lokasi['nama_lokasi']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Pilih satu atau lebih lokasi (tekan Ctrl untuk memilih lebih dari satu)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Satuan -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <i class="fas fa-boxes me-1"></i>
                                        Satuan
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Satuan Utuh</label>
                                            <select class="form-select" name="satuan_utuh_id" required>
                                                <option value="">Pilih Satuan</option>
                                                <?php foreach ($satuan_list as $satuan): ?>
                                                    <option value="<?php echo $satuan['id']; ?>" 
                                                        <?php echo ($barang && $barang['satuan_utuh_id'] == $satuan['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $satuan['nama_satuan']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Satuan Pecahan</label>
                                            <select class="form-select" name="satuan_pecahan_id">
                                                <option value="">Tidak Ada</option>
                                                <?php foreach ($satuan_list as $satuan): ?>
                                                    <option value="<?php echo $satuan['id']; ?>" 
                                                        <?php echo ($barang && $barang['satuan_pecahan_id'] == $satuan['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $satuan['nama_satuan']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
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
        const namaBarangInput = document.getElementById('nama_barang');
        const kodeBarangInput = document.getElementById('kode_barang');
        const formBarang = document.getElementById('formBarang');
        let generateTimeout;

        // Generate kode barang secara real-time saat mengetik
        namaBarangInput.addEventListener('keyup', function () {
            // Clear previous timeout
            if (generateTimeout) {
                clearTimeout(generateTimeout);
            }

            // Set new timeout untuk generate kode
            generateTimeout = setTimeout(() => {
                const namaBarang = this.value.trim();
                if (namaBarang.length > 0) {
                    // Generate inisial dari nama barang
                    const words = namaBarang.toUpperCase().split(' ');
                    let initials = '';

                    // Ambil huruf pertama dari setiap kata sampai 3 huruf
                    for (const word of words) {
                        if (initials.length < 3 && word.length > 0) {
                            initials += word[0];
                        }
                    }

                    // Jika masih kurang dari 3 huruf, tambahkan X
                    while (initials.length < 3) {
                        initials += 'X';
                    }

                    // Kirim AJAX request untuk mendapatkan nomor urut
                    fetch('generate_kode.php?nama_barang=' + encodeURIComponent(namaBarang))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                kodeBarangInput.value = data.kode_barang;
                            } else {
                                console.error('Error:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                } else {
                    kodeBarangInput.value = '';
                }
            }, 100); // Delay 100ms untuk menghindari terlalu banyak request
        });

        // Form validation
        formBarang.addEventListener('submit', function (e) {
            if (!kodeBarangInput.value) {
                e.preventDefault();
                alert('Kode barang harus diisi. Silakan isi nama barang terlebih dahulu.');
                namaBarangInput.focus();
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>