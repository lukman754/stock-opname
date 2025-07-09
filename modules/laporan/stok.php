<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'manager_keuangan', 'kasir']); // Hanya roles ini yang dapat mencetak laporan

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan lokasi dan kategori
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';
$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';

// Query untuk mengambil data stok
$query = "SELECT b.kode_barang, b.nama_barang, kb.nama_kategori, l.nama_lokasi,
          s.jumlah_utuh, s.jumlah_pecahan, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM stock s
          JOIN barang b ON s.barang_id = b.id
          JOIN kategori_barang kb ON b.kategori_id = kb.id
          JOIN lokasi l ON s.lokasi_id = l.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE 1=1";

$params = [];

if (!empty($lokasi_id)) {
    $query .= " AND s.lokasi_id = :lokasi_id";
    $params[':lokasi_id'] = $lokasi_id;
}

if (!empty($kategori_id)) {
    $query .= " AND b.kategori_id = :kategori_id";
    $params[':kategori_id'] = $kategori_id;
}

$query .= " AND (s.jumlah_utuh > 0 OR s.jumlah_pecahan > 0)";
$query .= " ORDER BY l.nama_lokasi, kb.nama_kategori, b.nama_barang";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$stok_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$kategori_name = '';
if (!empty($kategori_id)) {
    $query_kategori = "SELECT nama_kategori FROM kategori_barang WHERE id = :id";
    $stmt = $conn->prepare($query_kategori);
    $stmt->bindParam(':id', $kategori_id);
    $stmt->execute();
    $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
    $kategori_name = $kategori ? $kategori['nama_kategori'] : '';
}

// Ambil informasi perusahaan
$nama_perusahaan = "Kopiluvium";
$alamat_perusahaan = "Jl. Kopi No. 123, Jakarta";
$telepon_perusahaan = "021-1234567";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Barang</title>
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
    
    <h2 style="text-align: center;">LAPORAN STOK BARANG</h2>
    <p style="text-align: center;">Per Tanggal: <?php echo formatDate(date('Y-m-d')); ?></p>
    
    <div class="info">
        <?php if (!empty($lokasi_name)): ?>
        <div class="info-row">
            <div class="info-label">Lokasi</div>
            <div class="info-value">: <?php echo $lokasi_name; ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($kategori_name)): ?>
        <div class="info-row">
            <div class="info-label">Kategori</div>
            <div class="info-value">: <?php echo $kategori_name; ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($stok_list)): ?>
    <p class="text-center">Tidak ada data stok yang tersedia.</p>
    <?php else: ?>
        <?php
        $current_lokasi = '';
        $current_kategori = '';
        
        foreach ($stok_list as $index => $stok):
            // Jika lokasi berubah, buat header baru
            if ($stok['nama_lokasi'] != $current_lokasi):
                if ($current_lokasi != ''):
                    // Tutup tabel sebelumnya jika bukan pertama kali
                    echo '</tbody></table>';
                endif;
                
                $current_lokasi = $stok['nama_lokasi'];
                $current_kategori = '';
        ?>
        <h3>Lokasi: <?php echo $current_lokasi; ?></h3>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Kode Barang</th>
                    <th width="30%">Nama Barang</th>
                    <th width="15%">Kategori</th>
                    <th width="15%">Stok Utuh</th>
                    <th width="20%">Stok Pecahan</th>
                </tr>
            </thead>
            <tbody>
        <?php
            endif;
            
            // Jika kategori berubah, buat subheader
            if ($stok['nama_kategori'] != $current_kategori):
                $current_kategori = $stok['nama_kategori'];
                $no = 1; // Reset nomor untuk setiap kategori
        ?>
                <tr style="background-color: #e9ecef;">
                    <td colspan="6"><strong>Kategori: <?php echo $current_kategori; ?></strong></td>
                </tr>
        <?php
            endif;
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $stok['kode_barang']; ?></td>
                <td><?php echo $stok['nama_barang']; ?></td>
                <td><?php echo $stok['nama_kategori']; ?></td>
                <td><?php echo $stok['jumlah_utuh'] . ' ' . $stok['satuan_utuh']; ?></td>
                <td><?php echo $stok['jumlah_pecahan'] > 0 && $stok['satuan_pecahan'] ? $stok['jumlah_pecahan'] . ' ' . $stok['satuan_pecahan'] : '-'; ?></td>
            </tr>
        <?php
            // Jika ini adalah item terakhir, tutup tabel
            if ($index == count($stok_list) - 1):
        ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endforeach; ?>
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
