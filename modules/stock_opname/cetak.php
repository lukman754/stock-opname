<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['admin', 'manager', 'bartender', 'kitchen', 'kasir', 'staf_gudang']);

if (!isset($_GET['id'])) {
    echo "ID stock opname tidak valid";
    exit;
}

$id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();

// Ambil data stock opname
$query = "SELECT so.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM stock_opname so
          JOIN lokasi l ON so.lokasi_id = l.id
          JOIN users u ON so.user_id = u.id
          WHERE so.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo "Data stock opname tidak ditemukan";
    exit;
}

$stock_opname = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail stock opname
$query_detail = "SELECT sod.*, b.kode_barang, b.nama_barang, k.nama_kategori,
                su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
                FROM stock_opname_details sod
                JOIN barang b ON sod.barang_id = b.id
                JOIN kategori_barang k ON b.kategori_id = k.id
                JOIN satuan su ON b.satuan_utuh_id = su.id
                LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                WHERE sod.stock_opname_id = :stock_opname_id
                ORDER BY k.nama_kategori, b.nama_barang ASC";
$stmt_detail = $conn->prepare($query_detail);
$stmt_detail->bindParam(':stock_opname_id', $id);
$stmt_detail->execute();
$details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

// Hitung total item dan selisih
$total_items = count($details);
$total_selisih_plus = 0;
$total_selisih_minus = 0;

foreach ($details as $detail) {
    if ($detail['selisih_utuh'] > 0) {
        $total_selisih_plus++;
    } elseif ($detail['selisih_utuh'] < 0) {
        $total_selisih_minus++;
    }

    if ($detail['selisih_pecahan'] > 0) {
        $total_selisih_plus++;
    } elseif ($detail['selisih_pecahan'] < 0) {
        $total_selisih_minus++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Stock Opname - Kopiluvium Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
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

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .info-table {
            width: 50%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 3px;
            border: none;
        }

        .info-table tr td:first-child {
            width: 30%;
        }

        .info-table tr td:nth-child(2) {
            width: 5%;
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

        .text-danger {
            color: #dc3545;
        }

        .text-success {
            color: #28a745;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            width: 30%;
        }

        .summary-item h3 {
            margin: 0;
            font-size: 24px;
        }

        .summary-item p {
            margin: 5px 0 0 0;
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
        <p>Laporan Stock Opname</p>
        <p>Tanggal: <?php echo formatDate($stock_opname['tanggal']); ?></p>
    </div>

    <table class="info-table">
        <tr>
            <td>Lokasi</td>
            <td>:</td>
            <td><?php echo $stock_opname['nama_lokasi']; ?></td>
        </tr>
        <tr>
            <td>Petugas</td>
            <td>:</td>
            <td><?php echo $stock_opname['nama_user']; ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td>:</td>
            <td>
                <?php
                if ($stock_opname['status'] == 'draft') {
                    echo "Draft";
                } elseif ($stock_opname['status'] == 'selesai') {
                    echo "Selesai";
                } else {
                    echo "Batal";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>Jam Mulai</td>
            <td>:</td>
            <td><?php echo $stock_opname['jam_mulai'] ? date('H:i', strtotime($stock_opname['jam_mulai'])) : '-'; ?>
            </td>
        </tr>
        <tr>
            <td>Jam Selesai</td>
            <td>:</td>
            <td><?php echo $stock_opname['jam_selesai'] ? date('H:i', strtotime($stock_opname['jam_selesai'])) : '-'; ?>
            </td>
        </tr>
        <tr>
            <td>Keterangan</td>
            <td>:</td>
            <td><?php echo $stock_opname['keterangan'] ?: '-'; ?></td>
        </tr>
    </table>

    <div class="summary">
        <div class="summary-item">
            <h3><?php echo $total_items; ?></h3>
            <p>Total Item</p>
        </div>
        <div class="summary-item">
            <h3 class="text-success"><?php echo $total_selisih_plus; ?></h3>
            <p>Selisih Lebih</p>
        </div>
        <div class="summary-item">
            <h3 class="text-danger"><?php echo $total_selisih_minus; ?></h3>
            <p>Selisih Kurang</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok Sistem (Utuh)</th>
                <th>Stok Sistem (Pecahan)</th>
                <th>Stok Fisik (Utuh)</th>
                <th>Stok Fisik (Pecahan)</th>
                <th>Selisih (Utuh)</th>
                <th>Selisih (Pecahan)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?>
                <tr>
                    <td colspan="11" class="no-data">Tidak ada data detail stock opname</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($details as $detail): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $detail['kode_barang']; ?></td>
                        <td><?php echo $detail['nama_barang']; ?></td>
                        <td><?php echo $detail['nama_kategori']; ?></td>
                        <td><?php echo $detail['jumlah_sistem_utuh'] . ' ' . $detail['satuan_utuh']; ?></td>
                        <td>
                            <?php if ($detail['jumlah_sistem_pecahan'] > 0 && $detail['satuan_pecahan']): ?>
                                <?php echo $detail['jumlah_sistem_pecahan'] . ' ' . $detail['satuan_pecahan']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $detail['actual_qty_whole'] . ' ' . $detail['satuan_utuh']; ?></td>
                        <td>
                            <?php if ($detail['satuan_pecahan']): ?>
                                <?php echo $detail['actual_qty_fraction'] . ' ' . $detail['satuan_pecahan']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td
                            class="<?php echo $detail['selisih_utuh'] < 0 ? 'text-danger' : ($detail['selisih_utuh'] > 0 ? 'text-success' : ''); ?>">
                            <?php echo $detail['selisih_utuh'] . ' ' . $detail['satuan_utuh']; ?>
                        </td>
                        <td
                            class="<?php echo $detail['selisih_pecahan'] < 0 ? 'text-danger' : ($detail['selisih_pecahan'] > 0 ? 'text-success' : ''); ?>">
                            <?php if ($detail['satuan_pecahan']): ?>
                                <?php echo $detail['selisih_pecahan'] . ' ' . $detail['satuan_pecahan']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $detail['keterangan'] ?: '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak oleh: <?php echo $_SESSION['nama_lengkap']; ?></p>
        <p>Tanggal Cetak: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <button onclick="window.print()" style="padding: 10px 20px; margin-top: 20px;">Cetak</button>

    <script>
        // Auto print when page loads
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>