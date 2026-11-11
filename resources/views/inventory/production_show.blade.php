@extends('layouts.app')

@section('title', 'Inventori / Detail Stok Produksi')
@section('custom_alerts', '1')

@section('sub_header')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="catalog-nav-tab">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="catalog-nav-tab">
            <span>Stok Keluar</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}" class="catalog-nav-tab active">
            <span>Produksi Stok</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}" class="catalog-nav-tab">
            <span>Stok Opname</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}" class="catalog-nav-tab">
            <span>Pergerakan Stok</span>
        </a>
    </div>
</div>

<style>
    .catalog-headbar-tabs {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .catalog-horizontal-nav {
        width: 100%;
        padding: 0;
        margin: 0;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .catalog-horizontal-nav::-webkit-scrollbar {
        display: none;
    }

    .catalog-nav-tab {
        flex: 1 1 0;
        min-width: 120px;
        padding: 12px 16px;
        font-size: 0.86rem;
        font-weight: 500;
        color: #334155;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-bottom: 2.5px solid transparent;
        transition: all 0.2s ease;
    }

    .catalog-nav-tab:hover {
        color: #b63352;
        background-color: #fff8f9;
    }

    .catalog-nav-tab.active {
        color: #b63352;
        font-weight: 600;
        background-color: #fff1f2;
        border-bottom-color: #b63352;
    }

    /* Qty Stepper Modern Olsera Styling */
    .qty-stepper {
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #0ea5e9;
        border-radius: 6px;
        overflow: hidden;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(14, 165, 233, 0.08);
        transition: all 0.15s ease-in-out;
        position: relative;
        max-width: 120px !important;
        height: 33px !important;
    }

    .qty-stepper:hover,
    .qty-stepper:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 2.5px rgba(14, 165, 233, 0.18);
    }

    .qty-stepper-btn {
        border: none;
        outline: none;
        width: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        transition: all 0.18s ease;
        padding: 0;
        font-size: 0.85rem;
    }

    .qty-stepper-minus {
        background-color: #ffffff;
        color: #64748b;
        border-right: 1px solid #e2e8f0;
    }

    .qty-stepper-minus:hover {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }

    .qty-stepper-plus {
        background-color: #0ea5e9;
        color: #ffffff;
    }

    .qty-stepper-plus:hover {
        background-color: #0284c7;
        color: #ffffff;
    }

    .qty-stepper-input {
        border: none !important;
        outline: none !important;
        width: 100%;
        min-width: 45px;
        text-align: center;
        font-weight: 700;
        font-size: 0.9rem;
        color: #0f172a;
        background-color: #ffffff;
        padding: 0 4px;
        margin: 0;
        box-shadow: none !important;
    }

    .qty-stepper-input::-webkit-outer-spin-button,
    .qty-stepper-input::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }

    .qty-stepper-input {
        -moz-appearance: textfield !important;
        appearance: textfield !important;
    }

    .prod-panel-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03) !important;
    }

    .table-prod-bordered,
    .table-prod-bordered th,
    .table-prod-bordered td {
        border-color: #e2e8f0 !important;
    }

    .ts-control {
        border-radius: 6px !important;
        border-color: #cbd5e1 !important;
        min-height: 38px !important;
        padding: 6px 12px !important;
        font-size: 0.88rem !important;
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
        font-size: 0.88rem !important;
        border: 1px solid #e2e8f0 !important;
    }

    .ts-dropdown .option {
        padding: 6px 12px !important;
    }

    /* Materials Accordion Toggle Arrow */
    .btn-materials-toggle .materials-toggle-arrow {
        font-size: 0.72rem;
        transition: transform 0.25s ease-in-out;
        display: inline-block;
    }

    .btn-materials-toggle[aria-expanded="true"] .materials-toggle-arrow,
    .btn-materials-toggle:not(.collapsed) .materials-toggle-arrow {
        transform: rotate(180deg);
    }

    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }

    /* Input Qty & Tombol Aksi Olsera Style (Batal X / Simpan Centang) */
    .qty-edit-input {
        max-width: 110px;
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 8px;
        background-color: #ffffff;
        color: #0f172a;
    }

    .qty-edit-input:focus {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.15);
    }

    .btn-olsera-circle {
        width: 26px;
        height: 26px;
        padding: 0;
        border: none;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        color: #ffffff;
        cursor: pointer;
        transition: transform 0.12s ease, opacity 0.15s ease;
    }

    .btn-olsera-circle:hover {
        transform: scale(1.06);
        opacity: 0.92;
    }

    .btn-olsera-circle-cancel {
        background-color: #ef4444;
    }

    .btn-olsera-circle-save {
        background-color: #0ea5e9;
    }

    .btn-olsera-icon-action {
        border: none;
        background: transparent;
        padding: 2px 4px;
        line-height: 1;
        cursor: pointer;
        transition: opacity 0.15s ease;
    }

    .btn-olsera-icon-action:hover {
        opacity: 0.75;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Banner Status Olsera --}}
    @if($production->isDraft())
    <div class="alert alert-warning py-2 px-3 small mb-3 border-0 bg-warning-subtle text-warning-emphasis d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle fs-5"></i>
        <span>Pastikan data sudah benar sebelum diposting. Setelah terposting, data tidak diperbolehkan diubah.</span>
    </div>
    @elseif($production->isPosted())
    <div class="alert alert-success py-2 px-3 small mb-3 border-0 bg-success-subtle text-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle fs-5"></i>
        <span><strong>Status Posted:</strong> Dokumen produksi ini telah diposting. Stok bahan baku telah dipotong dan stok produk jadi telah ditambahkan ke sistem.</span>
    </div>
    @else
    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 bg-danger-subtle text-danger d-flex align-items-center gap-2">
        <i class="bi bi-x-circle fs-5"></i>
        <span><strong>Status Batal:</strong> Dokumen transaksi produksi ini telah Dibatalkan.</span>
    </div>
    @endif

    {{-- Header Card Dokumen Olsera Style --}}
    <div class="card prod-panel-card mb-3 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-light border text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-gear-wide-connected fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">{{ $production->cno_production }}</h5>
                    <div>
                        @if($production->isPosted())
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Posted</span>
                        @elseif($production->isDraft())
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill">Draft</span>
                        @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">Batal</span>
                        @endif
                        <span class="text-muted small ms-2"><i class="bi bi-shop me-1"></i>{{ $production->outlet?->cname ?: 'Outlet #' . $production->nid_outlet }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Tombol Kembali ke Daftar --}}
                <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}" class="btn btn-sm btn-outline-secondary px-3 d-flex align-items-center gap-1 shadow-xs">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                </a>

                {{-- Tombol Cetak Langsung PDF --}}
                <a href="{{ route('inventory.production.print', $production->nid) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-3 d-flex align-items-center gap-1">
                    <i class="bi bi-printer"></i>
                    <span>Cetak</span>
                </a>

                @if($production->isPosted())
                {{-- Tombol Sinkronkan Stok Fisik ke Master Produk --}}
                <form action="{{ route('inventory.production.sync-stock', $production->nid) }}" method="POST" onsubmit="return confirm('Perbarui / sinkronkan stok fisik produk dan bahan baku ke Master Produk?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success px-3 d-flex align-items-center gap-1 shadow-sm" title="Sinkronkan stok fisik jika belum terupdate">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Sinkronkan Stok</span>
                    </button>
                </form>
                @endif

                @if($production->isDraft())
                {{-- Tombol Batalkan --}}
                <button type="button" class="btn btn-sm btn-outline-danger px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalCancelConfirm">
                    <i class="bi bi-x-circle"></i>
                    <span>Batalkan</span>
                </button>

                {{-- Tombol Posting Sekarang --}}
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalPostingConfirm">
                    <i class="bi bi-check2-circle"></i>
                    <span>Posting Sekarang</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Cards Informasi: Tanggal & Catatan --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card prod-panel-card p-3 h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Tanggal</span>
                    @if($production->isDraft())
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#modalUbahHeader">
                        <i class="bi bi-pencil me-1"></i>Ubah
                    </button>
                    @endif
                </div>
                <div class="fw-bold text-dark fs-6">{{ \Carbon\Carbon::parse($production->dproduction)->format('d-M-Y') }}</div>
                <div class="text-muted small mt-1">{{ $production->user?->cemail ?: ($production->user?->email ?: ($production->user?->cname ?: 'Admin')) }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card prod-panel-card p-3 h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Catatan</span>
                    @if($production->isDraft())
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#modalUbahHeader">
                        <i class="bi bi-pencil me-1"></i>Ubah
                    </button>
                    @endif
                </div>
                <div class="text-dark">{{ $production->cnote ?: '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Tabel Hasil Produksi & Bahan Baku --}}
    <div class="card prod-panel-card mb-3">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">Detail Stok Produksi</h6>
            @if($production->isDraft())
            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm d-inline-flex align-items-center gap-1" onclick="showInlineProductRow()">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Produk</span>
            </button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-prod-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small fw-semibold">
                        <th class="px-3 py-3" style="width: 46%;">Nama Produk</th>
                        <th class="px-3 py-3 text-center" style="width: 16%;">Qty</th>
                        <th class="px-3 py-3 text-center" style="width: 14%;">Satuan</th>
                        <th class="px-3 py-3 text-center" style="width: 14%;">Bahan</th>
                        <th class="px-3 py-3 text-center text-nowrap" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($production->products as $pItem)
                    {{-- Baris Produk Jadi --}}
                    <tr class="bg-white">
                        <td class="px-3 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded border p-1 bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-box-seam fs-5"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark">{{ $pItem->product?->cname ?: 'Produk #' . $pItem->nid_product }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-2 text-center align-middle" style="min-width: 130px;">
                            @if($production->isDraft())
                            <div id="prodQtyDisplay_{{ $pItem->nid }}">
                                <span class="fw-bold text-dark fs-6">{{ number_format($pItem->nqty, 2, ',', '.') }}</span>
                            </div>
                            <div id="prodQtyEdit_{{ $pItem->nid }}" class="d-none">
                                <input type="number"
                                    step="any"
                                    min="0.01"
                                    name="nqty"
                                    form="editProdForm_{{ $pItem->nid }}"
                                    class="form-control form-control-sm qty-edit-input mx-auto"
                                    id="prod_qty_edit_input_{{ $pItem->nid }}"
                                    value="{{ (float)$pItem->nqty }}"
                                    data-original="{{ (float)$pItem->nqty }}"
                                    required>
                            </div>
                            @else
                            <span class="fw-bold text-dark fs-6">{{ number_format($pItem->nqty, 2, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center text-muted">
                            {{ $pItem->product?->csatuan ?: 'pcs' }}
                        </td>
                        <td class="px-3 py-3 text-center">
                            @php
                            $linkedMaterials = $production->materials->where('nid_production_product', $pItem->nid);
                            $isMatOpen = $linkedMaterials->isNotEmpty();
                            @endphp
                            <button type="button"
                                class="btn btn-sm btn-primary py-1 px-3 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-1 btn-materials-toggle {{ $isMatOpen ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse"
                                data-bs-target="#materialsCollapse{{ $pItem->nid }}"
                                aria-expanded="{{ $isMatOpen ? 'true' : 'false' }}">
                                <span>Bahan ({{ $linkedMaterials->count() }})</span>
                                <i class="bi bi-chevron-down ms-1 materials-toggle-arrow"></i>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center text-nowrap">
                            @if($production->isDraft())
                            <div id="prodAksiDisplay_{{ $pItem->nid }}" class="d-inline-flex align-items-center gap-2">
                                <form action="{{ route('inventory.production.products.destroy', [$production->nid, $pItem->nid]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini beserta bahan yang terkait?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-olsera-icon-action text-danger" title="Hapus Produk">
                                        <i class="bi bi-x-lg fs-6"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn-olsera-icon-action text-primary" data-action="start-edit-prod" data-prod-id="{{ $pItem->nid }}" title="Ubah Qty">
                                    <i class="bi bi-pencil fs-6"></i>
                                </button>
                            </div>
                            <div id="prodAksiEdit_{{ $pItem->nid }}" class="d-none d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn-olsera-circle btn-olsera-circle-cancel" data-action="cancel-edit-prod" data-prod-id="{{ $pItem->nid }}" title="Batal">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <button type="submit" form="editProdForm_{{ $pItem->nid }}" class="btn-olsera-circle btn-olsera-circle-save" title="Simpan Qty">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Accordion Daftar Bahan Baku untuk Produk ini (Desain Seamless Ringkas) --}}
                    <tr class="collapse {{ $linkedMaterials->isNotEmpty() ? 'show' : '' }}" id="materialsCollapse{{ $pItem->nid }}">
                        <td colspan="5" class="p-0 border-bottom" style="background-color: #f8fafc;">
                            <div class="px-4 py-3" style="border-left: 4px solid #0ea5e9;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold text-secondary d-flex align-items-center gap-2" style="font-size: 0.82rem;">
                                        <i class="bi bi-arrow-return-right text-primary fs-6"></i>
                                        <span>Bahan Baku yang Digunakan:</span>
                                    </div>
                                    @if($production->isDraft())
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;" data-action="show-inline-mat" data-prod-id="{{ $pItem->nid }}">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>Tambah Bahan</span>
                                    </button>
                                    @endif
                                </div>

                                <div id="matTableWrap_{{ $pItem->nid }}" class="{{ $linkedMaterials->isEmpty() ? 'd-none' : '' }}">
                                    <div class="table-responsive rounded border bg-white shadow-xs">
                                        <table class="table align-middle mb-0" style="font-size: 0.84rem;">
                                            <thead>
                                                <tr class="text-secondary bg-light border-bottom" style="font-size: 0.76rem; font-weight: 600; letter-spacing: 0.3px;">
                                                    <th class="px-3 py-2.5" style="width: 28%;">NAMA BAHAN BAKU</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 10%;">TIPE</th>
                                                    <th class="px-3 py-2.5 text-end" style="width: 14%;">HARGA SATUAN</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 15%;">QTY DIPOTONG</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 9%;">SATUAN</th>
                                                    <th class="px-3 py-2.5 text-end" style="width: 14%;">SUBTOTAL BIAYA</th>
                                                    @if($production->isDraft())
                                                    <th class="px-3 py-2.5 text-center text-nowrap" style="width: 10%;">AKSI</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody id="matTableBody_{{ $pItem->nid }}">
                                                @foreach($linkedMaterials as $mat)
                                                @php
                                                $matCostDecimals = fmod((float)$mat->ncost, 1) != 0 ? 2 : 0;
                                                $matSubDecimals = fmod((float)$mat->nsubtotal, 1) != 0 ? 2 : 0;
                                                @endphp
                                                <tr class="border-bottom border-light-subtle" id="matRow_{{ $mat->nid }}">
                                                    <td class="px-3 py-2.5 fw-medium text-dark">
                                                        {{ $mat->item_name }}
                                                        @if($mat->cnote)
                                                        <span class="text-muted ms-1 small">({{ $mat->cnote }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center">
                                                        <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-1" style="font-size: 0.68rem;">
                                                            {{ $mat->citem_type ?: 'PRODUK' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end text-muted text-nowrap">
                                                        Rp {{ number_format($mat->ncost, $matCostDecimals, ',', '.') }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center align-middle" style="min-width: 130px;">
                                                        @if($production->isDraft())
                                                        <div id="matQtyDisplay_{{ $mat->nid }}">
                                                            <span class="fw-semibold text-danger">-{{ number_format($mat->nqty, 2, ',', '.') }}</span>
                                                        </div>
                                                        <div id="matQtyEdit_{{ $mat->nid }}" class="d-none">
                                                            <input type="number"
                                                                step="any"
                                                                min="0.001"
                                                                name="nqty"
                                                                form="editMatForm_{{ $mat->nid }}"
                                                                class="form-control form-control-sm qty-edit-input mx-auto"
                                                                id="mat_qty_edit_input_{{ $mat->nid }}"
                                                                value="{{ (float)$mat->nqty }}"
                                                                data-original="{{ (float)$mat->nqty }}"
                                                                required>
                                                        </div>
                                                        @else
                                                        <span class="fw-semibold text-danger">-{{ number_format($mat->nqty, 2, ',', '.') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center text-muted">
                                                        {{ $mat->item_unit }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end fw-medium text-dark text-nowrap" id="mat_subtotal_{{ $mat->nid }}">
                                                        Rp {{ number_format($mat->nsubtotal, $matSubDecimals, ',', '.') }}
                                                    </td>
                                                    @if($production->isDraft())
                                                    <td class="px-3 py-2.5 text-center text-nowrap">
                                                        <div id="matAksiDisplay_{{ $mat->nid }}" class="d-inline-flex align-items-center gap-2">
                                                            <form action="{{ route('inventory.production.materials.destroy', [$production->nid, $mat->nid]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus bahan ini?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn-olsera-icon-action text-danger" title="Hapus Bahan">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </form>
                                                            <button type="button" class="btn-olsera-icon-action text-primary" data-action="start-edit-mat" data-mat-id="{{ $mat->nid }}" title="Ubah Qty Bahan">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                        </div>
                                                        <div id="matAksiEdit_{{ $mat->nid }}" class="d-none d-inline-flex align-items-center gap-2">
                                                            <button type="button" class="btn-olsera-circle btn-olsera-circle-cancel" data-action="cancel-edit-mat" data-mat-id="{{ $mat->nid }}" title="Batal">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                            <button type="submit" form="editMatForm_{{ $mat->nid }}" class="btn-olsera-circle btn-olsera-circle-save" title="Simpan Qty Bahan">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach

                                                @if($production->isDraft())
                                                {{-- Baris Tambah Bahan Inline (Pola Final dengan Tombol Batal X & Simpan Centang) --}}
                                                <tr id="inlineAddMatRow_{{ $pItem->nid }}" class="d-none bg-primary-subtle border-bottom border-primary-subtle">
                                                    <td class="px-3 py-2.5">
                                                        <select class="form-select form-select-sm" id="inlineMatSelect_{{ $pItem->nid }}">
                                                            <option value="">-- Cari atau pilih bahan baku --</option>
                                                            @foreach($availableProducts as $prod)
                                                            <option value="{{ $prod->nid }}"
                                                                data-cname="{{ $prod->cname }}"
                                                                data-stock="{{ (float)($prod->nqty ?? 0) }}"
                                                                data-unit="{{ $prod->csatuan ?: 'PCS' }}"
                                                                data-price="{{ (float)($prod->nprice_buy ?? 0) }}">
                                                                {{ $prod->cname }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center">
                                                        <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-1" style="font-size: 0.68rem;">
                                                            BAHAN
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end text-muted text-nowrap" id="inlineMatPrice_{{ $pItem->nid }}">
                                                        -
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center align-middle" style="min-width: 130px;">
                                                        <input type="number"
                                                            step="any"
                                                            min="0.001"
                                                            class="form-control form-control-sm qty-edit-input mx-auto"
                                                            id="inlineMatQtyInput_{{ $pItem->nid }}"
                                                            data-action="preview-inline-mat"
                                                            data-prod-id="{{ $pItem->nid }}"
                                                            value="1">
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center text-muted" id="inlineMatUnit_{{ $pItem->nid }}">
                                                        PCS
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end text-secondary text-nowrap" id="inlineMatSubtotal_{{ $pItem->nid }}">
                                                        -
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center text-nowrap">
                                                        <div class="d-inline-flex align-items-center gap-2">
                                                            <button type="button" class="btn-olsera-circle btn-olsera-circle-cancel" data-action="close-inline-mat" data-prod-id="{{ $pItem->nid }}" title="Batal">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                            <button type="button" class="btn-olsera-circle btn-olsera-circle-save" data-action="submit-inline-mat" data-prod-id="{{ $pItem->nid }}" title="Simpan Bahan">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                @if($linkedMaterials->isEmpty())
                                <div class="text-center py-4 text-muted small bg-white rounded border my-2" style="font-size: 0.82rem;" id="emptyMatNotice_{{ $pItem->nid }}">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Belum ada bahan baku untuk produk ini.
                                    @if($production->isDraft())
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded shadow-xs" data-action="show-inline-mat" data-prod-id="{{ $pItem->nid }}">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Bahan Sekarang
                                        </button>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyProductNoticeRow">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-box fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            <div>Belum ada produk hasil produksi yang ditambahkan.</div>
                            @if($production->isDraft())
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" onclick="showInlineProductRow()">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk Sekarang
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforelse

                    @if($production->isDraft())
                    {{-- Baris Tambah Produk Jadi Inline --}}
                    <tr id="inlineAddProductRow" class="d-none bg-primary-subtle border-bottom border-primary-subtle">
                        <td class="px-3 py-2.5">
                            <select class="form-select form-select-sm" id="inlineProductSelect">
                                <option value="">-- Cari atau pilih produk jadi --</option>
                                @foreach($availableProducts as $prod)
                                <option value="{{ $prod->nid }}"
                                    data-cname="{{ $prod->cname }}"
                                    data-stock="{{ (float)($prod->nqty ?? 0) }}"
                                    data-unit="{{ $prod->csatuan ?: 'PCS' }}"
                                    data-price="{{ (float)($prod->nprice_buy ?? 0) }}">
                                    {{ $prod->cname }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2.5 text-center align-middle" style="min-width: 130px;">
                            <input type="number"
                                step="any"
                                min="0.01"
                                class="form-control form-control-sm qty-edit-input mx-auto"
                                id="inlineProductQtyInput"
                                value="1">
                        </td>
                        <td class="px-3 py-2 text-center text-muted" id="inlineProductUnitBadge">
                            PCS
                        </td>
                        <td class="px-3 py-2 text-center text-muted">
                            -
                        </td>
                        <td class="px-3 py-2 text-center text-nowrap">
                            <div class="d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn-olsera-circle btn-olsera-circle-cancel" onclick="closeInlineProductRow()" title="Batal">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <button type="button" class="btn-olsera-circle btn-olsera-circle-save" onclick="submitInlineProduct()" title="Simpan Produk">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Baris Ringkasan Jumlah Qty Produk --}}
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-secondary">Jumlah Produk:</span>
            <span class="fw-bold fs-6 text-primary" id="totalProductQtyDisplay">{{ number_format($production->ntotal_product_qty, 2, ',', '.') }}</span>
        </div>
    </div>

</div>

{{-- Modal Ubah Tanggal & Catatan --}}
@if($production->isDraft())
<div class="modal fade" id="modalUbahHeader" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.update', $production->nid) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">Ubah Informasi Dokumen</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tanggal Produksi</label>
                        <input type="date" name="dproduction" class="form-control form-control-sm" value="{{ \Carbon\Carbon::parse($production->dproduction)->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan</label>
                        <textarea name="cnote" class="form-control form-control-sm" rows="3">{{ $production->cnote }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Tambah Bahan Baku Manual --}}
<div class="modal fade" id="modalAddMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.materials.store', $production->nid) }}" method="POST">
                @csrf
                <input type="hidden" name="nid_production_product" id="matProdId">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-layers text-primary me-2"></i>Tambah Bahan Baku
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border py-2 px-3 small mb-3 text-secondary" id="matProdInfo">
                        Untuk Produk: -
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilih Bahan Baku <span class="text-danger">*</span></label>
                        <select name="nid_product" class="form-select form-select-sm" id="matSelect" required>
                            <option value="">-- Cari atau pilih bahan baku --</option>
                            @foreach($availableProducts as $prod)
                            <option value="{{ $prod->nid }}"
                                data-cname="{{ $prod->cname }}"
                                data-stock="{{ (float)($prod->nqty ?? 0) }}"
                                data-unit="{{ $prod->csatuan ?: 'PCS' }}"
                                data-cost="{{ (float)($prod->nprice_buy ?? 0) }}">
                                {{ $prod->cname }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Qty Bahan Baku (Total Dipotong) <span class="text-danger">*</span></label>
                        <input type="number" name="nqty" id="matQtyFinalInput" class="form-control form-control-sm" value="1" min="0.001" step="any" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Catatan (Opsional)</label>
                        <input type="text" name="cnote" class="form-control form-control-sm" placeholder="Contoh: Tambahan porsi resep">
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Tambahkan Bahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($production->isDraft())
{{-- Hidden Form Submit Produk Inline --}}
<form id="hiddenAddProductForm" action="{{ route('inventory.production.products.store', $production->nid) }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="nid_product" id="hiddenProdNid">
    <input type="hidden" name="nqty" id="hiddenProdQty">
    <input type="hidden" name="dexpired" id="hiddenProdExpired">
    <input type="hidden" name="crack" id="hiddenProdRack">
</form>
@endif

{{-- Hidden Form Submit Bahan Inline --}}
<form id="hiddenAddMaterialForm" action="{{ route('inventory.production.materials.store', $production->nid) }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="nid_production_product" id="hiddenMatProdId">
    <input type="hidden" name="nid_product" id="hiddenMatProductId">
    <input type="hidden" name="nqty" id="hiddenMatFinalQty">
</form>

@if($production->isDraft())
{{-- Form Native Update Qty Produk & Bahan (Submit saat klik tombol Centang) --}}
@foreach($production->products as $pItem)
<form id="editProdForm_{{ $pItem->nid }}" action="{{ route('inventory.production.products.update', [$production->nid, $pItem->nid]) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endforeach
@foreach($production->materials as $mat)
<form id="editMatForm_{{ $mat->nid }}" action="{{ route('inventory.production.materials.update', [$production->nid, $mat->nid]) }}" method="POST" class="d-none">
    @csrf
    @method('PUT')
</form>
@endforeach
@endif

{{-- Modal Ubah Qty & Informasi Produk Hasil Produksi --}}
@if($production->isDraft())
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditProduct" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Ubah Qty & Info Produk
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border py-2 px-3 small mb-3 text-secondary" id="editProdNameInfo">
                        Produk: -
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Qty Produksi (Jumlah yang dibuat) <span class="text-danger">*</span></label>
                        <input type="number" name="nqty" id="editProdQtyInput" class="form-control form-control-sm" value="1" min="0.01" step="any" required>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Form Cepat Ubah Qty Baris Produk --}}
<form id="quickQtyForm" method="POST" action="" style="display: none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="nqty" id="quickQtyInput">
</form>

{{-- ==================== MODAL KONFIRMASI POSTING ==================== --}}
<div class="modal fade" id="modalPostingConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.post', $production->nid) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check2-circle me-2"></i> Konfirmasi Posting Produksi Stok
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        Anda akan memposting dokumen produksi <strong>{{ $production->cno_production }}</strong>.
                    </p>
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Penting:</strong> Setelah diposting:
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Stok fisik bahan baku di <strong>{{ $production->outlet?->cname ?: 'Outlet Produksi' }}</strong> akan otomatis terpotong / berkurang.</li>
                            <li>Stok produk jadi hasil produksi akan langsung bertambah ke sistem.</li>
                            <li>Seluruh mutasi tercatat resmi pada <strong>Pergerakan Stok</strong>.</li>
                            <li>Dokumen produksi terkunci dan tidak dapat diubah kembali.</li>
                        </ul>
                    </div>
                    <div class="bg-light p-3 rounded-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Hasil Jadi:</span>
                            <span class="fw-bold text-primary" id="modalConfirmTotalProdQty">{{ number_format($production->ntotal_product_qty, 2, ',', '.') }} unit</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Jumlah Item Bahan:</span>
                            <span class="fw-bold text-danger">{{ $production->materials->count() }} item bahan</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Kembali Cek</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Ya, Posting Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL KONFIRMASI BATALKAN ==================== --}}
<div class="modal fade" id="modalCancelConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.cancel', $production->nid) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i> Batalkan Dokumen Produksi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-dark mb-2">
                        Apakah Anda yakin ingin membatalkan dokumen produksi <strong>{{ $production->cno_production }}</strong>?
                    </p>
                    <div class="alert alert-danger-subtle text-danger border-0 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Dokumen yang telah dibatalkan tidak dapat diaktifkan atau diposting kembali.
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-x-circle me-1"></i> Ya, Batalkan Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    let matTsInstance = null;
    let inlineProdTsInstance = null;
    const inlineMatTsInstances = {};

    document.addEventListener('DOMContentLoaded', function() {
        // TomSelect pada modal Tambah Bahan Baku (Fallback)
        const matSelectEl = document.getElementById('matSelect');
        if (matSelectEl && window.TomSelect) {
            matTsInstance = new TomSelect(matSelectEl, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih bahan baku --',
                allowEmptyOption: true,
                dropdownParent: 'body',
                searchField: ['text', 'cname'],
                sortField: {
                    field: 'text',
                    direction: 'asc'
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
                        const stockVal = (data.stock !== undefined && data.stock !== null && data.stock !== '') ? data.stock : 0;
                        const unitVal = data.unit || 'PCS';
                        const stockNum = parseFloat(stockVal) || 0;
                        const stockLabel = `${stockNum.toLocaleString('id-ID')} ${unitVal}`;
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                            '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                            '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                            '</div>';
                    }
                }
            });
        }

        // Event delegation untuk tombol edit/batal qty dan tambah bahan baku inline
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const prodId = btn.dataset.prodId;
            const matId = btn.dataset.matId;

            if (action === 'start-edit-prod') {
                toggleProdEditMode(prodId, true);
            } else if (action === 'cancel-edit-prod') {
                toggleProdEditMode(prodId, false);
            } else if (action === 'start-edit-mat') {
                toggleMatEditMode(matId, true);
            } else if (action === 'cancel-edit-mat') {
                toggleMatEditMode(matId, false);
            } else if (action === 'show-inline-mat') {
                showInlineMaterialRow(prodId);
            } else if (action === 'submit-inline-mat') {
                submitInlineMaterial(prodId);
            } else if (action === 'close-inline-mat') {
                closeInlineMaterialRow(prodId);
            }
        });
    });

    // ==================== TOGGLE EDIT MODE QTY PRODUK & BAHAN (X / CENTANG) ====================
    function toggleProdEditMode(prodId, isEditing) {
        const displayQty = document.getElementById('prodQtyDisplay_' + prodId);
        const editQty = document.getElementById('prodQtyEdit_' + prodId);
        const displayAksi = document.getElementById('prodAksiDisplay_' + prodId);
        const editAksi = document.getElementById('prodAksiEdit_' + prodId);
        const input = document.getElementById('prod_qty_edit_input_' + prodId);

        if (displayQty) displayQty.classList.toggle('d-none', isEditing);
        if (editQty) editQty.classList.toggle('d-none', !isEditing);
        if (displayAksi) displayAksi.classList.toggle('d-none', isEditing);
        if (editAksi) editAksi.classList.toggle('d-none', !isEditing);

        if (input) {
            if (isEditing) {
                input.focus();
                input.select();
            } else if (input.dataset.original !== undefined) {
                input.value = input.dataset.original;
            }
        }
    }

    function toggleMatEditMode(matId, isEditing) {
        const displayQty = document.getElementById('matQtyDisplay_' + matId);
        const editQty = document.getElementById('matQtyEdit_' + matId);
        const displayAksi = document.getElementById('matAksiDisplay_' + matId);
        const editAksi = document.getElementById('matAksiEdit_' + matId);
        const input = document.getElementById('mat_qty_edit_input_' + matId);

        if (displayQty) displayQty.classList.toggle('d-none', isEditing);
        if (editQty) editQty.classList.toggle('d-none', !isEditing);
        if (displayAksi) displayAksi.classList.toggle('d-none', isEditing);
        if (editAksi) editAksi.classList.toggle('d-none', !isEditing);

        if (input) {
            if (isEditing) {
                input.focus();
                input.select();
            } else if (input.dataset.original !== undefined) {
                input.value = input.dataset.original;
            }
        }
    }

    // ==================== INLINE TAMBAH PRODUK JADI ====================
    function showInlineProductRow() {
        const row = document.getElementById('inlineAddProductRow');
        const emptyRow = document.getElementById('emptyProductNoticeRow');
        if (emptyRow) emptyRow.classList.add('d-none');
        if (row) {
            row.classList.remove('d-none');
            initInlineProductSelect();
            row.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }

    function closeInlineProductRow() {
        const row = document.getElementById('inlineAddProductRow');
        if (row) row.classList.add('d-none');

        const tbody = document.querySelector('.table-prod-bordered tbody');
        const existingProdRows = tbody ? tbody.querySelectorAll('tr:not(#inlineAddProductRow):not(#emptyProductNoticeRow):not([id^="materialsCollapse"])') : [];
        if (existingProdRows.length === 0) {
            const emptyRow = document.getElementById('emptyProductNoticeRow');
            if (emptyRow) emptyRow.classList.remove('d-none');
        }
    }

    function initInlineProductSelect() {
        const selectEl = document.getElementById('inlineProductSelect');
        if (!selectEl || inlineProdTsInstance) return;

        if (window.TomSelect) {
            inlineProdTsInstance = new TomSelect(selectEl, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih produk jadi --',
                allowEmptyOption: true,
                dropdownParent: 'body',
                searchField: ['text', 'cname'],
                sortField: {
                    field: 'text',
                    direction: 'asc'
                },
                render: {
                    item: function(data, escape) {
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                    },
                    option: function(data, escape) {
                        if (!data.value) return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                        const stockVal = (data.stock !== undefined && data.stock !== null && data.stock !== '') ? data.stock : 0;
                        const unitVal = data.unit || 'PCS';
                        const stockNum = parseFloat(stockVal) || 0;
                        const stockLabel = `${stockNum.toLocaleString('id-ID')} ${unitVal}`;
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                            '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                            '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                            '</div>';
                    }
                }
            });

            inlineProdTsInstance.on('change', function(val) {
                const opt = inlineProdTsInstance.options[val];
                const unitBadge = document.getElementById('inlineProductUnitBadge');
                if (opt && unitBadge) {
                    unitBadge.textContent = opt.unit || 'PCS';
                }
            });
        }
    }

    function submitInlineProduct() {
        const selectVal = inlineProdTsInstance ? inlineProdTsInstance.getValue() : document.getElementById('inlineProductSelect')?.value;
        if (!selectVal) {
            alert('Silakan pilih produk jadi terlebih dahulu.');
            return;
        }
        const qty = parseFloat(document.getElementById('inlineProductQtyInput')?.value) || 0;
        if (qty <= 0) {
            alert('Qty produksi harus lebih dari 0.');
            return;
        }
        document.getElementById('hiddenProdNid').value = selectVal;
        document.getElementById('hiddenProdQty').value = qty;
        document.getElementById('hiddenProdExpired').value = '';
        document.getElementById('hiddenProdRack').value = '';
        document.getElementById('hiddenAddProductForm').submit();
    }

    // ==================== INLINE TAMBAH BAHAN BAKU (POLA FINAL) ====================
    function showInlineMaterialRow(prodId) {
        const wrap = document.getElementById('matTableWrap_' + prodId);
        if (wrap) wrap.classList.remove('d-none');
        const notice = document.getElementById('emptyMatNotice_' + prodId);
        if (notice) notice.classList.add('d-none');

        const row = document.getElementById('inlineAddMatRow_' + prodId);
        if (row) {
            row.classList.remove('d-none');
            initInlineMatSelect(prodId);
        }
    }

    function closeInlineMaterialRow(prodId) {
        const row = document.getElementById('inlineAddMatRow_' + prodId);
        if (row) row.classList.add('d-none');

        // Jika belum ada bahan baku terdaftar sama sekali, tampilkan kembali notice kosong
        const tbody = document.getElementById('matTableBody_' + prodId);
        const existingRows = tbody ? tbody.querySelectorAll('tr:not(#inlineAddMatRow_' + prodId + ')') : [];
        if (existingRows.length === 0) {
            const wrap = document.getElementById('matTableWrap_' + prodId);
            if (wrap) wrap.classList.add('d-none');
            const notice = document.getElementById('emptyMatNotice_' + prodId);
            if (notice) notice.classList.remove('d-none');
        }
    }

    function initInlineMatSelect(prodId) {
        const selectEl = document.getElementById('inlineMatSelect_' + prodId);
        if (!selectEl || inlineMatTsInstances[prodId]) return;

        if (window.TomSelect) {
            inlineMatTsInstances[prodId] = new TomSelect(selectEl, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih bahan baku --',
                allowEmptyOption: true,
                dropdownParent: 'body',
                searchField: ['text', 'cname'],
                sortField: {
                    field: 'text',
                    direction: 'asc'
                },
                render: {
                    item: function(data, escape) {
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                    },
                    option: function(data, escape) {
                        if (!data.value) return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                        const stockVal = (data.stock !== undefined && data.stock !== null && data.stock !== '') ? data.stock : 0;
                        const unitVal = data.unit || 'PCS';
                        const stockNum = parseFloat(stockVal) || 0;
                        const stockLabel = `${stockNum.toLocaleString('id-ID')} ${unitVal}`;
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                            '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                            '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                            '</div>';
                    }
                }
            });

            inlineMatTsInstances[prodId].on('change', function(val) {
                const opt = inlineMatTsInstances[prodId].options[val];
                const unitEl = document.getElementById('inlineMatUnit_' + prodId);
                if (opt && unitEl) {
                    unitEl.textContent = opt.unit || 'PCS';
                }
                updateInlineMatPricePreview(prodId);
            });
        }
    }

    function formatRupiahInline(amount) {
        const num = parseFloat(amount) || 0;
        const hasDecimal = Math.abs(num % 1) > 0.0001;
        return 'Rp ' + num.toLocaleString('id-ID', {
            minimumFractionDigits: hasDecimal ? 2 : 0,
            maximumFractionDigits: hasDecimal ? 2 : 0
        });
    }

    function updateInlineMatPricePreview(prodId) {
        const ts = inlineMatTsInstances[prodId];
        const selectEl = document.getElementById('inlineMatSelect_' + prodId);
        const qtyInp = document.getElementById('inlineMatQtyInput_' + prodId);
        const priceEl = document.getElementById('inlineMatPrice_' + prodId);
        const subEl = document.getElementById('inlineMatSubtotal_' + prodId);
        if (!priceEl || !subEl) return;

        let unitPrice = null;
        if (ts && ts.getValue()) {
            const opt = ts.options[ts.getValue()];
            if (opt && opt.price !== undefined) {
                unitPrice = parseFloat(opt.price) || 0;
            }
        } else if (selectEl && selectEl.value) {
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.price !== undefined) {
                unitPrice = parseFloat(selectedOpt.dataset.price) || 0;
            }
        }

        if (unitPrice === null) {
            priceEl.textContent = '-';
            subEl.textContent = '-';
            return;
        }

        const qtyVal = parseFloat(qtyInp ? qtyInp.value : 0) || 0;
        priceEl.textContent = formatRupiahInline(unitPrice);
        subEl.textContent = formatRupiahInline(unitPrice * qtyVal);
    }

    document.addEventListener('input', function(e) {
        const el = e.target.closest('[data-action="preview-inline-mat"]');
        if (!el) return;
        updateInlineMatPricePreview(el.dataset.prodId);
    });

    function submitInlineMaterial(prodId) {
        const select = document.getElementById('inlineMatSelect_' + prodId);
        const qtyInp = document.getElementById('inlineMatQtyInput_' + prodId);
        const prodSelectVal = inlineMatTsInstances[prodId] ? inlineMatTsInstances[prodId].getValue() : (select ? select.value : '');

        if (!prodSelectVal) {
            alert('Silakan pilih bahan baku terlebih dahulu.');
            return;
        }

        const qtyVal = parseFloat(qtyInp ? qtyInp.value : 0) || 0;
        if (qtyVal <= 0) {
            alert('Qty bahan baku harus lebih dari 0.');
            return;
        }

        document.getElementById('hiddenMatProdId').value = prodId;
        document.getElementById('hiddenMatProductId').value = prodSelectVal;
        document.getElementById('hiddenMatFinalQty').value = qtyVal;

        document.getElementById('hiddenAddMaterialForm').submit();
    }

    // ==================== HELPER MODAL ====================
    function openAddMaterialModal(productId, productName) {
        document.getElementById('matProdId').value = productId;
        document.getElementById('matProdInfo').innerHTML = 'Untuk Produk: <strong>' + productName + '</strong>';
        if (matTsInstance) {
            matTsInstance.clear();
        }
        const qtyInp = document.getElementById('matQtyFinalInput');
        if (qtyInp) qtyInp.value = 1;

        const modal = new bootstrap.Modal(document.getElementById('modalAddMaterial'));
        modal.show();
    }

    function openEditProductModal(productId, productName, currentQty) {
        const form = document.getElementById('formEditProduct');
        const updateUrlPattern = "{{ route('inventory.production.products.update', [$production->nid, ':productId']) }}";
        form.action = updateUrlPattern.replace(':productId', productId);

        document.getElementById('editProdNameInfo').innerHTML = 'Produk: <strong>' + productName + '</strong>';
        document.getElementById('editProdQtyInput').value = currentQty;

        const modal = new bootstrap.Modal(document.getElementById('modalEditProduct'));
        modal.show();
    }
</script>
@endsection