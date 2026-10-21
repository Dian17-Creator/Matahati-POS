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
            align-items: center;
            border-radius: 6px;
            margin-bottom: 3px;
            font-size: 0.925rem;
            transition: all 0.2s ease-in-out;
        }

        .sidebar a i {
            flex-shrink: 0;
        }

        .sidebar a span:not(.nav-icon) {
            flex: 1;
            line-height: 1.4;
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.18);
        }

        /* Top level direct nav link (Dashboard) */
        .sidebar a.nav-top-link {
            font-size: 0.88rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.82);
            padding: 9px 14px;
            margin-bottom: 2px;
        }

        .sidebar a.nav-top-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .sidebar a.nav-top-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.18);
        }

        /* Nav icon fixed width & alignment */
        .sidebar .nav-icon {
            width: 20px;
            height: 20px;
            font-size: 1.05rem;
            margin-right: 10px;
            text-align: center;
            flex: 0 0 20px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar .nav-icon-svg {
            max-width: 19px;
            max-height: 19px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: inline-block;
            opacity: 0.85;
            transition: opacity 0.2s ease;
        }

        .sidebar a:hover .nav-icon-svg,
        .sidebar a.active .nav-icon-svg,
        .sidebar .nav-category:hover .nav-icon-svg,
        .sidebar .nav-category.active-parent .nav-icon-svg,
        .sidebar .nav-category:not(.collapsed) .nav-icon-svg {
            opacity: 1;
        }

        /* Category Header with Toggle */
        .sidebar .nav-category {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.82);
            padding: 9px 14px;
            margin-top: 8px;
            margin-bottom: 2px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease-in-out;
        }

        .sidebar .nav-category:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .sidebar .nav-category.active-parent {
            color: #fff;
        }

        /* Chevron Arrow Animation */
        .sidebar .nav-category .nav-arrow {
            font-size: 0.75rem;
            transition: transform 0.25s ease-in-out;
            opacity: 0.8;
            flex-shrink: 0;
        }

        .sidebar .nav-category:hover .nav-arrow {
            opacity: 1;
        }

        .sidebar .nav-category:not(.collapsed) .nav-arrow,
        .sidebar .nav-category[aria-expanded="true"] .nav-arrow {
            transform: rotate(180deg);
            opacity: 1;
        }

        /* Sub Menu Container */
        .sidebar .nav-sub-menu {
            padding: 2px 0 4px;
        }

        /* Sub Menu Links (Indented to align perfectly with category text: 14px padding + 20px icon + 10px margin = 44px) */
        .sidebar a.sidebar-sub-item {
            color: rgba(255, 255, 255, 0.75);
            padding: 7px 14px 7px 44px;
            font-size: 0.88rem;
            margin-bottom: 2px;
        }

        .sidebar a.sidebar-sub-item:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.14);
        }

        .sidebar a.sidebar-sub-item.active {
            color: #fff;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.22);
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

                    @php
                        $isMasterActive = request()->routeIs(['categories.*', 'customer-types.*', 'products.*', 'ingredients.*', 'combos.*']);
                        $isPosActive = request()->routeIs(['customers.*', 'outlets.*', 'pos-users.*', 'payments.*', 'vouchers.*']);
                        $isPurchasesActive = request()->routeIs(['purchases.*', 'suppliers.*']);
                        $isReportsActive = request()->routeIs(['reports.*']);
                    @endphp

                    <a href="{{ route('dashboard') }}" class="nav-top-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nav-icon"><img src="{{ asset('images/ic_pos_home.svg') }}" alt="" class="nav-icon-svg"></span>
                        <span>Dashboard</span>
                    </a>

                    <!-- Master Data -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isMasterActive ? '' : 'collapsed' }} {{ $isMasterActive ? 'active-parent' : '' }}"
                         data-bs-toggle="collapse"
                         data-bs-target="#menuMasterData"
                         aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}"
                         aria-controls="menuMasterData"
                         role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon"><img src="{{ asset('images/ic_pos_master.svg') }}" alt="" class="nav-icon-svg"></span>
                            <span>Master Data</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isMasterActive ? 'show' : '' }}" id="menuMasterData">
                        <div class="nav-sub-menu">
                            <a href="{{ route('categories.index') }}" class="sidebar-sub-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <span>Kategori Produk</span>
                            </a>
                            <a href="{{ route('customer-types.index') }}" class="sidebar-sub-item {{ request()->routeIs('customer-types.*') ? 'active' : '' }}">
                                <span>Kategori Pelanggan</span>
                            </a>
                            <a href="{{ route('products.index') }}" class="sidebar-sub-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                <span>Produk</span>
                            </a>
                            <a href="{{ route('ingredients.index') }}" class="sidebar-sub-item {{ request()->routeIs('ingredients.*') ? 'active' : '' }}">
                                <span>Bahan Baku</span>
                            </a>
                            <a href="{{ route('combos.index') }}" class="sidebar-sub-item {{ request()->routeIs('combos.*') ? 'active' : '' }}">
                                <span>Produk Kombo</span>
                            </a>
                        </div>
                    </div>

                    <!-- POS Management -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isPosActive ? '' : 'collapsed' }} {{ $isPosActive ? 'active-parent' : '' }}"
                         data-bs-toggle="collapse"
                         data-bs-target="#menuPosManagement"
                         aria-expanded="{{ $isPosActive ? 'true' : 'false' }}"
                         aria-controls="menuPosManagement"
                         role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon"><img src="{{ asset('images/ic_pos_management.svg') }}" alt="" class="nav-icon-svg"></span>
                            <span>POS Management</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isPosActive ? 'show' : '' }}" id="menuPosManagement">
                        <div class="nav-sub-menu">
                            <a href="{{ route('customers.index') }}" class="sidebar-sub-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <span>Pelanggan</span>
                            </a>
                            @if(Auth::user()?->isOwnerLevel())
                            <a href="{{ route('outlets.index') }}" class="sidebar-sub-item {{ request()->routeIs('outlets.*') ? 'active' : '' }}">
                                <span>Outlet</span>
                            </a>
                            <a href="{{ route('pos-users.index') }}" class="sidebar-sub-item {{ request()->routeIs('pos-users.*') ? 'active' : '' }}">
                                <span>Pengguna POS</span>
                            </a>
                            @endif
                            <a href="{{ route('payments.index') }}" class="sidebar-sub-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                                <span>Metode Pembayaran</span>
                            </a>
                            <a href="{{ route('vouchers.index') }}" class="sidebar-sub-item {{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
                                <span>Voucher</span>
                            </a>
                        </div>
                    </div>

                    <!-- Transaksi & Pembelian -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isPurchasesActive ? '' : 'collapsed' }} {{ $isPurchasesActive ? 'active-parent' : '' }}"
                         data-bs-toggle="collapse"
                         data-bs-target="#menuPurchases"
                         aria-expanded="{{ $isPurchasesActive ? 'true' : 'false' }}"
                         aria-controls="menuPurchases"
                         role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon"><img src="{{ asset('images/ic_pos_transaction.svg') }}" alt="" class="nav-icon-svg"></span>
                            <span>Transaksi & Pembelian</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isPurchasesActive ? 'show' : '' }}" id="menuPurchases">
                        <div class="nav-sub-menu">
                            <a href="{{ route('purchases.index') }}" class="sidebar-sub-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                                <span>Pembelian</span>
                            </a>
                            <a href="{{ route('suppliers.index') }}" class="sidebar-sub-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                <span>Supplier</span>
                            </a>
                        </div>
                    </div>

                    <!-- Laporan -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isReportsActive ? '' : 'collapsed' }} {{ $isReportsActive ? 'active-parent' : '' }}"
                         data-bs-toggle="collapse"
                         data-bs-target="#menuReports"
                         aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}"
                         aria-controls="menuReports"
                         role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon"><img src="{{ asset('images/ic_pos_report.svg') }}" alt="" class="nav-icon-svg"></span>
                            <span>Laporan</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="menuReports">
                        <div class="nav-sub-menu">
                            <a href="{{ route('reports.customer-product') }}" class="sidebar-sub-item {{ request()->routeIs('reports.customer-product*') ? 'active' : '' }}">
                                <span>Laporan Pelanggan</span>
                            </a>
                            <a href="{{ route('reports.sales-detail') }}" class="sidebar-sub-item {{ request()->routeIs('reports.sales-detail*') ? 'active' : '' }}">
                                <span>Laporan Penjualan</span>
                            </a>
                            <a href="{{ route('reports.sales-item') }}" class="sidebar-sub-item {{ request()->routeIs('reports.sales-item*') ? 'active' : '' }}">
                                <span>Laporan Item Penjualan</span>
                            </a>
                        </div>
                    </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebarMenu');
            if (sidebar) {
                sidebar.querySelectorAll('.collapse').forEach(function (collapseEl) {
                    const id = collapseEl.id;
                    const savedState = sessionStorage.getItem('sidebar_collapse_' + id);

                    if (savedState === 'show' && !collapseEl.classList.contains('show')) {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                        bsCollapse.show();
                    } else if (savedState === 'hide' && collapseEl.classList.contains('show')) {
                        const trigger = document.querySelector('[data-bs-target="#' + id + '"]');
                        if (!trigger || !trigger.classList.contains('active-parent')) {
                            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                            bsCollapse.hide();
                        }
                    }

                    collapseEl.addEventListener('shown.bs.collapse', function () {
                        sessionStorage.setItem('sidebar_collapse_' + id, 'show');
                    });
                    collapseEl.addEventListener('hidden.bs.collapse', function () {
                        sessionStorage.setItem('sidebar_collapse_' + id, 'hide');
                    });
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>