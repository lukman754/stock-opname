<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'index.php') {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopiluvium Inventory Management</title>
    <!-- Bootstrap CSS dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome dari CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --coffee-dark: #4A3728;
            --coffee-medium: #7D5A50;
            --coffee-light: #B08968;
            --coffee-cream: #E5D3B3;
            --coffee-bg: #FDF6EC;
            --sidebar-width: 280px;
            --header-height: 56px;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--coffee-bg);
            color: #333;
            overflow-x: hidden;
        }

        /* Navbar styles */
        .navbar {
            background-color: var(--coffee-dark) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 0 1rem;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--coffee-cream) !important;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            color: var(--coffee-cream);
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            color: var(--coffee-cream);
            background: transparent;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        /* User profile styles */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--coffee-cream);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--coffee-dark);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            color: var(--coffee-cream);
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0;
        }

        .user-role {
            color: rgba(229, 211, 179, 0.8);
            font-size: 0.75rem;
            margin: 0;
        }

        /* Dropdown menu styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.5rem;
            min-width: 200px;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            color: var(--coffee-dark);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dropdown-item:hover {
            background-color: var(--coffee-cream);
            color: var(--coffee-dark);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            color: var(--coffee-medium);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: rgba(0, 0, 0, 0.05);
        }

        /* Table styles */
        .table {
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            color: var(--coffee-dark);
            font-weight: 500;
            padding: 0.85rem 1rem;
            white-space: nowrap;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }

        .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            color: #495057;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: rgba(229, 211, 179, 0.05);
        }

        /* Page title styles */
        .page-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
            color: var(--coffee-dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-title h1,
        .page-title h2,
        .page-title h3 {
            font-weight: 500;
            margin: 0;
            font-size: 1.5rem;
        }

        /* Form styles */
        .form-control,
        .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--coffee-medium);
            box-shadow: 0 0 0 0.2rem rgba(125, 90, 80, 0.15);
        }

        .form-label {
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Modal styles */
        .modal-content {
            border: none;
            border-radius: 6px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
        }

        .modal-title {
            font-weight: 500;
            color: var(--coffee-dark);
            font-size: 1.1rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
        }

        /* Alert styles */
        .alert {
            border: none;
            border-radius: 4px;
            padding: 0.85rem 1.25rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(56, 193, 114, 0.1);
            color: #2d9f5c;
        }

        .alert-danger {
            background-color: rgba(227, 52, 47, 0.1);
            color: #c92a25;
        }

        .alert-warning {
            background-color: rgba(246, 153, 63, 0.1);
            color: #e58a2c;
        }

        .alert-info {
            background-color: rgba(108, 178, 235, 0.1);
            color: #4d9bd6;
        }

        /* Card styles */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
        }

        .card-header i {
            color: var(--coffee-medium);
            margin-right: 0.75rem;
        }

        .card-title {
            color: var(--coffee-dark);
            font-weight: 500;
            margin: 0;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Badge styles */
        .badge {
            padding: 0.4em 0.65em;
            font-weight: 500;
            border-radius: 4px;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
        }

        .bg-success {
            background-color: #38c172 !important;
        }

        .bg-primary {
            background-color: #3490dc !important;
        }

        .bg-warning {
            background-color: #f6993f !important;
            color: #fff !important;
        }

        .bg-danger {
            background-color: #e3342f !important;
        }

        .bg-info {
            background-color: #6cb2eb !important;
        }

        /* Button styles */
        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background-color: var(--coffee-medium);
            border-color: var(--coffee-medium);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--coffee-dark);
            border-color: var(--coffee-dark);
            box-shadow: 0 0 0 0.2rem rgba(125, 90, 80, 0.25);
        }

        .btn-outline-primary {
            color: var(--coffee-medium);
            border-color: var(--coffee-medium);
        }

        .btn-outline-primary:hover {
            background-color: var(--coffee-medium);
            border-color: var(--coffee-medium);
            color: #fff;
        }

        .btn-success {
            background-color: #38c172;
            border-color: #38c172;
        }

        .btn-success:hover {
            background-color: #2d9f5c;
            border-color: #2d9f5c;
        }

        .btn-warning {
            background-color: #f6993f;
            border-color: #f6993f;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e58a2c;
            border-color: #e58a2c;
            color: white;
        }

        .btn-danger {
            background-color: #e3342f;
            border-color: #e3342f;
        }

        .btn-danger:hover {
            background-color: #c92a25;
            border-color: #c92a25;
        }

        .btn i {
            font-size: 0.85rem;
        }

        /* Welcome card */
        .welcome-card {
            background-color: var(--coffee-dark);
            color: white;
        }

        /* Quick stats cards */
        .quick-stats-card {
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .quick-stats-card:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            border-left-color: var(--coffee-medium);
        }

        .quick-stats-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background-color: rgba(125, 90, 80, 0.1);
            color: var(--coffee-medium);
        }

        .quick-stats-value {
            font-size: 1.35rem;
            font-weight: 500;
            color: var(--coffee-dark);
        }

        .quick-stats-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1020;
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
        }

        .sidebar-backdrop {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .content {
                margin-left: 0 !important;
                width: 100%;
            }
        }

        @media (min-width: 768px) {
            .content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }

            .navbar-toggler {
                display: none;
            }
        }

        /* Content styles */
        .content {
            margin-top: var(--header-height);
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            min-height: calc(100vh - var(--header-height));
        }

        /* Sidebar header and navigation styles */
        .sidebar-header {
            background-color: var(--coffee-dark);
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .nav-category {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav-category-header {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: var(--coffee-light);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .nav-category-header:hover {
            background-color: var(--coffee-medium);
        }

        .nav-category-header i.category-icon {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }

        .nav-category-header i.toggle-icon {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .nav-category-header[aria-expanded="true"] i.toggle-icon {
            transform: rotate(180deg);
        }

        .nav-category-body {
            background-color: #fff;
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: var(--coffee-dark);
            padding: 12px 15px 12px 49px;
            border-radius: 0;
            transition: all 0.2s ease;
            position: relative;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
            color: var(--coffee-medium);
        }

        .nav-link:hover {
            background-color: var(--coffee-cream);
            color: var(--coffee-dark);
            border-left-color: var(--coffee-medium);
        }

        .nav-link.active {
            background-color: var(--coffee-cream);
            color: var(--coffee-dark);
            font-weight: 600;
            border-left-color: var(--coffee-dark);
        }


        .dataTables_wrapper .dataTables_length select {
            width: 80px !important;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" id="sidebarToggler" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="/kopi/dashboard.php">
                <i class="fas fa-coffee me-2"></i>Kopiluvium Inventory
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle User Menu">
                <i class="fas fa-user"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle p-0" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-profile">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                    <div class="user-info">
                                        <p class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></p>
                                        <p class="user-role"><?php
                                        $role_display = array(
                                            'admin' => 'Kepala Toko',
                                            'manager' => 'Staf Gudang',
                                            'kepala_toko' => 'Kepala Toko',
                                            'staf_gudang' => 'Staf Gudang',
                                            'manager_keuangan' => 'Manager Keuangan',
                                            'bartender' => 'Bartender',
                                            'kitchen' => 'Kitchen',
                                            'kasir' => 'Kasir',
                                            'waiters' => 'Waiters'
                                        );
                                        echo isset($role_display[$_SESSION['role']]) ? $role_display[$_SESSION['role']] : ucfirst($_SESSION['role']);
                                        ?></p>
                                    </div>
                                    <i class="fas fa-chevron-down ms-2" style="color: var(--coffee-cream);"></i>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="/kopi/modules/auth/ganti_password.php">
                                        <i class="fas fa-key"></i> Ganti Password
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/kopi/modules/auth/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar backdrop for mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <?php if (isLoggedIn()): ?>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-coffee me-2"></i>Menu Navigasi
            </div>
            <div class="accordion" id="sidebarAccordion">
                <!-- Dashboard -->
                <div class="nav-category">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                        href="/kopi/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>

                <!-- Master Data Category -->
                <?php if (in_array($_SESSION['role'], ['kepala_toko', 'staf_gudang'])): // Hanya kepala_toko dan staf_gudang yang dapat mengakses master data ?>
                    <div class="nav-category">
                        <div class="nav-category-header" data-bs-toggle="collapse" data-bs-target="#masterDataCollapse"
                            aria-expanded="<?php echo strpos($_SERVER['PHP_SELF'], '/master/') !== false ? 'true' : 'false'; ?>"
                            aria-controls="masterDataCollapse">
                            <i class="fas fa-database category-icon"></i> Master Data
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div id="masterDataCollapse"
                            class="collapse <?php echo strpos($_SERVER['PHP_SELF'], '/master/') !== false ? 'show' : ''; ?>"
                            data-bs-parent="#sidebarAccordion">
                            <div class="nav-category-body">
                                <?php if (in_array($_SESSION['role'], ['kepala_toko', 'staf_gudang'])): ?>
                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/master/') !== false ? 'active' : ''; ?>"
                                        href="/kopi/modules/master/index.php">
                                        <i class="fas fa-list"></i> Daftar Master
                                    </a>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] == 'kepala_toko'): // Hanya kepala_toko yang dapat mengelola pengguna ?>
                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"
                                        href="/kopi/modules/master/users.php">
                                        <i class="fas fa-users"></i> Pengguna
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inventory Category -->
                <?php if (in_array($_SESSION['role'], ['kepala_toko', 'staf_gudang', 'manager_keuangan', 'bartender', 'kitchen', 'kasir', 'waiters'])): // Semua roles dapat melihat data barang ?>
                    <div class="nav-category">
                        <div class="nav-category-header" data-bs-toggle="collapse" data-bs-target="#inventoryCollapse"
                            aria-expanded="<?php echo strpos($_SERVER['PHP_SELF'], '/barang/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_masuk/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_keluar/') !== false ? 'true' : 'false'; ?>"
                            aria-controls="inventoryCollapse">
                            <i class="fas fa-boxes category-icon"></i> Inventory
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div id="inventoryCollapse"
                            class="collapse <?php echo strpos($_SERVER['PHP_SELF'], '/barang/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_masuk/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_keluar/') !== false ? 'show' : ''; ?>"
                            data-bs-parent="#sidebarAccordion">
                            <div class="nav-category-body">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/barang/') !== false ? 'active' : ''; ?>"
                                    href="/kopi/modules/barang/index.php">
                                    <i class="fas fa-box"></i> Data Barang
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/stock/') !== false ? 'active' : ''; ?>"
                                    href="/kopi/modules/stock/index.php">
                                    <i class="fas fa-cubes"></i> Stok Barang
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Operations Category -->
                <div class="nav-category">
                    <div class="nav-category-header" data-bs-toggle="collapse" data-bs-target="#operationsCollapse"
                        aria-expanded="<?php echo strpos($_SERVER['PHP_SELF'], '/stock_opname/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_masuk/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_keluar/') !== false ? 'true' : 'false'; ?>"
                        aria-controls="operationsCollapse">
                        <i class="fas fa-cogs category-icon"></i> Operasional
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div id="operationsCollapse"
                        class="collapse <?php echo strpos($_SERVER['PHP_SELF'], '/stock_opname/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_masuk/') !== false || strpos($_SERVER['PHP_SELF'], '/barang_keluar/') !== false ? 'show' : ''; ?>"
                        data-bs-parent="#sidebarAccordion">
                        <div class="nav-category-body">
                            <?php if (in_array($_SESSION['role'], ['staf_gudang'])): // Hanya staf_gudang yang dapat input stock opname ?>
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/stock_opname/') !== false ? 'active' : ''; ?>"
                                href="/kopi/modules/stock_opname/index.php">
                                <i class="fas fa-clipboard-check"></i> Stock Opname
                            </a>
                            <?php endif; ?>
                            <?php if (in_array($_SESSION['role'], ['staf_gudang', 'bartender', 'kitchen', 'kasir', 'waiters', 'manager_keuangan'])): // Roles yang dapat input barang masuk/keluar ?>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/barang_masuk/') !== false ? 'active' : ''; ?>"
                                    href="/kopi/modules/barang_masuk/index.php">
                                    <i class="fas fa-arrow-right-to-bracket"></i> Barang Masuk
                                </a>
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/barang_keluar/') !== false ? 'active' : ''; ?>"
                                    href="/kopi/modules/barang_keluar/index.php">
                                    <i class="fas fa-arrow-right-from-bracket"></i> Barang Keluar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Reports Category -->
                <?php if (in_array($_SESSION['role'], ['kepala_toko', 'manager_keuangan', 'kasir'])): // Hanya roles ini yang dapat mencetak laporan ?>
                    <div class="nav-category">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/laporan/') !== false ? 'active' : ''; ?>"
                            href="/kopi/modules/laporan/index.php">
                            <i class="fas fa-chart-bar"></i> Laporan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="content">
        <?php displayAlert(); ?>