<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'manager_keuangan', 'kasir']); // Hanya roles ini yang dapat mencetak laporan

$db = new Database();
$conn = $db->getConnection();

// Filter berdasarkan lokasi
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : '';

// Ambil data stok barang
$query = "SELECT s.*, b.kode_barang, b.nama_barang, k.nama_kategori, 
          l.nama_lokasi, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM stock s
          JOIN barang b ON s.barang_id = b.id
          JOIN kategori_barang k ON b.kategori_id = k.id
          JOIN lokasi l ON s.lokasi_id = l.id
          JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE b.is_aktif = 1";

if (!empty($lokasi_id)) {
    $query .= " AND s.lokasi_id = :lokasi_id";
}

$query .= " ORDER BY l.nama_lokasi, k.nama_kategori, b.nama_barang ASC";

$stmt = $conn->prepare($query);

if (!empty($lokasi_id)) {
    $stmt->bindParam(':lokasi_id', $lokasi_id);
}

$stmt->execute();
$stock_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil nama lokasi jika ada filter
$lokasi_name = 'Semua Lokasi';
if (!empty($lokasi_id)) {
    $query_lokasi = "SELECT nama_lokasi FROM lokasi WHERE id = :id";
    $stmt_lokasi = $conn->prepare($query_lokasi);
    $stmt_lokasi->bindParam(':id', $lokasi_id);
    $stmt_lokasi->execute();
    
    if ($stmt_lokasi->rowCount() > 0) {
        $lokasi = $stmt_lokasi->fetch(PDO::FETCH_ASSOC);
        $lokasi_name = $lokasi['nama_lokasi'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Data Stok Barang - Kopiluvium Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            padding: 0;
        }
        .header p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .footer p {
            margin: 5px 0;
        }
        .no-data {
            text-align: center;
            padding: 20px;
        }
        @media print {
            body {
                padding: 0;
            }
            button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>KOPILUVIUM INVENTORY</h2>
        <p>Laporan Data Stok Barang</p>
        <p>Lokasi: <?php echo $lokasi_name; ?></p>
        <p>Tanggal Cetak: <?php echo date('d/m/Y H:i'); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Stok Utuh</th>
                <th>Stok Pecahan</th>
                <th>Tanggal Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stock_list)): ?>
            <tr>
                <td colspan="8" class="no-data">Tidak ada data stok</td>
            </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($stock_list as $stock): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $stock['kode_barang']; ?></td>
                    <td><?php echo $stock['nama_barang']; ?></td>
                    <td><?php echo $stock['nama_kategori']; ?></td>
                    <td><?php echo $stock['nama_lokasi']; ?></td>
                    <td><?php echo $stock['jumlah_utuh'] . ' ' . $stock['satuan_utuh']; ?></td>
                    <td>
                        <?php if ($stock['jumlah_pecahan'] > 0 && $stock['satuan_pecahan']): ?>
                            <?php echo $stock['jumlah_pecahan'] . ' ' . $stock['satuan_pecahan']; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDate($stock['tanggal_update']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak oleh: <?php echo $_SESSION['nama_lengkap']; ?></p>
    </div>
    
    <button onclick="window.print()" style="padding: 10px 20px; margin-top: 20px;">Cetak</button>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
