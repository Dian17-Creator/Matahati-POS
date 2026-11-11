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
                        <th class="px-3 py-3" style="width: 48%;">Nama Produk</th>
                        <th class="px-3 py-3 text-center" style="width: 18%;">Qty</th>
                        <th class="px-3 py-3 text-center" style="width: 14%;">Satuan</th>
                        <th class="px-3 py-3 text-center" style="width: 14%;">Bahan</th>
                        <th class="px-3 py-3 text-center" style="width: 6%;">Aksi</th>
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
                            <div class="qty-stepper mx-auto">
                                <button type="button" class="qty-stepper-btn qty-stepper-minus" data-action="update-prod-qty" data-prod-id="{{ $pItem->nid }}" data-delta="-1" title="Kurangi 1">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number"
                                    step="any"
                                    min="0.01"
                                    class="qty-stepper-input no-spinners"
                                    id="prod_qty_{{ $pItem->nid }}"
                                    value="{{ (float)$pItem->nqty }}"
                                    data-action="save-prod-qty"
                                    data-prod-id="{{ $pItem->nid }}">
                                <button type="button" class="qty-stepper-btn qty-stepper-plus" data-action="update-prod-qty" data-prod-id="{{ $pItem->nid }}" data-delta="1" title="Tambah 1">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
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
                        <td class="px-3 py-3 text-center">
                            @if($production->isDraft())
                            <form action="{{ route('inventory.production.products.destroy', [$production->nid, $pItem->nid]) }}" method="POST" onsubmit="return confirm('Hapus produk ini beserta bahan yang terkait?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0 border-0" title="Hapus Produk">
                                    <i class="bi bi-x-lg fs-6"></i>
                                </button>
                            </form>
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
                                                    <th class="px-3 py-2.5" style="width: 30%;">NAMA BAHAN BAKU</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 12%;">TIPE</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 15%;">TAKARAN / UNIT</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 17%;">TOTAL QTY DIPOTONG</th>
                                                    <th class="px-3 py-2.5 text-center" style="width: 10%;">SATUAN</th>
                                                    <th class="px-3 py-2.5 text-end" style="width: 11%;">SUBTOTAL BIAYA</th>
                                                    @if($production->isDraft())
                                                    <th class="px-3 py-2.5 text-center" style="width: 5%;">AKSI</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody id="matTableBody_{{ $pItem->nid }}">
                                                @foreach($linkedMaterials as $mat)
                                                @php
                                                $unitQtyVal = ((float)$pItem->nqty > 0) ? ((float)$mat->nqty / (float)$pItem->nqty) : (float)$mat->nqty;
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
                                                    <td class="px-3 py-2.5 text-center align-middle" style="min-width: 130px;">
                                                        @if($production->isDraft())
                                                        <div class="qty-stepper mx-auto">
                                                            <button type="button" class="qty-stepper-btn qty-stepper-minus" data-action="update-mat-qty" data-mat-id="{{ $mat->nid }}" data-prod-id="{{ $pItem->nid }}" data-delta="-1" title="Kurangi 1">
                                                                <i class="bi bi-dash-lg"></i>
                                                            </button>
                                                            <input type="number"
                                                                step="any"
                                                                min="0.001"
                                                                class="qty-stepper-input no-spinners"
                                                                id="mat_unit_input_{{ $mat->nid }}"
                                                                value="{{ (float)$unitQtyVal }}"
                                                                data-action="save-mat-qty"
                                                                data-mat-id="{{ $mat->nid }}"
                                                                data-prod-id="{{ $pItem->nid }}">
                                                            <button type="button" class="qty-stepper-btn qty-stepper-plus" data-action="update-mat-qty" data-mat-id="{{ $mat->nid }}" data-prod-id="{{ $pItem->nid }}" data-delta="1" title="Tambah 1">
                                                                <i class="bi bi-plus-lg"></i>
                                                            </button>
                                                        </div>
                                                        @else
                                                        <span class="text-secondary fw-medium" id="mat_unit_qty_{{ $mat->nid }}">{{ number_format($unitQtyVal, 2, ',', '.') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center fw-semibold text-danger" id="mat_qty_{{ $mat->nid }}">
                                                        -{{ number_format($mat->nqty, 2, ',', '.') }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center text-muted">
                                                        {{ $mat->item_unit }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end text-secondary" id="mat_subtotal_{{ $mat->nid }}">
                                                        Rp {{ number_format($mat->nsubtotal, 0, ',', '.') }}
                                                    </td>
                                                    @if($production->isDraft())
                                                    <td class="px-3 py-2.5 text-center">
                                                        <form action="{{ route('inventory.production.materials.destroy', [$production->nid, $mat->nid]) }}" method="POST" onsubmit="return confirm('Hapus bahan ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-link text-danger p-0 border-0" title="Hapus Bahan">
                                                                <i class="bi bi-trash fs-6"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach

                                                @if($production->isDraft())
                                                {{-- Baris Tambah Bahan Inline (Muncul saat user klik + Tambah Bahan) --}}
                                                <tr id="inlineAddMatRow_{{ $pItem->nid }}" class="d-none bg-primary-subtle border-bottom border-primary-subtle">
                                                    <td class="px-3 py-2.5">
                                                        <select class="form-select form-select-sm" id="inlineMatSelect_{{ $pItem->nid }}">
                                                            <option value="">-- Cari atau pilih bahan baku --</option>
                                                            @foreach($availableProducts as $prod)
                                                            <option value="{{ $prod->nid }}"
                                                                data-cname="{{ $prod->cname }}"
                                                                data-stock="{{ (float)($prod->nqty ?? 0) }}"
                                                                data-unit="{{ $prod->csatuan ?: 'PCS' }}">
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
                                                    <td class="px-3 py-2.5 text-center align-middle" style="min-width: 130px;">
                                                        <div class="qty-stepper mx-auto">
                                                            <button type="button" class="qty-stepper-btn qty-stepper-minus" data-action="adjust-inline-mat-qty" data-prod-id="{{ $pItem->nid }}" data-delta="-1">
                                                                <i class="bi bi-dash-lg"></i>
                                                            </button>
                                                            <input type="number"
                                                                step="any"
                                                                min="0.001"
                                                                class="qty-stepper-input no-spinners"
                                                                id="inlineMatUnitInput_{{ $pItem->nid }}"
                                                                value="1"
                                                                data-action="calc-inline-mat"
                                                                data-prod-id="{{ $pItem->nid }}">
                                                            <button type="button" class="qty-stepper-btn qty-stepper-plus" data-action="adjust-inline-mat-qty" data-prod-id="{{ $pItem->nid }}" data-delta="1">
                                                                <i class="bi bi-plus-lg"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center fw-semibold text-danger" id="inlineMatTotalQty_{{ $pItem->nid }}">
                                                        -{{ number_format((float)$pItem->nqty, 2, ',', '.') }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center text-muted" id="inlineMatUnit_{{ $pItem->nid }}">
                                                        PCS
                                                    </td>
                                                    <td class="px-3 py-2.5 text-end text-secondary">
                                                        -
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center">
                                                        <div class="d-inline-flex gap-1">
                                                            <button type="button" class="btn btn-sm btn-success py-1 px-2 rounded" data-action="submit-inline-mat" data-prod-id="{{ $pItem->nid }}" title="Simpan Bahan">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded" data-action="close-inline-mat" data-prod-id="{{ $pItem->nid }}" title="Batal">
                                                                <i class="bi bi-x-lg"></i>
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
                            <div class="qty-stepper mx-auto">
                                <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="adjustInlineProductQty(-1)" title="Kurangi 1">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number"
                                    step="any"
                                    min="0.01"
                                    class="qty-stepper-input no-spinners"
                                    id="inlineProductQtyInput"
                                    value="1">
                                <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="adjustInlineProductQty(1)" title="Tambah 1">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center text-muted" id="inlineProductUnitBadge">
                            PCS
                        </td>
                        <td class="px-3 py-2 text-center text-muted">
                            -
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-sm btn-success py-1 px-2 rounded" onclick="submitInlineProduct()" title="Simpan Produk">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded" onclick="closeInlineProductRow()" title="Batal">
                                    <i class="bi bi-x-lg"></i>
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
                        <label class="form-label small fw-semibold text-secondary">Takaran per 1 Unit Produk <span class="text-danger">*</span></label>
                        <div class="qty-stepper w-100" style="max-width: 100% !important;">
                            <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="adjustMatModalUnitQty(-1)">-</button>
                            <input type="number" name="nqty" id="matQtyPerUnitInput" class="qty-stepper-input" value="1" min="0.001" step="any" required oninput="calculateMatTotalPreview()">
                            <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="adjustMatModalUnitQty(1)">+</button>
                        </div>
                    </div>

                    {{-- Live Preview Total Bahan yang akan Dipotong --}}
                    <div class="p-2 mb-3 bg-primary-subtle rounded border border-primary-subtle d-flex justify-content-between align-items-center" id="matPreviewBox" style="font-size: 0.82rem;">
                        <span class="text-primary fw-semibold">
                            <i class="bi bi-calculator me-1"></i> Total Bahan Baku Dipotong:
                        </span>
                        <span class="fw-bold text-primary" id="matTotalPreviewText">0 PCS</span>
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
    <input type="hidden" name="nqty" id="hiddenMatUnitQty">
</form>

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
                        <div class="qty-stepper w-100" style="max-width: 100% !important;">
                            <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="adjustEditProductQty(-1)">-</button>
                            <input type="number" name="nqty" id="editProdQtyInput" class="qty-stepper-input" value="1" min="0.01" step="any" required>
                            <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="adjustEditProductQty(1)">+</button>
                        </div>
                        <div class="form-text small text-muted">Kebutuhan bahan baku dari resep akan disesuaikan otomatis dengan jumlah produksi baru.</div>
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
    let qtyDebounceTimers = {};
    let matDebounceTimers = {};

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

        // Event delegation untuk aksi qty & bahan baku inline tanpa inline Blade-JS
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const prodId = btn.dataset.prodId;
            const matId = btn.dataset.matId;
            const delta = parseFloat(btn.dataset.delta || '0');

            if (action === 'update-prod-qty') {
                updateRowQty(prodId, delta);
            } else if (action === 'show-inline-mat') {
                showInlineMaterialRow(prodId);
            } else if (action === 'update-mat-qty') {
                updateMatUnitQty(matId, prodId, delta);
            } else if (action === 'adjust-inline-mat-qty') {
                adjustInlineMatQty(prodId, delta);
            } else if (action === 'submit-inline-mat') {
                submitInlineMaterial(prodId);
            } else if (action === 'close-inline-mat') {
                closeInlineMaterialRow(prodId);
            }
        });

        document.addEventListener('change', function(e) {
            const el = e.target.closest('[data-action]');
            if (!el) return;

            if (el.dataset.action === 'save-prod-qty') {
                saveRowQty(el.dataset.prodId, el.value);
            } else if (el.dataset.action === 'save-mat-qty') {
                saveMatUnitQty(el.dataset.matId, el.dataset.prodId, el.value);
            }
        });

        document.addEventListener('input', function(e) {
            const el = e.target.closest('[data-action="calc-inline-mat"]');
            if (!el) return;
            calcInlineMatTotal(el.dataset.prodId);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const el = e.target.closest('[data-action="save-prod-qty"], [data-action="save-mat-qty"]');
            if (!el) return;
            e.preventDefault();
            el.blur();
        });
    });

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

    function adjustInlineProductQty(delta) {
        const inp = document.getElementById('inlineProductQtyInput');
        if (inp) {
            let val = parseFloat(inp.value) || 0;
            val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
            inp.value = val;
        }
    }

    function submitInlineProduct() {
        const selectVal = inlineProdTsInstance ? inlineProdTsInstance.getValue() : document.getElementById('inlineProductSelect')?.value;
        if (!selectVal) {
            alert('Silakan pilih produk jadi terlebih dahulu.');
            return;
        }
        const qty = parseFloat(document.getElementById('inlineProductQtyInput')?.value) || 1;
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

    // ==================== UPDATE QTY PRODUK JADI ====================
    function updateRowQty(productId, delta) {
        const inp = document.getElementById('prod_qty_' + productId);
        if (!inp) return;
        let current = parseFloat(inp.value) || 0;
        let nextVal = Math.max(0.01, parseFloat((current + delta).toFixed(2)));
        inp.value = nextVal;
        queueSaveRowQty(productId, nextVal);
    }

    function saveRowQty(productId, val) {
        let nextVal = Math.max(0.01, parseFloat(val) || 0);
        const inp = document.getElementById('prod_qty_' + productId);
        if (inp) inp.value = nextVal;
        queueSaveRowQty(productId, nextVal);
    }

    function queueSaveRowQty(productId, qty) {
        if (qtyDebounceTimers[productId]) {
            clearTimeout(qtyDebounceTimers[productId]);
        }
        const inp = document.getElementById('prod_qty_' + productId);
        if (inp) {
            inp.style.transition = 'opacity 0.2s';
            inp.style.opacity = '0.5';
        }

        qtyDebounceTimers[productId] = setTimeout(() => {
            fetchSaveRowQty(productId, qty);
        }, 350);
    }

    async function fetchSaveRowQty(productId, qty) {
        const inp = document.getElementById('prod_qty_' + productId);
        const updateUrlPattern = "{{ route('inventory.production.products.update', [$production->nid, ':productId']) }}";
        const url = updateUrlPattern.replace(':productId', productId);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    nqty: qty
                })
            });

            if (inp) inp.style.opacity = '1';

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                // Update total produk qty display
                const totalProdQtyEl = document.getElementById('totalProductQtyDisplay');
                if (totalProdQtyEl && data.formatted_total_product_qty) {
                    totalProdQtyEl.textContent = data.formatted_total_product_qty;
                }
                const modalConfirmProdQtyEl = document.getElementById('modalConfirmTotalProdQty');
                if (modalConfirmProdQtyEl && data.formatted_total_product_qty) {
                    modalConfirmProdQtyEl.textContent = data.formatted_total_product_qty + ' unit';
                }

                // Update bahan baku live
                if (Array.isArray(data.materials)) {
                    data.materials.forEach(mat => {
                        const matUnitInput = document.getElementById('mat_unit_input_' + mat.nid);
                        if (matUnitInput && mat.unit_qty !== undefined) {
                            matUnitInput.value = parseFloat(mat.unit_qty);
                        }
                        const matUnitQtyEl = document.getElementById('mat_unit_qty_' + mat.nid);
                        if (matUnitQtyEl && mat.formatted_unit_qty) {
                            matUnitQtyEl.textContent = mat.formatted_unit_qty;
                        }
                        const matQtyEl = document.getElementById('mat_qty_' + mat.nid);
                        if (matQtyEl && mat.formatted_qty) {
                            matQtyEl.textContent = mat.formatted_qty;
                        }
                        const matSubtotalEl = document.getElementById('mat_subtotal_' + mat.nid);
                        if (matSubtotalEl && mat.formatted_subtotal) {
                            matSubtotalEl.textContent = mat.formatted_subtotal;
                        }
                    });
                }
            } else {
                alert(data.message || 'Gagal memperbarui Qty');
            }
        } catch (err) {
            if (inp) inp.style.opacity = '1';
            console.error('Error updating qty:', err);
        }
    }

    // ==================== STEPPER & AJAX UPDATE BAHAN BAKU EXISTING ====================
    function updateMatUnitQty(matId, prodId, delta) {
        const inp = document.getElementById('mat_unit_input_' + matId);
        if (!inp) return;
        let current = parseFloat(inp.value) || 0;
        let nextVal = Math.max(0.001, parseFloat((current + delta).toFixed(2)));
        inp.value = nextVal;
        recalcAndQueueSaveMat(matId, prodId, nextVal);
    }

    function saveMatUnitQty(matId, prodId, val) {
        let nextVal = Math.max(0.001, parseFloat(val) || 0);
        const inp = document.getElementById('mat_unit_input_' + matId);
        if (inp) inp.value = nextVal;
        recalcAndQueueSaveMat(matId, prodId, nextVal);
    }

    function recalcAndQueueSaveMat(matId, prodId, unitVal) {
        const prodQtyInp = document.getElementById('prod_qty_' + prodId);
        const productQty = prodQtyInp ? (parseFloat(prodQtyInp.value) || 1) : 1;
        const totalMatQty = unitVal * productQty;

        // Live preview Total Qty Dipotong
        const matQtyEl = document.getElementById('mat_qty_' + matId);
        if (matQtyEl) {
            matQtyEl.textContent = '-' + totalMatQty.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        if (matDebounceTimers[matId]) {
            clearTimeout(matDebounceTimers[matId]);
        }

        const inp = document.getElementById('mat_unit_input_' + matId);
        if (inp) {
            inp.style.transition = 'opacity 0.2s';
            inp.style.opacity = '0.5';
        }

        matDebounceTimers[matId] = setTimeout(() => {
            fetchSaveMatQty(matId, totalMatQty);
        }, 400);
    }

    async function fetchSaveMatQty(matId, totalQty) {
        const inp = document.getElementById('mat_unit_input_' + matId);
        const updateUrlPattern = "{{ route('inventory.production.materials.update', [$production->nid, ':materialId']) }}";
        const url = updateUrlPattern.replace(':materialId', matId);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    nqty: totalQty
                })
            });

            if (inp) inp.style.opacity = '1';

            const data = await response.json();
            if (response.ok && data.status === 'success') {
                const matQtyEl = document.getElementById('mat_qty_' + matId);
                if (matQtyEl && data.material && data.material.formatted_qty) {
                    matQtyEl.textContent = data.material.formatted_qty;
                }
                const matSubtotalEl = document.getElementById('mat_subtotal_' + matId);
                if (matSubtotalEl && data.material && data.material.formatted_subtotal) {
                    matSubtotalEl.textContent = data.material.formatted_subtotal;
                }
            } else {
                alert(data.message || 'Gagal memperbarui takaran bahan.');
            }
        } catch (err) {
            if (inp) inp.style.opacity = '1';
            console.error('Error updating material qty:', err);
        }
    }

    // ==================== INLINE TAMBAH BAHAN BAKU (SEPERTI DI MODAL) ====================
    function showInlineMaterialRow(prodId) {
        const wrap = document.getElementById('matTableWrap_' + prodId);
        if (wrap) wrap.classList.remove('d-none');
        const notice = document.getElementById('emptyMatNotice_' + prodId);
        if (notice) notice.classList.add('d-none');

        const row = document.getElementById('inlineAddMatRow_' + prodId);
        if (row) {
            row.classList.remove('d-none');
            initInlineMatSelect(prodId);
            calcInlineMatTotal(prodId);
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
                calcInlineMatTotal(prodId);
            });
        }
    }

    function adjustInlineMatQty(prodId, delta) {
        const inp = document.getElementById('inlineMatUnitInput_' + prodId);
        if (inp) {
            let val = parseFloat(inp.value) || 0;
            val = Math.max(0.001, parseFloat((val + delta).toFixed(2)));
            inp.value = val;
            calcInlineMatTotal(prodId);
        }
    }

    function calcInlineMatTotal(prodId) {
        const unitInp = document.getElementById('inlineMatUnitInput_' + prodId);
        const previewEl = document.getElementById('inlineMatTotalQty_' + prodId);
        const prodQtyInp = document.getElementById('prod_qty_' + prodId);
        if (!unitInp || !previewEl) return;

        const unitVal = parseFloat(unitInp.value) || 0;
        const prodQty = prodQtyInp ? (parseFloat(prodQtyInp.value) || 1) : 1;
        const total = unitVal * prodQty;
        previewEl.textContent = '-' + total.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function submitInlineMaterial(prodId) {
        const select = document.getElementById('inlineMatSelect_' + prodId);
        const unitInp = document.getElementById('inlineMatUnitInput_' + prodId);
        const prodSelectVal = inlineMatTsInstances[prodId] ? inlineMatTsInstances[prodId].getValue() : (select ? select.value : '');

        if (!prodSelectVal) {
            alert('Silakan pilih bahan baku terlebih dahulu.');
            return;
        }

        const unitVal = parseFloat(unitInp ? unitInp.value : 1) || 1;
        if (unitVal <= 0) {
            alert('Takaran bahan baku harus lebih dari 0.');
            return;
        }

        document.getElementById('hiddenMatProdId').value = prodId;
        document.getElementById('hiddenMatProductId').value = prodSelectVal;
        document.getElementById('hiddenMatUnitQty').value = unitVal;

        document.getElementById('hiddenAddMaterialForm').submit();
    }

    // ==================== HELPER MODAL ====================
    function adjustModalAddProdQty(delta) {
        const inp = document.getElementById('modalAddProdQtyInput');
        if (inp) {
            let val = parseFloat(inp.value) || 0;
            val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
            inp.value = val;
        }
    }

    function adjustMatModalUnitQty(delta) {
        const inp = document.getElementById('matQtyPerUnitInput');
        if (inp) {
            let val = parseFloat(inp.value) || 0;
            val = Math.max(0.001, parseFloat((val + delta).toFixed(2)));
            inp.value = val;
            calculateMatTotalPreview();
        }
    }

    let modalTargetProductQty = 1;

    function openAddMaterialModal(productId, productName, fallbackProductQty) {
        const liveQtyInp = document.getElementById('prod_qty_' + productId);
        modalTargetProductQty = liveQtyInp ? (parseFloat(liveQtyInp.value) || 1) : (parseFloat(fallbackProductQty) || 1);
        document.getElementById('matProdId').value = productId;
        document.getElementById('matProdInfo').innerHTML = 'Untuk Produk: <strong>' + productName + '</strong> (Jumlah Produksi: <strong>' + modalTargetProductQty + '</strong>)';
        if (matTsInstance) {
            matTsInstance.clear();
        }
        const unitInp = document.getElementById('matQtyPerUnitInput');
        if (unitInp) unitInp.value = 1;
        calculateMatTotalPreview();

        const modal = new bootstrap.Modal(document.getElementById('modalAddMaterial'));
        modal.show();
    }

    function calculateMatTotalPreview() {
        const unitInp = document.getElementById('matQtyPerUnitInput');
        const previewEl = document.getElementById('matTotalPreviewText');
        if (!unitInp || !previewEl) return;
        const unitVal = parseFloat(unitInp.value) || 0;
        const total = unitVal * modalTargetProductQty;
        previewEl.textContent = total.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }) + ' (' + unitVal + ' × ' + modalTargetProductQty + ' produk)';
    }

    function openEditProductModal(productId, productName, currentQty, currentExpired, currentRack) {
        const form = document.getElementById('formEditProduct');
        const updateUrlPattern = "{{ route('inventory.production.products.update', [$production->nid, ':productId']) }}";
        form.action = updateUrlPattern.replace(':productId', productId);

        document.getElementById('editProdNameInfo').innerHTML = 'Produk: <strong>' + productName + '</strong>';
        document.getElementById('editProdQtyInput').value = currentQty;
        document.getElementById('editProdExpiredInput').value = currentExpired || '';
        document.getElementById('editProdRackInput').value = currentRack || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditProduct'));
        modal.show();
    }

    function adjustEditProductQty(delta) {
        const inp = document.getElementById('editProdQtyInput');
        if (inp) {
            let val = parseFloat(inp.value) || 0;
            val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
            inp.value = val;
        }
    }
</script>
@endsection