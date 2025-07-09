<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = "Akses Ditolak";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Akses Ditolak</h4>
                </div>
                <div class="card-body py-5">
                    <i class="fas fa-exclamation-triangle text-warning mb-4" style="font-size: 4rem;"></i>
                    <h5 class="card-title mb-4">Anda tidak memiliki izin untuk mengakses halaman ini</h5>
                    <p class="card-text mb-4">Silakan kembali ke halaman dashboard atau hubungi administrator jika Anda memerlukan akses ke halaman ini.</p>
                    <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
