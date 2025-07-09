<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'manager_keuangan', 'kasir']); // Hanya roles ini yang dapat mencetak laporan

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan tanggal dan lokasi
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';

// Query untuk mengambil data barang masuk
$query = "SELECT bm.id, bm.nomor_transaksi, bm.tanggal, bm.keterangan, 
          u.nama_lengkap as nama_user, l.nama_lokasi
          FROM barang_masuk bm
          JOIN users u ON bm.user_id = u.id
          JOIN lokasi l ON bm.lokasi_id = l.id
          WHERE bm.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir";

$params = [
    ':tanggal_awal' => $tanggal_awal,
    ':tanggal_akhir' => $tanggal_akhir
];

if (!empty($lokasi_id)) {
    $query .= " AND bm.lokasi_id = :lokasi_id";
    $params[':lokasi_id'] = $lokasi_id;
}

$query .= " ORDER BY bm.tanggal, bm.id";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$barang_masuk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil informasi filter
$lokasi_name = '';
if (!empty($lokasi_id)) {
    $query_lokasi = "SELECT nama_lokasi FROM lokasi WHERE id = :id";
    $stmt = $conn->prepare($query_lokasi);
    $stmt->bindParam(':id', $lokasi_id);
    $stmt->execute();
    $lokasi = $stmt->fetch(PDO::FETCH_ASSOC);
    $lokasi_name = $lokasi ? $lokasi['nama_lokasi'] : '';
}

// Ambil informasi perusahaan
$nama_perusahaan = "Kopiluvium";
$alamat_perusahaan = "Jl. Kopi No. 123, Jakarta";
$telepon_perusahaan = "021-1234567";

// Tidak perlu menghitung total nilai karena tidak ada kolom total_nilai
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Masuk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .header p {
            margin: 5px 0;
        }
        .info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .footer p {
            margin: 5px 0;
        }
        .page-break {
            page-break-after: always;
        }
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $nama_perusahaan; ?></h1>
        <p><?php echo $alamat_perusahaan; ?></p>
        <p>Telp: <?php echo $telepon_perusahaan; ?></p>
    </div>
    
    <h2 style="text-align: center;">LAPORAN BARANG MASUK</h2>
    <p style="text-align: center;">Periode: <?php echo formatDate($tanggal_awal); ?> s/d <?php echo formatDate($tanggal_akhir); ?></p>
    
    <div class="info">
        <?php if (!empty($lokasi_name)): ?>
        <div class="info-row">
            <div class="info-label">Lokasi</div>
            <div class="info-value">: <?php echo $lokasi_name; ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($barang_masuk_list)): ?>
    <p class="text-center">Tidak ada data barang masuk pada periode tersebut.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">No. Transaksi</th>
                <th width="15%">Tanggal</th>
                <th width="25%">Lokasi</th>
                <th width="20%">Petugas</th>
                <th width="15%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($barang_masuk_list as $bm): ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $bm['nomor_transaksi']; ?></td>
                <td><?php echo formatDate($bm['tanggal']); ?></td>
                <td><?php echo $bm['nama_lokasi']; ?></td>
                <td><?php echo $bm['nama_user']; ?></td>
                <td><?php echo $bm['keterangan'] ?: '-'; ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Tidak perlu menampilkan total karena tidak ada nilai yang dijumlahkan -->
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
    
    <div class="footer">
        <p>Dicetak oleh: <?php echo $_SESSION['nama_lengkap']; ?></p>
        <p>Tanggal Cetak: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></p>
    </div>
    
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    
    <script>
        window.onload = function() {
            // Auto print when page loads
            window.print();
        }
    </script>
</body>
</html>
