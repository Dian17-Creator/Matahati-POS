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
            background-color: #ffffffff;
        }

        .sidebar,
        .sidebar.offcanvas-md,
        .sidebar.offcanvas,
        .sidebar .offcanvas-header,
        .sidebar .offcanvas-body {
            background-color: #ffffff !important;
            --bs-offcanvas-bg: #ffffff !important;
            color: #1e293b !important;
        }

        .sidebar {
            width: 198px;
            border-right: 1px solid #eef2f6;
            box-shadow: 4px 0 20px rgba(15, 23, 42, 0.08), 1px 0 4px rgba(15, 23, 42, 0.04);
            z-index: 1030;
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
                width: 225px;
                max-width: 80vw;
                box-shadow: 6px 0 24px rgba(15, 23, 42, 0.16);
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
            background: rgba(15, 23, 42, 0.16);
            border-radius: 4px;
        }

        .sidebar a {
            color: #1e293b;
            text-decoration: none;
            padding: 8px 8px;
            display: flex;
            align-items: center;
            border-radius: 7px;
            margin-bottom: 2px;
            font-size: 0.73rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        .sidebar a i {
            flex-shrink: 0;
        }

        .sidebar a span:not(.nav-icon) {
            flex: 1;
            line-height: 1.35;
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: #0f172a;
            background-color: #f1f5f9;
        }

        /* Top level direct nav link (Dashboard) */
        .sidebar a.nav-top-link {
            font-size: 0.73rem;
            font-weight: 500;
            color: #1e293b;
            padding: 9px 8px;
            margin-bottom: 4px;
            white-space: nowrap;
        }

        .sidebar a.nav-top-link:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }

        .sidebar a.nav-top-link.active {
            color: #0f172a;
            font-weight: 600;
            background-color: #f1f5f9;
        }

        .sidebar-divider {
            border: 0;
            border-top: 1px solid #e2e8f0;
            opacity: 1;
            margin: 10px 6px 12px;
        }

        /* Nav icon fixed width & alignment */
        .sidebar .nav-icon {
            width: 15px;
            height: 15px;
            font-size: 0.88rem;
            margin-right: 8px;
            text-align: center;
            flex: 0 0 15px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            opacity: 0.85;
            transition: opacity 0.2s ease;
        }

        .sidebar .nav-icon i {
            font-size: 0.88rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
        }

        .sidebar .nav-icon i::before {
            vertical-align: 0;
        }

        .sidebar .nav-icon .nav-svg-icon {
            width: 15px;
            height: 15px;
            filter: none;
            object-fit: contain;
            display: inline-block;
            vertical-align: middle;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .sidebar a:hover .nav-icon,
        .sidebar a.active .nav-icon,
        .sidebar .nav-category:hover .nav-icon,
        .sidebar .nav-category.active-parent .nav-icon,
        .sidebar .nav-category:not(.collapsed) .nav-icon {
            opacity: 1;
        }

        /* Category Header with Toggle */
        .sidebar .nav-category {
            font-size: 0.73rem;
            color: #1e293b;
            padding: 9px 8px;
            margin-top: 6px;
            margin-bottom: 4px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
        }

        .sidebar .nav-category:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }

        .sidebar .nav-category.active-parent {
            color: #0f172a;
            font-weight: 600;
            background-color: #f8fafc;
        }

        /* Chevron Arrow Animation */
        .sidebar .nav-category .nav-arrow {
            font-size: 0.6rem;
            color: #94a3b8;
            transition: transform 0.25s ease-in-out, color 0.2s ease;
            opacity: 1;
            flex-shrink: 0;
            margin-left: 4px;
        }

        .sidebar .nav-category:hover .nav-arrow {
            color: #334155;
        }

        .sidebar .nav-category:not(.collapsed) .nav-arrow,
        .sidebar .nav-category[aria-expanded="true"] .nav-arrow {
            transform: rotate(180deg);
            color: #334155;
        }

        /* Sub Menu Container */
        .sidebar .nav-sub-menu {
            padding: 4px 0 6px;
        }

        /* Sub Menu Links (Indented to align perfectly with category text: 8px padding + 15px icon + 8px margin = 31px) */
        .sidebar a.sidebar-sub-item {
            color: #475569;
            padding: 6px 8px 6px 31px;
            font-size: 0.7rem;
            font-weight: 400;
            margin-bottom: 3px;
            white-space: nowrap;
        }

        .sidebar a.sidebar-sub-item:hover {
            color: #0f172a;
            background-color: #f1f5f9;
        }

        .sidebar a.sidebar-sub-item.active {
            color: #0f172a;
            font-weight: 600;
            background-color: #f1f5f9;
        }

        /* Olsera-style Top Sidebar User Card & Dropdown */
        .sidebar-brand-title {
            font-size: 0.88rem;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .sidebar-user-card {
            background-color: #b63352;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0 0 13px 13px;
            box-shadow: 0 4px 12px rgba(182, 51, 82, 0.22);
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }

        .sidebar-user-card:hover {
            background-color: #a12b47;
            box-shadow: 0 6px 14px rgba(182, 51, 82, 0.28);
        }

        .sidebar-user-card.active,
        .sidebar-user-card[aria-expanded="true"] {
            background-color: #962640;
        }

        .sidebar-user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar-user-name {
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        .sidebar-user-role {
            font-size: 0.64rem;
            color: rgba(255, 255, 255, 0.78);
            line-height: 1.2;
        }

        .sidebar-user-arrow {
            font-size: 0.62rem;
            color: rgba(255, 255, 255, 0.85);
            transition: transform 0.25s ease-in-out;
        }

        .sidebar-user-card[aria-expanded="true"] .sidebar-user-arrow {
            transform: rotate(180deg);
        }

        /* Olsera-style Floating User Dropdown Menu */
        .sidebar-user-section {
            position: relative;
        }

        #userProfileMenu {
            position: absolute !important;
            top: 100%;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1060;
        }

        /* Olsera White Dropdown Menu Box */
        .user-profile-menu-box {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 5px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-top: 6px;
            margin-bottom: 0;
            animation: fadeInMenu 0.15s ease-in-out;
        }

        @keyframes fadeInMenu {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            padding: 6px 9px;
            color: #334155 !important;
            font-size: 0.73rem;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            width: 100%;
            background: transparent;
            border: none;
        }

        .user-menu-item i {
            font-size: 0.92rem;
            color: #64748b;
            flex-shrink: 0;
        }

        .user-menu-item:hover,
        .user-menu-item.active {
            background-color: #f1f5f9;
            color: #0f172a !important;
        }

        .user-menu-item:hover i,
        .user-menu-item.active i {
            color: #0f172a;
        }

        /* Keluar / Logout button (Olsera light-pink box with red text) */
        .user-menu-item.user-menu-logout {
            background-color: #fff1f2;
            color: #e11d48 !important;
            font-weight: 500;
            margin-top: 3px;
        }

        .user-menu-item.user-menu-logout i {
            color: #e11d48;
        }

        .user-menu-item.user-menu-logout:hover {
            background-color: #ffe4e6;
            color: #be123c !important;
        }

        .user-menu-item.user-menu-logout:hover i {
            color: #be123c;
        }

        /* Hide browser native password reveal icon (Edge / Chromium) to prevent duplicate eye icons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
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

        /* Top Maroon Gradient Header Navbar (Left Dark -> Right Light) */
        .top-header-navbar {
            background: linear-gradient(90deg, #b63352 50%, #e04a70 100%) !important;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
            border-bottom: none !important;
        }

        .top-header-navbar .navbar-brand {
            color: #ffffff !important;
            font-weight: 600;
            font-size: 1.05rem !important;
            letter-spacing: 0.2px;
        }

        /* Olsera-Style Header Outlet Switcher */
        .outlet-switcher-btn {
            background-color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 9999px;
            padding: 4px 12px 4px 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 0.82rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .outlet-switcher-btn:hover,
        .outlet-switcher-btn:focus,
        .show>.outlet-switcher-btn {
            background-color: #f8fafc;
            border-color: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
            color: #0f172a;
        }

        .outlet-icon-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #fff1f2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #b63352;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .outlet-name-text {
            max-width: 190px;
            line-height: 1.2;
            font-size: 0.84rem;
        }

        .outlet-chevron {
            font-size: 0.72rem;
            color: #94a3b8;
            transition: transform 0.2s ease;
        }

        .show>.outlet-switcher-btn .outlet-chevron {
            transform: rotate(180deg);
        }

        .outlet-switcher-dropdown {
            min-width: 290px;
            max-width: 340px;
            border-radius: 14px;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12) !important;
            padding: 6px;
            margin-top: 6px !important;
        }

        .outlet-dropdown-item {
            border-radius: 8px;
            padding: 9px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #334155;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            margin-bottom: 2px;
        }

        .outlet-dropdown-item:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }

        .outlet-dropdown-item.active {
            background-color: #fff1f2;
            color: #b63352;
        }

        .outlet-dropdown-item.active .outlet-item-icon {
            background-color: #b63352;
            color: #ffffff;
        }

        .outlet-dropdown-item.active .outlet-item-title {
            color: #b63352;
            font-weight: 700;
        }

        .outlet-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1rem;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .outlet-btn-img {
            width: 15px;
            height: 15px;
            object-fit: contain;
            display: inline-block;
            vertical-align: middle;
        }

        .outlet-img-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            display: inline-block;
            vertical-align: middle;
            transition: filter 0.15s ease;
        }

        .outlet-dropdown-item.active .outlet-img-icon {
            filter: brightness(0) invert(1);
        }

        .outlet-item-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.25;
        }

        .outlet-item-subtitle {
            font-size: 0.72rem;
            color: #94a3b8;
            line-height: 1.2;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <div class="sidebar offcanvas-md offcanvas-start text-dark flex-shrink-0" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel" style="background-color: #ffffff !important; --bs-offcanvas-bg: #ffffff !important;">
            <div class="offcanvas-header d-md-none p-3 justify-content-between align-items-center" style="background-color: #ffffff !important; border-bottom: 1px solid #eef2f6;">
                <h5 class="offcanvas-title text-dark fw-bold mb-0" id="sidebarMenuLabel">MATAHATI POS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 flex-column" style="background-color: #ffffff !important;">
                <div class="w-100" style="padding: 0 8px 14px;">

                    <!-- Olsera-Style Top User Profile & Menu -->
                    <div class="sidebar-user-section mb-3">
                        <div class="sidebar-user-card d-flex align-items-center justify-content-between p-2"
                            data-bs-toggle="collapse"
                            data-bs-target="#userProfileMenu"
                            aria-expanded="false"
                            aria-controls="userProfileMenu"
                            role="button"
                            title="Menu Pengguna">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <div class="sidebar-user-avatar">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="d-flex flex-column overflow-hidden text-start">
                                    <span class="sidebar-user-name text-truncate">{{ Auth::user()->cname ?? 'Administrator' }}</span>
                                    <span class="sidebar-user-role text-truncate">{{ Auth::user()->role_name ?? 'Supervisor' }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-1">
                                <i class="bi bi-chevron-down sidebar-user-arrow"></i>
                            </div>
                        </div>

                        <!-- Dropdown Menu: Profil & Keluar -->
                        <div class="collapse" id="userProfileMenu">
                            <div class="user-profile-menu-box shadow-sm">
                                <a href="{{ route('profile') }}" class="user-menu-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                                    <i class="bi bi-person me-2"></i>
                                    <span>Profil</span>
                                </a>
                                <div class="my-1 border-top" style="border-color: #f1f5f9 !important;"></div>
                                <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="user-menu-item user-menu-logout">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @php
                    $isProductActive = request()->routeIs(['categories.*', 'products.*', 'ingredients.*', 'combos.*']);
                    $isMarketingActive = request()->routeIs(['vouchers.*']);
                    $isCustomerSupplierActive = request()->routeIs(['customer-types.*', 'customers.*', 'suppliers.*']);
                    $isTransactionsActive = request()->routeIs(['purchases.*', 'payments.*']);
                    $isReportsActive = request()->routeIs(['reports.*']);
                    $isPegawaiActive = request()->routeIs(['outlets.*', 'pos-users.*']);
                    @endphp

                    <a href="{{ route('dashboard') }}" class="nav-top-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <img src="{{ asset('images/ic_matahati_home.svg') }}" alt="Dashboard" class="nav-svg-icon">
                        </span>
                        <span>Dashboard</span>
                    </a>

                    <hr class="sidebar-divider">

                    <!-- Produk & Inventori -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isProductActive ? '' : 'collapsed' }} {{ $isProductActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuProductInventory"
                        aria-expanded="{{ $isProductActive ? 'true' : 'false' }}"
                        aria-controls="menuProductInventory"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_inventory.svg') }}" alt="Produk & Inventori" class="nav-svg-icon">
                            </span>
                            <span>Produk & Inventori</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isProductActive ? 'show' : '' }}" id="menuProductInventory">
                        <div class="nav-sub-menu">
                            <a href="{{ route('categories.index') }}" class="sidebar-sub-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <span>Kategori Produk</span>
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

                    <!-- Marketing -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isMarketingActive ? '' : 'collapsed' }} {{ $isMarketingActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuMarketing"
                        aria-expanded="{{ $isMarketingActive ? 'true' : 'false' }}"
                        aria-controls="menuMarketing"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_marketing.svg') }}" alt="Marketing" class="nav-svg-icon">
                            </span>
                            <span>Marketing</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isMarketingActive ? 'show' : '' }}" id="menuMarketing">
                        <div class="nav-sub-menu">
                            <a href="{{ route('vouchers.index') }}" class="sidebar-sub-item {{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
                                <span>Voucher</span>
                            </a>
                        </div>
                    </div>

                    <!-- Pelanggan & Supplier -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isCustomerSupplierActive ? '' : 'collapsed' }} {{ $isCustomerSupplierActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuCustomerSupplier"
                        aria-expanded="{{ $isCustomerSupplierActive ? 'true' : 'false' }}"
                        aria-controls="menuCustomerSupplier"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_customer.svg') }}" alt="Pelanggan & Supplier" class="nav-svg-icon">
                            </span>
                            <span>Pelanggan & Supplier</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isCustomerSupplierActive ? 'show' : '' }}" id="menuCustomerSupplier">
                        <div class="nav-sub-menu">
                            <a href="{{ route('customer-types.index') }}" class="sidebar-sub-item {{ request()->routeIs('customer-types.*') ? 'active' : '' }}">
                                <span>Kategori Pelanggan</span>
                            </a>
                            <a href="{{ route('customers.index') }}" class="sidebar-sub-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <span>Pelanggan</span>
                            </a>
                            <a href="{{ route('suppliers.index') }}" class="sidebar-sub-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                <span>Supplier</span>
                            </a>
                        </div>
                    </div>

                    <!-- Transaksi & Pembayaran -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isTransactionsActive ? '' : 'collapsed' }} {{ $isTransactionsActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuTransactions"
                        aria-expanded="{{ $isTransactionsActive ? 'true' : 'false' }}"
                        aria-controls="menuTransactions"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_transaction.svg') }}" alt="Transaksi & Pembayaran" class="nav-svg-icon">
                            </span>
                            <span>Transaksi & Pembayaran</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isTransactionsActive ? 'show' : '' }}" id="menuTransactions">
                        <div class="nav-sub-menu">
                            <a href="{{ route('purchases.index') }}" class="sidebar-sub-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                                <span>Pembelian</span>
                            </a>
                            <a href="{{ route('payments.index') }}" class="sidebar-sub-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                                <span>Metode Pembayaran</span>
                            </a>
                        </div>
                    </div>

                    <!-- Laporan & Pembukuan -->
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isReportsActive ? '' : 'collapsed' }} {{ $isReportsActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuReports"
                        aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}"
                        aria-controls="menuReports"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_report.svg') }}" alt="Laporan & Pembukuan" class="nav-svg-icon">
                            </span>
                            <span>Laporan & Pembukuan</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="menuReports">
                        <div class="nav-sub-menu">
                            <a href="{{ route('reports.sales-detail') }}" class="sidebar-sub-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                <span>Laporan</span>
                            </a>
                        </div>
                    </div>

                    <!-- Pegawai -->
                    @if(Auth::user()?->isOwnerLevel())
                    <div class="nav-category d-flex align-items-center justify-content-between {{ $isPegawaiActive ? '' : 'collapsed' }} {{ $isPegawaiActive ? 'active-parent' : '' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuPegawai"
                        aria-expanded="{{ $isPegawaiActive ? 'true' : 'false' }}"
                        aria-controls="menuPegawai"
                        role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-icon">
                                <img src="{{ asset('images/ic_matahati_user2.svg') }}" alt="Pegawai" class="nav-svg-icon">
                            </span>
                            <span>Pegawai</span>
                        </div>
                        <i class="bi bi-chevron-down nav-arrow"></i>
                    </div>
                    <div class="collapse {{ $isPegawaiActive ? 'show' : '' }}" id="menuPegawai">
                        <div class="nav-sub-menu">
                            <a href="{{ route('outlets.index') }}" class="sidebar-sub-item {{ request()->routeIs('outlets.*') ? 'active' : '' }}">
                                <span>Outlet</span>
                            </a>
                            <a href="{{ route('pos-users.index') }}" class="sidebar-sub-item {{ request()->routeIs('pos-users.*') ? 'active' : '' }}">
                                <span>Pegawai Pos</span>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width: 0;">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark top-header-navbar px-3 px-md-4 py-2">
                <div class="container-fluid p-0">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-light d-md-none me-2 px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Toggle navigation">
                            <i class="bi bi-list fs-5"></i>
                        </button>
                        <span class="navbar-brand text-white mb-0 h1">@yield('title')</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @yield('header_actions')

                        <!-- Olsera-Style Outlet Switcher (Pojok Kanan Atas) -->
                        @if(isset($headerCanSwitchOutlet) && $headerCanSwitchOutlet)
                        <div class="dropdown outlet-switcher-wrapper ms-1">
                            <button class="outlet-switcher-btn dropdown-toggle" type="button" id="headerOutletDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih Cabang / Outlet">
                                <span class="outlet-icon-circle">
                                    @if(empty($headerActiveOutletId))
                                    <img src="{{ asset('images/ic_matahati_pos_all.svg') }}" alt="Semua Outlet" class="outlet-btn-img">
                                    @else
                                    <img src="{{ asset('images/ic_matahati_pos.svg') }}" alt="Outlet" class="outlet-btn-img">
                                    @endif
                                </span>
                                <span class="outlet-name-text text-truncate">{{ $headerActiveOutletName ?? 'Semua Outlet' }}</span>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end outlet-switcher-dropdown shadow-lg" aria-labelledby="headerOutletDropdown">
                                <div class="px-3 py-2 border-bottom mb-1">
                                    <div class="fw-bold text-dark small">Pilih Cabang / Outlet</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Seluruh data otomatis terfilter by outlet</div>
                                </div>

                                <form action="{{ route('switch-outlet') }}" method="POST" class="m-0 p-0">
                                    @csrf
                                    <div class="py-1" style="max-height: 320px; overflow-y: auto;">
                                        <!-- Opsi Semua Outlet -->
                                        <button type="submit" name="outlet_id" value="" class="outlet-dropdown-item {{ empty($headerActiveOutletId) ? 'active' : '' }}">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <span class="outlet-item-icon">
                                                    <img src="{{ asset('images/ic_matahati_pos_all.svg') }}" alt="Semua Outlet" class="outlet-img-icon">
                                                </span>
                                                <div class="d-flex flex-column text-start overflow-hidden">
                                                    <span class="outlet-item-title text-truncate">Semua Outlet</span>
                                                    <span class="outlet-item-subtitle text-truncate">Gabungan data semua cabang</span>
                                                </div>
                                            </div>
                                            @if(empty($headerActiveOutletId))
                                            <i class="bi bi-check2-circle text-danger fs-5 ms-2 flex-shrink-0"></i>
                                            @endif
                                        </button>

                                        <div class="dropdown-divider my-1"></div>

                                        <!-- Daftar Cabang / Outlet -->
                                        @foreach($headerOutlets as $hOutlet)
                                        <button type="submit" name="outlet_id" value="{{ $hOutlet->nid }}" class="outlet-dropdown-item {{ (string)$headerActiveOutletId === (string)$hOutlet->nid ? 'active' : '' }}">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <span class="outlet-item-icon">
                                                    <img src="{{ asset('images/ic_matahati_pos.svg') }}" alt="{{ $hOutlet->cname }}" class="outlet-img-icon">
                                                </span>
                                                <div class="d-flex flex-column text-start overflow-hidden">
                                                    <span class="outlet-item-title text-truncate">{{ $hOutlet->cname }}</span>
                                                </div>
                                            </div>
                                            @if((string)$headerActiveOutletId === (string)$hOutlet->nid)
                                            <i class="bi bi-check2-circle text-danger fs-5 ms-2 flex-shrink-0"></i>
                                            @endif
                                        </button>
                                        @endforeach
                                    </div>
                                </form>

                                @if(!empty($isOwnerLevelUser))
                                <div class="border-top pt-1 mt-1 px-2">
                                    <a href="{{ route('outlets.index') }}" class="btn btn-sm btn-light w-100 text-muted d-flex align-items-center justify-content-center gap-1 py-1" style="font-size: 0.78rem;">
                                        <i class="bi bi-plus-lg"></i>
                                        <span>Kelola Outlet</span>
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @elseif(!empty($headerActiveOutletName) && $headerActiveOutletName !== 'Semua Outlet')
                        <!-- Static outlet badge for single-outlet locked users (Kasir / Captain) -->
                        <div class="outlet-switcher-btn disabled ms-1" title="Outlet Aktif: {{ $headerActiveOutletName }}" style="cursor: default; opacity: 0.95;">
                            <span class="outlet-icon-circle">
                                <img src="{{ asset('images/ic_matahati_pos.svg') }}" alt="Outlet" class="outlet-btn-img">
                            </span>
                            <span class="outlet-name-text text-truncate">{{ $headerActiveOutletName }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </nav>

            @yield('sub_header')

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
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebarMenu');
            if (sidebar) {
                sidebar.querySelectorAll('.collapse').forEach(function(collapseEl) {
                    const id = collapseEl.id;
                    if (id === 'userProfileMenu') return; // Handled separately without session storage persistence
                    const savedState = sessionStorage.getItem('sidebar_collapse_' + id);

                    if (savedState === 'show' && !collapseEl.classList.contains('show')) {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                            toggle: false
                        });
                        bsCollapse.show();
                    } else if (savedState === 'hide' && collapseEl.classList.contains('show')) {
                        const trigger = document.querySelector('[data-bs-target="#' + id + '"]');
                        if (!trigger || !trigger.classList.contains('active-parent')) {
                            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                                toggle: false
                            });
                            bsCollapse.hide();
                        }
                    }

                    collapseEl.addEventListener('shown.bs.collapse', function() {
                        sessionStorage.setItem('sidebar_collapse_' + id, 'show');
                    });
                    collapseEl.addEventListener('hidden.bs.collapse', function() {
                        sessionStorage.setItem('sidebar_collapse_' + id, 'hide');
                    });
                });

                // Close user profile menu when clicking outside
                document.addEventListener('click', function(e) {
                    const userMenu = document.getElementById('userProfileMenu');
                    const userTrigger = document.querySelector('[data-bs-target="#userProfileMenu"]');
                    if (userMenu && userTrigger && userMenu.classList.contains('show')) {
                        if (!userMenu.contains(e.target) && !userTrigger.contains(e.target)) {
                            const bsCollapse = bootstrap.Collapse.getInstance(userMenu);
                            if (bsCollapse) {
                                bsCollapse.hide();
                            }
                        }
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>