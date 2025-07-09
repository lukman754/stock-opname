<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['kepala_toko', 'manager_keuangan', 'kasir']); // Hanya roles ini yang dapat mencetak laporan

$db = new Database();
$conn = $db->getConnection();

// Ambil ID barang masuk
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Validasi ID
if (empty($id)) {
    echo "ID tidak valid!";
    exit;
}

// Ambil data header barang masuk
$query = "SELECT bm.*, l.nama_lokasi, u.nama_lengkap as nama_user
          FROM barang_masuk bm
          JOIN lokasi l ON bm.lokasi_id = l.id
          JOIN users u ON bm.user_id = u.id
          WHERE bm.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo "Data barang masuk tidak ditemukan!";
    exit;
}

$barang_masuk = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail barang masuk
$query = "SELECT bmd.*, b.kode_barang, b.nama_barang, 
          su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
          FROM barang_masuk_detail bmd
          JOIN barang b ON bmd.barang_id = b.id
          LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
          LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
          WHERE bmd.barang_masuk_id = :barang_masuk_id
          ORDER BY b.nama_barang ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':barang_masuk_id', $id);
$stmt->execute();
$detail_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Cetak Barang Masuk - <?php echo $barang_masuk['nomor_transaksi']; ?></title>
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

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
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
            display: flex;
            justify-content: space-between;
        }

        .signature {
            width: 30%;
            text-align: center;
        }

        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
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

    <div class="info">
        <div class="info-row">
            <div class="info-label">No. Transaksi</div>
            <div class="info-value">: <?php echo $barang_masuk['nomor_transaksi']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal</div>
            <div class="info-value">: <?php echo formatDate($barang_masuk['tanggal']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Lokasi Penerima</div>
            <div class="info-value">: <?php echo $barang_masuk['nama_lokasi']; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Keterangan</div>
            <div class="info-value">: <?php echo $barang_masuk['keterangan'] ?: '-'; ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Kode Barang</th>
                <th width="45%">Nama Barang</th>
                <th width="15%">Jumlah Utuh</th>
                <th width="15%">Jumlah Pecahan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($detail_list)): ?>
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data detail barang</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($detail_list as $detail): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo $detail['kode_barang']; ?></td>
                        <td><?php echo $detail['nama_barang']; ?></td>
                        <td><?php echo $detail['jumlah_utuh'] . ' ' . $detail['satuan_utuh']; ?></td>
                        <td><?php echo $detail['jumlah_pecahan'] > 0 && $detail['satuan_pecahan'] ? $detail['jumlah_pecahan'] . ' ' . $detail['satuan_pecahan'] : '-'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Penerima</p>
            <div class="signature-line"></div>
            <p><?php echo $barang_masuk['nama_user']; ?></p>
        </div>

        <div class="signature">
            <p>Mengetahui</p>
            <div class="signature-line"></div>
            <p>Manager</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <script>
        window.onload = function () {
            // Auto print when page loads
            window.print();
        }
    </script>
</body>

</html>