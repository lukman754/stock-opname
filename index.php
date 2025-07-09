<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['lokasi_id'] = $user['lokasi_id'];

                redirect('dashboard.php');
            } else {
                $error = 'Password tidak valid';
            }
        } else {
            $error = 'Username tidak ditemukan';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kopiluvium Inventory</title>
    <!-- Bootstrap CSS dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome dari CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }

        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }

        .form-signin .card {
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .form-signin .card-header {
            background-color: #343a40;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }

        .form-signin .card-body {
            padding: 30px;
        }

        .form-signin .form-floating {
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #343a40;
            border-color: #343a40;
        }

        .btn-primary:hover {
            background-color: #23272b;
            border-color: #23272b;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Kopiluvium Inventory</h4>
                <p class="mb-0">Sistem Manajemen Inventaris</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                            required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required>
                        <label for="password">Password</label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Login</button>
                </form>
            </div>
        </div>
        <p class="mt-3 text-center text-muted">&copy; <?php echo date('Y'); ?> Kopiluvium</p>
    </main>

    <!-- Bootstrap JS Bundle dengan Popper dari CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>