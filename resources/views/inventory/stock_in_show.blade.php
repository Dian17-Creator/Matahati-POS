@extends('layouts.app')

@section('title', 'Inventori / Detail Stok Masuk')
@section('custom_alerts', '1')

@section('sub_header')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="catalog-nav-tab active">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="catalog-nav-tab">
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

    #stockInDetailsTable {
        table-layout: fixed !important;
        width: 100% !important;
    }

    #stockInDetailsTable th {
        font-size: 0.73rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.4px !important;
        color: #475569 !important;
        padding: 9px 6px !important;
        vertical-align: middle !important;
    }

    #stockInDetailsTable td {
        padding: 6px 6px !important;
        vertical-align: middle !important;
        font-size: 0.81rem !important;
    }

    #stockInDetailsTable .new-row-type {
        font-size: 0.78rem !important;
        height: 31px !important;
        padding: 2px 20px 2px 7px !important;
        background-position: right 6px center !important;
        background-size: 10px 10px !important;
    }

    #stockInDetailsTable .ts-control {
        font-size: 0.79rem !important;
        min-height: 31px !important;
        height: 31px !important;
        padding: 2px 8px !important;
    }

    #stockInDetailsTable .ts-dropdown {
        font-size: 0.79rem !important;
    }

    #stockInDetailsTable .new-row-cost-input,
    #stockInDetailsTable .detail-cost-input {
        font-size: 0.79rem !important;
        height: 31px !important;
        padding: 2px 6px !important;
    }

    #stockInDetailsTable .input-group-text {
        font-size: 0.74rem !important;
        padding: 2px 6px !important;
        height: 31px !important;
    }

    #stockInDetailsTable .qty-stepper {
        height: 31px !important;
        max-width: 105px !important;
    }

    #stockInDetailsTable .new-row-stock-badge {
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

    <!-- Alert Status Dokumen -->
    <div class="alert alert-light detail-card shadow-sm d-flex align-items-center mb-3 py-3 px-4 rounded-3 text-secondary" style="background-color: #f8fafc;">
        <i class="bi bi-exclamation-circle text-muted fs-5 me-3"></i>
        <div class="small">
            @if($stockIn->isDraft)
            <span><strong>Status Draf:</strong> Pastikan seluruh data, kuantitas, dan harga beli barang sudah benar sebelum diposting. Selama status masih <strong>Draf</strong>, stok fisik barang belum ditambahkan ke outlet penerima. Anda dapat menambah, mengubah, atau menghapus item sebelum menekan <strong>Posting Sekarang</strong>.</span>
            @elseif($stockIn->isPosted)
            <span><strong>Status Posted:</strong> Dokumen transaksi ini telah <strong>Diposting</strong>. Stok fisik barang telah ditambahkan secara otomatis ke outlet penerima dan tercatat pada laporan pergerakan stok.</span>
            @else
            <span><strong>Status Cancelled:</strong> Dokumen transaksi Stok Masuk ini telah <strong>Dibatalkan</strong> dan tidak menambah stok fisik.</span>
            @endif
        </div>
    </div>

    <!-- Banner Utama: Status & No Stok Masuk -->
    <div class="card detail-card shadow-sm mb-4 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    @if($stockIn->isPosted)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-check2-circle me-1"></i> Posted
                    </span>
                    @elseif($stockIn->isDraft)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-file-earmark me-1"></i> Draft
                    </span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-x-circle me-1"></i> Cancelled
                    </span>
                    @endif

                    <div>
                        <div class="text-muted small">No. Stok Masuk</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stockIn->cno_stock_in }}</h4>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-shop me-1"></i> {{ $stockIn->outlet?->cname ?: 'Outlet #' . $stockIn->nid_outlet }}
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="{{ route('inventory.stock-in.print', $stockIn->cno_stock_in) }}" target="_blank" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </a>

                    @if($stockIn->isDraft)
                    <form action="{{ route('inventory.stock-in.cancel', $stockIn->cno_stock_in) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan draf Stok Masuk ini?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm px-3 shadow-sm">
                            <i class="bi bi-x-lg me-1"></i> Batalkan
                        </button>
                    </form>
                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPostingConfirm">
                        <i class="bi bi-check2-circle me-1"></i> Posting Sekarang
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Kotak Info Atas (Tanggal, Diterima Dari, Catatan, Alasan) -->
    <div class="row g-3 mb-4">
        <!-- Kotak 1: Tanggal & User -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3 position-relative">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small fw-semibold">Tanggal Masuk</div>
                        @if($stockIn->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark">{{ $stockIn->dstock_in ? \Carbon\Carbon::parse($stockIn->dstock_in)->format('d-M-Y') : '-' }}</div>
                    <div class="text-muted small text-truncate" title="{{ $stockIn->creceived_by }}">{{ $stockIn->creceived_by }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 2: Diterima Dari / Outlet Penerima -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold mb-1">Diterima Dari</div>
                    @if(!empty($stockIn->targetOutlet))
                    <div class="fw-bold text-primary"><i class="bi bi-shop me-1"></i>{{ $stockIn->targetOutlet->cname }}</div>
                    <div class="text-muted small">Transfer Antar Outlet</div>
                    @elseif(!empty($stockIn->supplier))
                    <div class="fw-bold text-dark">{{ $stockIn->supplier->cname }}</div>
                    <div class="text-muted small">Supplier</div>
                    @else
                    <div class="fw-bold text-dark">{{ $stockIn->outlet?->cname ?: '-' }}</div>
                    <div class="text-muted small">Input Stok Masuk Manual</div>
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
                        @if($stockIn->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark text-truncate" title="{{ $stockIn->cnote }}">{{ $stockIn->cnote ?: '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 4: Alasan -->
        <div class="col-md-3">
            <div class="card detail-card shadow-sm h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small fw-semibold">Alasan Penerimaan</div>
                        @if($stockIn->isDraft)
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                        @endif
                    </div>
                    <div class="fw-bold text-dark">{{ $stockIn->creason ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Tabel Item Barang Masuk -->
    <div class="card detail-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-white">
                Item Barang Masuk (<span id="totalDetailItemCount">{{ $stockIn->details->count() }}</span>)
            </h6>
            @if($stockIn->isDraft)
            <button type="button" class="btn btn-light btn-sm fw-semibold shadow-sm text-primary" onclick="addNewDetailRow()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Item
            </button>
            @endif
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-hover align-middle mb-0" id="stockInDetailsTable" style="table-layout: fixed; width: 100%;">
                <thead class="bg-light">
                    @if($stockIn->isDraft)
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 4%;" class="text-center py-2.5 text-nowrap">No</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Tipe</th>
                        <th style="width: 29%;" class="py-2.5 text-nowrap ps-2">Produk / Bahan Baku</th>
                        <th style="width: 10%;" class="text-center py-2.5 text-nowrap">Stok Saat Ini</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Qty Masuk</th>
                        <th style="width: 14%;" class="text-end py-2.5 text-nowrap pe-3">Harga Beli</th>
                        <th style="width: 12%;" class="text-end py-2.5 text-nowrap pe-3">Subtotal</th>
                        <th style="width: 7%;" class="text-center py-2.5 text-nowrap">Aksi</th>
                    </tr>
                    @else
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 4%;" class="text-center py-2.5 text-nowrap">No</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Tipe</th>
                        <th style="width: 35%;" class="py-2.5 text-nowrap ps-2">Produk / Bahan Baku</th>
                        <th style="width: 11%;" class="text-center py-2.5 text-nowrap">Stok Saat Ini</th>
                        <th style="width: 12%;" class="text-center py-2.5 text-nowrap">Qty Masuk</th>
                        <th style="width: 13%;" class="text-end py-2.5 text-nowrap pe-3">Harga Beli</th>
                        <th style="width: 13%;" class="text-end py-2.5 text-nowrap pe-3">Subtotal</th>
                    </tr>
                    @endif
                </thead>
                <tbody class="small" id="stockInDetailsTableBody">
                    @php $totalDetailQty = 0; @endphp
                    @forelse($stockIn->details as $idx => $detail)
                    @php
                    $isIng = $detail->isIngredient();
                    $normType = $detail->getItemTypeNormalized();
                    $itemName = $detail->product?->cname ?: ($isIng ? ($detail->ingredient?->cname ?: 'Bahan Baku') : 'Produk');
                    $itemCategory = $detail->product?->category?->cname ?: $detail->getItemTypeLabel();
                    $satuan = strtolower($detail->getItemUnit());
                    $currentStock = $detail->current_stock ?? ($isIng ? ($detail->ingredient?->nstock ?? 0) : ($detail->product?->nqty ?? 0));
                    $totalDetailQty += (float) $detail->nqty;
                    @endphp
                    <tr id="row-item-{{ $detail->nid }}" data-item-id="{{ $detail->nid }}">
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
                                    @if(!empty($detail->cserial_no))
                                    <div class="text-muted small mt-1">
                                        <span class="font-monospace text-dark">{{ $detail->cserial_no }}</span>
                                    </div>
                                    @endif
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
                            @if($stockIn->isDraft)
                            <div class="d-inline-flex justify-content-center">
                                <div class="qty-stepper mx-auto" data-item-id="{{ $detail->nid }}">
                                    <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="stepDetailQty('{{ $detail->nid }}', -1)" title="Kurangi">−</button>
                                    <input type="number"
                                        class="qty-stepper-input detail-qty-input"
                                        id="qty-input-{{ $detail->nid }}"
                                        value="{{ rtrim(rtrim(number_format((float) $detail->nqty, 2, '.', ''), '0'), '.') }}"
                                        min="0.01"
                                        step="any"
                                        onchange="saveDetailRow('{{ $detail->nid }}')">
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
                            @if($stockIn->isDraft)
                            <div class="input-group input-group-sm ms-auto" style="max-width: 130px;">
                                <span class="input-group-text bg-light text-muted">Rp</span>
                                <input type="number"
                                    step="any"
                                    min="0"
                                    class="form-control form-control-sm text-end detail-cost-input"
                                    id="cost-input-{{ $detail->nid }}"
                                    value="{{ rtrim(rtrim(number_format((float) $detail->ncost, 2, '.', ''), '0'), '.') ?: 0 }}"
                                    onchange="saveDetailRow('{{ $detail->nid }}')">
                            </div>
                            @else
                            Rp {{ number_format((float) $detail->ncost, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-end text-dark fw-bold pe-3" id="subtotal-cell-{{ $detail->nid }}">
                            Rp {{ number_format((float) $detail->nsubtotal, 0, ',', '.') }}
                        </td>
                        @if($stockIn->isDraft)
                        <td class="text-center">
                            <form action="{{ route('inventory.stock-in.items.destroy', [$stockIn->cno_stock_in, $detail->nid]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari dokumen draf?');" class="d-inline">
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
                        <td colspan="{{ $stockIn->isDraft ? '8' : '7' }}" class="text-center py-4 text-muted">
                            Belum ada item detail pada dokumen stok masuk ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($stockIn->details->isNotEmpty())
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end py-2.5 pe-3">Jumlah:</td>
                        <td class="text-center text-primary py-2.5 fs-6" id="totalQtyDisplay">
                            {{ rtrim(rtrim(number_format($totalDetailQty, 2, '.', ''), '0'), '.') }}
                        </td>
                        <td class="text-end py-2.5 text-secondary pe-2">Total Nilai:</td>
                        <td class="text-end text-success py-2.5 fs-6 pe-3" id="totalCostDisplay">
                            Rp {{ number_format((float) $stockIn->ntotal_cost, 0, ',', '.') }}
                        </td>
                        @if($stockIn->isDraft)
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ==================== MODAL KONFIRMASI POSTING ==================== --}}
@if($stockIn->isDraft)
<div class="modal fade" id="modalPostingConfirm" tabindex="-1" aria-labelledby="modalPostingConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-in.post', $stockIn->cno_stock_in) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalPostingConfirmLabel">
                        <i class="bi bi-check2-circle me-2"></i> Konfirmasi Posting Stok Masuk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        Anda akan memposting dokumen <strong>{{ $stockIn->cno_stock_in }}</strong>.
                    </p>
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Penting:</strong> Setelah diposting:
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Stok fisik produk / bahan baku di <strong>{{ $stockIn->outlet?->cname ?: 'Outlet Penerima' }}</strong> akan langsung bertambah.</li>
                            <li>Data mutasi akan dicatat resmi pada Pergerakan Stok.</li>
                            <li>Dokumen terkunci dan tidak dapat diubah lagi.</li>
                        </ul>
                    </div>
                    <div class="bg-light p-3 rounded-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Item Barang:</span>
                            <span class="fw-bold">{{ $stockIn->details->count() }} item</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Kuantitas:</span>
                            <span class="fw-bold text-dark"><span id="modalConfirmQty">{{ rtrim(rtrim(number_format($totalDetailQty, 2, '.', ''), '0'), '.') }}</span> unit</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Nilai Masuk:</span>
                            <span class="fw-bold text-success" id="modalConfirmCost">Rp {{ number_format((float) $stockIn->ntotal_cost, 0, ',', '.') }}</span>
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
            <form action="{{ route('inventory.stock-in.update', $stockIn->cno_stock_in) }}" method="POST">
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
                        <label class="form-label small fw-semibold text-secondary">Tanggal Masuk <span class="text-danger">*</span></label>
                        <input type="date" name="dstock_in" class="form-control" value="{{ $stockIn->dstock_in ? \Carbon\Carbon::parse($stockIn->dstock_in)->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alasan Penerimaan</label>
                        <select name="creason" class="form-select">
                            <option value="Koreksi Selisih Stok (Adjustment)" {{ $stockIn->creason === 'Koreksi Selisih Stok (Adjustment)' ? 'selected' : '' }}>Koreksi Selisih Stok (Adjustment)</option>
                            <option value="Saldo Awal" {{ $stockIn->creason === 'Saldo Awal' ? 'selected' : '' }}>Saldo Awal</option>
                            <option value="Temuan Barang Fisik" {{ $stockIn->creason === 'Temuan Barang Fisik' ? 'selected' : '' }}>Temuan Barang Fisik</option>
                            <option value="Bonus / Hadiah Supplier" {{ $stockIn->creason === 'Bonus / Hadiah Supplier' ? 'selected' : '' }}>Bonus / Hadiah Supplier</option>
                            <option value="Pengembalian Manual" {{ $stockIn->creason === 'Pengembalian Manual' ? 'selected' : '' }}>Pengembalian Manual</option>
                            <option value="Lain-lain" {{ $stockIn->creason === 'Lain-lain' ? 'selected' : '' }}>Lain-lain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan</label>
                        <textarea name="cnote" class="form-control" rows="3" placeholder="Tuliskan catatan penerimaan...">{{ $stockIn->cnote }}</textarea>
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

<script type="application/json" id="stockInDraftProductsData">
    @json($availableProducts ?? [])
</script>
<script>
    const draftProducts = JSON.parse(document.getElementById('stockInDraftProductsData')?.textContent || '[]');
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

    function addNewDetailRow() {
        newRowCounter++;
        const curIdx = newRowCounter;
        const tbody = document.getElementById('stockInDetailsTableBody');
        if (!tbody) return;

        const existingRows = tbody.querySelectorAll('tr:not(#empty-row)').length;
        const nextNo = existingRows + 1;

        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const tr = document.createElement('tr');
        tr.id = `new-detail-row-${curIdx}`;
        tr.className = 'align-middle';
        tr.style.backgroundColor = '#f0fdf4';

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
                <div class="input-group input-group-sm ms-auto" style="max-width: 130px;">
                    <span class="input-group-text bg-light text-muted">Rp</span>
                    <input type="number" step="any" min="0" class="form-control form-control-sm text-end new-row-cost-input" id="new_cost_${curIdx}" value="0" oninput="calcNewRowSubtotal(${curIdx})">
                </div>
            </td>
            <td class="text-end fw-semibold text-success align-middle pe-3 new-row-subtotal-display" id="new_subtotal_${curIdx}">
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

        fetch('{{ route("inventory.stock-in.items.store", $stockIn->cno_stock_in) }}', {
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
        .catch(() => {
            window.location.reload();
        });
    }

    function stepDetailQty(itemId, delta) {
        const input = document.getElementById(`qty-input-${itemId}`);
        if (!input) return;
        let val = parseFloat(input.value) || 0;
        val = Math.max(0.01, parseFloat((val + delta).toFixed(2)));
        input.value = val;
        saveDetailRow(itemId);
    }

    function saveDetailRow(itemId) {
        const qtyInput = document.getElementById(`qty-input-${itemId}`);
        const costInput = document.getElementById(`cost-input-${itemId}`);
        if (!qtyInput) return;

        const qty = parseFloat(qtyInput.value) || 0;
        if (qty <= 0) {
            alert('Kuantitas harus lebih dari 0');
            return;
        }
        const cost = Math.max(0, parseFloat(costInput ? costInput.value : 0) || 0);

        fetch(`{{ url('/inventory/stock-in') }}/{{ $stockIn->cno_stock_in }}/items/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                nqty: qty,
                ncost: cost
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
                alert(data.message || 'Gagal memperbarui item.');
            }
        })
        .catch(err => {
            console.error('Error updating item:', err);
        });
    }

    function recalculatePageTotals() {
        let totalQty = 0;
        let totalCost = 0;

        document.querySelectorAll('#stockInDetailsTableBody tr[data-item-id]').forEach(row => {
            const itemId = row.getAttribute('data-item-id');
            const q = parseFloat(document.getElementById(`qty-input-${itemId}`)?.value || 0);
            const c = parseFloat(document.getElementById(`cost-input-${itemId}`)?.value || 0);
            totalQty += q;
            totalCost += (q * c);
        });

        const formattedQty = Number.isInteger(totalQty) ? String(totalQty) : String(parseFloat(totalQty.toFixed(2)));
        const formattedCost = 'Rp ' + Math.round(totalCost).toLocaleString('id-ID');

        const totalQtyEl = document.getElementById('totalQtyDisplay');
        const totalCostEl = document.getElementById('totalCostDisplay');
        const modalConfirmQtyEl = document.getElementById('modalConfirmQty');
        const modalConfirmCostEl = document.getElementById('modalConfirmCost');

        if (totalQtyEl) totalQtyEl.textContent = formattedQty;
        if (totalCostEl) totalCostEl.textContent = formattedCost;
        if (modalConfirmQtyEl) modalConfirmQtyEl.textContent = formattedQty;
        if (modalConfirmCostEl) modalConfirmCostEl.textContent = formattedCost;
    }
</script>
@endsection
