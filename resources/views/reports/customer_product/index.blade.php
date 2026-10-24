@extends('layouts.app')

@section('title', 'Daftar Laporan / Laporan Pelanggan')

@section('sub_header')
@include('reports.partials.horizontal_tabs', ['currentCategory' => 'produk'])
@endsection

@push('styles')
<style>
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.06);
    }

    .summary-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #b63352;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    .summary-label {
        font-size: 0.75rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .summary-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #2c3e50;
    }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.84rem;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }

    .filter-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(14, 165, 233, 0.25);
    }

    .filter-chip i {
        margin-right: 6px;
    }

    .filter-chip-active {
        background-color: #0ea5e9;
        color: white;
        border: 1px solid #0ea5e9;
    }

    .filter-chip-active:hover {
        background-color: #0284c7;
        border-color: #0284c7;
        color: white;
    }

    .filter-chip-active i {
        color: white;
    }

    .filter-chip-inactive {
        background-color: white;
        color: #0ea5e9;
        border: 1px solid #0ea5e9;
    }

    .filter-chip-inactive:hover {
        background-color: #f0f9ff;
        color: #0284c7;
    }

    .filter-chip-inactive i {
        color: #0ea5e9;
    }

    .filter-chip-date {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .filter-chip-date:hover {
        background: #e2e8f0;
    }

    .filter-chip-date i {
        color: #b63352;
    }

    .filter-chip.dropdown-toggle::after {
        display: none !important;
    }

    .filter-dropdown-menu {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        font-size: 0.85rem;
        z-index: 1060 !important;
    }

    .filter-dropdown-menu .dropdown-item {
        padding: 7px 14px;
        font-weight: 500;
        color: #334155;
        display: flex;
        align-items: center;
        transition: background 0.15s ease;
    }

    .filter-dropdown-menu .dropdown-item:hover,
    .filter-dropdown-menu .dropdown-item:focus {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }

    .filter-dropdown-menu .dropdown-item.active,
    .filter-dropdown-menu .dropdown-item:active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 600;
    }

    .sync-date-btn {
        color: #94a3b8 !important;
        transition: color 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .sync-date-btn:hover {
        color: #0ea5e9 !important;
    }

    /* Modernizing the Card / Table */
    .table-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02) !important;
    }

    .table-light th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-dark {
        background-color: #1e293b;
    }

    .table-dark td {
        background-color: #1e293b;
        color: #fff;
        border-color: #334155;
    }



    /* Modal Modernization */
    .modern-modal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modern-modal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 20px 24px;
    }

    .modern-modal .modal-body {
        padding: 24px;
    }

    .modern-modal .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 16px 24px;
        background-color: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .modern-modal .form-control,
    .modern-modal .form-select,
    .ts-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .modern-modal .form-control:focus,
    .ts-control.focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
    }

    .modern-modal .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    /* Tom Select Customization */
    .ts-dropdown {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        margin-top: 4px;
    }

    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 0.95rem;
    }

    .ts-dropdown .option.active {
        background-color: #f1f5f9;
        color: #0f172a;
    }

    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
    }

    .btn-primary {
        background-color: #0ea5e9;
        border-color: #0ea5e9;
    }

    .btn-primary:hover,
    .btn-primary:focus {
        background-color: #0284c7;
        border-color: #0284c7;
        box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25);
    }
</style>
<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<!-- Print Header (Hidden on Screen) -->
<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="mb-1" style="color: #000; font-weight: 600; font-size: 1rem;">Laporan Pelanggan berdasarkan Produk</h6>
            <div style="font-size: 0.7rem; color: #000;">
                <div>Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</div>
                <div>Dibuat pada {{ \Carbon\Carbon::now()->format('d F Y') }}</div>
            </div>
        </div>
        <div class="text-end">
            @php
            $activeOutletName = 'Semua Outlet';
            if (!empty($outletId)) {
            $activeOutletName = collect($outlets)->firstWhere('nid', $outletId)?->cname ?? 'Outlet #' . $outletId;
            } else {
            $mposUser = auth()->check() ? \App\Models\MposUser::with('outlet')->where('nid_user', auth()->id())->first() : null;
            $activeOutletName = $mposUser?->outlet?->cname ?? 'Matahati POS';
            }
            @endphp
            <div style="color: #000; font-weight: 700; font-size: 0.8rem;">{{ $activeOutletName }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Card Kiri: Daftar Laporan -->
    <div class="col-12 col-lg-3 col-xl-3">
        @include('reports.partials.sidebar_card', ['activeReport' => 'customer_product'])
    </div>

    <!-- Card Kanan: Card Laporannya -->
    <div class="col-12 col-lg-9 col-xl-9">
        <div class="card shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 me-3 fw-bold text-dark" style="font-size: 1.1rem;">Laporan Pelanggan</h5>
                </div>
                <div class="d-flex gap-2">
                    @php
                    $isAnyFilterActive = !empty($customerId) || !empty($categoryId) || !empty($productId) || !empty($outletId);
                    @endphp
                    <a href="{{ route('reports.customer-product.export', request()->query()) }}" class="btn btn-outline-success btn-sm" id="btnExportExcel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-danger btn-sm" id="btnPrintPdf">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak / PDF
                    </a>
                </div>
            </div>

            <div class="card-body p-0 d-flex flex-column">
                <!-- Active Filters -->
                <div class="px-4 py-3 bg-light d-print-none order-1 d-flex align-items-center flex-nowrap" style="border-bottom: 2px solid #f1f5f9; position: relative; z-index: 30; overflow: visible !important; gap: 8px;">
                    <span class="fw-bold me-1 text-secondary flex-shrink-0" style="font-size: 0.85rem; text-transform: uppercase;">Filter :</span>

                    <!-- Dropdown Periode Tanggal -->
                    <div class="dropdown d-inline-block flex-shrink-0">
                        <button type="button" class="filter-chip filter-chip-date dropdown-toggle border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                            <i class="bi bi-calendar"></i> {{ $startDate === $endDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                            <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                        </button>
                        <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 350px;">
                            <div class="px-2 py-1 text-muted text-center small fw-bold">Pilih Cepat</div>
                            @php
                            $today = \Carbon\Carbon::today()->format('Y-m-d');
                            $yesterday = \Carbon\Carbon::yesterday()->format('Y-m-d');
                            $last7Days = \Carbon\Carbon::today()->subDays(6)->format('Y-m-d');
                            $thisMonthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                            $thisMonthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
                            $lastMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                            $lastMonthEnd = \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                            @endphp
                            <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['start_date' => $today, 'end_date' => $today])) }}">
                                Hari Ini
                            </a>
                            <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                                Kemarin
                            </a>
                            <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['start_date' => $last7Days, 'end_date' => $today])) }}">
                                7 Hari Lalu
                            </a>
                            <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                                Bulan Ini
                            </a>
                            <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                                Bulan Lalu
                            </a>

                            <hr class="dropdown-divider my-2">
                            <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                            <form action="{{ route('reports.customer-product') }}" method="GET" class="px-1">
                                @if(!empty($outletId)) <input type="hidden" name="outlet_id" value="{{ $outletId }}"> @endif
                                @if(!empty($customerId)) <input type="hidden" name="customer_id" value="{{ $customerId }}"> @endif
                                @if(!empty($categoryId))
                                @if(is_array($categoryId))
                                @foreach($categoryId as $catVal) <input type="hidden" name="category_id[]" value="{{ $catVal }}"> @endforeach
                                @else
                                <input type="hidden" name="category_id[]" value="{{ $categoryId }}">
                                @endif
                                @endif
                                @if(!empty($productId)) <input type="hidden" name="product_id" value="{{ $productId }}"> @endif
                                @if(!empty($sortCol)) <input type="hidden" name="sort_col" value="{{ $sortCol }}"> <input type="hidden" name="sort_dir" value="{{ $sortDir }}"> @endif

                                <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                        <input type="date" id="dropdownStartDate" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                                    </div>
                                    <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                        <button type="button" class="btn btn-link p-1 border-0 sync-date-btn dropdown-sync-date-btn" title="Samakan Tanggal">
                                            <i class="bi bi-arrow-left-right fs-6"></i>
                                        </button>
                                    </div>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                                        <input type="date" id="dropdownEndDate" name="end_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                    Terapkan Tanggal
                                </button>
                            </form>
                        </div>
                    </div>


                    <!-- Dropdown Pelanggan -->
                    <div class="dropdown d-inline-block flex-shrink-0">
                        <button type="button" class="filter-chip {{ $customerId ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Pilih Pelanggan">
                            <i class="bi bi-person"></i> {{ $customerId ? $customerId : 'Semua Pelanggan' }}
                            <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                        </button>
                        <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-0" style="min-width: 240px;">
                            <div class="p-2 border-bottom">
                                <input type="text" class="form-control form-control-sm quick-dropdown-search" placeholder="Cari pelanggan...">
                            </div>
                            <div class="py-1" style="max-height: 250px; overflow-y: auto;">
                                <a class="dropdown-item filter-list-item {{ empty($customerId) ? 'active' : '' }}" href="{{ route('reports.customer-product', request()->except(['customer_id', 'page'])) }}">
                                    Semua Pelanggan
                                    @if(empty($customerId)) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                                </a>
                                @foreach($customers as $c)
                                <a class="dropdown-item filter-list-item {{ $customerId == $c->cname ? 'active' : '' }}" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['customer_id' => $c->cname])) }}">
                                    <span>{{ $c->cname }}</span>
                                    @if($customerId == $c->cname) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Kategori (Multi Checkbox) -->
                    <div class="dropdown d-inline-block flex-shrink-0" id="categoryDropdownContainer">
                        @php
                        $allCatCount = count($categories);
                        $selectedCatCount = is_array($categoryId) ? count($categoryId) : (!empty($categoryId) ? 1 : 0);
                        $isAllSelected = ($allCatCount > 0 && $selectedCatCount === $allCatCount);
                        if (!empty($categoryId)) {
                        if ($isAllSelected) {
                        $catDisplay = 'Semua Kategori';
                        } elseif (is_array($categoryId)) {
                        $catDisplay = count($categoryId) <= 2 ? implode(', ', $categoryId) : count($categoryId) . ' Kategori';
                            } else {
                            $catDisplay=$categoryId;
                            }
                            } else {
                            $catDisplay='Semua Kategori' ;
                            }
                            @endphp
                            <button type="button" class="filter-chip {{ !empty($categoryId) ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" id="catFilterChipBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Pilih Kategori">
                            <i class="bi bi-tags"></i> <span id="catFilterChipText">{{ $catDisplay }}</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                            </button>
                            <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-0" style="min-width: 270px;">
                                <form id="categoryFilterForm" action="{{ route('reports.customer-product') }}" method="GET" onsubmit="return false;">
                                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                                    @if(!empty($outletId)) <input type="hidden" name="outlet_id" value="{{ $outletId }}"> @endif
                                    @if(!empty($customerId)) <input type="hidden" name="customer_id" value="{{ $customerId }}"> @endif
                                    @if(!empty($productId)) <input type="hidden" name="product_id" value="{{ $productId }}"> @endif
                                    @if(!empty($sortCol)) <input type="hidden" name="sort_col" value="{{ $sortCol }}"> <input type="hidden" name="sort_dir" value="{{ $sortDir }}"> @endif

                                    <div class="p-2 border-bottom bg-light rounded-top">
                                        <span class="small fw-semibold text-secondary">Kategori Produk</span>
                                    </div>
                                    <div class="p-2 border-bottom">
                                        <input type="text" class="form-control form-control-sm quick-cat-search" placeholder="Cari kategori...">
                                    </div>
                                    <div class="p-2" style="max-height: 220px; overflow-y: auto;">
                                        <label class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 rounded fw-semibold text-primary border-bottom mb-1 cat-filter-all" style="cursor: pointer;">
                                            <input class="form-check-input mt-0" type="checkbox" id="quickCatCheckAll" {{ $isAllSelected ? 'checked' : '' }}>
                                            <span class="small">Semua Kategori</span>
                                        </label>
                                        @foreach($categories as $cat)
                                        @php
                                        $isSelected = is_array($categoryId) ? in_array($cat->cname, $categoryId) : $categoryId == $cat->cname;
                                        @endphp
                                        <label class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 rounded cat-filter-item" style="cursor: pointer;">
                                            <input class="form-check-input mt-0 quick-cat-cb" type="checkbox" name="category_id[]" value="{{ $cat->cname }}" {{ $isSelected ? 'checked' : '' }}>
                                            <span class="small text-dark">{{ $cat->cname }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </form>
                            </div>
                    </div>

                    <!-- Dropdown Produk -->
                    <!-- <div class="dropdown d-inline-block flex-shrink-0" id="productDropdownContainer">
                        <button type="button" class="filter-chip {{ $productId ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" id="productFilterChipBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Pilih Produk">
                            <i class="bi bi-cup-hot"></i> <span id="productFilterChipText">{{ $productId ? $productId : 'Semua Produk' }}</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                        </button>
                        <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-0" style="min-width: 250px;">
                            <div class="p-2 border-bottom">
                                <input type="text" class="form-control form-control-sm quick-dropdown-search" placeholder="Cari produk...">
                            </div>
                            <div class="py-1 product-dropdown-list" style="max-height: 250px; overflow-y: auto;">
                                <a class="dropdown-item filter-list-item {{ empty($productId) ? 'active' : '' }}" href="{{ route('reports.customer-product', request()->except(['product_id', 'page'])) }}">
                                    Semua Produk
                                    @if(empty($productId)) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                                </a>
                                @foreach($products as $p)
                                <a class="dropdown-item filter-list-item {{ $productId == $p->cname ? 'active' : '' }}" href="{{ route('reports.customer-product', array_merge(request()->except(['page']), ['product_id' => $p->cname])) }}">
                                    <span>{{ $p->cname }}</span>
                                    @if($productId == $p->cname) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div> -->

                </div>

                <!-- Table -->
                <div class="table-responsive order-3 order-print-2">
                    @php
                    $sortUrl = function($col, $defaultDir) use ($sortCol, $sortDir) {
                    $nextDir = $defaultDir;
                    if ($sortCol === $col) {
                    $nextDir = $sortDir === 'asc' ? 'desc' : 'asc';
                    }
                    return request()->fullUrlWithQuery(['sort_col' => $col, 'sort_dir' => $nextDir]);
                    };
                    $sortIcon = function($col) use ($sortCol, $sortDir) {
                    if ($sortCol === $col) {
                    return $sortDir === 'asc' ? '<i class="bi bi-sort-alpha-down ms-1 text-dark"></i>' : '<i class="bi bi-sort-alpha-down-alt ms-1 text-dark"></i>';
                    }
                    return '<i class="bi bi-arrow-down-up ms-1 text-muted opacity-25" style="font-size: 0.7rem;"></i>';
                    };
                    $sortIconNum = function($col) use ($sortCol, $sortDir) {
                    if ($sortCol === $col) {
                    return $sortDir === 'asc' ? '<i class="bi bi-sort-numeric-down ms-1 text-dark"></i>' : '<i class="bi bi-sort-numeric-down-alt ms-1 text-dark"></i>';
                    }
                    return '<i class="bi bi-arrow-down-up ms-1 text-muted opacity-25" style="font-size: 0.7rem;"></i>';
                    };
                    @endphp
                    <table class="table table-hover table-borderless align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                            <tr>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('customer_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Pelanggan {!! $sortIcon('customer_name') !!}</a>
                                </th>
                                @if(!$isSummary)
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('category_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Kategori {!! $sortIcon('category_name') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('product_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Produk {!! $sortIcon('product_name') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('qty', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Qty {!! $sortIconNum('qty') !!}</a>
                                </th>
                                @endif
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('total_penjualan', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Total Penjualan {!! $sortIconNum('total_penjualan') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('diskon', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Diskon {!! $sortIconNum('diskon') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('modal', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Modal Produk {!! $sortIconNum('modal') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('laba', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Laba {!! $sortIconNum('laba') !!}</a>
                                </th>
                                <th class="py-3 text-center">
                                    <a href="{{ $sortUrl('jml_transaksi', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Jumlah Transaksi {!! $sortIconNum('jml_transaksi') !!}</a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reports as $row)
                            @php
                            $modal = 0; // Default as we don't have modal field
                            $laba = $row->total_penjualan - $row->diskon - $modal;
                            @endphp
                            <tr style="border-bottom: 1px solid #f8f9fa;">
                                <td class="text-center">{{ $row->customer_name ?? 'Unknown' }}</td>
                                @if(!$isSummary)
                                <td class="text-center">{{ $row->category_name ?? '-' }}</td>
                                <td class="text-center">{{ $row->product_name }}</td>
                                <td class="text-center">{{ number_format($row->qty, 0, ',', '.') }}</td>
                                @endif
                                <td class="text-center">IDR {{ number_format($row->total_penjualan, 2, ',', '.') }}</td>
                                <td class="text-center">IDR {{ number_format($row->diskon, 2, ',', '.') }}</td>
                                <td class="text-center">IDR {{ number_format($modal, 2, ',', '.') }}</td>
                                <td class="text-center">IDR {{ number_format($laba, 2, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row->jml_transaksi, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isSummary ? 6 : 9 }}" class="text-center py-4 text-muted">Tidak ada data transaksi yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Grand Total -->
                <div class="px-4 py-3 print-grand-total order-2 order-print-3" style="background-color: #1e293b; color: #fff;">
                    <div class="fw-bold mb-2" style="font-size: 0.85rem; text-transform: uppercase; color: #94a3b8;">Grand Total</div>
                    <div class="row w-100 m-0">
                        @if(!$isSummary)
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Qty</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem;">{{ number_format($grandTotals->total_qty ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Total Penjualan</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #34d399;">IDR {{ number_format($grandTotals->total_penjualan ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Diskon</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #f87171;">IDR {{ number_format($grandTotals->total_diskon ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Modal Produk</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #fbbf24;">IDR {{ number_format($grandTotals->total_modal ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Laba</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #60a5fa;">IDR {{ number_format($grandTotals->total_laba ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="col px-0 d-flex flex-column">
                            <span style="font-size: 0.75rem; color: #cbd5e1;">Jml Transaksi</span>
                            <span class="fw-bold print-text-black" style="font-size: 0.9rem;">{{ number_format($grandTotals->total_transaksi ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                @if(!request()->has('print') && method_exists($reports, 'hasPages'))
                <div class="d-flex justify-content-center mt-3 order-4">
                    {{ $reports->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade modern-modal" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('reports.customer-product') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Periode</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                            </div>
                            <div class="col-auto d-flex align-items-center">
                                -
                            </div>
                            <div class="col">
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="form-select tom-select-filter" placeholder="Semua Outlet" {{ ($isOutletLocked ?? false) ? 'disabled' : '' }}>
                            <option value="" {{ empty($outletId) ? 'selected' : '' }}>Semua Outlet</option>
                            @foreach($outlets as $outlet)
                            <option value="{{ $outlet->nid }}" {{ (string)($outletId ?? '') === (string)$outlet->nid ? 'selected' : '' }}>{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @if($isOutletLocked ?? false)
                        <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pelanggan</label>
                        <select name="customer_id" id="customer_id" class="form-select tom-select-filter" placeholder="Semua Pelanggan">
                            <option value="">Semua Pelanggan</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->cname }}" {{ $customerId == $c->cname ? 'selected' : '' }}>{{ $c->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id[]" id="category_id" class="form-select" multiple placeholder="Pilih Kategori (Kosong = Semua)">
                            @foreach($categories as $cat)
                            @php
                            $isSelected = is_array($categoryId) ? in_array($cat->cname, $categoryId) : $categoryId == $cat->cname;
                            @endphp
                            <option value="{{ $cat->cname }}" {{ $isSelected ? 'selected' : '' }}>{{ $cat->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="product_id" id="product_id" class="form-select tom-select-filter" placeholder="Semua Produk">
                            <option value="">Semua Produk</option>
                            @foreach($products as $p)
                            <option value="{{ $p->cname }}" {{ $productId == $p->cname ? 'selected' : '' }}>{{ $p->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="{{ route('reports.customer-product') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Auto-apply Category Filter via JS Fetching
        const catCheckAll = document.getElementById('quickCatCheckAll');
        const catCheckboxes = document.querySelectorAll('.quick-cat-cb');
        let filterFetchTimeout = null;

        function applyCategoryFilterFetch() {
            const checkedBoxes = Array.from(document.querySelectorAll('.quick-cat-cb:checked'));
            const checkedCats = checkedBoxes.map(cb => cb.value);
            const totalCats = document.querySelectorAll('.quick-cat-cb').length;

            // Build new URL
            const url = new URL(window.location.origin + window.location.pathname);
            const params = new URLSearchParams(window.location.search);

            // Clean up category_id and reset page
            params.delete('category_id');
            params.delete('category_id[]');
            params.delete('page');

            checkedCats.forEach(val => {
                params.append('category_id[]', val);
            });

            url.search = params.toString();
            const fetchUrl = url.toString();

            // Update Category Chip UI immediately
            const chipBtn = document.getElementById('catFilterChipBtn');
            const chipText = document.getElementById('catFilterChipText');
            if (chipBtn && chipText) {
                if (checkedCats.length === 0) {
                    chipText.textContent = 'Semua Kategori';
                    chipBtn.classList.remove('filter-chip-active');
                    chipBtn.classList.add('filter-chip-inactive');
                } else if (checkedCats.length === totalCats) {
                    chipText.textContent = 'Semua Kategori';
                    chipBtn.classList.remove('filter-chip-inactive');
                    chipBtn.classList.add('filter-chip-active');
                } else if (checkedCats.length <= 2) {
                    chipText.textContent = checkedCats.join(', ');
                    chipBtn.classList.remove('filter-chip-inactive');
                    chipBtn.classList.add('filter-chip-active');
                } else {
                    chipText.textContent = checkedCats.length + ' Kategori';
                    chipBtn.classList.remove('filter-chip-inactive');
                    chipBtn.classList.add('filter-chip-active');
                }
            }

            // Visual feedback: dim table & grand total during fetch
            const tableContainer = document.querySelector('.table-responsive');
            const grandTotalContainer = document.querySelector('.print-grand-total');
            if (tableContainer) {
                tableContainer.style.transition = 'opacity 0.2s ease';
                tableContainer.style.opacity = '0.5';
            }
            if (grandTotalContainer) {
                grandTotalContainer.style.transition = 'opacity 0.2s ease';
                grandTotalContainer.style.opacity = '0.5';
            }

            fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Fetch failed with status ' + res.status);
                    return res.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // 1. Update Table
                    const newTable = doc.querySelector('.table-responsive');
                    if (newTable && tableContainer) {
                        tableContainer.innerHTML = newTable.innerHTML;
                    }

                    // 2. Update Grand Total
                    const newGrandTotal = doc.querySelector('.print-grand-total');
                    if (newGrandTotal && grandTotalContainer) {
                        grandTotalContainer.innerHTML = newGrandTotal.innerHTML;
                    }

                    // 3. Update Pagination
                    const currentPagination = document.querySelector('.card-body > .order-4');
                    const newPagination = doc.querySelector('.card-body > .order-4');
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    } else if (currentPagination && !newPagination) {
                        currentPagination.remove();
                    } else if (!currentPagination && newPagination) {
                        const pagWrapper = document.createElement('div');
                        pagWrapper.className = 'd-flex justify-content-center mt-3 order-4';
                        pagWrapper.innerHTML = newPagination.innerHTML;
                        document.querySelector('.card-body').appendChild(pagWrapper);
                    }

                    // 4. Update Product Dropdown List
                    const newProductList = doc.querySelector('.product-dropdown-list');
                    const currentProductList = document.querySelector('.product-dropdown-list');
                    if (newProductList && currentProductList) {
                        currentProductList.innerHTML = newProductList.innerHTML;
                    }

                    // 5. Update Export Excel & Cetak / PDF links
                    const exportBtn = document.getElementById('btnExportExcel');
                    const newExportBtn = doc.getElementById('btnExportExcel');
                    if (exportBtn && newExportBtn) {
                        exportBtn.href = newExportBtn.href;
                    }

                    const printBtn = document.getElementById('btnPrintPdf');
                    const newPrintBtn = doc.getElementById('btnPrintPdf');
                    if (printBtn && newPrintBtn) {
                        printBtn.href = newPrintBtn.href;
                    }

                    // 6. Update URL in address bar without reload
                    window.history.pushState({
                        path: fetchUrl
                    }, '', fetchUrl);
                })
                .catch(err => {
                    console.error('Error applying category filter:', err);
                })
                .finally(() => {
                    if (tableContainer) tableContainer.style.opacity = '1';
                    if (grandTotalContainer) grandTotalContainer.style.opacity = '1';
                });
        }

        function debouncedApplyCategoryFilter() {
            clearTimeout(filterFetchTimeout);
            filterFetchTimeout = setTimeout(() => {
                applyCategoryFilterFetch();
            }, 300);
        }

        if (catCheckAll) {
            catCheckAll.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.quick-cat-cb').forEach(cb => {
                    cb.checked = isChecked;
                });
                debouncedApplyCategoryFilter();
            });
        }

        catCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const allCbs = document.querySelectorAll('.quick-cat-cb');
                const allChecked = Array.from(allCbs).length > 0 && Array.from(allCbs).every(c => c.checked);
                if (catCheckAll) {
                    catCheckAll.checked = allChecked;
                }
                debouncedApplyCategoryFilter();
            });
        });

        window.addEventListener('popstate', function() {
            window.location.reload();
        });

        // Live Search for Dropdown Items (Customer, Product)
        document.querySelectorAll('.quick-dropdown-search').forEach(input => {
            input.addEventListener('input', function(e) {
                e.stopPropagation();
                const filter = this.value.toLowerCase().trim();
                const menu = this.closest('.dropdown-menu');
                menu.querySelectorAll('.filter-list-item').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(filter) ? '' : 'none';
                });
            });
            input.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            input.addEventListener('keydown', function(e) {
                e.stopPropagation();
            });
        });

        // Live Search for Category Dropdown Checkboxes
        document.querySelectorAll('.quick-cat-search').forEach(input => {
            input.addEventListener('input', function(e) {
                e.stopPropagation();
                const filter = this.value.toLowerCase().trim();
                const menu = this.closest('.dropdown-menu');
                menu.querySelectorAll('.cat-filter-item').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(filter) ? 'flex' : 'none';
                });
                const allItem = menu.querySelector('.cat-filter-all');
                if (allItem) {
                    allItem.style.display = filter ? 'none' : 'flex';
                }
            });
            input.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
                e.stopPropagation();
            });
        });

        // Initialize Tom Select
        const tomSelects = {};

        document.querySelectorAll('.tom-select-filter').forEach((el) => {
            tomSelects[el.id] = new TomSelect(el, {
                create: false,
                maxItems: 1,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                placeholder: el.getAttribute('placeholder'),
                allowEmptyOption: true,
                onFocus: function() {
                    this.storedValue = this.getValue();
                    this.clear(true);
                },
                onBlur: function() {
                    if (this.getValue() === '') {
                        this.setValue(this.storedValue, true);
                    }
                }
            });
        });

        // Initialize Category Tom Select with checkboxes
        const categoryTs = new TomSelect('#category_id', {
            plugins: ['remove_button'],
            create: false,
            maxItems: null,
            hideSelected: false,
            placeholder: 'Pilih Kategori (Kosong = Semua)',
            render: {
                option: function(data, escape) {
                    const isSelected = this.items.includes(data.value);
                    return '<div class="d-flex align-items-center"><input type="checkbox" class="form-check-input me-2 mt-0" ' + (isSelected ? 'checked' : '') + ' style="pointer-events: none;">' + escape(data.text) + '</div>';
                },
                item: function(data, escape) {
                    return '<div>' + escape(data.text) + '</div>';
                }
            },
            onItemAdd: function(value) {
                const option = this.getOption(value);
                if (option) {
                    const cb = option.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = true;
                }
            },
            onItemRemove: function(value) {
                const option = this.getOption(value);
                if (option) {
                    const cb = option.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = false;
                }
            }
        });

        const productTs = tomSelects['product_id'];

        categoryTs.on('change', function() {
            const categoryId = this.getValue();

            // Clear current product options in Tom Select
            productTs.clear();
            productTs.clearOptions();
            productTs.addOption({
                value: '',
                text: 'Semua Produk'
            });

            // Format for query string
            let queryString = '';
            if (Array.isArray(categoryId)) {
                queryString = categoryId.map(id => `category_id[]=${encodeURIComponent(id)}`).join('&');
            } else if (categoryId) {
                queryString = `category_id[]=${encodeURIComponent(categoryId)}`;
            }

            fetch(`{{ route('reports.customer-product.products') }}?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(product => {
                        productTs.addOption({
                            value: product.cname,
                            text: product.cname
                        });
                    });

                    // Retain selection if applicable
                    if ("{{ $productId }}") {
                        productTs.setValue("{{ $productId }}");
                    } else {
                        productTs.setValue('');
                    }
                })
                .catch(error => console.error('Error fetching products:', error));
        });

        // Add print styling
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                .sidebar, nav.navbar, .btn, .filter-chip, .card-header, .pagination, .page-item {
                    display: none !important;
                }
                .main-content {
                    padding: 0 !important;
                }
                .container-fluid {
                    padding: 0 !important;
                }
                .row {
                    margin: 0 !important;
                }
                .col-md-10 {
                    width: 100% !important;
                    padding: 0 !important;
                }
                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
                .card-body {
                    padding: 0 !important;
                }
                .table-responsive {
                    overflow-x: visible !important;
                }
                table th {
                    color: #000 !important;
                    border-bottom: 1px solid #e5e7eb !important;
                    border: 1px solid #e5e7eb !important;
                    background-color: transparent !important;
                    white-space: nowrap !important;
                }
                table td {
                    color: #000 !important;
                    white-space: normal !important;
                    padding: 4px !important;
                    border: 1px solid #e5e7eb !important;
                }
                table {
                    width: 100% !important;
                    font-size: 0.65rem !important; /* Extremely small font to fit 9 columns in portrait */
                    border-collapse: collapse !important;
                }
                .print-grand-total {
                    border: 1px solid #e5e7eb !important;
                    background-color: transparent !important;
                    color: #000 !important;
                    margin-top: 15px !important;
                }
                .print-grand-total * {
                    color: #000 !important;
                    font-size: 0.65rem !important;
                }
                .print-text-black {
                    color: #000 !important;
                    white-space: nowrap !important;
                    font-size: 0.75rem !important;
                }
                .d-print-block {
                    display: block !important;
                }
                a {
                    text-decoration: none !important;
                    color: #000 !important;
                }
                .bi-arrow-down-up, .bi-sort-alpha-down, .bi-sort-alpha-down-alt, .bi-sort-numeric-down, .bi-sort-numeric-down-alt {
                    display: none !important;
                }
                .order-print-2 {
                    order: 2 !important;
                }
                .order-print-3 {
                    order: 3 !important;
                }
                body {
                    margin-top: 1.5cm;
                    margin-bottom: 1.5cm;
                    margin-left: 1cm;
                    margin-right: 1cm;
                }
            }
            @page {
                size: portrait; /* Force portrait (vertical) orientation */
                margin: 0; /* Menghilangkan header/footer bawaan browser */
            }
        `;
        document.head.appendChild(style);

        // Sync Date Button (Dropdown)
        document.querySelectorAll('.dropdown-sync-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const form = this.closest('form');
                if (form) {
                    const startInp = form.querySelector('input[name="start_date"]');
                    const endInp = form.querySelector('input[name="end_date"]');
                    if (startInp && endInp) {
                        if (startInp.value) {
                            endInp.value = startInp.value;
                        } else if (endInp.value) {
                            startInp.value = endInp.value;
                        }
                    }
                }
            });
        });

        // Auto print if ?print=1 is present
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            window.print();
        }
    });
</script>
@endpush