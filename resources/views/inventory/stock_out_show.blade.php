@extends('layouts.app')

@section('title', 'Inventori / Detail Stok Keluar')
@section('custom_alerts', '1')

@section('sub_header')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="catalog-nav-tab">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="catalog-nav-tab active">
            <span>Stok Keluar</span>
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
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background-color: #ffffff;
        transition: all 0.15s ease-in-out;
        position: relative;
        max-width: 105px !important;
        height: 31px !important;
    }

    .qty-stepper:hover,
    .qty-stepper:focus-within {
        border-color: #0ea5e9;
        box-shadow: 0 0 0 2.5px rgba(14, 165, 233, 0.18);
    }

    .qty-stepper-btn {
        border: none;
        outline: none;
        width: 30px;
        min-width: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ffffff;
        color: #64748b;
        cursor: pointer;
        position: relative;
        transition: all 0.18s ease;
        padding: 0;
        font-size: 0.82rem;
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
        min-width: 45px;
        text-align: center;
        font-weight: 700;
        font-size: 0.88rem;
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

    /* Table Compact & Balanced Styling untuk Detail Stok Keluar (Menyamakan Modal Stok Masuk & Stok Keluar) */
    #stockOutDetailsTable {
        table-layout: fixed !important;
        width: 100% !important;
    }

    #stockOutDetailsTable th {
        font-size: 0.73rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.4px !important;
        color: #475569 !important;
        padding: 9px 6px !important;
        vertical-align: middle !important;
    }

    #stockOutDetailsTable td {
        padding: 6px 6px !important;
        vertical-align: middle !important;
        font-size: 0.81rem !important;
    }

    #stockOutDetailsTable .new-row-type {
        font-size: 0.78rem !important;
        height: 31px !important;
        padding: 2px 20px 2px 7px !important;
        background-position: right 6px center !important;
        background-size: 10px 10px !important;
    }

    #stockOutDetailsTable .ts-control {
        font-size: 0.79rem !important;
        min-height: 31px !important;
        height: 31px !important;
        padding: 2px 8px !important;
    }

    #stockOutDetailsTable .ts-dropdown {
        font-size: 0.79rem !important;
    }

    #stockOutDetailsTable .new-row-cost-input {
        font-size: 0.79rem !important;
        height: 31px !important;
        padding: 2px 6px !important;
    }

    #stockOutDetailsTable .input-group-text {
        font-size: 0.74rem !important;
        padding: 2px 6px !important;
        height: 31px !important;
    }

    #stockOutDetailsTable .qty-stepper {
        height: 31px !important;
        max-width: 105px !important;
    }

    #stockOutDetailsTable .new-row-stock-badge {
        font-size: 0.78rem !important;
        padding: 4px 8px !important;
    }

    .detail-card {
        border: 1px solid #cbd5e1 !important;
    }

    @media (max-width: 575.98px) {
        .catalog-nav-tab {
            min-width: 0;
            padding: 11px 16px;
        }
    }
</style>
@endsection

@section('content')
<div class="mb-4">
    <!-- Alert Notifikasi -->
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

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('price_warnings') && count(session('price_warnings')) > 0)
    <div class="alert alert-warning alert-dismissible fade show mb-3 shadow-sm" role="alert">
        <div class="fw-bold mb-2">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Peringatan Perbedaan Harga Beli (PDF vs Master Produk)
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white mb-0 small">
                <thead class="table-warning">
                    <tr>
                        <th>Produk</th>
                        <th class="text-end">Harga di PDF</th>
                        <th class="text-end">Harga Beli Master</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(session('price_warnings') as $pw)
                    <tr>
                        <td class="fw-semibold">{{ $pw['cname'] }}</td>
                        <td class="text-end text-danger fw-semibold">Rp {{ number_format((float) $pw['pdf_cost'], 0, ',', '.') }}</td>
                        <td class="text-end text-success fw-semibold">Rp {{ number_format((float) $pw['master_cost'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Alert Notifikasi Olsera -->
    <div class="alert alert-light detail-card shadow-sm d-flex align-items-center mb-3 py-3 px-4 rounded-3 text-secondary" style="background-color: #f8fafc;">
        <i class="bi bi-exclamation-circle text-muted fs-5 me-3"></i>
        <div class="small">
            @if($stockOut->isDraft)
            <span><strong>Status Draf:</strong> Pastikan seluruh data dan kuantitas barang sudah benar sebelum diposting. Selama status masih <strong>Draf</strong>, stok fisik barang belum dipotong dari outlet asal. Anda dapat menambah, mengubah, atau menghapus item sebelum menekan <strong>Posting Sekarang</strong>.</span>
            @elseif($stockOut->isPosted)
            <span><strong>Status Posted:</strong> Dokumen transaksi ini telah <strong>Diposting</strong>. Stok fisik barang telah dipotong secara otomatis{{ $stockOut->targetOutlet ? ' dan ditambahkan ke outlet tujuan' : '' }}. Mutasi telah dicatat di laporan pergerakan stok.</span>
            @else
            <span><strong>Status Cancelled:</strong> Dokumen transaksi Stok Keluar ini telah <strong>Dibatalkan</strong>. Transaksi tidak memotong stok fisik atau stok telah dikembalikan ke outlet asal.</span>
            @endif
        </div>
    </div>

    <!-- Banner Utama: Status & No Stok Keluar (Gaya Olsera) -->
    <div class="card detail-card shadow-sm mb-4 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    @if($stockOut->isPosted)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-check2-circle me-1"></i> Posted
                    </span>
                    @elseif($stockOut->isDraft)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-file-earmark me-1"></i> Draft
                    </span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Cancelled
                    </span>
                    @endif

                    <div>
                        <div class="text-muted small">{{ $stockOut->targetOutlet ? 'No. Transfer Stok' : 'No. Stok Keluar' }}</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stockOut->cno_stock_out }}</h4>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-shop me-1"></i> {{ $stockOut->outlet?->cname ?: 'Outlet #' . $stockOut->nid_outlet }}
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="{{ route('inventory.stock-out.print', $stockOut->cno_stock_out) }}" target="_blank" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </a>

                    @if($canModifyData && $stockOut->isDraft)
                    <form action="{{ route('inventory.stock-out.cancel', $stockOut->cno_stock_out) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan draf Stok Keluar ini?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm px-3 shadow-sm">
                            <i class="bi bi-x-lg me-1"></i> Batalkan
                        </button>
                    </form>
                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPostingConfirm">
                        <i class="bi bi-check2-circle me-1"></i> Posting Sekarang
                    </button>
                    @elseif(($editEligibility['showButton'] ?? false) && $stockOut->isPosted)
                        @if($editEligibility['canEdit'])
                        <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#editPostedStockOutModal">
                            <i class="bi bi-pencil-square me-1"></i> Edit Transaksi (SPV)
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" disabled title="{{ $editEligibility['reason'] }}">
                            <i class="bi bi-lock me-1"></i> Edit Terkunci
                        </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Kotak Info Atas (Tanggal, Transfer ke, Catatan, Alasan) -->
    <div class="row g-3 mb-4">
        <!-- Kotak 1: Tanggal & User -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3 position-relative">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small fw-semibold">Tanggal Keluar</div>
                        @if($canModifyData && $stockOut->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark">{{ $stockOut->dstock_out ? $stockOut->dstock_out->format('d-M-Y') : '-' }}</div>
                    @php
                    $stockOutUserDisplay = $stockOut->user?->cemail ?: ($stockOut->user?->email ?: ($stockOut->user?->cname ?: 'admin'));
                    @endphp
                    <div class="text-muted small text-truncate" title="{{ $stockOutUserDisplay }}">{{ $stockOutUserDisplay }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 2: Transfer ke -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold mb-1">Transfer ke</div>
                    @if($stockOut->targetOutlet)
                    <div class="fw-bold text-primary">{{ $stockOut->targetOutlet->cname }}</div>
                    <div class="text-muted small">Transfer Antar Outlet</div>
                    @else
                    <div class="fw-bold text-secondary">-</div>
                    <div class="text-muted small">Bukan Transfer (Stok Keluar)</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Kotak 3: Catatan -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small fw-semibold">Catatan</div>
                        @if($canModifyData && $stockOut->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark text-truncate" title="{{ $stockOut->cnote }}">{{ $stockOut->cnote ?: '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 4: Alasan -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small fw-semibold">Alasan</div>
                        @if($canModifyData && $stockOut->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark">{{ $stockOut->creason ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Biru Tabel Produk (Persis Olsera Image 3 & 4) -->
    <div class="card detail-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-white">
                Item Barang Keluar (<span id="totalDetailItemCount">{{ $stockOut->details->count() }}</span>)
            </h6>
            @if($canModifyData && $stockOut->isDraft)
            <button type="button" class="btn btn-light btn-sm fw-semibold shadow-sm text-primary" onclick="addNewDetailRow()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Item
            </button>
            @endif
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" id="stockOutDetailsTable" style="table-layout: fixed; width: 100%;">
                <thead class="bg-light">
                    @if($canModifyData && $stockOut->isDraft)
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 4%;" class="text-center py-2.5 text-nowrap">No</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Tipe</th>
                        <th style="width: 30%;" class="py-2.5 text-nowrap ps-2">Produk / Bahan Baku</th>
                        <th style="width: 10%;" class="text-center py-2.5 text-nowrap">Stok Saat Ini</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Qty Keluar</th>
                        <th style="width: 13%;" class="text-end py-2.5 text-nowrap pe-3">Harga Beli</th>
                        <th style="width: 12%;" class="text-end py-2.5 text-nowrap pe-3">Subtotal</th>
                        <th style="width: 7%;" class="text-center py-2.5 text-nowrap">Aksi</th>
                    </tr>
                    @else
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 4%;" class="text-center py-2.5 text-nowrap">No</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Tipe</th>
                        <th style="width: 35%;" class="py-2.5 text-nowrap ps-2">Produk / Bahan Baku</th>
                        <th style="width: 11%;" class="text-center py-2.5 text-nowrap">Stok Saat Ini</th>
                        <th style="width: 13%;" class="text-center py-2.5 text-nowrap">Qty Keluar</th>
                        <th style="width: 14%;" class="text-end py-2.5 text-nowrap pe-3">Harga Beli</th>
                        <th style="width: 15%;" class="text-end py-2.5 text-nowrap pe-3">Subtotal</th>
                    </tr>
                    @endif
                </thead>
                <tbody class="small" id="stockOutDetailsTableBody">
                    @php $totalDetailQty = 0; @endphp
                    @forelse($stockOut->details as $idx => $detail)
                    @php
                    $isIng = $detail->isIngredient();
                    $normType = $detail->getItemTypeNormalized();
                    $itemName = $detail->product?->cname ?: ($isIng ? ($detail->ingredient?->cname ?: 'Bahan Baku') : 'Produk');
                    $itemCategory = $detail->product?->category?->cname ?: $detail->getItemTypeLabel();
                    $satuan = strtolower($detail->getItemUnit());
                    $currentStock = $detail->current_stock ?? ($isIng ? ($detail->ingredient?->nstock ?? 0) : ($detail->product?->nqty ?? 0));
                    $totalDetailQty += (float) $detail->nqty;
                    @endphp
                    <tr id="row-item-{{ $detail->nid }}">
                        <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                        <td class="text-center">
                            @if($normType === 'SETENGAH_JADI')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill">Setengah Jadi</span>
                            @elseif($normType === 'BAHAN_BAKU')
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill">Bahan Baku</span>
                            @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">Produk</span>
                            @endif
                        </td>
                        <td class="ps-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-light d-flex align-items-center justify-content-center border flex-shrink-0" style="width: 32px; height: 32px; min-width: 32px;">
                                    <i class="bi bi-{{ $normType === 'BAHAN_BAKU' ? 'droplet text-info' : ($normType === 'SETENGAH_JADI' ? 'layers text-warning' : 'box-seam text-primary') }} fs-6"></i>
                                </div>
                                <div class="text-truncate">
                                    <div class="fw-bold text-dark text-truncate" title="{{ $itemName }}">{{ $itemName }}</div>
                                    <div class="text-muted" style="font-size: 0.74rem;">
                                        <span class="badge bg-light text-secondary border me-1">{{ $itemCategory }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fw-semibold">
                                {{ rtrim(rtrim(number_format((float) $currentStock, 2, '.', ''), '0'), '.') }}
                                @if($satuan)<span class="text-muted fw-normal">{{ $satuan }}</span>@endif
                            </span>
                        </td>
                        <td class="text-center">
                            @if($canModifyData && $stockOut->isDraft)
                            <div class="d-inline-flex justify-content-center">
                                <div class="qty-stepper mx-auto" data-item-id="{{ $detail->nid }}" data-cost="{{ (float) $detail->ncost }}">
                                    <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="stepDetailQty('{{ $detail->nid }}', -1)" title="Kurangi">−</button>
                                    <input type="number"
                                        class="qty-stepper-input detail-qty-input"
                                        id="qty-input-{{ $detail->nid }}"
                                        value="{{ rtrim(rtrim(number_format((float) $detail->nqty, 2, '.', ''), '0'), '.') }}"
                                        min="0.01"
                                        step="any"
                                        onchange="saveDetailQty('{{ $detail->nid }}')">
                                    <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="stepDetailQty('{{ $detail->nid }}', 1)" title="Tambah">+</button>
                                </div>
                            </div>
                            @else
                            <span class="fw-bold fs-6 text-dark">
                                {{ rtrim(rtrim(number_format((float) $detail->nqty, 2, '.', ''), '0'), '.') }}
                                @if($satuan)<span class="small text-muted fw-normal ms-1">{{ $satuan }}</span>@endif
                            </span>
                            @endif
                        </td>
                        <td class="text-end text-secondary fw-semibold pe-3">
                            <div>Rp {{ number_format((float) $detail->ncost, 0, ',', '.') }}</div>
                            @if($detail->product && (float) $detail->product->nprice_buy > 0 && abs((float) $detail->product->nprice_buy - (float) $detail->ncost) > 0.01)
                            <div class="small text-warning-emphasis mt-1" title="Harga beli di Master Produk: Rp {{ number_format((float) $detail->product->nprice_buy, 0, ',', '.') }}">
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Beda Master (Rp {{ number_format((float) $detail->product->nprice_buy, 0, ',', '.') }})
                                </span>
                            </div>
                            @endif
                        </td>
                        <td class="text-end text-dark fw-bold pe-3" id="subtotal-cell-{{ $detail->nid }}">
                            Rp {{ number_format((float) $detail->nsubtotal, 0, ',', '.') }}
                        </td>
                        @if($canModifyData && $stockOut->isDraft)
                        <td class="text-center">
                            <form action="{{ route('inventory.stock-out.items.destroy', [$stockOut->cno_stock_out, $detail->nid]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari dokumen draf?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm p-1 rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" title="Hapus Item">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr id="empty-row">
                        <td colspan="{{ ($canModifyData && $stockOut->isDraft) ? '8' : '7' }}" class="text-center py-4 text-muted">
                            Belum ada item detail pada dokumen stok keluar ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($stockOut->details->isNotEmpty())
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end py-2.5 pe-3">Jumlah:</td>
                        <td class="text-center text-primary py-2.5 fs-6" id="totalQtyDisplay">
                            {{ rtrim(rtrim(number_format($totalDetailQty, 2, '.', ''), '0'), '.') }}
                        </td>
                        <td class="text-end py-2.5 text-secondary pe-2">Total Nilai:</td>
                        <td class="text-end text-primary py-2.5 fs-6 pe-3" id="totalCostDisplay">
                            Rp {{ number_format((float) $stockOut->ntotal_cost, 0, ',', '.') }}
                        </td>
                        @if($canModifyData && $stockOut->isDraft)
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@if($stockOut->dedited_at)
<div class="card detail-card shadow-sm mb-4 rounded-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-1 text-warning"></i> Riwayat Revisi Transaksi</h6>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pernah Diedit</span>
        </div>
    </div>
    <div class="card-body bg-white py-3">
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted mb-1">Terakhir Diedit Oleh</div>
                <div class="fw-semibold text-dark">{{ $stockOut->cedited_by ?: 'SPV' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted mb-1">Waktu Revisi</div>
                <div class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($stockOut->dedited_at)->format('d M Y, H:i') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted mb-1">Alasan Revisi</div>
                <div class="text-dark" style="white-space: pre-line;">{{ $stockOut->cedit_reason ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ==================== MODAL EDIT TRANSAKSI POSTED (SPV) ==================== --}}
@if(($editEligibility['canEdit'] ?? false) && $stockOut->isPosted)
<div class="modal fade" id="editPostedStockOutModal" tabindex="-1" aria-labelledby="editPostedStockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('inventory.stock-out.update-posted', $stockOut->cno_stock_out) }}" method="POST" class="d-flex flex-column overflow-hidden" style="max-height: calc(100vh - 2rem);">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning-subtle flex-shrink-0">
                    <h5 class="modal-title fw-bold text-dark" id="editPostedStockOutModalLabel">
                        <i class="bi bi-pencil-square me-1"></i> Edit Stok Keluar ({{ $stockOut->cno_stock_out }})
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body overflow-y-auto" style="max-height: 72vh;">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-shield-check me-1"></i>
                        <strong>Perhatian:</strong> Penambahan qty keluar akan divalidasi terhadap stok saat ini di outlet asal{{ $stockOut->targetOutlet ? ' (dan pengurangan transfer divalidasi terhadap stok outlet tujuan ' . $stockOut->targetOutlet->cname . ')' : '' }}.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Edit / Revisi <span class="text-danger">*</span></label>
                        <textarea name="cedit_reason" class="form-control form-control-sm" rows="2" required minlength="5" maxlength="500" placeholder="Jelaskan alasan perubahan transaksi (minimal 5 karakter)...">{{ old('cedit_reason') }}</textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Tanggal Keluar <span class="text-danger">*</span></label>
                            <input type="date" name="dstock_out" class="form-control form-control-sm" value="{{ old('dstock_out', $stockOut->dstock_out ? \Carbon\Carbon::parse($stockOut->dstock_out)->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Alasan Pengeluaran</label>
                            <input type="text" name="creason" class="form-control form-control-sm" value="{{ old('creason', $stockOut->creason) }}" maxlength="255">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Catatan</label>
                            <input type="text" name="cnote" class="form-control form-control-sm" value="{{ old('cnote', $stockOut->cnote) }}" maxlength="1000">
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="card bg-light border mb-3 p-2">
                        <div class="small fw-semibold mb-2"><i class="bi bi-plus-circle me-1 text-primary"></i> Tambah Item Baru ke Stok Keluar</div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Pilih Item</label>
                                <select id="editStockOutNewItemSelect" class="form-select form-select-sm">
                                    <option value="">-- Cari atau pilih Produk / Bahan Baku --</option>
                                    @foreach($outletProducts as $p)
                                    @php
                                        $pType = \App\Models\MposStockMutation::normalizeItemType($p->cproduct_type ?? 'PRODUK');
                                        $pSatuan = $p->csatuan ?: 'PCS';
                                    @endphp
                                    <option value="product:{{ $p->nid }}"
                                        data-kind="product"
                                        data-id="{{ $p->nid }}"
                                        data-type="{{ $pType }}"
                                        data-name="{{ $p->cname }}"
                                        data-unit="{{ $pSatuan }}"
                                        data-stock="{{ $p->nqty ?? 0 }}"
                                        data-cost="{{ $p->nprice_buy ?? 0 }}">
                                        [{{ $pType === 'SETENGAH_JADI' ? 'Setengah Jadi' : ($pType === 'BAHAN_BAKU' ? 'Bahan Baku' : 'Produk') }}] {{ $p->cname }} (Stok: {{ $p->nqty ?? 0 }} {{ $pSatuan }})
                                    </option>
                                    @endforeach
                                    @foreach($outletIngredients as $ing)
                                    @php $ingSatuan = $ing->csatuan ?: 'PCS'; @endphp
                                    <option value="ingredient:{{ $ing->nid }}"
                                        data-kind="ingredient"
                                        data-id="{{ $ing->nid }}"
                                        data-type="BAHAN_BAKU"
                                        data-name="{{ $ing->cname }}"
                                        data-unit="{{ $ingSatuan }}"
                                        data-stock="{{ $ing->nstock ?? 0 }}"
                                        data-cost="{{ $ing->nprice ?? 0 }}">
                                        [Bahan Baku] {{ $ing->cname }} (Stok: {{ $ing->nstock ?? 0 }} {{ $ingSatuan }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Qty</label>
                                <input type="number" id="editStockOutNewQty" class="form-control form-control-sm" min="0.01" step="any" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Harga Modal (Rp)</label>
                                <input type="number" id="editStockOutNewCost" class="form-control form-control-sm" min="0" step="any" value="0">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btnAddEditStockOutRow" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Daftar Item Stok Keluar</h6>
                        <span class="text-muted small">Minimal 1 item harus tersisa</span>
                    </div>

                    <div class="table-responsive border rounded mb-2">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>ITEM</th>
                                    <th class="text-center">TIPE</th>
                                    <th class="text-center">STOK SEKARANG</th>
                                    <th style="width: 120px;">QTY KELUAR</th>
                                    <th style="width: 160px;">HARGA MODAL (RP)</th>
                                    <th class="text-end" style="width: 150px;">SUBTOTAL</th>
                                    <th class="text-center" style="width: 60px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="small" id="editStockOutItemsBody">
                                @foreach($stockOut->details as $idx => $detail)
                                @php
                                    $isIng = $detail->isIngredient();
                                    $itemName = $isIng ? ($detail->ingredient?->cname ?? 'Bahan Baku') : ($detail->product?->cname ?? 'Produk');
                                    $itemUnit = $isIng ? ($detail->ingredient?->csatuan ?: 'PCS') : ($detail->product?->csatuan ?: 'PCS');
                                    $normType = $detail->getItemTypeNormalized();
                                    $currStock = (float) ($detail->current_stock ?? 0);
                                @endphp
                                <tr class="edit-so-row">
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $itemName }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Satuan: {{ $itemUnit }}</div>
                                        <input type="hidden" name="items[{{ $idx }}][citem_type]" value="{{ $normType }}">
                                        <input type="hidden" name="items[{{ $idx }}][nid_product]" value="{{ $detail->nid_product }}">
                                        <input type="hidden" name="items[{{ $idx }}][nid_ingredient]" value="{{ $detail->nid_ingredient }}">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $detail->getItemTypeLabel() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis border">{{ rtrim(rtrim(number_format($currStock, 2, ',', '.'), '0'), ',') }} {{ $itemUnit }}</span>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][nqty]" class="form-control form-control-sm edit-so-qty" min="0.01" step="any" value="{{ (float) $detail->nqty }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][ncost]" class="form-control form-control-sm edit-so-cost" min="0" step="any" value="{{ (float) $detail->ncost }}" required>
                                    </td>
                                    <td class="text-end fw-bold edit-so-subtotal">
                                        Rp {{ number_format((float) $detail->nsubtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-so-row" title="Hapus Baris">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">TOTAL MODAL REVISI:</td>
                                    <td class="text-end fw-bold text-danger" id="editStockOutGrandTotal">
                                        Rp {{ number_format((float) $stockOut->ntotal_cost, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 bg-white">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm px-3 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Revisi & Sinkronkan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ==================== MODAL KONFIRMASI POSTING ==================== --}}
@if($canModifyData && $stockOut->isDraft)
<div class="modal fade" id="modalPostingConfirm" tabindex="-1" aria-labelledby="modalPostingConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-out.post', $stockOut->cno_stock_out) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalPostingConfirmLabel">
                        <i class="bi bi-check2-circle me-2"></i> Konfirmasi Posting Stok Keluar
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        Anda akan memposting dokumen <strong>{{ $stockOut->cno_stock_out }}</strong>.
                    </p>
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Penting:</strong> Setelah diposting:
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Stok fisik produk / bahan baku di <strong>{{ $stockOut->outlet?->cname ?: 'Outlet Asal' }}</strong> akan langsung berkurang.</li>
                            @if($stockOut->targetOutlet)
                            <li>Stok fisik di outlet tujuan <strong>{{ $stockOut->targetOutlet->cname }}</strong> akan langsung bertambah dan dokumen Stok Masuk otomatis dibuat.</li>
                            @endif
                            <li>Data mutasi akan dicatat resmi pada Pergerakan Stok.</li>
                        </ul>
                    </div>
                    <div class="bg-light p-3 rounded-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Item Barang:</span>
                            <span class="fw-bold">{{ $stockOut->details->count() }} item</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Kuantitas:</span>
                            <span class="fw-bold text-dark">{{ rtrim(rtrim(number_format($totalDetailQty, 2, '.', ''), '0'), '.') }} unit</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Nilai Modal:</span>
                            <span class="fw-bold text-danger">Rp {{ number_format((float) $stockOut->ntotal_cost, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Ya, Posting Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL UBAH INFO DOKUMEN (HEADER) ==================== --}}
<div class="modal fade" id="modalEditHeader" tabindex="-1" aria-labelledby="modalEditHeaderLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-out.update', $stockOut->cno_stock_out) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="modalEditHeaderLabel">
                        <i class="bi bi-pencil-square me-2 text-primary"></i> Ubah Informasi Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tanggal Keluar <span class="text-danger">*</span></label>
                        <input type="date" name="dstock_out" class="form-control" value="{{ $stockOut->dstock_out ? $stockOut->dstock_out->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alasan Pengeluaran</label>
                        <select name="creason" class="form-select">
                            <option value="Transfer Antar Toko" {{ $stockOut->creason === 'Transfer Antar Toko' ? 'selected' : '' }}>Transfer Antar Toko</option>
                            <option value="Rusak / Cacat" {{ $stockOut->creason === 'Rusak / Cacat' ? 'selected' : '' }}>Rusak / Cacat</option>
                            <option value="Kedaluwarsa" {{ $stockOut->creason === 'Kedaluwarsa' ? 'selected' : '' }}>Kedaluwarsa</option>
                            <option value="Pemakaian Bar / Kitchen" {{ $stockOut->creason === 'Pemakaian Bar / Kitchen' ? 'selected' : '' }}>Pemakaian Bar / Kitchen</option>
                            <option value="Pemakaian Internal / Promosi" {{ $stockOut->creason === 'Pemakaian Internal / Promosi' ? 'selected' : '' }}>Pemakaian Internal / Promosi</option>
                            <option value="Koreksi Selisih Stok" {{ $stockOut->creason === 'Koreksi Selisih Stok' ? 'selected' : '' }}>Koreksi Selisih Stok</option>
                            <option value="Lain-lain" {{ $stockOut->creason === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan</label>
                        <textarea name="cnote" class="form-control" rows="3" placeholder="Tuliskan catatan transaksi...">{{ $stockOut->cnote }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script type="application/json" id="stockOutDraftProductsData">
    @json($availableProducts ?? [])
</script>
<script>
    // Master data mpos_product untuk outlet asal
    const draftProducts = JSON.parse(document.getElementById('stockOutDraftProductsData')?.textContent || '[]');
    let newRowCounter = 0;
    const newRowTsInstances = {};

    function normalizeDraftItemType(val) {
        const raw = String(val || 'PRODUK').trim().toUpperCase();
        if (raw === 'SETENGAH_JADI') return 'SETENGAH_JADI';
        if (raw === 'BAHAN_BAKU' || raw === 'INGREDIENT') return 'BAHAN_BAKU';
        return 'PRODUK';
    }

    function getDraftPlaceholderForType(type) {
        const norm = normalizeDraftItemType(type);
        if (norm === 'BAHAN_BAKU') return '-- Cari atau pilih bahan baku --';
        if (norm === 'SETENGAH_JADI') return '-- Cari atau pilih setengah jadi --';
        return '-- Cari atau pilih produk --';
    }

    function formatNumber(num) {
        const val = parseFloat(num || 0);
        return Number.isInteger(val) ? String(val) : String(parseFloat(val.toFixed(2)));
    }

    // Tambah baris baru langsung ke bawah tabel item
    function addNewDetailRow() {
        newRowCounter++;
        const curIdx = newRowCounter;
        const tbody = document.getElementById('stockOutDetailsTableBody');
        if (!tbody) return;

        const existingRows = tbody.querySelectorAll('tr:not(#empty-row)').length;
        const nextNo = existingRows + 1;

        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const tr = document.createElement('tr');
        tr.id = `new-detail-row-${curIdx}`;
        tr.className = 'align-middle';
        tr.style.backgroundColor = '#fffbeb';

        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold">${nextNo}</td>
            <td class="text-center align-middle">
                <select class="form-select form-select-sm fw-semibold new-row-type" id="new_type_${curIdx}" onchange="onNewRowTypeChange(${curIdx})">
                    <option value="PRODUK" selected>Produk</option>
                    <option value="SETENGAH_JADI">Setengah Jadi</option>
                    <option value="BAHAN_BAKU">Bahan Baku</option>
                </select>
            </td>
            <td class="align-middle ps-2">
                <select class="form-select form-select-sm new-row-item-select" id="new_item_select_${curIdx}">
                    <option value="">-- Cari atau pilih produk --</option>
                </select>
            </td>
            <td class="text-center align-middle">
                <span class="badge bg-light text-secondary border new-row-stock-badge" id="new_stock_badge_${curIdx}">-</span>
            </td>
            <td class="text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="stepNewRowQty(${curIdx}, -1)" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" step="any" min="0.01" class="qty-stepper-input no-spinners new-row-qty-input" id="new_qty_${curIdx}" value="1" placeholder="1" oninput="calcNewRowSubtotal(${curIdx})">
                    <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="stepNewRowQty(${curIdx}, 1)" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="text-end align-middle pe-3">
                <div class="input-group input-group-sm ms-auto" style="max-width: 125px;">
                    <span class="input-group-text bg-light text-muted">Rp</span>
                    <input type="number" step="any" min="0" class="form-control form-control-sm text-end new-row-cost-input" id="new_cost_${curIdx}" value="0" oninput="calcNewRowSubtotal(${curIdx})">
                </div>
            </td>
            <td class="text-end fw-semibold text-danger align-middle pe-3 new-row-subtotal-display" id="new_subtotal_${curIdx}">
                Rp 0
            </td>
            <td class="text-center align-middle">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-success btn-sm p-1 rounded-circle shadow-sm" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" title="Simpan Baris Ini" onclick="saveNewDetailRow(${curIdx})">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" title="Batal Tambah" onclick="cancelNewDetailRow(${curIdx})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);

        const selectEl = document.getElementById(`new_item_select_${curIdx}`);
        const stockBadge = document.getElementById(`new_stock_badge_${curIdx}`);
        const costInput = document.getElementById(`new_cost_${curIdx}`);

        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: '-- Cari atau pilih produk --',
            allowEmptyOption: true,
            sortField: {
                field: "text",
                direction: "asc"
            },
            render: {
                item: function(data, escape) {
                    return '<div class="text-truncate fw-medium">' + escape(data.text) + '</div>';
                },
                option: function(data, escape) {
                    if (!data.value) return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                    const stockVal = (data.stock !== undefined && data.stock !== null) ? data.stock : '-';
                    const unitVal = data.satuan || 'pcs';
                    const stockLabel = stockVal === '-' ? '-' : `${formatNumber(stockVal)} ${unitVal}`;
                    return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                        '<span class="fw-medium text-dark text-truncate me-2">' + escape(data.text) + '</span>' +
                        '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                        '</div>';
                }
            }
        });

        newRowTsInstances[curIdx] = ts;

        ts.on('change', function(val) {
            const optData = ts.options[val];
            if (optData && val) {
                const stock = parseFloat(optData.stock || 0);
                const unit = optData.satuan || 'pcs';
                stockBadge.textContent = `${formatNumber(stock)} ${unit}`;
                if (stock <= 0) {
                    stockBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle new-row-stock-badge';
                } else if (stock <= 5) {
                    stockBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle new-row-stock-badge';
                } else {
                    stockBadge.className = 'badge bg-success-subtle text-success border border-success-subtle new-row-stock-badge';
                }
                costInput.value = Math.round(parseFloat(optData.cost || 0));
            } else {
                stockBadge.textContent = '-';
                stockBadge.className = 'badge bg-light text-secondary border new-row-stock-badge';
                costInput.value = 0;
            }
            calcNewRowSubtotal(curIdx);
        });

        populateNewRowOptions(curIdx, 'PRODUK');
        calcNewRowSubtotal(curIdx);

        // Auto-focus ke dropdown tom-select
        ts.focus();
    }

    function onNewRowTypeChange(rowId) {
        const typeSelect = document.getElementById(`new_type_${rowId}`);
        const type = typeSelect ? typeSelect.value : 'PRODUK';
        populateNewRowOptions(rowId, type);
    }

    function populateNewRowOptions(rowId, type) {
        const ts = newRowTsInstances[rowId];
        if (!ts) return;

        const normType = normalizeDraftItemType(type);
        ts.clear(true);
        ts.clearOptions();

        const placeholder = getDraftPlaceholderForType(normType);
        ts.settings.placeholder = placeholder;
        ts.inputState();

        const list = draftProducts.filter(item => normalizeDraftItemType(item.cproduct_type || 'PRODUK') === normType);
        list.forEach(item => {
            const stock = parseFloat(item.nqty !== undefined && item.nqty !== null ? item.nqty : 0);
            const cost = parseFloat(item.nprice_buy !== undefined && item.nprice_buy !== null ? item.nprice_buy : 0);
            const satuan = item.csatuan || 'pcs';

            ts.addOption({
                value: String(item.nid),
                text: item.cname,
                stock: stock,
                cost: cost,
                satuan: satuan
            });
        });
        ts.refreshOptions(false);
    }

    function stepNewRowQty(rowId, delta) {
        const input = document.getElementById(`new_qty_${rowId}`);
        if (!input) return;
        let val = parseFloat(input.value) || 1;
        val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
        input.value = val;
        calcNewRowSubtotal(rowId);
    }

    function calcNewRowSubtotal(rowId) {
        const qtyInput = document.getElementById(`new_qty_${rowId}`);
        const costInput = document.getElementById(`new_cost_${rowId}`);
        const subtotalEl = document.getElementById(`new_subtotal_${rowId}`);

        const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
        const cost = parseFloat(costInput ? costInput.value : 0) || 0;
        const subtotal = qty * cost;

        if (subtotalEl) {
            subtotalEl.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
        }
    }

    function cancelNewDetailRow(rowId) {
        const row = document.getElementById(`new-detail-row-${rowId}`);
        if (!row) return;
        if (newRowTsInstances[rowId]) {
            newRowTsInstances[rowId].destroy();
            delete newRowTsInstances[rowId];
        }
        row.remove();
    }

    function saveNewDetailRow(rowId) {
        const row = document.getElementById(`new-detail-row-${rowId}`);
        if (!row) return;

        const ts = newRowTsInstances[rowId];
        const itemId = ts ? ts.getValue() : null;
        if (!itemId) {
            alert('Silakan pilih produk atau bahan baku terlebih dahulu.');
            return;
        }

        const type = document.getElementById(`new_type_${rowId}`)?.value || 'PRODUK';
        const qty = parseFloat(document.getElementById(`new_qty_${rowId}`)?.value) || 1;
        const cost = parseFloat(document.getElementById(`new_cost_${rowId}`)?.value) || 0;

        const saveBtn = row.querySelector('.btn-success');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }

        fetch('{{ route("inventory.stock-out.items.store", $stockOut->cno_stock_out) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                citem_type: type,
                nid_item: itemId,
                nqty: qty,
                ncost: cost
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Gagal menambahkan item.');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
                }
            }
        })
        .catch(err => {
            window.location.reload();
        });
    }

    // Stepper tombol +/- pada baris detail yang sudah tersimpan
    function stepDetailQty(itemId, delta) {
        const input = document.getElementById(`qty-input-${itemId}`);
        if (!input) return;
        let val = parseFloat(input.value) || 0;
        val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
        input.value = val;
        saveDetailQty(itemId);
    }

    // Update Qty via AJAX
    function saveDetailQty(itemId) {
        const input = document.getElementById(`qty-input-${itemId}`);
        if (!input) return;
        const qty = parseFloat(input.value) || 0;
        if (qty <= 0) {
            alert('Kuantitas harus lebih dari 0');
            return;
        }

        const stepperEl = input.closest('.qty-stepper');
        const cost = parseFloat(stepperEl?.getAttribute('data-cost') || 0);

        fetch(`{{ url('/inventory/stock-out') }}/{{ $stockOut->cno_stock_out }}/items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    nqty: qty
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const subtotalCell = document.getElementById(`subtotal-cell-${itemId}`);
                    if (subtotalCell) {
                        const subtotal = qty * cost;
                        subtotalCell.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
                    }
                    recalculatePageTotals();
                } else {
                    alert(data.message || 'Gagal memperbarui kuantitas.');
                }
            })
            .catch(err => {
                console.error('Error updating qty:', err);
            });
    }

    // Hitung ulang total footer tabel di client-side
    function recalculatePageTotals() {
        let totalQty = 0;
        let totalCost = 0;

        document.querySelectorAll('.detail-qty-input').forEach(input => {
            const q = parseFloat(input.value) || 0;
            const cost = parseFloat(input.closest('.qty-stepper')?.getAttribute('data-cost') || 0);
            totalQty += q;
            totalCost += (q * cost);
        });

        const totalQtyEl = document.getElementById('totalQtyDisplay');
        const totalCostEl = document.getElementById('totalCostDisplay');

        if (totalQtyEl) {
            totalQtyEl.textContent = Number.isInteger(totalQty) ? String(totalQty) : String(parseFloat(totalQty.toFixed(2)));
        }
        if (totalCostEl) {
            totalCostEl.textContent = 'Rp ' + Math.round(totalCost).toLocaleString('id-ID');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const body = document.getElementById('editStockOutItemsBody');
        const grandEl = document.getElementById('editStockOutGrandTotal');
        const sel = document.getElementById('editStockOutNewItemSelect');
        const qtyIn = document.getElementById('editStockOutNewQty');
        const costIn = document.getElementById('editStockOutNewCost');
        const addBtn = document.getElementById('btnAddEditStockOutRow');

        function fmtRp(v) {
            return 'Rp ' + Math.round(v || 0).toLocaleString('id-ID');
        }

        function recalcSo() {
            if (!body || !grandEl) return;
            let total = 0;
            body.querySelectorAll('.edit-so-row').forEach((row, idx) => {
                row.querySelectorAll('input[name^="items["]').forEach(inp => {
                    inp.name = inp.name.replace(/items\[\d+\]/, 'items[' + idx + ']');
                });
                const q = parseFloat(row.querySelector('.edit-so-qty')?.value || 0);
                const c = parseFloat(row.querySelector('.edit-so-cost')?.value || 0);
                const sub = q * c;
                total += sub;
                const subEl = row.querySelector('.edit-so-subtotal');
                if (subEl) subEl.textContent = fmtRp(sub);
            });
            grandEl.textContent = fmtRp(total);
        }

        if (body) {
            body.addEventListener('input', function(e) {
                if (e.target.classList.contains('edit-so-qty') || e.target.classList.contains('edit-so-cost')) {
                    recalcSo();
                }
            });
            body.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-so-row');
                if (!btn) return;
                if (body.querySelectorAll('.edit-so-row').length <= 1) {
                    alert('Minimal harus ada 1 item dalam dokumen.');
                    return;
                }
                btn.closest('.edit-so-row').remove();
                recalcSo();
            });
        }

        let soTs = null;
        if (sel && typeof TomSelect !== 'undefined') {
            soTs = new TomSelect(sel, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih Produk / Bahan Baku --',
                allowEmptyOption: true,
                onChange: function(val) {
                    if (val && costIn) {
                        const opt = sel.querySelector('option[value="' + CSS.escape(val) + '"]');
                        if (opt) costIn.value = parseFloat(opt.getAttribute('data-cost') || 0);
                    }
                }
            });
        } else if (sel) {
            sel.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.value && costIn) costIn.value = parseFloat(opt.getAttribute('data-cost') || 0);
            });
        }

        if (addBtn && body && sel) {
            addBtn.addEventListener('click', function() {
                const val = soTs ? soTs.getValue() : sel.value;
                const opt = val ? sel.querySelector('option[value="' + CSS.escape(val) + '"]') : null;
                if (!opt || !opt.value) {
                    alert('Pilih item terlebih dahulu.');
                    return;
                }
                const kind = opt.getAttribute('data-kind');
                const id = opt.getAttribute('data-id');
                const type = opt.getAttribute('data-type') || 'PRODUK';
                const name = opt.getAttribute('data-name') || 'Item';
                const unit = opt.getAttribute('data-unit') || 'PCS';
                const stock = opt.getAttribute('data-stock') || '0';
                const q = Math.max(0.01, parseFloat(qtyIn?.value || '1'));
                const c = Math.max(0, parseFloat(costIn?.value || '0'));
                const idx = body.querySelectorAll('.edit-so-row').length;
                const typeLabel = type === 'BAHAN_BAKU' ? 'Bahan Baku' : (type === 'SETENGAH_JADI' ? 'Setengah Jadi' : 'Produk');

                const tr = document.createElement('tr');
                tr.className = 'edit-so-row';
                tr.innerHTML = `
                    <td>
                        <div class="fw-semibold text-dark">${name}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Satuan: ${unit}</div>
                        <input type="hidden" name="items[${idx}][citem_type]" value="${type}">
                        <input type="hidden" name="items[${idx}][nid_product]" value="${kind === 'product' ? id : ''}">
                        <input type="hidden" name="items[${idx}][nid_ingredient]" value="${kind === 'ingredient' ? id : ''}">
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border">${typeLabel}</span></td>
                    <td class="text-center"><span class="badge bg-info-subtle text-info-emphasis border">${stock} ${unit}</span></td>
                    <td><input type="number" name="items[${idx}][nqty]" class="form-control form-control-sm edit-so-qty" min="0.01" step="any" value="${q}" required></td>
                    <td><input type="number" name="items[${idx}][ncost]" class="form-control form-control-sm edit-so-cost" min="0" step="any" value="${c}" required></td>
                    <td class="text-end fw-bold edit-so-subtotal">${fmtRp(q * c)}</td>
                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-so-row" title="Hapus Baris"><i class="bi bi-trash"></i></button></td>
                `;
                body.appendChild(tr);
                recalcSo();
                if (soTs) soTs.clear(true); else sel.value = '';
                if (qtyIn) qtyIn.value = '1';
                if (costIn) costIn.value = '0';
            });
        }
    });
</script>
@endsection