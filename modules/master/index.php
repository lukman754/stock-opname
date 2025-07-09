<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya admin dan manager yang boleh mengakses halaman ini
checkRole(['kepala_toko', 'staf_gudang']); // Hanya kepala_toko dan staf_gudang yang dapat mengakses pengaturan master

$page_title = "Master Data";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Master Data</h1>
</div>

<?php displayAlert(); ?>

<div class="row">
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Pengguna</h5>
                <p class="card-text">Kelola data pengguna sistem</p>
                <a href="users.php" class="btn btn-primary">Kelola Pengguna</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-map-marker-alt fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Lokasi</h5>
                <p class="card-text">Kelola data lokasi penyimpanan</p>
                <a href="lokasi.php" class="btn btn-success">Kelola Lokasi</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-balance-scale fa-3x mb-3 text-info"></i>
                <h5 class="card-title">Satuan</h5>
                <p class="card-text">Kelola data satuan barang</p>
                <a href="satuan.php" class="btn btn-info">Kelola Satuan</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-tags fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Kategori Barang</h5>
                <p class="card-text">Kelola data kategori barang</p>
                <a href="kategori.php" class="btn btn-warning">Kelola Kategori</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
