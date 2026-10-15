<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Matahati POS')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar,
        .sidebar.offcanvas-md,
        .sidebar.offcanvas,
        .sidebar .offcanvas-header,
        .sidebar .offcanvas-body {
            background-color: #b63352 !important;
            --bs-offcanvas-bg: #b63352 !important;
            color: white !important;
        }

        .sidebar {
            width: 250px;
        }

        /* Desktop Sticky & Scroll */
        @media (min-width: 768px) {
            .sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
            }
        }

        /* Mobile Offcanvas Drawer from Side */
        @media (max-width: 767.98px) {
            .sidebar.offcanvas-md {
                width: 270px;
                max-width: 80vw;
            }
            .sidebar .offcanvas-body {
                overflow-y: auto;
                height: 100%;
            }
        }

        .sidebar::-webkit-scrollbar,
        .sidebar .offcanvas-body::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb,
        .sidebar .offcanvas-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            padding: 9px 14px;
            display: flex;
            align-items: flex-start;
            border-radius: 6px;
            margin-bottom: 3px;
            font-size: 0.925rem;
            transition: all 0.2s ease-in-out;
        }

        .sidebar a i {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .sidebar a span {
            flex: 1;
            line-height: 1.4;
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.18);
        }

        .sidebar .nav-category {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.55);
            margin: 14px 14px 4px;
            font-weight: 600;
        }

        .main-content {
            padding: 20px;
        }

        /* Bouncy Modal Animation */
        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
            animation: bounceIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.7);
            }

            50% {
                transform: scale(1.05);
            }

            70% {
                transform: scale(0.95);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Modern Pagination */
        .pagination {
            gap: 4px;
            margin-bottom: 0;
        }

        .page-item .page-link {
            border-radius: 6px !important;
            border: 1px solid transparent;
            color: #64748b;
            font-weight: 500;
            padding: 6px 12px;
            background-color: #f8fafc;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
        }

        .page-item .page-link:hover:not(.active):not(.disabled) {
            background-color: #e2e8f0;
            color: #1e293b;
            transform: translateY(-1px);
        }

        .page-item.disabled .page-link {
            background-color: transparent;
            color: #cbd5e1;
            cursor: not-allowed;
        }

        nav.d-flex.justify-items-center.justify-content-between {
            align-items: center;
            width: 100%;
            flex: 1 1 100%;
            padding: 10px 20px;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <div class="sidebar offcanvas-md offcanvas-start text-white flex-shrink-0" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel" style="background-color: #b63352 !important; --bs-offcanvas-bg: #b63352 !important;">
            <div class="offcanvas-header d-md-none p-3 justify-content-between align-items-center" style="background-color: #b63352 !important; border-bottom: 1px solid rgba(255,255,255,0.15);">
                <h5 class="offcanvas-title text-white fw-bold mb-0" id="sidebarMenuLabel">MATAHATI POS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 flex-column" style="background-color: #b63352 !important;">
                <div class="p-3 w-100">
                    <h4 class="text-center text-white mb-4 d-none d-md-block fw-bold">MATAHATI POS</h4>

                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-house me-2"></i>
                        <span>Dashboard</span>
                    </a>

                    <div class="nav-category">Master Data</div>
                    <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="bi bi-tags me-2"></i>
                        <span>Kategori Produk</span>
                    </a>
                    <a href="{{ route('customer-types.index') }}" class="{{ request()->routeIs('customer-types.*') ? 'active' : '' }}">
                        <i class="bi bi-person-vcard me-2"></i>
                        <span>Kategori Pelanggan</span>
                    </a>
                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="bi bi-cup-hot me-2"></i>
                        <span>Produk</span>
                    </a>
                    <a href="{{ route('ingredients.index') }}" class="{{ request()->routeIs('ingredients.*') ? 'active' : '' }}">
                        <i class="bi bi-basket me-2"></i>
                        <span>Bahan Baku</span>
                    </a>

                    <a href="{{ route('combos.index') }}" class="{{ request()->routeIs('combos.*') ? 'active' : '' }}">
                        <i class="bi bi-boxes me-2"></i>
                        <span>Produk Kombo</span>
                    </a>

                    <div class="nav-category">POS Management</div>
                    <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i>
                        <span>Pelanggan</span>
                    </a>
                    <a href="{{ route('outlets.index') }}" class="{{ request()->routeIs('outlets.*') ? 'active' : '' }}">
                        <i class="bi bi-shop me-2"></i>
                        <span>Outlet</span>
                    </a>
                    <a href="{{ route('pos-users.index') }}" class="{{ request()->routeIs('pos-users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge me-2"></i>
                        <span>Pengguna POS</span>
                    </a>
                    <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card me-2"></i>
                        <span>Metode Pembayaran</span>
                    </a>
                    <a href="{{ route('vouchers.index') }}" class="{{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
                        <i class="bi bi-ticket-perforated me-2"></i>
                        <span>Voucher</span>
                    </a>

                    <div class="nav-category">Laporan</div>
                    <a href="{{ route('reports.customer-product') }}" class="{{ request()->routeIs('reports.customer-product*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>
                        <span>Laporan Pelanggan</span>
                    </a>
                    <a href="{{ route('reports.sales-detail') }}" class="{{ request()->routeIs('reports.sales-detail*') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-2"></i>
                        <span>Laporan Penjualan</span>
                    </a>
                    <a href="{{ route('reports.sales-item') }}" class="{{ request()->routeIs('reports.sales-item*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam me-2"></i>
                        <span>Laporan Item Penjualan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width: 0;">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 px-md-4 py-2">
                <div class="container-fluid p-0">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-danger d-md-none me-2 px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Toggle navigation">
                            <i class="bi bi-list fs-5"></i>
                        </button>
                        <span class="navbar-brand mb-0 h1 fs-5">@yield('title')</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        <span class="text-muted small"><i class="bi bi-person-circle me-1"></i> {{ Auth::user()->cname ?? 'Sistem Admin' }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            <div class="main-content flex-grow-1">
                <!-- Alert Success -->
                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Alert Error -->
                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Main Content -->
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>