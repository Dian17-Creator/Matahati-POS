@extends('layouts.app')

@php
$purchaseSubTitles = [
'pending' => 'Pembelian Butuh Diproses',
'received' => 'Pembelian Telah Diproses',
'return' => 'Retur Pembelian',
'cancelled' => 'Pembelian Dibatalkan',
];
$currentPurchaseSubTitle = $purchaseSubTitles[$statusTab] ?? 'Pembelian Butuh Diproses';
@endphp

@section('title', 'Daftar Pembelian / ' . $currentPurchaseSubTitle)
@section('custom_alerts', '1')

@section('sub_header')
@include('purchases.partials.purchase_tabs', ['activeTab' => $statusTab])
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .nav-tabs-custom .nav-link {
        font-weight: 600;
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 20px;
        transition: all 0.2s;
    }

    .nav-tabs-custom .nav-link:hover {
        color: #0284c7;
    }

    .nav-tabs-custom .nav-link.active {
        color: #0284c7;
        background: transparent;
        border-bottom: 2px solid #0284c7;
    }

    .ts-control {
        border-radius: 6px !important;
        font-size: 0.85rem !important;
        min-height: 33px !important;
        padding: 4px 10px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
    }

    .ts-control:focus,
    .ts-control.focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.15) !important;
    }

    .ts-dropdown {
        z-index: 1060 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        font-size: 0.85rem !important;
        border: 1px solid #e2e8f0 !important;
    }

    .ts-dropdown .option {
        padding: 6px 12px !important;
    }

    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }

    #createItemsTable {
        border-collapse: separate;
        border-spacing: 0;
    }

    #createItemsTable th {
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #64748b;
        background: transparent;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        white-space: nowrap;
    }

    #createItemsTable td {
        border-bottom: 1px solid #f1f5f9;
        border-top: none;
        vertical-align: middle;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    #createItemsTable tr:hover td,
    #createReturnItemsTable tr:hover td {
        background-color: #f8fafc;
    }

    #createReturnItemsTable {
        border-collapse: separate;
        border-spacing: 0;
    }

    #createReturnItemsTable th {
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #64748b;
        background: transparent;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        white-space: nowrap;
    }

    #createReturnItemsTable td {
        border-bottom: 1px solid #f1f5f9;
        border-top: none;
        vertical-align: middle;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    #createReturnItemsTable .ts-wrapper.single .ts-control {
        flex-wrap: nowrap !important;
        padding-right: 26px !important;
    }

    #createReturnItemsTable .ts-wrapper.single .ts-control>.item {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px;
    }

    #createReturnItemsTable .ts-wrapper.single .ts-control>input {
        width: 4px !important;
        min-width: 4px !important;
    }

    #createReturnItemsTable .ts-wrapper.single.input-active .ts-control>input {
        width: auto !important;
        min-width: 60px !important;
    }

    /* Modern Filter Chips (Report Style) */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }

    .filter-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        filter: brightness(0.96);
    }

    .filter-chip i {
        margin-right: 6px;
    }

    .filter-chip-active {
        background-color: #0ea5e9;
        color: white;
        border: 1px solid #0ea5e9;
    }

    .filter-chip-active i {
        color: white;
    }

    .filter-chip-inactive {
        background-color: white;
        color: #0ea5e9;
        border: 1px solid #0ea5e9;
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
        color: #0ea5e9;
    }

    .filter-chip.dropdown-toggle::after {
        display: none !important;
    }

    .filter-dropdown-menu {
        border-radius: 12px;
    }

    .sync-date-btn {
        color: #94a3b8;
        transition: color 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .sync-date-btn:hover {
        color: #0ea5e9;
    }

    .table-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(1px);
        z-index: 10;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Qty Stepper Widget */
    .qty-stepper {
        display: inline-flex;
        align-items: stretch;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: all 0.2s ease;
        position: relative;
        max-width: 105px;
        height: 31px;
    }

    .qty-stepper:hover,
    .qty-stepper:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .qty-stepper-btn {
        border: none;
        outline: none;
        width: 27px;
        min-width: 27px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ffffff;
        color: #64748b;
        cursor: pointer;
        position: relative;
        transition: all 0.18s ease;
        padding: 0;
        font-size: 0.75rem;
    }

    .qty-stepper-minus {
        border-right: 1px solid #e2e8f0;
    }

    .qty-stepper-plus {
        border-left: 1px solid #e2e8f0;
    }

    .qty-stepper-btn:hover {
        background-color: #0ea5e9;
        color: #ffffff;
    }

    .qty-stepper-btn:active {
        background-color: #0284c7;
        color: #ffffff;
        transform: scale(0.92);
    }

    .qty-stepper-input {
        border: none !important;
        outline: none !important;
        width: 100%;
        min-width: 36px;
        text-align: center;
        font-weight: 700;
        font-size: 0.82rem;
        color: #0f172a;
        background-color: #ffffff;
        padding: 0 4px;
        margin: 0;
        box-shadow: none !important;
    }

    .qty-stepper-input:focus {
        background-color: #ffffff;
        box-shadow: none !important;
        outline: none !important;
    }

    .qty-stepper-input::-webkit-outer-spin-button,
    .qty-stepper-input::-webkit-inner-spin-button,
    .no-spinners::-webkit-outer-spin-button,
    .no-spinners::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }

    .qty-stepper-input,
    .no-spinners {
        -moz-appearance: textfield !important;
        appearance: textfield !important;
    }
</style>
@endpush

@section('content')
<div class="card olsera-panel-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">{{ $currentPurchaseSubTitle }}</h5>
        <div class="d-flex gap-2">
            @if($statusTab === 'return')
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createReturnModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Retur
            </button>
            @else
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#importPdfModal">
                <i class="bi bi-file-earmark-pdf me-1"></i> Import PDF
            </button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createPurchaseModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Pembelian
            </button>
            @endif
        </div>
    </div>

    @php
    if (!empty($startDate) && !empty($endDate)) {
    $dateLabel = \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
    } elseif (!empty($startDate)) {
    $dateLabel = 'Sejak ' . \Carbon\Carbon::parse($startDate)->format('d M Y');
    } elseif (!empty($endDate)) {
    $dateLabel = 'Hingga ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
    } else {
    $dateLabel = 'Semua Tanggal';
    }

    $statusFilterLabels = $statusTab === 'return'
    ? [
    '' => 'Semua Status',
    'PENDING' => 'Pending',
    'POSTED' => 'Diposting',
    'CANCELLED' => 'Batal',
    ]
    : [
    '' => 'Semua Status',
    'PENDING' => 'Pending',
    'RECEIVED' => 'Diterima',
    ];
    $activeStatusFilter = $statusFilter ?? '';
    $statusFilterLabelText = $statusFilterLabels[$activeStatusFilter] ?? 'Semua Status';

    $paymentFilterLabels = [
    '' => 'Semua Pembayaran',
    'UNPAID' => 'Belum',
    'PAID' => 'Selesai',
    ];
    $activePaymentFilter = $paymentStatus ?? '';
    $paymentFilterLabelText = $paymentFilterLabels[$activePaymentFilter] ?? 'Semua Pembayaran';
    @endphp

    <!-- Active Filters Bar (Report Style: Status, Pembayaran, Date, Search) -->
    <div class="px-4 py-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold me-1 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">FILTER :</span>

            @if($statusTab === 'return')
            <!-- Dropdown Status Retur -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip {{ $activeStatusFilter !== '' ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" id="statusFilterChip" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel"></i> <span id="statusFilterLabel">{{ $statusFilterLabelText }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-1">
                    @foreach($statusFilterLabels as $val => $lbl)
                    <button type="button" class="dropdown-item rounded py-1 px-3 d-flex justify-content-between align-items-center status-filter-btn {{ $activeStatusFilter === $val ? 'active' : '' }}" data-status-filter="{{ $val }}">
                        <span>{{ $lbl }}</span>
                        <i class="bi bi-check2 check-icon {{ $activeStatusFilter === $val ? '' : 'd-none' }}"></i>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Dropdown Pembayaran / Pengembalian -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip {{ $activePaymentFilter !== '' ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" id="paymentFilterChip" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-cash-coin"></i> <span id="paymentFilterLabel">{{ $paymentFilterLabelText }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-1">
                    @foreach($paymentFilterLabels as $val => $lbl)
                    <button type="button" class="dropdown-item rounded py-1 px-3 d-flex justify-content-between align-items-center payment-filter-btn {{ $activePaymentFilter === $val ? 'active' : '' }}" data-payment-filter="{{ $val }}">
                        <span>{{ $lbl }}</span>
                        <i class="bi bi-check2 check-icon {{ $activePaymentFilter === $val ? '' : 'd-none' }}"></i>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Dropdown Periode Tanggal -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip filter-chip-date dropdown-toggle border-0" id="dateFilterChip" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                    <i class="bi bi-calendar"></i> <span id="dateFilterLabel">{{ $dateLabel }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 350px;">
                    <div class="px-2 py-1 text-muted text-center small fw-bold">Pilih Cepat</div>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="today">
                        Hari Ini
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="yesterday">
                        Kemarin
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="last7days">
                        7 Hari Lalu
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="thismonth">
                        Bulan Ini
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="lastmonth">
                        Bulan Lalu
                    </button>

                    <hr class="dropdown-divider my-2">
                    <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                    <div class="px-1">
                        <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                <input type="date" id="customStartDate" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}">
                            </div>
                            <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                <button type="button" class="btn btn-link p-1 border-0 sync-date-btn" id="syncCustomDateBtn" title="Samakan Tanggal">
                                    <i class="bi bi-arrow-left-right fs-6"></i>
                                </button>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                                <input type="date" id="customEndDate" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}">
                            </div>
                        </div>
                        <button type="button" id="applyCustomDateBtn" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                            Terapkan Tanggal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Searching Box -->
        <div class="d-flex align-items-center">
            <div class="input-group input-group-sm" style="width: {{ $statusTab === 'return' ? '360px' : '700px' }}; max-width: 100%;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="filterSearchInput" class="form-control border-start-0 ps-0" placeholder="{{ $statusTab === 'return' ? 'Cari No. Retur, No. PO, Supplier...' : 'Cari No. PO, Supplier, No. SJ...' }}" value="{{ $search }}">
                <button class="btn btn-white border border-start-0 text-muted" type="button" id="clearSearchBtn" @style(['display: none'=> !$search]) title="Hapus Pencarian">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Purchases Table Container with Realtime Loading -->
    <div class="card-body p-0 position-relative" id="purchasesTableContainer" style="min-height: 220px;">
        <div id="tableLoadingOverlay" class="table-loading-overlay d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Memuat data...</span>
            </div>
        </div>

        <div id="tableContent" class="p-3">
            @include($statusTab === 'return' ? 'purchases.partials.return_table' : 'purchases.partials.table', ['purchases' => $purchases])
        </div>
    </div>
</div>

<!-- Modal Import Pembelian dari PDF -->
<div class="modal fade" id="importPdfModal" tabindex="-1" aria-labelledby="importPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light-subtle py-3 border-bottom">
                <h5 class="modal-title fw-bold" id="importPdfModalLabel">
                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Import Pembelian dari PDF
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchases.import-pdf') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border small text-muted mb-3 d-flex align-items-start">
                        <i class="bi bi-info-circle text-primary me-2 mt-1 fs-6"></i>
                        <div>
                            Unggah dokumen invoice / purchase order dalam format PDF (misal: Olsera/IQOS). Sistem otomatis membaca nomor pembelian, tanggal beli, jatuh tempo, mencocokkan produk, stok, dan harga beli satuan.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="import_supplier_id" class="form-label fw-semibold small">Pilih Supplier <span class="text-danger">*</span></label>
                        <select class="form-select @error('nid_supplier') is-invalid @enderror" id="import_supplier_id" name="nid_supplier" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->nid }}" {{ (string)old('nid_supplier') === (string)$s->nid ? 'selected' : '' }}>
                                {{ $s->cname }}
                            </option>
                            @endforeach
                        </select>
                        @error('nid_supplier')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="import_outlet_id" class="form-label fw-semibold small">Outlet Tujuan Stok <span class="text-danger">*</span></label>
                        @if($isOutletLocked)
                        @php
                        $lockedOutlet = $outlets->firstWhere('nid', $outletId);
                        @endphp
                        <input type="text" class="form-control bg-light" value="{{ $lockedOutlet ? $lockedOutlet->cname : 'Outlet Terkunci' }}" readonly>
                        <input type="hidden" name="nid_outlet" value="{{ $outletId }}">
                        @else
                        <select class="form-select @error('nid_outlet') is-invalid @enderror" id="import_outlet_id" name="nid_outlet" required>
                            <option value="">-- Pilih Outlet --</option>
                            @foreach($outlets as $o)
                            <option value="{{ $o->nid }}" {{ (string)($outletId ?? old('nid_outlet')) === (string)$o->nid ? 'selected' : '' }}>
                                {{ $o->cname }}
                            </option>
                            @endforeach
                        </select>
                        @error('nid_outlet')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @endif
                    </div>

                    <div class="mb-2">
                        <label for="import_pdf_file" class="form-label fw-semibold small">File PDF (.pdf) <span class="text-danger">*</span></label>
                        <input class="form-control @error('pdf_file') is-invalid @enderror" type="file" id="import_pdf_file" name="pdf_file" accept=".pdf,application/pdf" required>
                        @error('pdf_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-upload me-1"></i> Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pembelian Baru (Fit & Clean Layout) -->
<div class="modal fade" id="createPurchaseModal" tabindex="-1" aria-labelledby="createPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1040px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.store') }}" method="POST" id="createPurchaseForm">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="createPurchaseModalLabel">
                        <i class="bi bi-cart-plus me-2 text-primary"></i>Pembelian Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Form Header Info (Baris 1: Supplier, Outlet, Tanggal Beli, Jatuh Tempo) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Supplier <span class="text-danger">*</span></label>
                            <select name="nid_supplier" id="modalCreateSupplier" class="form-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $sp)
                                <option value="{{ $sp->nid }}" data-outlet="{{ $sp->nid_outlet ?? '' }}" {{ old('nid_supplier') == $sp->nid ? 'selected' : '' }}>
                                    {{ $sp->cname }} @if($sp->outlet) ({{ $sp->outlet->cname }}) @endif {{ $sp->cphone ? "({$sp->cphone})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Outlet Tujuan Stok <span class="text-danger">*</span></label>
                            <select name="nid_outlet" id="modalCreateOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
                                @foreach($outlets as $ot)
                                <option value="{{ $ot->nid }}" {{ (string)($outletId ?: $outlets->first()?->nid) === (string)$ot->nid ? 'selected' : '' }}>
                                    {{ $ot->cname }}
                                </option>
                                @endforeach
                            </select>
                            @if($isOutletLocked)
                            <input type="hidden" name="nid_outlet" value="{{ $outletId }}">
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Beli <span class="text-danger">*</span></label>
                            <input type="date" name="dpurchase" class="form-control" value="{{ old('dpurchase', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Jatuh Tempo</label>
                            <input type="date" name="ddue_date" class="form-control" value="{{ old('ddue_date') }}">
                        </div>
                    </div>

                    <!-- Baris 2: Catatan Pembelian -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Pembelian</label>
                            <textarea name="cnote" class="form-control" rows="3" style="min-height: 80px;" placeholder="Catatan PO atau keterangan tambahan...">{{ old('cnote') }}</textarea>
                        </div>
                    </div>

                    <!-- Header Section Item Pesanan -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-box-seam me-1 text-primary"></i> Item Pesanan
                            </span>
                            <span class="text-muted small ms-2 d-none d-sm-inline">Pilih tipe <strong>Produk</strong>, <strong>Setengah Jadi</strong>, atau <strong>Bahan Baku</strong> yang dibeli</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" id="btnAddNewItemRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <!-- Table Item Tanpa Box/Card Wrapper -->
                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="createItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 35px;" class="text-center py-2 text-muted text-nowrap">No</th>
                                    <th style="width: 135px;" class="py-2 text-center text-nowrap">TIPE ITEM</th>
                                    <th class="py-2 text-center text-nowrap">PRODUK / BAHAN BAKU</th>
                                    <th style="width: 115px;" class="text-center py-2 text-nowrap">STOK SAAT INI</th>
                                    <th style="width: 115px;" class="text-center py-2 text-nowrap">QTY</th>
                                    <th style="width: 155px;" class="text-center py-1 text-nowrap">HARGA BELI</th>
                                    <th style="width: 125px;" class="text-end py-2 text-nowrap">SUBTOTAL</th>
                                    <th style="width: 35px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="createItemsTableBody" class="small">
                                <!-- Baris produk / bahan baku via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Baris Ringkasan Total (Flat & Rapi) -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="text-muted small">
                            <i></i>
                        </div>
                        <div class="d-flex align-items-center gap-4 text-end">
                            <div>
                                <span class="text-secondary small d-block">Total Kuantitas:</span>
                                <span class="fw-bold fs-6 text-dark" id="createTotalQty">0</span> <span class="small text-muted">unit</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Total Nilai Pesanan:</span>
                                <span class="fw-bold fs-5 text-primary" id="createTotalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Retur Barang (Alur serupa Pembelian Baru) -->
<div class="modal fade" id="createReturnModal" tabindex="-1" aria-labelledby="createReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1120px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.returns.store') }}" method="POST" id="createReturnForm">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="createReturnModalLabel">
                        <i class="bi bi-arrow-return-left me-2 text-warning"></i>Retur Pembelian Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Baris 1: Supplier, Nomor Pembelian, Outlet (otomatis), Tanggal Retur -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Supplier <span class="text-danger">*</span></label>
                            <select name="nid_supplier" id="modalReturnSupplier" class="form-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $sp)
                                <option value="{{ $sp->nid }}">
                                    {{ $sp->cname }} @if($sp->outlet) ({{ $sp->outlet->cname }}) @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Nomor Pembelian (PO Diterima) <span class="text-danger">*</span></label>
                            <select name="nid_purchase_ref" id="modalReturnPurchase" class="form-select" required disabled>
                                <option value="">-- Pilih Supplier Dulu --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Outlet Asal Stok</label>
                            <input type="text" id="modalReturnOutletDisplay" class="form-control bg-light" value="-" readonly placeholder="Otomatis dari PO">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Retur <span class="text-danger">*</span></label>
                            <input type="date" name="dpurchase" id="modalReturnDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <!-- Baris 2: Catatan Retur -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Retur</label>
                            <textarea name="cnote" class="form-control" rows="2" style="min-height: 70px;" placeholder="Catatan alasan atau keterangan retur barang..."></textarea>
                        </div>
                    </div>

                    <!-- Header Section Item Retur -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-box-seam me-1 text-primary"></i> Barang Retur
                            </span>
                            <span class="text-muted small ms-2 d-none d-sm-inline">Hanya menampilkan barang &amp; stok dari <strong>Nomor Pembelian</strong> yang dipilih</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" id="btnAddReturnItemRow" disabled>
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <div id="returnPoInfoBanner" class="alert alert-info py-2 px-3 small mt-2 mb-2 d-none"></div>

                    <!-- Table Item Retur -->
                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="createReturnItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 35px;" class="text-center py-2 text-muted text-nowrap">No</th>
                                    <th style="min-width: 280px;" class="py-2 text-nowrap">BARANG (DARI PO)</th>
                                    <th style="width: 120px;" class="text-center py-2 text-nowrap">QTY RETUR</th>
                                    <th style="width: 115px;" class="text-end py-2 text-nowrap">HARGA BELI</th>
                                    <th style="min-width: 220px; width: 235px;" class="py-2 text-nowrap">ALASAN</th>
                                    <th style="width: 120px;" class="text-end py-2 text-nowrap">SUBTOTAL</th>
                                    <th style="width: 35px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="createReturnItemsTableBody" class="small">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Pilih <strong>Supplier</strong> dan <strong>Nomor Pembelian</strong> untuk melihat barang yang dapat diretur.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Baris Ringkasan Total Retur -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="text-muted small">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            Stok akan otomatis berkurang (<strong>Stok Keluar</strong>) saat dokumen diposting pada halaman Rincian Retur.
                        </div>
                        <div class="d-flex align-items-center gap-4 text-end">
                            <div>
                                <span class="text-secondary small d-block">Total Kuantitas Retur:</span>
                                <span class="fw-bold fs-6 text-dark" id="createReturnTotalQty">0</span> <span class="small text-muted">unit</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Total Nilai Retur:</span>
                                <span class="fw-bold fs-5 text-primary" id="createReturnTotalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitReturnForm" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Simpan Draft Retur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script type="application/json" id="purchasesProductsData">
    @json($products ?? [])
</script>
<script>
    const allProducts = JSON.parse(document.getElementById('purchasesProductsData')?.textContent || '[]');
    let rowIndex = 0;
    const tsInstances = {};

    function getSelectedOutletId() {
        const outletSelect = document.getElementById('modalCreateOutlet');
        if (outletSelect) {
            return outletSelect.value;
        }
        return "{{ $outletId }}";
    }

    function formatStockNumber(val) {
        const num = parseFloat(val || 0);
        if (Number.isNaN(num)) return '0';
        return Number.isInteger(num) ? String(num) : String(parseFloat(num.toFixed(2)));
    }

    function normalizeItemType(val) {
        const raw = String(val || 'PRODUK').trim().toUpperCase();
        if (raw === 'SETENGAH_JADI') return 'SETENGAH_JADI';
        if (raw === 'BAHAN_BAKU' || raw === 'INGREDIENT') return 'BAHAN_BAKU';
        return 'PRODUK';
    }

    function getItemsForType(itemType, selectedOutletId) {
        const targetType = normalizeItemType(itemType);
        const byName = new Map();

        allProducts.forEach(p => {
            const pType = normalizeItemType(p.cproduct_type || 'PRODUK');
            if (pType !== targetType) return;

            const key = String(p.cname || '').trim().toUpperCase();
            if (!key) return;
            if (!byName.has(key)) {
                byName.set(key, []);
            }
            byName.get(key).push(p);
        });

        const list = [];
        byName.forEach((prods) => {
            const match = prods.find(p => String(p.nid_outlet) === String(selectedOutletId));
            if (match) {
                list.push({
                    nid: match.nid,
                    cname: match.cname,
                    nstock: match.nqty || 0,
                    nprice: (match.nprice_buy !== undefined && match.nprice_buy !== null) ? parseFloat(match.nprice_buy) : 0,
                    nprice_buy: parseFloat(match.nprice_buy || 0),
                    csatuan: match.csatuan || 'PCS',
                    cproduct_type: match.cproduct_type || 'PRODUK'
                });
            } else {
                const tmpl = prods[0];
                list.push({
                    nid: tmpl.nid,
                    cname: tmpl.cname,
                    nstock: 0,
                    nprice: (tmpl.nprice_buy !== undefined && tmpl.nprice_buy !== null) ? parseFloat(tmpl.nprice_buy) : 0,
                    nprice_buy: parseFloat(tmpl.nprice_buy || 0),
                    csatuan: tmpl.csatuan || 'PCS',
                    cproduct_type: tmpl.cproduct_type || 'PRODUK'
                });
            }
        });

        list.sort((a, b) => a.cname.localeCompare(b.cname));
        return list;
    }

    function getPlaceholderForType(itemType) {
        const type = normalizeItemType(itemType);
        if (type === 'BAHAN_BAKU') {
            return '-- Cari atau pilih bahan baku --';
        }
        if (type === 'SETENGAH_JADI') {
            return '-- Cari atau pilih produk setengah jadi --';
        }
        return '-- Cari atau pilih produk --';
    }

    function addRow(itemId = '', qty = 1, cost = 0, itemType = 'PRODUK') {
        const tbody = document.getElementById('createItemsTableBody');
        const outletId = getSelectedOutletId();
        const normalizedType = normalizeItemType(itemType);
        const itemList = getItemsForType(normalizedType, outletId);
        const placeholderText = getPlaceholderForType(normalizedType);
        const currentIdx = rowIndex++;

        const tr = document.createElement('tr');
        tr.id = `item-row-${currentIdx}`;
        tr.setAttribute('data-row-id', currentIdx);
        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle row-number py-2"></td>
            <td class="py-2">
                <select name="items[${currentIdx}][citem_type]" class="form-select form-select-sm fw-semibold item-type-select" id="type_select_${currentIdx}" data-row="${currentIdx}">
                    <option value="PRODUK" ${normalizedType === 'PRODUK' ? 'selected' : ''}>Produk</option>
                    <option value="SETENGAH_JADI" ${normalizedType === 'SETENGAH_JADI' ? 'selected' : ''}>Setengah Jadi</option>
                    <option value="BAHAN_BAKU" ${normalizedType === 'BAHAN_BAKU' ? 'selected' : ''}>Bahan Baku</option>
                </select>
            </td>
            <td class="py-2">
                <select name="items[${currentIdx}][item_id]" class="form-select form-select-sm item-product-select" id="product_select_${currentIdx}">
                    <option value="">${placeholderText}</option>
                    ${itemList.map(p => `<option value="${p.nid}" ${String(itemId) === String(p.nid) ? 'selected' : ''} data-stock="${p.nstock}" data-unit="${p.csatuan}">${p.cname}</option>`).join('')}
                </select>
            </td>
            <td class="text-center align-middle py-2">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 item-stock-badge fw-medium" id="stock-badge-${currentIdx}">-</span>
            </td>
            <td class="py-2 text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus btn-qty-minus" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" name="items[${currentIdx}][nqty]" class="qty-stepper-input item-qty-input no-spinners" value="${qty}" min="1" step="1" data-row="${currentIdx}" placeholder="1" required>
                    <button type="button" class="qty-stepper-btn qty-stepper-plus btn-qty-plus" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="py-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted px-2 border-end-0">Rp</span>
                    <input type="number" name="items[${currentIdx}][ncost]" class="form-control form-control-sm text-end fw-semibold item-cost-input border-start-0 ps-1" value="${cost}" min="0" step="any" data-row="${currentIdx}">
                </div>
            </td>
            <td class="text-end align-middle fw-bold text-dark py-2 item-subtotal-text" id="subtotal-${currentIdx}">
                Rp 0
            </td>
            <td class="text-center align-middle py-2">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle btn-remove-row" data-row="${currentIdx}" title="Hapus Baris" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const typeSelectEl = tr.querySelector('.item-type-select');
        const selectEl = tr.querySelector('.item-product-select');
        const qtyInput = tr.querySelector('.item-qty-input');
        const btnMinus = tr.querySelector('.btn-qty-minus');
        const btnPlus = tr.querySelector('.btn-qty-plus');
        const costInput = tr.querySelector('.item-cost-input');
        const removeBtn = tr.querySelector('.btn-remove-row');

        // Inisialisasi TomSelect untuk pencarian live search yang rapi
        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: placeholderText,
            allowEmptyOption: true,
            sortField: {
                field: "text",
                direction: "asc"
            },
            render: {
                item: function(data, escape) {
                    const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                    return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                },
                option: function(data, escape) {
                    if (!data.value) {
                        return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                    }
                    const stockVal = data.nstock !== undefined ? data.nstock : (data.stock !== undefined ? data.stock : '-');
                    const unitVal = data.csatuan || data.unit || 'PCS';
                    const stockLabel = stockVal === '-' ? '-' : `${formatStockNumber(stockVal)} ${unitVal}`;
                    const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                    return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                        '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                        '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                        '</div>';
                }
            }
        });
        tsInstances[currentIdx] = ts;

        function populateOptionsForRow(rowType, selectedOutletId, keepByName = '') {
            const list = getItemsForType(rowType, selectedOutletId);
            const ph = getPlaceholderForType(rowType);

            ts.settings.placeholder = ph;
            ts.inputState();
            ts.clear(true);
            ts.clearOptions();

            list.forEach(item => {
                ts.addOption({
                    value: String(item.nid),
                    text: item.cname,
                    cname: item.cname,
                    nstock: item.nstock,
                    csatuan: item.csatuan || 'PCS'
                });
            });
            ts.refreshOptions(false);

            if (keepByName) {
                const matched = list.find(it => String(it.cname).trim().toLowerCase() === String(keepByName).trim().toLowerCase());
                if (matched) {
                    ts.setValue(String(matched.nid), true);
                    const stockBadge = document.getElementById(`stock-badge-${currentIdx}`);
                    if (stockBadge) {
                        stockBadge.textContent = `${formatStockNumber(matched.nstock)} ${matched.csatuan || 'PCS'}`;
                    }
                    return;
                }
            }

            const stockBadge = document.getElementById(`stock-badge-${currentIdx}`);
            if (stockBadge) {
                stockBadge.textContent = '-';
            }
        }

        // Pastikan metadata nstock & csatuan terset pada opsi awal
        populateOptionsForRow(normalizedType, outletId);
        if (itemId) {
            ts.setValue(String(itemId), true);
        }

        typeSelectEl.addEventListener('change', function() {
            const newType = normalizeItemType(this.value);
            costInput.value = 0;
            populateOptionsForRow(newType, getSelectedOutletId());
            recalculateSubtotal(currentIdx);
        });

        ts.on('change', function(val) {
            const currentOutletId = getSelectedOutletId();
            const currentType = normalizeItemType(typeSelectEl ? typeSelectEl.value : 'PRODUK');
            const currentList = getItemsForType(currentType, currentOutletId);
            const selectedItem = currentList.find(p => String(p.nid) === String(val));
            const stockBadge = document.getElementById(`stock-badge-${currentIdx}`);

            if (selectedItem) {
                stockBadge.textContent = `${formatStockNumber(selectedItem.nstock)} ${selectedItem.csatuan || 'PCS'}`;
                costInput.value = parseFloat(selectedItem.nprice || 0);
            } else {
                stockBadge.textContent = '-';
            }
            recalculateSubtotal(currentIdx);
        });

        tr._refreshForOutlet = function(newOutletId) {
            const currentType = normalizeItemType(typeSelectEl ? typeSelectEl.value : 'PRODUK');
            const curVal = ts.getValue();
            const curOption = curVal ? ts.options[curVal] : null;
            const curName = curOption ? (curOption.cname || curOption.text || '') : '';
            populateOptionsForRow(currentType, newOutletId, curName);
        };

        if (btnMinus) {
            btnMinus.addEventListener('click', function() {
                const currentVal = parseInt(qtyInput.value, 10) || 1;
                qtyInput.value = Math.max(1, currentVal - 1);
                recalculateSubtotal(currentIdx);
            });
        }

        if (btnPlus) {
            btnPlus.addEventListener('click', function() {
                const currentVal = parseInt(qtyInput.value, 10) || 0;
                qtyInput.value = currentVal + 1;
                recalculateSubtotal(currentIdx);
            });
        }

        qtyInput.addEventListener('input', () => recalculateSubtotal(currentIdx));
        costInput.addEventListener('input', () => recalculateSubtotal(currentIdx));

        removeBtn.addEventListener('click', function() {
            if (tsInstances[currentIdx]) {
                tsInstances[currentIdx].destroy();
                delete tsInstances[currentIdx];
            }
            tr.remove();
            updateRowNumbers();
            recalculateTotals();

            // Bila semua baris dihapus, buat 1 baris kosong otomatis agar selalu ada baris input
            const curTbody = document.getElementById('createItemsTableBody');
            if (curTbody && curTbody.children.length === 0) {
                addRow();
            }
        });

        updateRowNumbers();
        recalculateSubtotal(currentIdx);
    }

    function recalculateSubtotal(idx) {
        const row = document.getElementById(`item-row-${idx}`);
        if (!row) return;

        const qty = parseFloat(row.querySelector('.item-qty-input')?.value || 0);
        const cost = parseFloat(row.querySelector('.item-cost-input')?.value || 0);
        const subtotal = Math.max(0, qty * cost);

        const subtotalText = document.getElementById(`subtotal-${idx}`);
        if (subtotalText) {
            subtotalText.textContent = 'Rp ' + subtotal.toLocaleString('id-ID', {
                maximumFractionDigits: 2
            });
        }

        recalculateTotals();
    }

    function recalculateTotals() {
        let totalQty = 0;
        let totalAmount = 0;

        document.querySelectorAll('#createItemsTableBody tr').forEach(tr => {
            const rowIdx = tr.getAttribute('data-row-id');
            const ts = tsInstances[rowIdx];
            if (ts && ts.getValue()) {
                const qty = parseFloat(tr.querySelector('.item-qty-input')?.value || 0);
                const cost = parseFloat(tr.querySelector('.item-cost-input')?.value || 0);
                totalQty += qty;
                totalAmount += (qty * cost);
            }
        });

        const totalQtyEl = document.getElementById('createTotalQty');
        const totalAmountEl = document.getElementById('createTotalAmount');

        if (totalQtyEl) totalQtyEl.textContent = totalQty.toLocaleString('id-ID');
        if (totalAmountEl) totalAmountEl.textContent = 'Rp ' + totalAmount.toLocaleString('id-ID', {
            maximumFractionDigits: 2
        });
    }

    function updateRowNumbers() {
        document.querySelectorAll('#createItemsTableBody tr').forEach((tr, index) => {
            const numEl = tr.querySelector('.row-number');
            if (numEl) numEl.textContent = index + 1;
        });
    }

    function refreshAllProductSelects() {
        const outletId = getSelectedOutletId();
        document.querySelectorAll('#createItemsTableBody tr').forEach(tr => {
            if (typeof tr._refreshForOutlet === 'function') {
                tr._refreshForOutlet(outletId);
            }
        });
        recalculateTotals();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addBtn = document.getElementById('btnAddNewItemRow');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                addRow();
            });
        }

        const outletSelect = document.getElementById('modalCreateOutlet');
        if (outletSelect) {
            outletSelect.addEventListener('change', function() {
                refreshAllProductSelects();
            });
        }

        // Siapkan 1 baris kosong otomatis ketika modal dibuka pertama kali
        const createModalEl = document.getElementById('createPurchaseModal');
        if (createModalEl) {
            createModalEl.addEventListener('show.bs.modal', function() {
                const tbody = document.getElementById('createItemsTableBody');
                if (tbody && tbody.children.length === 0) {
                    addRow();
                }
            });
        }

        // ==========================================
        // Real-Time AJAX Fetching & Filter Handler
        // ==========================================
        let currentTab = @json($statusTab);
        let currentStatusFilter = @json($statusFilter ?? '');
        let currentPaymentStatus = @json($paymentStatus ?? '');
        let currentStartDate = @json($startDate ?? '');
        let currentEndDate = @json($endDate ?? '');
        let currentSearch = @json($search ?? '');
        let abortController = null;
        let searchDebounceTimeout = null;

        const dateFilterChip = document.getElementById('dateFilterChip');
        const dateFilterLabel = document.getElementById('dateFilterLabel');
        const statusFilterChip = document.getElementById('statusFilterChip');
        const statusFilterLabel = document.getElementById('statusFilterLabel');
        const paymentFilterChip = document.getElementById('paymentFilterChip');
        const paymentFilterLabel = document.getElementById('paymentFilterLabel');
        const customStartDate = document.getElementById('customStartDate');
        const customEndDate = document.getElementById('customEndDate');
        const filterSearchInput = document.getElementById('filterSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const overlay = document.getElementById('tableLoadingOverlay');
        const tableContent = document.getElementById('tableContent');

        const statusFilterNames = {
            '': 'Semua Status',
            'PENDING': 'Pending',
            'RECEIVED': 'Diterima',
            'POSTED': 'Diposting',
            'CANCELLED': 'Batal'
        };

        const paymentFilterNames = {
            '': 'Semua Pembayaran',
            'UNPAID': 'Belum',
            'PAID': 'Selesai'
        };

        function formatYmd(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function formatDisplayDate(d) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const day = d.getDate();
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            return `${day} ${month} ${year}`;
        }

        function fetchPurchases(page = 1) {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            if (overlay) overlay.classList.remove('d-none');

            const params = new URLSearchParams();
            if (currentTab) params.append('tab', currentTab);
            if (currentStatusFilter) params.append('status_filter', currentStatusFilter);
            if (currentPaymentStatus) params.append('payment_status', currentPaymentStatus);
            if (currentStartDate) params.append('start_date', currentStartDate);
            if (currentEndDate) params.append('end_date', currentEndDate);
            if (currentSearch) params.append('search', currentSearch);
            if (page && page > 1) params.append('page', page);
            params.append('ajax', '1');

            const fetchUrl = '{{ route("purchases.index") }}?' + params.toString();

            // Update URL in browser address bar without reloading
            const displayParams = new URLSearchParams(params);
            displayParams.delete('ajax');
            const displayUrl = '{{ route("purchases.index") }}' + (displayParams.toString() ? '?' + displayParams.toString() : '');
            window.history.replaceState({}, '', displayUrl);

            fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: abortController.signal
                })
                .then(res => res.json())
                .then(data => {
                    if (data.html && tableContent) {
                        tableContent.innerHTML = data.html;
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Gagal memuat data pembelian:', err);
                    }
                })
                .finally(() => {
                    if (overlay) overlay.classList.add('d-none');
                });
        }

        // Sub-filter Status Handler
        function setStatusFilter(filterVal) {
            currentStatusFilter = filterVal;

            if (statusFilterLabel) {
                statusFilterLabel.textContent = statusFilterNames[filterVal] || 'Semua Status';
            }
            if (statusFilterChip) {
                if (filterVal !== '') {
                    statusFilterChip.classList.remove('filter-chip-inactive');
                    statusFilterChip.classList.add('filter-chip-active');
                } else {
                    statusFilterChip.classList.remove('filter-chip-active');
                    statusFilterChip.classList.add('filter-chip-inactive');
                }
            }

            document.querySelectorAll('.status-filter-btn').forEach(btn => {
                const btnStatus = btn.getAttribute('data-status-filter') || '';
                const check = btn.querySelector('.check-icon');
                if (btnStatus === filterVal) {
                    btn.classList.add('active');
                    if (check) check.classList.remove('d-none');
                } else {
                    btn.classList.remove('active');
                    if (check) check.classList.add('d-none');
                }
            });

            fetchPurchases(1);
        }

        // Status dropdown items click
        document.querySelectorAll('.status-filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const filterVal = this.getAttribute('data-status-filter') || '';
                setStatusFilter(filterVal);
            });
        });

        // Payment filter dropdown items click (Retur)
        document.querySelectorAll('.payment-filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const val = this.getAttribute('data-payment-filter') || '';
                currentPaymentStatus = val;

                if (paymentFilterLabel) {
                    paymentFilterLabel.textContent = paymentFilterNames[val] || 'Semua Pembayaran';
                }
                if (paymentFilterChip) {
                    if (val !== '') {
                        paymentFilterChip.classList.remove('filter-chip-inactive');
                        paymentFilterChip.classList.add('filter-chip-active');
                    } else {
                        paymentFilterChip.classList.remove('filter-chip-active');
                        paymentFilterChip.classList.add('filter-chip-inactive');
                    }
                }

                document.querySelectorAll('.payment-filter-btn').forEach(item => {
                    const itemVal = item.getAttribute('data-payment-filter') || '';
                    const check = item.querySelector('.check-icon');
                    if (itemVal === val) {
                        item.classList.add('active');
                        if (check) check.classList.remove('d-none');
                    } else {
                        item.classList.remove('active');
                        if (check) check.classList.add('d-none');
                    }
                });

                fetchPurchases(1);
            });
        });

        // Date Presets click
        document.querySelectorAll('.quick-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const preset = this.getAttribute('data-preset');
                const now = new Date();

                if (preset === 'today') {
                    currentStartDate = formatYmd(now);
                    currentEndDate = formatYmd(now);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Hari Ini (' + formatDisplayDate(now) + ')';
                } else if (preset === 'yesterday') {
                    const y = new Date();
                    y.setDate(now.getDate() - 1);
                    currentStartDate = formatYmd(y);
                    currentEndDate = formatYmd(y);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Kemarin (' + formatDisplayDate(y) + ')';
                } else if (preset === 'last7days') {
                    const past = new Date();
                    past.setDate(now.getDate() - 6);
                    currentStartDate = formatYmd(past);
                    currentEndDate = formatYmd(now);
                    if (dateFilterLabel) dateFilterLabel.textContent = formatDisplayDate(past) + ' - ' + formatDisplayDate(now);
                } else if (preset === 'thismonth') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    currentStartDate = formatYmd(firstDay);
                    currentEndDate = formatYmd(lastDay);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Bulan Ini (' + formatDisplayDate(firstDay) + ' - ' + formatDisplayDate(lastDay) + ')';
                } else if (preset === 'lastmonth') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
                    currentStartDate = formatYmd(firstDay);
                    currentEndDate = formatYmd(lastDay);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Bulan Lalu (' + formatDisplayDate(firstDay) + ' - ' + formatDisplayDate(lastDay) + ')';
                } else if (preset === 'all') {
                    currentStartDate = '';
                    currentEndDate = '';
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Semua Tanggal';
                }

                if (customStartDate) customStartDate.value = currentStartDate;
                if (customEndDate) customEndDate.value = currentEndDate;

                // Close date dropdown
                if (dateFilterChip) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dateFilterChip);
                    if (bsDropdown) bsDropdown.hide();
                }

                fetchPurchases(1);
            });
        });

        // Custom Date Apply
        const applyCustomDateBtn = document.getElementById('applyCustomDateBtn');
        if (applyCustomDateBtn) {
            applyCustomDateBtn.addEventListener('click', function() {
                const sVal = customStartDate ? customStartDate.value : '';
                const eVal = customEndDate ? customEndDate.value : '';

                if (!sVal && !eVal) {
                    currentStartDate = '';
                    currentEndDate = '';
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Semua Tanggal';
                } else {
                    currentStartDate = sVal;
                    currentEndDate = eVal;

                    if (sVal && eVal) {
                        const sD = new Date(sVal + 'T00:00:00');
                        const eD = new Date(eVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = formatDisplayDate(sD) + ' - ' + formatDisplayDate(eD);
                    } else if (sVal) {
                        const sD = new Date(sVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = 'Sejak ' + formatDisplayDate(sD);
                    } else {
                        const eD = new Date(eVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = 'Hingga ' + formatDisplayDate(eD);
                    }
                }

                // Close date dropdown
                if (dateFilterChip) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dateFilterChip);
                    if (bsDropdown) bsDropdown.hide();
                }

                fetchPurchases(1);
            });
        }

        // Tombol sync / panah bolak-balik untuk menyamakan tanggal Dari ke Sampai
        const syncCustomDateBtn = document.getElementById('syncCustomDateBtn');
        if (syncCustomDateBtn && customStartDate && customEndDate) {
            syncCustomDateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (customStartDate.value) {
                    customEndDate.value = customStartDate.value;
                } else if (customEndDate.value) {
                    customStartDate.value = customEndDate.value;
                }
            });
        }

        // Search Input with Debounce
        if (filterSearchInput) {
            filterSearchInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = val ? 'block' : 'none';
                }
                clearTimeout(searchDebounceTimeout);
                searchDebounceTimeout = setTimeout(function() {
                    currentSearch = val;
                    fetchPurchases(1);
                }, 350);
            });

            filterSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimeout);
                    currentSearch = this.value.trim();
                    fetchPurchases(1);
                }
            });
        }

        // Clear Search Button
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                if (filterSearchInput) {
                    filterSearchInput.value = '';
                }
                this.style.display = 'none';
                currentSearch = '';
                fetchPurchases(1);
            });
        }

        // AJAX Pagination delegation
        if (tableContent) {
            tableContent.addEventListener('click', function(e) {
                const link = e.target.closest('.pagination a');
                if (link) {
                    e.preventDefault();
                    try {
                        const url = new URL(link.href);
                        const page = url.searchParams.get('page') || 1;
                        fetchPurchases(page);
                    } catch (err) {
                        // fallback
                    }
                }
            });
        }

        // ==========================================
        // Modal Tambah Retur Barang (Alur Pembelian Baru)
        // ==========================================
        const returnModalEl = document.getElementById('createReturnModal');
        const returnSupplierSelect = document.getElementById('modalReturnSupplier');
        const returnPurchaseSelect = document.getElementById('modalReturnPurchase');
        const returnOutletDisplay = document.getElementById('modalReturnOutletDisplay');
        const returnItemsTbody = document.getElementById('createReturnItemsTableBody');
        const btnAddReturnRow = document.getElementById('btnAddReturnItemRow');
        const btnSubmitReturn = document.getElementById('btnSubmitReturnForm');
        const returnPoInfoBanner = document.getElementById('returnPoInfoBanner');
        const returnTotalQtyEl = document.getElementById('createReturnTotalQty');
        const returnTotalAmountEl = document.getElementById('createReturnTotalAmount');

        let returnPoTomSelect = null;
        let currentPoReturnItems = [];
        let returnRowCounter = 0;

        const returnReasons = [
            'Barang Rusak / Cacat',
            'Salah Kirim Barang',
            'Kadaluarsa',
            'Kelebihan Kirim',
            'Tidak Sesuai Pesanan',
            'Lainnya'
        ];

        function formatRupiahRetur(num) {
            const val = Math.round(Number(num) || 0);
            return 'Rp ' + val.toLocaleString('id-ID');
        }

        function destroyReturnRowTomSelects() {
            if (!returnItemsTbody) return;
            returnItemsTbody.querySelectorAll('tr.return-item-row').forEach(tr => {
                if (tr._returnTs) {
                    tr._returnTs.destroy();
                    tr._returnTs = null;
                }
            });
        }

        function resetReturnItemsTable(messageHtml) {
            destroyReturnRowTomSelects();
            currentPoReturnItems = [];
            if (returnOutletDisplay) returnOutletDisplay.value = '-';
            if (btnAddReturnRow) btnAddReturnRow.disabled = true;
            if (btnSubmitReturn) btnSubmitReturn.disabled = true;
            if (returnPoInfoBanner) returnPoInfoBanner.classList.add('d-none');
            if (returnItemsTbody) {
                returnItemsTbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">' + messageHtml + '</td></tr>';
            }
            updateReturnTotals();
        }

        function updateReturnTotals() {
            if (!returnItemsTbody) return;
            let totalQty = 0;
            let totalAmount = 0;
            let activeRows = 0;

            returnItemsTbody.querySelectorAll('tr.return-item-row').forEach((tr, idx) => {
                const noCell = tr.querySelector('.return-row-no');
                if (noCell) noCell.textContent = idx + 1;

                const sel = tr.querySelector('.return-item-select');
                const qtyInput = tr.querySelector('.return-item-qty');
                const costInput = tr.querySelector('.return-item-cost');
                const subtotalEl = tr.querySelector('.return-item-subtotal');

                const qty = parseInt(qtyInput ? qtyInput.value : '0', 10) || 0;
                const cost = parseFloat(costInput ? costInput.value : '0') || 0;
                const sub = qty * cost;

                if (subtotalEl) subtotalEl.textContent = formatRupiahRetur(sub);

                if (sel && sel.value && qty > 0) {
                    activeRows++;
                    totalQty += qty;
                    totalAmount += sub;
                }
            });

            if (returnTotalQtyEl) returnTotalQtyEl.textContent = totalQty;
            if (returnTotalAmountEl) returnTotalAmountEl.textContent = formatRupiahRetur(totalAmount);
            if (btnSubmitReturn) btnSubmitReturn.disabled = activeRows === 0;
            syncAddReturnRowState();
        }

        function getReturnablePoItems() {
            return currentPoReturnItems.filter(it => Number(it.qty_remaining) > 0);
        }

        function syncAddReturnRowState() {
            if (!btnAddReturnRow || !returnItemsTbody) return;
            const maxRows = getReturnablePoItems().length;
            const currentRows = returnItemsTbody.querySelectorAll('tr.return-item-row').length;
            btnAddReturnRow.disabled = maxRows === 0 || currentRows >= maxRows;
        }

        function addReturnItemRow() {
            if (!returnItemsTbody || currentPoReturnItems.length === 0) return;

            const availableItems = getReturnablePoItems();
            const currentRows = returnItemsTbody.querySelectorAll('tr.return-item-row').length;
            if (availableItems.length === 0 || currentRows >= availableItems.length) {
                syncAddReturnRowState();
                return;
            }

            if (returnItemsTbody.querySelector('tr:not(.return-item-row)')) {
                returnItemsTbody.innerHTML = '';
            }

            const idx = returnRowCounter++;
            const tr = document.createElement('tr');
            tr.className = 'return-item-row';

            let optionsHtml = '<option value="">-- Cari atau pilih barang dari PO --</option>';
            availableItems.forEach(item => {
                optionsHtml += `<option value="${item.nid_purchase_detail_ref}" data-type="${item.item_type_label}" data-name="${item.item_name}" data-remaining="${item.qty_remaining}" data-unit="${item.unit}">[${item.item_type_label}] ${item.item_name}</option>`;
            });

            let reasonOptionsHtml = '<option value="">-- Pilih Alasan (Opsional) --</option>';
            returnReasons.forEach(r => {
                reasonOptionsHtml += `<option value="${r}">${r}</option>`;
            });

            tr.innerHTML = `
                <td class="text-center text-muted return-row-no">1</td>
                <td>
                    <select name="items[${idx}][nid_purchase_detail_ref]" class="form-select form-select-sm return-item-select">
                        ${optionsHtml}
                    </select>
                </td>
                <td class="text-center">
                    <div class="qty-stepper mx-auto">
                        <button type="button" class="qty-stepper-btn qty-stepper-minus return-qty-minus" title="Kurangi 1" disabled>
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <input type="number" name="items[${idx}][nqty]" class="qty-stepper-input return-item-qty no-spinners" min="1" step="1" value="1" disabled required>
                        <button type="button" class="qty-stepper-btn qty-stepper-plus return-qty-plus" title="Tambah 1" disabled>
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <input type="hidden" name="items[${idx}][csatuan]" class="return-item-unit" value="">
                </td>
                <td class="text-end">
                    <span class="return-item-cost-text text-muted">-</span>
                    <input type="hidden" name="items[${idx}][ncost]" class="return-item-cost" value="0">
                </td>
                <td>
                    <select name="items[${idx}][creason]" class="form-select form-select-sm return-item-reason">
                        ${reasonOptionsHtml}
                    </select>
                </td>
                <td class="text-end fw-bold text-dark return-item-subtotal">Rp 0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-return-row" title="Hapus Baris">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            `;

            const selectEl = tr.querySelector('.return-item-select');
            const qtyEl = tr.querySelector('.return-item-qty');
            const btnMinus = tr.querySelector('.return-qty-minus');
            const btnPlus = tr.querySelector('.return-qty-plus');
            const unitEl = tr.querySelector('.return-item-unit');
            const costTextEl = tr.querySelector('.return-item-cost-text');
            const costHiddenEl = tr.querySelector('.return-item-cost');

            let returnItemTs = null;
            if (typeof TomSelect !== 'undefined') {
                returnItemTs = new TomSelect(selectEl, {
                    create: false,
                    maxItems: 1,
                    placeholder: '-- Cari atau pilih barang dari PO --',
                    allowEmptyOption: true,
                    sortField: {
                        field: 'text',
                        direction: 'asc'
                    },
                    render: {
                        item: function(data, escape) {
                            const label = data.name ? `[${data.type || 'Produk'}] ${data.name}` : (data.text || '');
                            if (!data.value) {
                                return '<span class="text-truncate fw-medium text-muted">' + escape(label) + '</span>';
                            }
                            const qtyBadge = (data.remaining !== undefined ? data.remaining : '0') + ' ' + (data.unit || 'PCS');
                            return '<span class="text-truncate fw-medium">' + escape(label) + '</span>' +
                                '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-0.5 small flex-shrink-0">' + escape(qtyBadge) + '</span>';
                        },
                        option: function(data, escape) {
                            if (!data.value) {
                                return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                            }
                            const qtyLabel = (data.remaining !== undefined ? data.remaining : '0') + ' ' + (data.unit || 'PCS');
                            const itemLabel = data.name ? `[${data.type || 'Produk'}] ${data.name}` : (data.text || '');
                            return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                                '<span class="fw-medium text-dark text-truncate me-2">' + escape(itemLabel) + '</span>' +
                                '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-0.5 small flex-shrink-0">Qty: ' + escape(qtyLabel) + '</span>' +
                                '</div>';
                        }
                    }
                });

                returnItemTs.clear(true);
                returnItemTs.clearOptions();
                availableItems.forEach(item => {
                    returnItemTs.addOption({
                        value: String(item.nid_purchase_detail_ref),
                        text: `[${item.item_type_label}] ${item.item_name}`,
                        name: item.item_name,
                        type: item.item_type_label,
                        remaining: item.qty_remaining,
                        unit: item.unit || 'PCS'
                    });
                });
                returnItemTs.refreshOptions(false);
                tr._returnTs = returnItemTs;
            }

            function handleReturnItemSelected(selectedVal) {
                const selectedId = Number(selectedVal || 0);
                const found = currentPoReturnItems.find(it => Number(it.nid_purchase_detail_ref) === selectedId);
                if (!found) {
                    qtyEl.value = 1;
                    qtyEl.disabled = true;
                    btnMinus.disabled = true;
                    btnPlus.disabled = true;
                    unitEl.value = '';
                    costTextEl.textContent = '-';
                    costHiddenEl.value = 0;
                    updateReturnTotals();
                    return;
                }

                // Cek duplikasi pilihan barang dalam satu retur
                const duplicate = Array.from(returnItemsTbody.querySelectorAll('.return-item-select'))
                    .some(other => other !== selectEl && Number(other.value) === selectedId);
                if (duplicate) {
                    alert('Barang ini sudah dipilih pada baris lain.');
                    if (returnItemTs) {
                        returnItemTs.clear(true);
                    } else {
                        selectEl.value = '';
                    }
                    handleReturnItemSelected('');
                    return;
                }

                qtyEl.disabled = false;
                btnMinus.disabled = false;
                btnPlus.disabled = false;
                qtyEl.max = found.qty_remaining;
                qtyEl.value = 1;
                unitEl.value = found.unit;
                costTextEl.textContent = formatRupiahRetur(found.ncost);
                costHiddenEl.value = found.ncost;

                updateReturnTotals();
            }

            if (returnItemTs) {
                returnItemTs.on('change', function(val) {
                    handleReturnItemSelected(val);
                });
            } else {
                selectEl.addEventListener('change', function() {
                    handleReturnItemSelected(this.value);
                });
            }

            btnMinus.addEventListener('click', function() {
                let currentVal = parseInt(qtyEl.value || '1', 10);
                if (Number.isNaN(currentVal) || currentVal <= 1) {
                    qtyEl.value = 1;
                } else {
                    qtyEl.value = currentVal - 1;
                }
                qtyEl.dispatchEvent(new Event('input'));
            });

            btnPlus.addEventListener('click', function() {
                const selectedId = Number(selectEl.value || 0);
                const found = currentPoReturnItems.find(it => Number(it.nid_purchase_detail_ref) === selectedId);
                const maxVal = found ? Number(found.qty_remaining) : 999999;
                let currentVal = parseInt(qtyEl.value || '1', 10);
                if (Number.isNaN(currentVal)) currentVal = 1;
                if (currentVal < maxVal) {
                    qtyEl.value = currentVal + 1;
                } else {
                    qtyEl.value = maxVal;
                }
                qtyEl.dispatchEvent(new Event('input'));
            });

            qtyEl.addEventListener('input', function() {
                const selectedId = Number(selectEl.value || 0);
                const found = currentPoReturnItems.find(it => Number(it.nid_purchase_detail_ref) === selectedId);
                let val = parseInt(this.value || '0', 10);
                if (this.value !== '' && (Number.isNaN(val) || val < 1)) {
                    val = 1;
                    this.value = val;
                }
                if (found && val > found.qty_remaining) {
                    val = found.qty_remaining;
                    this.value = val;
                }
                updateReturnTotals();
            });

            qtyEl.addEventListener('blur', function() {
                let val = parseInt(this.value || '0', 10);
                if (Number.isNaN(val) || val < 1) {
                    this.value = 1;
                    updateReturnTotals();
                }
            });

            tr.querySelector('.btn-remove-return-row').addEventListener('click', function() {
                if (tr._returnTs) {
                    tr._returnTs.destroy();
                    tr._returnTs = null;
                }
                tr.remove();
                if (returnItemsTbody.querySelectorAll('tr.return-item-row').length === 0) {
                    addReturnItemRow();
                } else {
                    updateReturnTotals();
                }
            });

            returnItemsTbody.appendChild(tr);
            updateReturnTotals();
        }

        function loadPurchasesForReturnSupplier(supplierId) {
            if (!returnPurchaseSelect) return;

            if (returnPoTomSelect) {
                returnPoTomSelect.destroy();
                returnPoTomSelect = null;
            }

            if (!supplierId) {
                returnPurchaseSelect.innerHTML = '<option value="">-- Pilih Supplier Dulu --</option>';
                returnPurchaseSelect.disabled = true;
                resetReturnItemsTable('Pilih <strong>Supplier</strong> dan <strong>Nomor Pembelian</strong> untuk melihat barang yang dapat diretur.');
                return;
            }

            returnPurchaseSelect.disabled = true;
            returnPurchaseSelect.innerHTML = '<option value="">Memuat daftar PO...</option>';
            resetReturnItemsTable('Memuat nomor pembelian...');

            const url = @json(route('purchases.returns.purchases-by-supplier')) + '?supplier_id=' + encodeURIComponent(supplierId);
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const list = data.purchases || [];
                    if (list.length === 0) {
                        returnPurchaseSelect.innerHTML = '<option value="">Tidak ada PO Diterima untuk supplier ini</option>';
                        returnPurchaseSelect.disabled = true;
                        resetReturnItemsTable('Supplier ini belum memiliki Nomor Pembelian berstatus <strong>Diterima</strong>.');
                        return;
                    }

                    let html = '<option value="">-- Pilih Nomor Pembelian --</option>';
                    list.forEach(po => {
                        const disabledAttr = po.remaining_qty <= 0 ? 'disabled' : '';
                        html += `<option value="${po.nid}" ${disabledAttr}>${po.cno_purchase}</option>`;
                    });
                    returnPurchaseSelect.innerHTML = html;
                    returnPurchaseSelect.disabled = false;

                    if (typeof TomSelect !== 'undefined') {
                        returnPoTomSelect = new TomSelect(returnPurchaseSelect, {
                            allowEmptyOption: true,
                            placeholder: '-- Cari Nomor Pembelian --'
                        });
                    }

                    resetReturnItemsTable('Pilih <strong>Nomor Pembelian</strong> untuk menampilkan barang &amp; stok dari PO tersebut.');
                })
                .catch(err => {
                    console.error('Gagal memuat PO supplier:', err);
                    returnPurchaseSelect.innerHTML = '<option value="">Gagal memuat PO</option>';
                });
        }

        function loadPoItemsForReturn(purchaseId) {
            if (!purchaseId) {
                resetReturnItemsTable('Pilih <strong>Nomor Pembelian</strong> untuk menampilkan barang &amp; stok dari PO tersebut.');
                return;
            }

            const baseUrl = @json(route('purchases.returns.po-items', ['purchaseId' => '__PO_ID__']));
            const url = baseUrl.replace('__PO_ID__', encodeURIComponent(purchaseId));

            destroyReturnRowTomSelects();
            if (returnItemsTbody) {
                returnItemsTbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Memuat barang &amp; stok dari PO...</td></tr>';
            }

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const po = data.purchase || {};
                    const items = data.items || [];
                    currentPoReturnItems = items;

                    if (returnOutletDisplay) returnOutletDisplay.value = po.outlet_name || '-';

                    const returnableItems = items.filter(it => Number(it.qty_remaining) > 0);
                    if (returnPoInfoBanner) {
                        returnPoInfoBanner.innerHTML = `<i class="bi bi-receipt me-1"></i> PO <strong>${po.cno_purchase}</strong> (${po.dpurchase_formatted}) • Outlet: <strong>${po.outlet_name}</strong> • Barang tersedia untuk retur: <strong>${returnableItems.length} item</strong>`;
                        returnPoInfoBanner.classList.remove('d-none');
                    }

                    if (returnableItems.length === 0) {
                        if (btnAddReturnRow) btnAddReturnRow.disabled = true;
                        if (btnSubmitReturn) btnSubmitReturn.disabled = true;
                        returnItemsTbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Seluruh barang pada Nomor Pembelian ini sudah habis diretur.</td></tr>';
                        updateReturnTotals();
                        return;
                    }

                    destroyReturnRowTomSelects();
                    returnItemsTbody.innerHTML = '';
                    addReturnItemRow();
                    syncAddReturnRowState();
                })
                .catch(err => {
                    console.error('Gagal memuat item PO:', err);
                    resetReturnItemsTable('Gagal memuat barang dari Nomor Pembelian.');
                });
        }

        if (returnSupplierSelect) {
            returnSupplierSelect.addEventListener('change', function() {
                loadPurchasesForReturnSupplier(this.value);
            });
        }

        if (returnPurchaseSelect) {
            returnPurchaseSelect.addEventListener('change', function() {
                loadPoItemsForReturn(this.value);
            });
        }

        if (btnAddReturnRow) {
            btnAddReturnRow.addEventListener('click', function() {
                addReturnItemRow();
            });
        }

        if (returnModalEl) {
            returnModalEl.addEventListener('show.bs.modal', function() {
                if (returnSupplierSelect && returnSupplierSelect.value && (!returnPurchaseSelect || returnPurchaseSelect.options.length <= 1)) {
                    loadPurchasesForReturnSupplier(returnSupplierSelect.value);
                }
            });
        }
    });
</script>
@endpush