<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $error = 'Semua field harus diisi';
    } elseif ($password_baru != $konfirmasi_password) {
        $error = 'Password baru dan konfirmasi password tidak cocok';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password_lama, $user['password'])) {
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                
                $query_update = "UPDATE users SET password = :password WHERE id = :user_id";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bindParam(':password', $password_hash);
                $stmt_update->bindParam(':user_id', $_SESSION['user_id']);
                
                if ($stmt_update->execute()) {
                    $success = 'Password berhasil diubah';
                } else {
                    $error = 'Gagal mengubah password';
                }
            } else {
                $error = 'Password lama tidak valid';
            }
        } else {
            $error = 'User tidak ditemukan';
        }
    }
}

$page_title = "Ganti Password";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ganti Password</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Form Ganti Password</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="password_lama" class="form-label">Password Lama</label>
                        <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_baru" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                        <div class="form-text">Password minimal 6 karakter</div>
                    </div>
                    <div class="mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="../../dashboard.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
