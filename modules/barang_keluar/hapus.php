<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat menghapus data barang keluar

// Redirect ke proses.php dengan action hapus
$id = isset($_GET['id']) ? $_GET['id'] : 0;

if (empty($id)) {
    showAlert("ID tidak valid!", "danger");
    header("Location: index.php");
    exit;
}

header("Location: proses.php?action=hapus&id=" . $id);
exit;
?>
