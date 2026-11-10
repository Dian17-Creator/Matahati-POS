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
                <form action="{{ route('inventory.production.cancel', $production->nid) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dokumen produksi ini?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i>
                        <span>Batalkan</span>
                    </button>
                </form>

                {{-- Tombol Posting Sekarang --}}
                <form action="{{ route('inventory.production.post', $production->nid) }}" method="POST" onsubmit="return confirm('Apakah data produksi sudah benar? Stok bahan akan dipotong dan stok produk jadi akan ditambah.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm d-flex align-items-center gap-1">
                        <i class="bi bi-check2-circle"></i>
                        <span>Posting Sekarang</span>
                    </button>
                </form>
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

    {{-- Form Tambah Produk Jadi (Hanya Muncul Jika DRAFT) --}}
    @if($production->isDraft())
    <div class="card prod-panel-card mb-3 p-3">
        <h6 class="fw-bold text-dark mb-3">Tambah Produk</h6>
        <form action="{{ route('inventory.production.products.store', $production->nid) }}" method="POST" id="formAddProduct">
            @csrf
            <div class="row g-2 align-items-center">
                <div class="col-md-7">
                    <select name="nid_product" id="productSelect" class="form-select" required>
                        <option value="">-- Cari atau pilih produk --</option>
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
                </div>
                <div class="col-md-3">
                    <div class="qty-stepper w-100">
                        <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="adjustProductQty(-1)">-</button>
                        <input type="number" name="nqty" id="productQtyInput" class="qty-stepper-input" value="1" min="0.01" step="any" required>
                        <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="adjustProductQty(1)">+</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="height: 38px;">
                        <i class="bi bi-plus-lg fs-5"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Tabel Hasil Produksi & Bahan Baku --}}
    <div class="card prod-panel-card mb-3">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-bold text-dark mb-0">Detail Stok Produksi</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-prod-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small fw-semibold">
                        <th class="px-3 py-3" style="width: 35%;">Nama Produk</th>
                        <th class="px-3 py-3 text-center" style="width: 14%;">Kadaluarsa</th>
                        <th class="px-3 py-3 text-center" style="width: 12%;">Rack</th>
                        <th class="px-3 py-3 text-center" style="width: 12%;">Qty</th>
                        <th class="px-3 py-3 text-center" style="width: 10%;">Satuan</th>
                        <th class="px-3 py-3 text-center" style="width: 12%;">Bahan</th>
                        <th class="px-3 py-3 text-center" style="width: 5%;">-</th>
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
                                    <div class="text-muted" style="font-size: 0.75rem;">Biaya/HPP: Rp {{ number_format($pItem->ncost_unit, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center text-muted">
                            {{ $pItem->dexpired ? \Carbon\Carbon::parse($pItem->dexpired)->format('d-M-Y') : '-' }}
                        </td>
                        <td class="px-3 py-3 text-center text-muted">
                            {{ $pItem->crack ?: '-' }}
                        </td>
                        <td class="px-2 py-2 text-center align-middle" style="min-width: 130px;">
                            @if($production->isDraft())
                            <div class="qty-stepper mx-auto">
                                <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="updateRowQty({{ $pItem->nid }}, -1)" title="Kurangi 1">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number"
                                    step="any"
                                    min="0.01"
                                    class="qty-stepper-input no-spinners"
                                    id="prod_qty_{{ $pItem->nid }}"
                                    value="{{ (float)$pItem->nqty }}"
                                    onchange="saveRowQty({{ $pItem->nid }}, this.value)"
                                    onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.blur(); }">
                                <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="updateRowQty({{ $pItem->nid }}, 1)" title="Tambah 1">
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

                    {{-- Accordion Daftar Bahan Baku untuk Produk ini --}}
                    <tr class="collapse {{ $linkedMaterials->isNotEmpty() ? 'show' : '' }}" id="materialsCollapse{{ $pItem->nid }}">
                        <td colspan="7" class="p-3 bg-light-subtle border-bottom">
                            <div class="p-3 rounded-2 bg-white border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold text-secondary small">
                                        <i class="bi bi-layers me-1 text-primary"></i> Bahan Baku yang Digunakan untuk <strong>{{ $pItem->product?->cname }}</strong>:
                                    </div>
                                    @if($production->isDraft())
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded" style="font-size: 0.78rem;" onclick="openAddMaterialModal({{ $pItem->nid }}, '{{ addslashes($pItem->product?->cname ?? '') }}', {{ (float)$pItem->nqty }})">
                                        <i class="bi bi-plus-circle me-1"></i>Tambah Bahan Manual
                                    </button>
                                    @endif
                                </div>

                                @if($linkedMaterials->isEmpty())
                                <div class="text-center py-3 text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Belum ada bahan baku untuk produk ini.
                                    @if($production->isDraft())
                                    <a href="javascript:void(0)" class="text-primary text-decoration-none ms-1" onclick="openAddMaterialModal({{ $pItem->nid }}, '{{ addslashes($pItem->product?->cname ?? '') }}', {{ (float)$pItem->nqty }})">Tambah Bahan Sekarang</a>
                                    @endif
                                </div>
                                @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light">
                                            <tr class="text-secondary">
                                                <th class="px-2 py-2" style="width: 32%;">Nama Bahan Baku</th>
                                                <th class="px-2 py-2 text-center" style="width: 12%;">Tipe</th>
                                                <th class="px-2 py-2 text-center" style="width: 14%;">Takaran / Unit</th>
                                                <th class="px-2 py-2 text-center" style="width: 16%;">Total Qty Dipotong</th>
                                                <th class="px-2 py-2 text-center" style="width: 10%;">Satuan</th>
                                                <th class="px-2 py-2 text-end" style="width: 11%;">Subtotal Biaya</th>
                                                @if($production->isDraft())
                                                <th class="px-2 py-2 text-center" style="width: 5%;">Aksi</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($linkedMaterials as $mat)
                                            @php
                                            $unitQtyVal = ((float)$pItem->nqty > 0) ? ((float)$mat->nqty / (float)$pItem->nqty) : (float)$mat->nqty;
                                            @endphp
                                            <tr>
                                                <td class="px-2 py-2 fw-medium text-dark">
                                                    {{ $mat->item_name }}
                                                    @if($mat->cnote)
                                                    <span class="text-muted ms-1 small">({{ $mat->cnote }})</span>
                                                    @endif
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 0.68rem;">
                                                        {{ $mat->citem_type ?: 'PRODUK' }}
                                                    </span>
                                                </td>
                                                <td class="px-2 py-2 text-center text-secondary fw-medium" id="mat_unit_qty_{{ $mat->nid }}">
                                                    {{ number_format($unitQtyVal, 2, ',', '.') }}
                                                </td>
                                                <td class="px-2 py-2 text-center fw-semibold text-danger" id="mat_qty_{{ $mat->nid }}">
                                                    -{{ number_format($mat->nqty, 2, ',', '.') }}
                                                </td>
                                                <td class="px-2 py-2 text-center text-muted">
                                                    {{ $mat->item_unit }}
                                                </td>
                                                <td class="px-2 py-2 text-end text-secondary" id="mat_subtotal_{{ $mat->nid }}">
                                                    Rp {{ number_format($mat->nsubtotal, 0, ',', '.') }}
                                                </td>
                                                @if($production->isDraft())
                                                <td class="px-2 py-2 text-center">
                                                    <form action="{{ route('inventory.production.materials.destroy', [$production->nid, $mat->nid]) }}" method="POST" onsubmit="return confirm('Hapus bahan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link text-danger p-0 border-0">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-box fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada produk hasil produksi yang ditambahkan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Baris Ringkasan Jumlah Qty Produk --}}
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-secondary">Jumlah Produk:</span>
            <span class="fw-bold fs-6 text-primary" id="totalProductQtyDisplay">{{ number_format($production->ntotal_product_qty, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- Section Biaya Produksi (Additional Cost) --}}
    <div class="card prod-panel-card mb-4 p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold text-dark mb-0">Biaya Produksi Tambahan</h6>
            @if($production->isDraft())
            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded" style="font-size: 0.82rem;" data-bs-toggle="modal" data-bs-target="#modalBiayaProduksi">
                <i class="bi bi-plus-lg me-1"></i> Tambah / Ubah Biaya
            </button>
            @endif
        </div>

        <div class="p-3 bg-light rounded-2 border">
            @if((float)$production->nadditional_cost > 0)
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-medium text-dark">Biaya Operasional / Tenaga Kerja</span>
                    <div class="text-muted small">Biaya overhead tambahan di luar bahan baku</div>
                </div>
                <div class="fw-bold text-success fs-6">
                    Rp {{ number_format($production->nadditional_cost, 0, ',', '.') }}
                </div>
            </div>
            @else
            <div class="text-muted small">
                Belum ada biaya produksi tambahan
            </div>
            @endif
        </div>

        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Total Biaya Produksi (Bahan + Biaya Tambahan):</span>
            <span class="fw-bold fs-6 text-dark" id="totalProductionCostDisplay">Rp {{ number_format($production->ntotal_production_cost, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Tombol Navigasi Bawah --}}
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
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

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kebutuhan per 1 Unit Produk <span class="text-danger">*</span></label>
                            <input type="number" name="nqty" id="matQtyPerUnitInput" class="form-control form-control-sm" min="0.001" step="any" required placeholder="Contoh: 10" oninput="calculateMatTotalPreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Biaya/HPP Satuan</label>
                            <input type="number" name="ncost" id="matCostInput" class="form-control form-control-sm" min="0" step="any" placeholder="Otomatis dari harga beli">
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

{{-- Modal Tambah / Ubah Biaya Produksi --}}
<div class="modal fade" id="modalBiayaProduksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.additional-cost', $production->nid) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-cash-stack text-success me-2"></i>Biaya Produksi Tambahan
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nominal Biaya Tambahan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nadditional_cost" class="form-control" value="{{ (float)$production->nadditional_cost }}" min="0" step="any" required placeholder="0">
                        <div class="form-text small">Masukkan biaya di luar bahan baku, misalnya biaya tenaga kerja, listrik, atau kemasan.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-3">Simpan Biaya</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ubah Qty & Informasi Produk Hasil Produksi --}}
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

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Kadaluarsa (Opsional)</label>
                            <input type="date" name="dexpired" id="editProdExpiredInput" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Posisi Rak (Opsional)</label>
                            <input type="text" name="crack" id="editProdRackInput" class="form-control form-control-sm" placeholder="Contoh: Rak A-1">
                        </div>
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
        {{-- Form Cepat Ubah Qty Baris Produk --}}
        <form id="quickQtyForm" method="POST" action="" style="display: none;">
            @csrf
            @method('PUT')
            <input type="hidden" name="nqty" id="quickQtyInput">
        </form>
        @endif

        <script>
            let matTsInstance = null;
            let prodTsInstance = null;

            document.addEventListener('DOMContentLoaded', function() {
                // TomSelect pada form Tambah Produk (Persis seperti modal Stok Masuk & Stok Keluar)
                const prodSelectEl = document.getElementById('productSelect');
                if (prodSelectEl && window.TomSelect) {
                    prodTsInstance = new TomSelect(prodSelectEl, {
                        create: false,
                        maxItems: 1,
                        placeholder: '-- Cari atau pilih produk --',
                        allowEmptyOption: true,
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

                // TomSelect pada modal Tambah Bahan Baku (Persis seperti modal Stok Masuk & Stok Keluar)
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

                    matTsInstance.on('change', function(val) {
                        const opt = matTsInstance.options[val];
                        const matCostInput = document.getElementById('matCostInput');
                        if (opt && matCostInput) {
                            const costVal = opt.cost !== undefined ? opt.cost : (opt.price !== undefined ? opt.price : 0);
                            matCostInput.value = Math.round(parseFloat(costVal) || 0);
                        }
                    });
                }
            });

            let qtyDebounceTimers = {};

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

                        // Update total biaya produksi display
                        const totalCostEl = document.getElementById('totalProductionCostDisplay');
                        if (totalCostEl && data.formatted_total_production_cost) {
                            totalCostEl.textContent = data.formatted_total_production_cost;
                        }

                        // Update bahan baku live
                        if (Array.isArray(data.materials)) {
                            data.materials.forEach(mat => {
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

            function adjustProductQty(delta) {
                const inp = document.getElementById('productQtyInput');
                if (inp) {
                    let val = parseFloat(inp.value) || 0;
                    val = Math.max(1, val + delta);
                    inp.value = val;
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
                if (unitInp) unitInp.value = '';
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
                previewEl.textContent = total.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' (' + unitVal + ' × ' + modalTargetProductQty + ' produk)';
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
                    val = Math.max(0.01, val + delta);
                    inp.value = val;
                }
            }
        </script>
        @endsection