<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses, hanya kepala toko yang boleh mengakses halaman ini
checkRole(['kepala_toko']); // Hanya kepala_toko yang dapat mengelola pengguna

$db = new Database();
$conn = $db->getConnection();

// Proses hapus user
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Cek apakah user yang akan dihapus bukan user yang sedang login
    if ($id == $_SESSION['user_id']) {
        showAlert('Anda tidak dapat menghapus akun yang sedang aktif', 'danger');
    } else {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            showAlert('User berhasil dihapus', 'success');
        } else {
            showAlert('Gagal menghapus user', 'danger');
        }
    }

    // Reset variabel $user_edit sebelum redirect
    $user_edit = null;
    redirect('users.php');
}

// Proses tambah/edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validasi input
    if (empty($username) || empty($nama_lengkap) || empty($role)) {
        showAlert('Semua field harus diisi kecuali password saat edit', 'danger');
    } else {
        // Cek apakah username sudah ada (untuk tambah user atau edit username)
        $query_check = "SELECT id FROM users WHERE username = :username AND id != :id";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bindParam(':username', $username);
        $stmt_check->bindParam(':id', $id);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            showAlert('Username sudah digunakan', 'danger');
        } else {
            if (empty($id)) { // Tambah user baru
                if (empty($password)) {
                    showAlert('Password harus diisi untuk user baru', 'danger');
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Cek apakah role memerlukan lokasi
                    $needs_location = !in_array($role, ['kepala_toko', 'manager_keuangan']);
                    $lokasi_id = isset($_POST['lokasi_id']) && !empty($_POST['lokasi_id']) ? $_POST['lokasi_id'] : null;

                    // Jika role memerlukan lokasi tapi lokasi tidak dipilih
                    if ($needs_location && empty($lokasi_id)) {
                        showAlert('Lokasi harus dipilih untuk role ini', 'danger');
                        redirect('users.php');
                        exit;
                    }

                    $query = "INSERT INTO users (username, password, nama_lengkap, role, lokasi_id) 
                              VALUES (:username, :password, :nama_lengkap, :role, :lokasi_id)";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $password_hash);
                    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':lokasi_id', $lokasi_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        showAlert('User berhasil ditambahkan', 'success');
                    } else {
                        showAlert('Gagal menambahkan user', 'danger');
                    }
                }
            } else { // Edit user
                // Cek apakah role memerlukan lokasi
                $needs_location = !in_array($role, ['kepala_toko', 'manager_keuangan']);
                $lokasi_id = isset($_POST['lokasi_id']) && !empty($_POST['lokasi_id']) ? $_POST['lokasi_id'] : null;

                // Jika role memerlukan lokasi tapi lokasi tidak dipilih
                if ($needs_location && empty($lokasi_id)) {
                    showAlert('Lokasi harus dipilih untuk role ini', 'danger');
                    redirect('users.php');
                    exit;
                }

                if (empty($password)) {
                    // Update tanpa mengubah password
                    $query = "UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, 
                              role = :role, lokasi_id = :lokasi_id WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':lokasi_id', $lokasi_id, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $id);
                } else {
                    // Update dengan password baru
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $query = "UPDATE users SET username = :username, password = :password, 
                              nama_lengkap = :nama_lengkap, role = :role, lokasi_id = :lokasi_id WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $password_hash);
                    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':lokasi_id', $lokasi_id, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $id);
                }

                if ($stmt->execute()) {
                    showAlert('User berhasil diperbarui', 'success');
                } else {
                    showAlert('Gagal memperbarui user', 'danger');
                }
            }
        }
    }

    // Reset variabel $user_edit sebelum redirect
    $user_edit = null;
    redirect('users.php');
}

// Ambil data user untuk ditampilkan
$query = "SELECT * FROM users ORDER BY id ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data user untuk edit jika ada parameter id
$user_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$page_title = "Manajemen Pengguna";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Pengguna</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"
        onclick="resetUserForm()">
        <i class="fas fa-plus"></i> Tambah User
    </button>
</div>

<?php displayAlert(); ?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        Daftar Pengguna
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Tidak ada data pengguna yang tersedia. Silakan tambahkan pengguna
                baru.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover <?php echo !empty($users) ? 'datatable' : ''; ?>"
                data-default-sort="1" data-default-order="asc">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['nama_lengkap']; ?></td>
                            <td>
                                <?php
                                switch ($user['role']) {
                                    case 'kepala_toko':
                                        echo '<span class="badge bg-danger">Kepala Toko</span>';
                                        break;
                                    case 'staf_gudang':
                                        echo '<span class="badge bg-primary">Staf Gudang</span>';
                                        break;
                                    case 'manager_keuangan':
                                        echo '<span class="badge bg-success">Manager Keuangan</span>';
                                        break;
                                    case 'bartender':
                                        echo '<span class="badge bg-info">Bartender</span>';
                                        break;
                                    case 'kitchen':
                                        echo '<span class="badge bg-warning">Kitchen</span>';
                                        break;
                                    case 'kasir':
                                        echo '<span class="badge bg-secondary">Kasir</span>';
                                        break;
                                    case 'waiters':
                                        echo '<span class="badge bg-dark">Waiters</span>';
                                        break;
                                    // For backward compatibility with old roles
                                    case 'admin':
                                        echo '<span class="badge bg-danger">Kepala Toko</span>';
                                        break;
                                    case 'manager':
                                        echo '<span class="badge bg-primary">Staf Gudang</span>';
                                        break;
                                    default:
                                        echo $user['role'];
                                }
                                ?>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <?php echo $user_edit ? 'Edit Pengguna' : 'Tambah Pengguna'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if ($user_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $user_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                            value="<?php echo $user_edit ? $user_edit['username'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password
                            <?php echo $user_edit ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo $user_edit ? '' : 'required'; ?>>
                    </div>

                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                            value="<?php echo $user_edit ? $user_edit['nama_lengkap'] : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required onchange="toggleLocationField()">
                            <option value="">Pilih Role</option>
                            <option value="kepala_toko" <?php echo ($user_edit && ($user_edit['role'] == 'kepala_toko' || $user_edit['role'] == 'admin')) ? 'selected' : ''; ?>>Kepala Toko</option>
                            <option value="staf_gudang" <?php echo ($user_edit && ($user_edit['role'] == 'staf_gudang' || $user_edit['role'] == 'manager')) ? 'selected' : ''; ?>>Staf Gudang</option>
                            <option value="manager_keuangan" <?php echo $user_edit && $user_edit['role'] == 'manager_keuangan' ? 'selected' : ''; ?>>Manager Keuangan</option>
                            <option value="bartender" <?php echo $user_edit && $user_edit['role'] == 'bartender' ? 'selected' : ''; ?>>Bartender</option>
                            <option value="kitchen" <?php echo $user_edit && $user_edit['role'] == 'kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                            <option value="kasir" <?php echo $user_edit && $user_edit['role'] == 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                            <option value="waiters" <?php echo $user_edit && $user_edit['role'] == 'waiters' ? 'selected' : ''; ?>>Waiters</option>
                        </select>
                    </div>

                    <div class="mb-3" id="lokasi-field">
                        <label for="lokasi_id" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi_id" name="lokasi_id">
                            <option value="">Pilih Lokasi</option>
                            <?php
                            $query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
                            $stmt_lokasi = $conn->prepare($query_lokasi);
                            $stmt_lokasi->execute();
                            $lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($lokasi_list as $lokasi):
                                ?>
                                <option value="<?php echo $lokasi['id']; ?>" <?php echo $user_edit && $user_edit['lokasi_id'] == $lokasi['id'] ? 'selected' : ''; ?>>
                                    <?php echo $lokasi['nama_lokasi']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

<?php if ($user_edit): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var userModal = new bootstrap.Modal(document.getElementById('userModal'));
            userModal.show();
            toggleLocationField(); // Initialize location field visibility
        });
    </script>
<?php endif; ?>

<script>
    function toggleLocationField() {
        var roleSelect = document.getElementById('role');
        var lokasiField = document.getElementById('lokasi-field');
        var selectedRole = roleSelect.value;

        // Roles that don't require a specific location (can access all locations)
        var globalAccessRoles = ['kepala_toko', 'manager_keuangan'];

        if (globalAccessRoles.includes(selectedRole)) {
            lokasiField.style.display = 'none';
        } else {
            lokasiField.style.display = 'block';
        }
    }

    function resetUserForm() {
        // Reset form fields
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        document.getElementById('nama_lengkap').value = '';
        document.getElementById('role').value = '';
        document.getElementById('lokasi_id').value = '';

        // Reset hidden input for edit mode
        var hiddenId = document.querySelector('input[name="id"]');
        if (hiddenId) {
            hiddenId.remove();
        }

        // Update modal title
        document.getElementById('userModalLabel').textContent = 'Tambah Pengguna';

        // Reset password field requirement
        document.getElementById('password').required = true;

        // Show location field by default
        document.getElementById('lokasi-field').style.display = 'block';
    }

    // Run on page load to set initial state
    document.addEventListener('DOMContentLoaded', function () {
        toggleLocationField();

        // Reset form when modal is hidden
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
            resetUserForm();
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>