<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang', 'kepala_toko', 'bartender', 'kasir', 'kitchen', 'waiters']);

$db = new Database();
$conn = $db->getConnection();

// Ambil data barang untuk ditampilkan
$query = "SELECT b.*, k.nama_kategori, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan,
          GROUP_CONCAT(DISTINCT l.nama_lokasi ORDER BY l.nama_lokasi ASC SEPARATOR ', ') as lokasi
          FROM barang b
          LEFT JOIN kategori_barang k ON b.kategori_id = k.id
          LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          LEFT JOIN stock s ON b.id = s.barang_id
          LEFT JOIN lokasi l ON s.lokasi_id = l.id
          GROUP BY b.id
          ORDER BY b.nama_barang ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Data Barang";
include '../../includes/header.php';
?>

<div class="page-title">
    <h1>Data Barang</h1>
    <?php if (in_array($_SESSION['role'], ['staf_gudang'])): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Barang
        </a>
    <?php endif; ?>
</div>

<?php displayAlert(); ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-boxes"></i>
        <span class="card-title">Daftar Barang</span>
    </div>
    <div class="card-body">
        <?php if (empty($barang_list)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data barang yang tersedia. Silakan tambahkan data baru.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table w-100 table-hover <?php echo !empty($barang_list) ? 'datatable' : ''; ?>">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Satuan Utuh</th>
                        <th>Satuan Pecahan</th>
                        <th>Status</th>
                        <?php if (in_array($_SESSION['role'], ['staf_gudang'])): ?>
                            <th width="15%">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($barang_list as $barang): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $barang['kode_barang']; ?></td>
                            <td><?php echo $barang['nama_barang']; ?></td>
                            <td><?php echo $barang['nama_kategori']; ?></td>
                            <td><?php echo $barang['lokasi'] ?: '-'; ?></td>
                            <td><?php echo $barang['satuan_utuh']; ?></td>
                            <td><?php echo $barang['satuan_pecahan'] ?? '-'; ?></td>
                            <td>
                                <?php if ($barang['is_aktif']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <?php if (in_array($_SESSION['role'], ['staf_gudang'])): ?>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="form.php?id=<?php echo $barang['id']; ?>" class="btn btn-sm btn-warning"
                                            title="Edit Barang">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="form.php?copy=<?php echo $barang['id']; ?>" class="btn btn-sm btn-info"
                                            title="Copy Barang">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="proses.php?action=toggle_status&id=<?php echo $barang['id']; ?>"
                                            class="btn btn-sm <?php echo $barang['is_aktif'] ? 'btn-danger' : 'btn-success'; ?>"
                                            onclick="return confirmStatus('<?php echo $barang['is_aktif'] ? 'menonaktifkan' : 'mengaktifkan'; ?>', '<?php echo $barang['nama_barang']; ?>')"
                                            title="<?php echo $barang['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan'; ?> Barang">
                                            <i class="fas <?php echo $barang['is_aktif'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                        </a>
                                        <a href="proses.php?action=delete&id=<?php echo $barang['id']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirmDelete('<?php echo $barang['nama_barang']; ?>')"
                                            title="Hapus Barang">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    $(document).ready(function () {
        // Destroy existing DataTable instance if it exists
        if ($.fn.DataTable.isDataTable('.datatable')) {
            $('.datatable').DataTable().destroy();
        }

        // Inisialisasi DataTable
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[2, 'asc']], // Urutkan berdasarkan nama barang
                columnDefs: [
                    { orderable: false, targets: -1 } // Kolom aksi tidak bisa diurutkan
                ],
                drawCallback: function () {
                    // Reinitialize tooltips after table redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
        }
    });

    // Fungsi konfirmasi hapus dengan SweetAlert2
    function confirmDelete(namaBarang) {
        return Swal.fire({
            title: 'Konfirmasi Hapus',
            html: `Apakah Anda yakin ingin menghapus barang <strong>${namaBarang}</strong>?<br>
               <small class="text-danger">Barang yang sudah digunakan dalam transaksi tidak dapat dihapus.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = event.target.href;
            }
            return false;
        });
    }

    // Fungsi konfirmasi toggle status dengan SweetAlert2
    function confirmStatus(action, namaBarang) {
        return Swal.fire({
            title: 'Konfirmasi Status',
            html: `Apakah Anda yakin ingin ${action} barang <strong>${namaBarang}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = event.target.href;
            }
            return false;
        });
    }
</script>