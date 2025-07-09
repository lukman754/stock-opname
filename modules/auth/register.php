<?php
// Cek jika form sudah disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../../includes/config.php';

    // Ambil data dari form
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role = $_POST['role'];

    // Validasi data
    $errors = [];

    // Cek username
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    }

    // Cek password
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }

    // Cek konfirmasi password
    if ($password != $confirm_password) {
        $errors[] = "Konfirmasi password tidak sesuai";
    }

    // Cek nama lengkap
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi";
    }

    // Jika tidak ada error, simpan data ke database
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();

        // Cek apakah username sudah ada
        $query = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username sudah digunakan";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Simpan data user baru
            $query = "INSERT INTO users (username, password, nama_lengkap, role, created_at) 
                      VALUES (:username, :password, :nama_lengkap, :role, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':nama_lengkap', $nama_lengkap);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                // Redirect ke halaman login
                header("Location: /kopi/modules/auth/login.php?registered=1");
                exit;
            } else {
                $errors[] = "Terjadi kesalahan saat menyimpan data";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register - Kopiluvium</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <h2>Register</h2>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div style="color: red; margin-bottom: 15px;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo isset($username) ? $username : ''; ?>">
        </div>
        <br>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password">
        </div>
        <br>
        <div>
            <label for="confirm_password">Konfirmasi Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        <br>
        <div>
            <label for="nama_lengkap">Nama Lengkap:</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap"
                value="<?php echo isset($nama_lengkap) ? $nama_lengkap : ''; ?>">
        </div>
        <br>
        <div>
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="admin" <?php echo (isset($role) && $role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="manager" <?php echo (isset($role) && $role == 'manager') ? 'selected' : ''; ?>>Manager
                </option>
                <option value="bartender" <?php echo (isset($role) && $role == 'bartender') ? 'selected' : ''; ?>>
                    Bartender</option>
                <option value="kitchen" <?php echo (isset($role) && $role == 'kitchen') ? 'selected' : ''; ?>>Kitchen
                </option>
                <option value="kasir" <?php echo (isset($role) && $role == 'kasir') ? 'selected' : ''; ?>>Kasir</option>
                <option value="waiters" <?php echo (isset($role) && $role == 'waiters') ? 'selected' : ''; ?>>Waiters
                </option>
            </select>
        </div>
        <br>
        <div>
            <input type="submit" value="Register">
        </div>
    </form>

    <p>Sudah punya akun? <a href="/kopi/modules/auth/login.php">Login</a></p>
</body>

</html>