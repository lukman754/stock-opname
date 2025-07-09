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

// Query untuk mengambil data stock opname
$query = "SELECT so.id, so.tanggal, so.status, so.jam_mulai, so.jam_selesai,
          l.nama_lokasi, u.nama_lengkap as nama_user
          FROM stock_opname so
          JOIN lokasi l ON so.lokasi_id = l.id
          JOIN users u ON so.user_id = u.id
          WHERE so.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir
          AND so.status = 'selesai'";

$params = [
    ':tanggal_awal' => $tanggal_awal,
    ':tanggal_akhir' => $tanggal_akhir
];

if (!empty($lokasi_id)) {
    $query .= " AND so.lokasi_id = :lokasi_id";
    $params[':lokasi_id'] = $lokasi_id;
}

$query .= " ORDER BY so.tanggal DESC, so.id DESC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$stock_opname_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stock Opname</title>
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
    
    <h2 style="text-align: center;">LAPORAN STOCK OPNAME</h2>
    <p style="text-align: center;">Periode: <?php echo formatDate($tanggal_awal); ?> s/d <?php echo formatDate($tanggal_akhir); ?></p>
    
    <div class="info">
        <?php if (!empty($lokasi_name)): ?>
        <div class="info-row">
            <div class="info-label">Lokasi</div>
            <div class="info-value">: <?php echo $lokasi_name; ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($stock_opname_list)): ?>
    <p class="text-center">Tidak ada data stock opname pada periode tersebut.</p>
    <?php else: ?>
        <?php foreach ($stock_opname_list as $index => $so): ?>
            <h3>Stock Opname #<?php echo $index + 1; ?></h3>
            <div class="info">
                <div class="info-row">
                    <div class="info-label">Tanggal</div>
                    <div class="info-value">: <?php echo formatDate($so['tanggal']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Lokasi</div>
                    <div class="info-value">: <?php echo $so['nama_lokasi']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Petugas</div>
                    <div class="info-value">: <?php echo $so['nama_user']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Waktu</div>
                    <div class="info-value">: <?php echo $so['jam_mulai'] . ' - ' . $so['jam_selesai']; ?></div>
                </div>
            </div>
            
            <?php
            // Ambil detail stock opname
            $query_detail = "SELECT sod.*, b.kode_barang, b.nama_barang, 
                            kb.nama_kategori, su.nama_satuan as satuan_utuh, 
                            sp.nama_satuan as satuan_pecahan
                            FROM stock_opname_details sod
                            JOIN barang b ON sod.barang_id = b.id
                            JOIN kategori_barang kb ON b.kategori_id = kb.id
                            JOIN satuan su ON b.satuan_utuh_id = su.id
                            LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                            WHERE sod.stock_opname_id = :id
                            ORDER BY kb.nama_kategori, b.nama_barang";
            $stmt_detail = $conn->prepare($query_detail);
            $stmt_detail->bindParam(':id', $so['id']);
            $stmt_detail->execute();
            $details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" width="5%">No</th>
                        <th rowspan="2" width="10%">Kode</th>
                        <th rowspan="2" width="20%">Nama Barang</th>
                        <th rowspan="2" width="15%">Kategori</th>
                        <th colspan="2" width="20%">Stok Sistem</th>
                        <th colspan="2" width="20%">Stok Aktual</th>
                        <th colspan="2" width="10%">Selisih</th>
                    </tr>
                    <tr>
                        <th>Utuh</th>
                        <th>Pecahan</th>
                        <th>Utuh</th>
                        <th>Pecahan</th>
                        <th>Utuh</th>
                        <th>Pecahan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="10" class="text-center">Tidak ada data detail stock opname</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1; 
                        $current_kategori = '';
                        foreach ($details as $detail): 
                            // Jika kategori berubah, buat subheader
                            if ($detail['nama_kategori'] != $current_kategori):
                                $current_kategori = $detail['nama_kategori'];
                        ?>
                            <tr style="background-color: #e9ecef;">
                                <td colspan="10"><strong>Kategori: <?php echo $current_kategori; ?></strong></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo $detail['kode_barang']; ?></td>
                            <td><?php echo $detail['nama_barang']; ?></td>
                            <td><?php echo $detail['nama_kategori']; ?></td>
                            <td><?php echo $detail['jumlah_sistem_utuh'] . ' ' . $detail['satuan_utuh']; ?></td>
                            <td><?php echo $detail['jumlah_sistem_pecahan'] > 0 && $detail['satuan_pecahan'] ? $detail['jumlah_sistem_pecahan'] . ' ' . $detail['satuan_pecahan'] : '-'; ?></td>
                            <td><?php echo $detail['actual_qty_whole'] . ' ' . $detail['satuan_utuh']; ?></td>
                            <td><?php echo $detail['actual_qty_fraction'] > 0 && $detail['satuan_pecahan'] ? $detail['actual_qty_fraction'] . ' ' . $detail['satuan_pecahan'] : '-'; ?></td>
                            <td class="<?php echo $detail['selisih_utuh'] < 0 ? 'text-danger' : ($detail['selisih_utuh'] > 0 ? 'text-success' : ''); ?>">
                                <?php echo $detail['selisih_utuh']; ?>
                            </td>
                            <td class="<?php echo $detail['selisih_pecahan'] < 0 ? 'text-danger' : ($detail['selisih_pecahan'] > 0 ? 'text-success' : ''); ?>">
                                <?php echo $detail['selisih_pecahan'] != 0 ? $detail['selisih_pecahan'] : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($index < count($stock_opname_list) - 1): ?>
            <div class="page-break"></div>
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
