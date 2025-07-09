<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Cek hak akses
checkRole(['staf_gudang']); // Hanya staf_gudang yang dapat input stock opname

$db = new Database();
$conn = $db->getConnection();

// Cek apakah ini edit atau tambah baru
$is_edit = isset($_GET['id']);
$stock_opname_id = $is_edit ? $_GET['id'] : null;
$stock_opname = null;
$selected_items = [];

// Ambil data lokasi untuk dropdown
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$stmt_lokasi = $conn->prepare($query_lokasi);
$stmt_lokasi->execute();
$lokasi_list = $stmt_lokasi->fetchAll(PDO::FETCH_ASSOC);

// Jika edit, ambil data stock opname
if ($is_edit) {
    // Ambil data stock opname
    $query = "SELECT so.*, l.nama_lokasi, u.nama_lengkap as nama_user
              FROM stock_opname so
              JOIN lokasi l ON so.lokasi_id = l.id
              JOIN users u ON so.user_id = u.id
              WHERE so.id = :id AND so.status = 'draft'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $stock_opname_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        showAlert('Data stock opname tidak ditemukan atau bukan draft', 'danger');
        redirect('index.php');
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
    $stmt_detail->bindParam(':stock_opname_id', $stock_opname_id);
    $stmt_detail->execute();
    $selected_items = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
}

// Filter berdasarkan lokasi
$lokasi_id = isset($_GET['lokasi_id']) ? $_GET['lokasi_id'] : ($is_edit ? $stock_opname['lokasi_id'] : '');

// Ambil data barang berdasarkan lokasi
$barang_list = [];
if (!empty($lokasi_id)) {
    $query_barang = "SELECT b.id, b.kode_barang, b.nama_barang, k.nama_kategori, 
                    s.jumlah_utuh, s.jumlah_pecahan, s.tanggal_update,
                    su.nama_satuan as satuan_utuh, sp.nama_satuan as satuan_pecahan
                    FROM barang b
                    JOIN kategori_barang k ON b.kategori_id = k.id
                    JOIN satuan su ON b.satuan_utuh_id = su.id
                    LEFT JOIN satuan sp ON b.satuan_pecahan_id = sp.id
                    JOIN stock s ON b.id = s.barang_id AND s.lokasi_id = :lokasi_id
                    WHERE b.is_aktif = 1
                    ORDER BY k.nama_kategori, b.nama_barang ASC";
    $stmt_barang = $conn->prepare($query_barang);
    $stmt_barang->bindParam(':lokasi_id', $lokasi_id);
    $stmt_barang->execute();
    $barang_list = $stmt_barang->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = $is_edit ? "Edit Stock Opname" : "Buat Stock Opname Baru";
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?php displayAlert(); ?>

<!-- Form Stock Opname -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-clipboard-check me-1"></i>
        <?php echo $is_edit ? "Edit Stock Opname" : "Buat Stock Opname Baru"; ?>
    </div>
    <div class="card-body">
        <form method="post" action="proses.php" id="stockOpnameForm">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $stock_opname_id; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="tanggal" class="form-label">Tanggal</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required
                        value="<?php echo $is_edit ? $stock_opname['tanggal'] : date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="lokasi_id" class="form-label">Lokasi</label>
                    <select class="form-select" id="lokasi_id" name="lokasi_id" required 
                            <?php echo $is_edit ? 'disabled' : ''; ?>>
                        <option value="">Pilih Lokasi</option>
                        <?php foreach ($lokasi_list as $lokasi): ?>
                            <option value="<?php echo $lokasi['id']; ?>" 
                                <?php echo $lokasi_id == $lokasi['id'] ? 'selected' : ''; ?>>
                                <?php echo $lokasi['nama_lokasi']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="lokasi_id" value="<?php echo $stock_opname['lokasi_id']; ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?php echo $is_edit ? $stock_opname['keterangan'] : ''; ?></textarea>
            </div>

            <?php if (empty($lokasi_id) && !$is_edit): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Silakan pilih lokasi terlebih dahulu untuk menampilkan daftar barang.
                </div>
            <?php elseif (empty($barang_list) && !empty($lokasi_id)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Tidak ada barang yang tersedia di lokasi ini.
                </div>
            <?php else: ?>
                <!-- Daftar Barang -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>Daftar Barang</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" id="checkAllBtn">
                                <i class="fas fa-check-square"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="uncheckAllBtn">
                                <i class="fas fa-square"></i> Batal Pilih
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Barang Terpilih -->
                    <div id="selectedItemsContainer" class="mb-4" style="display: <?php echo !empty($selected_items) ? 'block' : 'none'; ?>">
                        <div class="alert alert-primary">
                            <i class="fas fa-info-circle me-2"></i> Barang yang telah dipilih untuk stock opname:
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="selectedItemsTable">
                                <thead class="table-primary">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="10%">Kode</th>
                                        <th width="20%">Nama Barang</th>
                                        <th width="15%">Kategori</th>
                                        <th width="15%">Stok Sistem (Utuh)</th>
                                        <th width="15%">Stok Sistem (Pecahan)</th>
                                        <th width="15%">Stok Aktual (Utuh)</th>
                                        <th width="15%">Stok Aktual (Pecahan)</th>
                                        <th width="20%">Keterangan</th>
                                        <th width="5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedItemsList">
                                    <?php if (!empty($selected_items)): ?>
                                        <?php $no = 1; foreach ($selected_items as $item): ?>
                                            <tr data-id="<?php echo $item['barang_id']; ?>" class="selected-item">
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo $item['kode_barang']; ?></td>
                                                <td><?php echo $item['nama_barang']; ?></td>
                                                <td><?php echo $item['nama_kategori']; ?></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" 
                                                            name="barang[<?php echo $item['barang_id']; ?>][jumlah_sistem_utuh]" 
                                                            value="<?php echo $item['jumlah_sistem_utuh']; ?>" readonly>
                                                        <span class="input-group-text"><?php echo $item['satuan_utuh']; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" 
                                                            name="barang[<?php echo $item['barang_id']; ?>][jumlah_sistem_pecahan]" 
                                                            value="<?php echo $item['jumlah_sistem_pecahan']; ?>" readonly>
                                                        <span class="input-group-text"><?php echo $item['satuan_pecahan'] ?: '-'; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" 
                                                            name="barang[<?php echo $item['barang_id']; ?>][actual_qty_whole]" 
                                                            value="<?php echo $item['actual_qty_whole']; ?>" required min="0" step="0.01">
                                                        <span class="input-group-text"><?php echo $item['satuan_utuh']; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" 
                                                            name="barang[<?php echo $item['barang_id']; ?>][actual_qty_fraction]" 
                                                            value="<?php echo $item['actual_qty_fraction']; ?>" <?php echo $item['satuan_pecahan'] ? 'required' : 'readonly'; ?> min="0" step="0.01">
                                                        <span class="input-group-text"><?php echo $item['satuan_pecahan'] ?: '-'; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                        name="barang[<?php echo $item['barang_id']; ?>][keterangan]" 
                                                        value="<?php echo $item['keterangan']; ?>">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-item">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tabel Semua Barang -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover datatable">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                        </div>
                                    </th>
                                    <th width="10%">Kode</th>
                                    <th width="25%">Nama Barang</th>
                                    <th width="15%">Kategori</th>
                                    <th width="15%">Stok Sistem (Utuh)</th>
                                    <th width="15%">Stok Sistem (Pecahan)</th>
                                    <th width="15%">Terakhir Opname</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($barang_list as $barang): 
                                    // Skip if already selected
                                    $already_selected = false;
                                    if (!empty($selected_items)) {
                                        foreach ($selected_items as $selected) {
                                            if ($selected['barang_id'] == $barang['id']) {
                                                $already_selected = true;
                                                break;
                                            }
                                        }
                                    }
                                    if ($already_selected) continue;
                                ?>
                                    <tr data-id="<?php echo $barang['id']; ?>" 
                                        data-kode="<?php echo $barang['kode_barang']; ?>"
                                        data-nama="<?php echo $barang['nama_barang']; ?>"
                                        data-kategori="<?php echo $barang['nama_kategori']; ?>"
                                        data-stok-utuh="<?php echo $barang['jumlah_utuh'] ?? 0; ?>"
                                        data-stok-pecahan="<?php echo $barang['jumlah_pecahan'] ?? 0; ?>"
                                        data-satuan-utuh="<?php echo $barang['satuan_utuh']; ?>"
                                        data-satuan-pecahan="<?php echo $barang['satuan_pecahan'] ?? ''; ?>">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input item-checkbox" type="checkbox" value="<?php echo $barang['id']; ?>">
                                            </div>
                                        </td>
                                        <td><?php echo $barang['kode_barang']; ?></td>
                                        <td><?php echo $barang['nama_barang']; ?></td>
                                        <td><?php echo $barang['nama_kategori']; ?></td>
                                        <td><?php echo number_format($barang['jumlah_utuh'] ?? 0, 2); ?> <?php echo $barang['satuan_utuh']; ?></td>
                                        <td>
                                            <?php if (!empty($barang['satuan_pecahan'])): ?>
                                                <?php echo number_format($barang['jumlah_pecahan'] ?? 0, 2); ?> <?php echo $barang['satuan_pecahan']; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($barang['tanggal_update']) && $barang['tanggal_update'] != '0000-00-00' 
                                                ? formatDate($barang['tanggal_update']) 
                                                : 'Belum pernah'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-primary" id="addSelectedItems">
                        <i class="fas fa-plus"></i> Tambahkan Item Terpilih
                    </button>
                    
                    <div>
                        <button type="submit" name="save_draft" class="btn btn-warning">
                            <i class="fas fa-save"></i> Simpan sebagai Draft
                        </button>
                        <?php if ($is_edit && !empty($selected_items)): ?>
                            <button type="submit" name="finish" class="btn btn-success" 
                                    onclick="return confirm('Apakah Anda yakin ingin menyelesaikan stock opname ini? Status akan berubah menjadi Selesai dan data tidak dapat diubah lagi.')">
                                <i class="fas fa-check-circle"></i> Selesaikan Stock Opname
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Redirect to form with lokasi_id when lokasi is selected
    const lokasiSelect = document.getElementById('lokasi_id');
    if (lokasiSelect) {
        lokasiSelect.addEventListener('change', function() {
            const selectedLokasi = this.value;
            if (selectedLokasi) {
                window.location.href = 'form.php?lokasi_id=' + selectedLokasi;
            }
        });
    }

    // Check/Uncheck all items
    const checkAllBtn = document.getElementById('checkAllBtn');
    const uncheckAllBtn = document.getElementById('uncheckAllBtn');
    const checkAllCheckbox = document.getElementById('checkAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            checkAllCheckbox.checked = true;
        });
    }
    
    if (uncheckAllBtn) {
        uncheckAllBtn.addEventListener('click', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            checkAllCheckbox.checked = false;
        });
    }
    
    if (checkAllCheckbox) {
        checkAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }

    // Add selected items to the top table
    const addSelectedItemsBtn = document.getElementById('addSelectedItems');
    const selectedItemsList = document.getElementById('selectedItemsList');
    const selectedItemsContainer = document.getElementById('selectedItemsContainer');
    
    if (addSelectedItemsBtn && selectedItemsList) {
        addSelectedItemsBtn.addEventListener('click', function() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            
            if (checkedItems.length === 0) {
                alert('Silakan pilih barang terlebih dahulu');
                return;
            }
            
            let anyItemAdded = false;
            
            checkedItems.forEach(checkbox => {
                const itemRow = checkbox.closest('tr');
                const itemId = itemRow.dataset.id;
                
                // Check if item already exists in selected items
                const existingItem = document.querySelector(`.selected-item[data-id="${itemId}"]`);
                if (existingItem) {
                    return; // Skip if already added
                }
                
                anyItemAdded = true;
                
                // Get item data
                const itemData = {
                    id: itemId,
                    kode: itemRow.dataset.kode,
                    nama: itemRow.dataset.nama,
                    kategori: itemRow.dataset.kategori,
                    stokUtuh: itemRow.dataset.stokUtuh,
                    stokPecahan: itemRow.dataset.stokPecahan,
                    satuanUtuh: itemRow.dataset.satuanUtuh,
                    satuanPecahan: itemRow.dataset.satuanPecahan
                };
                
                // Create new row for selected items
                const newRow = document.createElement('tr');
                newRow.classList.add('selected-item');
                newRow.dataset.id = itemId;
                
                // Count existing rows for numbering
                const rowCount = selectedItemsList.querySelectorAll('tr').length + 1;
                
                newRow.innerHTML = `
                    <td>${rowCount}</td>
                    <td>${itemData.kode}</td>
                    <td>${itemData.nama}</td>
                    <td>${itemData.kategori}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" 
                                name="barang[${itemData.id}][jumlah_sistem_utuh]" 
                                value="${itemData.stokUtuh}" readonly>
                            <span class="input-group-text">${itemData.satuanUtuh}</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" 
                                name="barang[${itemData.id}][jumlah_sistem_pecahan]" 
                                value="${itemData.stokPecahan}" readonly>
                            <span class="input-group-text">${itemData.satuanPecahan || '-'}</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" 
                                name="barang[${itemData.id}][actual_qty_whole]" 
                                value="${itemData.stokUtuh}" required min="0" step="0.01">
                            <span class="input-group-text">${itemData.satuanUtuh}</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" 
                                name="barang[${itemData.id}][actual_qty_fraction]" 
                                value="${itemData.stokPecahan}" 
                                ${itemData.satuanPecahan ? 'required' : 'readonly'} min="0" step="0.01">
                            <span class="input-group-text">${itemData.satuanPecahan || '-'}</span>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" 
                            name="barang[${itemData.id}][keterangan]" value="">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
                
                selectedItemsList.appendChild(newRow);
                
                // Uncheck the item
                checkbox.checked = false;
            });
            
            if (anyItemAdded) {
                selectedItemsContainer.style.display = 'block';
                checkAllCheckbox.checked = false;
                
                // Add event listeners to remove buttons
                addRemoveItemListeners();
            }
        });
    }
    
    // Function to add event listeners to remove buttons
    function addRemoveItemListeners() {
        const removeButtons = document.querySelectorAll('.remove-item');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                row.remove();
                
                // Renumber rows
                const rows = selectedItemsList.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    row.querySelector('td:first-child').textContent = index + 1;
                });
                
                // Hide container if no items left
                if (rows.length === 0) {
                    selectedItemsContainer.style.display = 'none';
                }
            });
        });
    }
    
    // Initialize remove buttons
    addRemoveItemListeners();
    
    // Form submission handling
    const stockOpnameForm = document.getElementById('stockOpnameForm');
    if (stockOpnameForm) {
        stockOpnameForm.addEventListener('submit', function(e) {
            const selectedItems = document.querySelectorAll('.selected-item');
            
            if (selectedItems.length === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal satu barang untuk stock opname');
                return;
            }
            
            // If finish button is clicked
            if (e.submitter && e.submitter.name === 'finish') {
                // Change the action value to 'finish'
                const actionInput = this.querySelector('input[name="action"]');
                if (actionInput) {
                    actionInput.value = 'finish';
                }
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
