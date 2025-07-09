<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        showAlert('Username dan password harus diisi', 'danger');
        redirect('../../index.php');
    }

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

            redirect('../../dashboard.php');
        } else {
            showAlert('Password tidak valid', 'danger');
            redirect('../../index.php');
        }
    } else {
        showAlert('Username tidak ditemukan', 'danger');
        redirect('../../index.php');
    }
} else {
    redirect('../../index.php');
}
