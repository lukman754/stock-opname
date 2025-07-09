<?php
require_once 'config.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function checkRole($allowed_roles)
{
    if (!isLoggedIn()) {
        header("Location: " . baseUrl('index.php'));
        exit;
    }

    $user_role = $_SESSION['role'];

    // Check if user has direct access with current role
    if (in_array($user_role, $allowed_roles)) {
        return true;
    }

    header("Location: " . baseUrl('unauthorized.php'));
    exit;
}

function generateTransactionNumber($prefix)
{
    $date = date('Ymd');
    $random = mt_rand(1000, 9999);
    return $prefix . $date . $random;
}

function formatRupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatDate($date)
{
    if ($date) {
        $timestamp = strtotime($date);
        return date('d/m/Y', $timestamp);
    }
    return '';
}

function formatDatetime($datetime)
{
    if ($datetime) {
        $timestamp = strtotime($datetime);
        return date('d/m/Y H:i', $timestamp);
    }
    return '';
}

function baseUrl($path = '')
{
    return '/kopi' . ($path ? '/' . ltrim($path, '/') : '');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function showAlert($message, $type = 'success')
{
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayAlert()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $type = $alert['type'];
        $message = $alert['message'];

        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";

        unset($_SESSION['alert']);
    }
}

function getStockByBarangAndLokasi($barang_id, $lokasi_id, $date = null)
{
    $db = new Database();
    $conn = $db->getConnection();

    if ($date === null) {
        $date = date('Y-m-d');
    }

    // Cek struktur tabel stock
    $query_check_columns = "SHOW COLUMNS FROM stock";
    $stmt_check_columns = $conn->prepare($query_check_columns);
    $stmt_check_columns->execute();
    $columns = $stmt_check_columns->fetchAll(PDO::FETCH_COLUMN);

    // Tentukan nama kolom yang benar berdasarkan struktur tabel
    $qty_whole_column = in_array('qty_whole', $columns) ? 'qty_whole' : 'jumlah_utuh';
    $qty_fraction_column = in_array('qty_fraction', $columns) ? 'qty_fraction' : 'jumlah_pecahan';

    $query = "SELECT $qty_whole_column, $qty_fraction_column FROM stock 
              WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id 
              ORDER BY updated_at DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':barang_id', $barang_id);
    $stmt->bindParam(':lokasi_id', $lokasi_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Mengembalikan dengan nama kolom yang konsisten untuk digunakan di kode lain
        return [
            'jumlah_utuh' => $result[$qty_whole_column],
            'jumlah_pecahan' => $result[$qty_fraction_column]
        ];
    } else {
        return ['jumlah_utuh' => 0, 'jumlah_pecahan' => 0];
    }
}

/**
 * Fungsi untuk mengupdate stok barang
 * 
 * @param PDO $conn Koneksi database
 * @param int $barang_id ID barang
 * @param int $lokasi_id ID lokasi
 * @param float $jumlah_utuh Jumlah utuh
 * @param float $jumlah_pecahan Jumlah pecahan
 * @param string $jenis Jenis transaksi (masuk/keluar)
 * @param string|null $tanggal Tanggal transaksi (opsional)
 * @return bool True jika berhasil, false jika gagal
 */
function updateStock($conn, $barang_id, $lokasi_id, $jumlah_utuh, $jumlah_pecahan, $jenis = 'masuk', $tanggal = null)
{
    $tanggal = $tanggal ?? date('Y-m-d');

    // Cek apakah stok sudah ada
    $query = "SELECT * FROM stock WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':barang_id', $barang_id);
    $stmt->bindParam(':lokasi_id', $lokasi_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Update stok yang sudah ada
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($jenis == 'stock_opname') {
            // Untuk stock opname, langsung update ke nilai yang diberikan
            $new_jumlah_utuh = $jumlah_utuh;
            $new_jumlah_pecahan = $jumlah_pecahan;
        } else {
            // Untuk transaksi normal (masuk/keluar), tambah/kurang dari stok yang ada
            $multiplier = ($jenis == 'masuk') ? 1 : -1;
            $new_jumlah_utuh = $stock['jumlah_utuh'] + ($jumlah_utuh * $multiplier);
            $new_jumlah_pecahan = $stock['jumlah_pecahan'] + ($jumlah_pecahan * $multiplier);
        }

        // Validasi stok tidak boleh negatif
        if ($new_jumlah_utuh < 0 || $new_jumlah_pecahan < 0) {
            throw new Exception("Stok tidak boleh negatif!");
        }

        $query = "UPDATE stock SET 
                  jumlah_utuh = :jumlah_utuh, 
                  jumlah_pecahan = :jumlah_pecahan,
                  tanggal_update = :tanggal_update,
                  updated_at = NOW() 
                  WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':jumlah_utuh', $new_jumlah_utuh);
        $stmt->bindParam(':jumlah_pecahan', $new_jumlah_pecahan);
        $stmt->bindParam(':tanggal_update', $tanggal);
        $stmt->bindParam(':barang_id', $barang_id);
        $stmt->bindParam(':lokasi_id', $lokasi_id);
        return $stmt->execute();
    } else {
        // Insert stok baru jika jenis = masuk atau stock_opname
        if ($jenis == 'masuk' || $jenis == 'stock_opname') {
            // Validasi stok tidak boleh negatif
            if ($jumlah_utuh < 0 || $jumlah_pecahan < 0) {
                throw new Exception("Stok tidak boleh negatif!");
            }

            $query = "INSERT INTO stock (barang_id, lokasi_id, jumlah_utuh, jumlah_pecahan, tanggal_update, created_at)
                      VALUES (:barang_id, :lokasi_id, :jumlah_utuh, :jumlah_pecahan, :tanggal_update, NOW())";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':barang_id', $barang_id);
            $stmt->bindParam(':lokasi_id', $lokasi_id);
            $stmt->bindParam(':jumlah_utuh', $jumlah_utuh);
            $stmt->bindParam(':jumlah_pecahan', $jumlah_pecahan);
            $stmt->bindParam(':tanggal_update', $tanggal);
            return $stmt->execute();
        } else {
            // Jika jenis = keluar dan stok tidak ada, throw exception
            throw new Exception("Stok barang tidak ditemukan di lokasi ini!");
        }
    }
}

function getSatuanName($id)
{
    if (!$id)
        return '';

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT nama_satuan FROM satuan WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nama_satuan'];
    }

    return '';
}

function getLokasiName($id)
{
    if (!$id)
        return '';

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT nama_lokasi FROM lokasi WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nama_lokasi'];
    }

    return '';
}

function getKategoriName($id)
{
    if (!$id)
        return '';

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT nama_kategori FROM kategori_barang WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nama_kategori'];
    }

    return '';
}

function getUserName($id)
{
    if (!$id)
        return '';

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT nama_lengkap FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nama_lengkap'];
    }

    return '';
}

function getBarangName($id)
{
    if (!$id)
        return '';

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT nama_barang FROM barang WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nama_barang'];
    }

    return '';
}

function getBarangInfo($id)
{
    if (!$id)
        return null;

    $db = new Database();
    $conn = $db->getConnection();

    $query = "SELECT b.*, k.nama_kategori, su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan 
              FROM barang b
              LEFT JOIN kategori_barang k ON b.kategori_id = k.id
              LEFT JOIN satuan su ON b.satuan_utuh_id = su.id
              LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
              WHERE b.id = :id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return null;
}

/**
 * Menghasilkan kode barang otomatis berdasarkan inisial nama barang
 * Format: [INISIAL]-[NOMOR URUT 3 DIGIT]
 * Contoh: KPS-001 untuk "Kopi Susu"
 *
 * @param string $nama_barang Nama barang yang akan dibuatkan kodenya
 * @return string Kode barang yang dihasilkan
 */
function generateItemCode($nama_barang)
{
    $db = new Database();
    $conn = $db->getConnection();

    // Ambil inisial dari setiap kata dalam nama barang
    $words = explode(' ', strtoupper($nama_barang));
    $initials = '';

    // Ambil huruf pertama dari setiap kata, maksimal 3 huruf
    foreach ($words as $word) {
        if (strlen($word) > 0 && strlen($initials) < 3) {
            $initials .= $word[0];
        }
    }

    // Jika inisial kurang dari 2 karakter, tambahkan karakter dari kata pertama
    if (strlen($initials) < 2 && isset($words[0]) && strlen($words[0]) > 1) {
        $initials .= $words[0][1];
    }

    // Jika masih kurang dari 2 karakter, tambahkan 'X'
    if (strlen($initials) < 2) {
        $initials .= 'X';
    }

    // Cari kode dengan inisial yang sama dan ambil nomor urut tertinggi
    $query = "SELECT kode_barang FROM barang WHERE kode_barang LIKE :prefix ORDER BY kode_barang DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $prefix = $initials . '-%';
    $stmt->bindParam(':prefix', $prefix);
    $stmt->execute();

    $next_num = 1;
    if ($stmt->rowCount() > 0) {
        $last_code = $stmt->fetch(PDO::FETCH_ASSOC)['kode_barang'];
        $last_num = (int) substr($last_code, -3);
        $next_num = $last_num + 1;
    }

    // Format nomor urut dengan leading zeros
    $formatted_num = sprintf('%03d', $next_num);

    // Hasilkan kode final
    return $initials . '-' . $formatted_num;
}

function hasRole($roles)
{
    if (!isset($_SESSION['role']))
        return false;

    $user_role = $_SESSION['role'];

    // Check if user has direct access with current role
    if (in_array($user_role, $roles)) {
        return true;
    }

    return false;
}

function getUserLocationId()
{
    return $_SESSION['lokasi_id'] ?? null;
}

function hasGlobalAccess()
{
    // User dengan lokasi_id NULL memiliki akses global
    if (getUserLocationId() === null) {
        return true;
    }
    // Role tertentu juga memiliki akses global
    $global_roles = ['kepala_toko', 'manager_keuangan', 'staf_gudang'];
    return in_array($_SESSION['role'] ?? '', $global_roles);
}

function getLocationFilter($table_alias = '')
{
    // Jika user memiliki akses global (lokasi_id NULL atau role global), tidak perlu filter
    if (hasGlobalAccess()) {
        return '';
    }
    $alias = $table_alias ? $table_alias . '.' : '';
    return "WHERE {$alias}lokasi_id = :lokasi_id";
}

function bindLocationParam($stmt)
{
    // Hanya bind parameter jika user tidak memiliki akses global
    if (!hasGlobalAccess()) {
        $user_location_id = getUserLocationId();
        if ($user_location_id !== null) {
            $stmt->bindParam(':lokasi_id', $user_location_id);
        }
    }
    return $stmt;
}
