@extends('layouts.app')

@section('title', 'Detail Pembelian - ' . $purchase->cno_purchase)
@section('custom_alerts', '1')

@section('sub_header')
@php
    if ($purchase->cstatus === 'CANCELLED') {
        $purchaseDetailTab = 'cancelled';
    } elseif ($purchase->cstatus === 'RECEIVED' && $purchase->cpayment_status === 'PAID') {
        $purchaseDetailTab = 'received';
    } else {
        $purchaseDetailTab = 'pending';
    }
@endphp
@include('purchases.partials.purchase_tabs', ['activeTab' => $purchaseDetailTab])
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .purchase-status-bar {
        border-radius: 8px 8px 0 0;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .purchase-status-bar.unpaid {
        background-color: #ef4444;
        color: white;
    }

    .purchase-status-bar.paid {
        background-color: #10b981;
        color: white;
    }

    .detail-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .detail-card-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 0.2px;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .kv-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 5px 0;
        font-size: 0.85rem;
    }

    .kv-label {
        color: #64748b;
        flex-shrink: 0;
    }

    .kv-value {
        color: #1e293b;
        font-weight: 500;
        text-align: right;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}" class="text-decoration-none">Daftar Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $purchase->cno_purchase }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $purchase->cno_purchase }}</h4>
            @if($purchase->cstatus === 'RECEIVED')
            <span class="badge bg-success px-2 py-1"><i class="bi bi-check2-circle me-1"></i> Telah Diterima</span>
            @elseif($purchase->cstatus === 'CANCELLED')
            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i> Dibatalkan</span>
            @else
            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock me-1"></i> Butuh Diproses (Pending)</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('purchases.print', $purchase->nid) }}" target="_blank" class="btn btn-outline-dark btn-sm px-3">
            <i class="bi bi-printer me-1"></i> Cetak PO
        </a>
        @if($canModifyData && $purchase->cstatus === 'PENDING')
        <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#receiveModal">
            <i class="bi bi-box-seam me-1"></i> Konfirmasi Barang Diterima
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-lg me-1"></i> Batalkan PO
        </button>
        @elseif(($editEligibility['showButton'] ?? false) && $purchase->cstatus === 'RECEIVED')
            @if($editEligibility['canEdit'])
            <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#editReceivedModal">
                <i class="bi bi-pencil-square me-1"></i> Edit Transaksi (SPV)
            </button>
            @else
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" disabled title="{{ $editEligibility['reason'] }}">
                <i class="bi bi-lock me-1"></i> Edit Terkunci
            </button>
            @endif
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(($editEligibility['showButton'] ?? false) && !$editEligibility['canEdit'] && !empty($editEligibility['reason']))
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3 small" role="alert">
    <i class="bi bi-lock-fill"></i>
    <span>{{ $editEligibility['reason'] }}</span>
</div>
@endif

<!-- Top Status Header (Mirip Olsera) -->
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="purchase-status-bar {{ $purchase->cpayment_status === 'PAID' ? 'paid' : 'unpaid' }} d-flex justify-content-between align-items-center">
        <div>
            @if($purchase->cpayment_status === 'PAID')
            <i class="bi bi-check-circle-fill me-1"></i> Telah Dibayar (Lunas)
            @else
            <i class="bi bi-exclamation-circle-fill me-1"></i> Belum Bayar
            @endif
        </div>
        @if($canModifyData)
        <div>
            <button type="button" class="btn btn-sm {{ $purchase->cpayment_status === 'PAID' ? 'btn-light text-success' : 'btn-light text-danger' }} py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#paymentModal" style="font-size: 0.78rem;">
                <i class="bi bi-cash-stack me-1"></i> Ubah Status Bayar
            </button>
        </div>
        @endif
    </div>
    <div class="card-body bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-receipt-cutoff fs-4 text-primary"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark">{{ $purchase->cno_purchase }}</div>
                <div class="text-muted small">Dibuat oleh: {{ $purchase->user?->cname ?? 'Admin' }} pada {{ \Carbon\Carbon::parse($purchase->dpurchase)->format('d M Y') }}</div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small">Outlet Tujuan</div>
            <div class="fw-bold text-dark fs-6">{{ $purchase->outlet?->cname ?? '-' }}</div>
        </div>
    </div>
</div>

<!-- 4 Cards Grid (Supplier, Info Penerimaan, Catatan, Jatuh Tempo) -->
<div class="row g-3 mb-4">
    <!-- Card Supplier -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-building me-1 text-primary"></i> Supplier</span>
                @if($purchase->supplier)
                <a href="{{ route('suppliers.show', $purchase->supplier->nid) }}" class="btn btn-link btn-sm p-0 text-decoration-none fw-normal" style="font-size: 0.75rem;">
                    Rincian <i class="bi bi-arrow-right"></i>
                </a>
                @endif
            </div>
            @if($purchase->supplier)
            <div class="kv-row">
                <span class="kv-label">Nama Supplier</span>
                <span class="kv-value fw-semibold">{{ $purchase->supplier->cname }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">PIC / Kontak</span>
                <span class="kv-value">{{ $purchase->supplier->ccontact_person ?: '-' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Telepon & Email</span>
                <span class="kv-value">
                    {{ $purchase->supplier->cphone ?: '-' }}
                    @if($purchase->supplier->cemail)
                    <span class="text-muted fw-normal">({{ $purchase->supplier->cemail }})</span>
                    @endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Alamat</span>
                <span class="kv-value text-muted fw-normal">{{ $purchase->supplier->caddress ?: '-' }}</span>
            </div>
            @else
            <div class="text-muted small">Supplier tidak ditemukan.</div>
            @endif
        </div>
    </div>

    <!-- Card Info Penerimaan -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-box-seam me-1 text-success"></i> Info Penerimaan</span>
            </div>

            <div class="kv-row">
                <span class="kv-label">Tanggal Diterima</span>
                <span class="kv-value">
                    @if($purchase->dreceived)
                    <span class="text-success fw-bold">{{ \Carbon\Carbon::parse($purchase->dreceived)->format('d F Y') }}</span>
                    @else
                    <span class="text-muted fst-italic fw-normal">Belum diterima</span>
                    @endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">No. Terima / Surat Jalan</span>
                <span class="kv-value">{{ $purchase->cno_reference ?: '-' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Penerima Barang</span>
                <span class="kv-value">{{ $purchase->creceived_by ?: '-' }}</span>
            </div>

            @if($purchase->cstatus === 'RECEIVED')
            <div class="alert alert-success p-2 small mb-0 mt-3">
                <i class="bi bi-check-circle me-1"></i> Stok produk telah ditambahkan ke sistem.
            </div>
            @endif
        </div>
    </div>

    <!-- Card Catatan & Jatuh Tempo -->
    <div class="col-md-4">
        <div class="d-flex flex-column gap-3 h-100">
            <div class="detail-card flex-fill">
                <div class="detail-card-title">
                    <span><i class="bi bi-card-text me-1 text-secondary"></i> Catatan</span>
                </div>
                <div class="text-muted small pt-1" style="white-space: pre-line;">{{ $purchase->cnote ?: 'Tidak ada catatan khusus.' }}</div>
            </div>

            <div class="detail-card">
                <div class="detail-card-title">
                    <span><i class="bi bi-calendar-event me-1 text-warning"></i> Jatuh Tempo</span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Tanggal Jatuh Tempo</span>
                    <span class="kv-value">
                        @if($purchase->ddue_date)
                        {{ \Carbon\Carbon::parse($purchase->ddue_date)->format('d F Y') }}
                        @else
                        <span class="text-muted fw-normal">Tidak ditentukan</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Item Pesanan -->
<div class="card mb-4 overflow-hidden" style="border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold">Daftar Item Pesanan</h6>
            <span class="badge bg-light text-dark border">{{ $purchase->details->count() }} Item</span>
        </div>
        @if($canModifyData && $purchase->cstatus === 'PENDING')
        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item
        </button>
        @endif
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <th class="py-3 ps-3" style="width: 50px;">NO.</th>
                        <th class="py-3">ITEM (PRODUK / BAHAN BAKU)</th>
                        <th class="py-3 text-center">TIPE</th>
                        <th class="py-3 text-center">STOK SAAT INI</th>
                        <th class="py-3 text-center">KUANTITAS BELI</th>
                        <th class="py-3 text-end">HARGA BELI SATUAN</th>
                        <th class="py-3 text-end">TOTAL HARGA</th>
                        @if($canModifyData && $purchase->cstatus === 'PENDING')
                        <th class="py-3 text-center" style="width: 80px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($purchase->details as $index => $item)
                    @php
                        $prodModel = $item->product;
                        $ingModel = $item->ingredient;
                        $itemUnit = $item->getItemUnit();
                        $itemTypeLabel = $item->getItemTypeLabel();
                        $normType = $item->getItemTypeNormalized();
                        $currentStock = $prodModel ? ($prodModel->nqty ?? 0) : ($ingModel ? (float) ($ingModel->nstock ?? 0) : 0);
                        $currentStockFormatted = rtrim(rtrim(number_format((float) $currentStock, 2, ',', '.'), '0'), ',');
                    @endphp
                    <tr class="align-middle" style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-3 text-muted text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($prodModel)
                                    @if(!empty($prodModel->cphotos))
                                    <img src="{{ asset($prodModel->cphotos) }}"
                                         alt="{{ $prodModel->cname }}"
                                         class="rounded border flex-shrink-0"
                                         style="width: 44px; height: 44px; object-fit: cover; background-color: #f8fafc;"
                                         onerror="this.onerror=null; this.src=''; this.classList.add('d-none'); if(this.nextElementSibling) this.nextElementSibling.classList.remove('d-none');">
                                    <div class="rounded border bg-light text-muted d-none align-items-center justify-content-center flex-shrink-0"
                                         style="width: 44px; height: 44px; font-size: 1.1rem;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                    @else
                                    <div class="rounded border bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width: 44px; height: 44px; font-size: 1.1rem;">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark">{{ $prodModel->cname }}</div>
                                        <div class="text-muted small">{{ $prodModel->category?->cname ? $prodModel->category->cname . ' • ' : '' }}Satuan: {{ $itemUnit }}</div>
                                    </div>
                                @elseif($ingModel)
                                    <div class="rounded border bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width: 44px; height: 44px; font-size: 1.15rem;">
                                        <i class="bi bi-basket2-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $ingModel->cname }}</div>
                                        <div class="text-muted small">Satuan: {{ $itemUnit }}</div>
                                    </div>
                                @else
                                    <div class="rounded border bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width: 44px; height: 44px; font-size: 1.1rem;">
                                        <i class="bi bi-question-circle"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-muted">Item dihapus</div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            @if($normType === 'BAHAN_BAKU')
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Bahan Baku</span>
                            @elseif($normType === 'SETENGAH_JADI')
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Setengah Jadi</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Produk</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                {{ $currentStockFormatted }} {{ $itemUnit }}
                            </span>
                        </td>
                        <td class="text-center fw-bold fs-6 text-primary">
                            +{{ $item->nqty }} <span class="fw-normal small text-muted">{{ $itemUnit }}</span>
                        </td>
                        <td class="text-end">
                            Rp {{ rtrim(rtrim(number_format((float) $item->ncost, 2, ',', '.'), '0'), ',') }}
                        </td>
                        <td class="text-end fw-bold text-dark">
                            Rp {{ rtrim(rtrim(number_format((float) $item->nsubtotal, 2, ',', '.'), '0'), ',') }}
                        </td>
                        @if($canModifyData && $purchase->cstatus === 'PENDING')
                        <td class="text-center">
                            <form action="{{ route('purchases.remove-item', [$purchase->nid, $item->nid]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari pesanan?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Hapus Item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canModifyData && $purchase->cstatus === 'PENDING' ? 8 : 7 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-box-arrow-in-down fs-2 d-block text-secondary opacity-50 mb-2"></i>
                            Belum ada item dalam pesanan pembelian ini.
                            @if($canModifyData && $purchase->cstatus === 'PENDING')
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Item Sekarang
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($purchase->details->isNotEmpty())
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="{{ $canModifyData && $purchase->cstatus === 'PENDING' ? 6 : 5 }}" class="text-end fw-bold py-3">
                            GRAND TOTAL PEMBELIAN:
                        </td>
                        <td class="text-end fw-bold fs-5 text-primary py-3">
                            Rp {{ rtrim(rtrim(number_format((float) $purchase->ntotal_amount, 2, ',', '.'), '0'), ',') }}
                        </td>
                        @if($canModifyData && $purchase->cstatus === 'PENDING')
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Section Riwayat Revisi Transaksi -->
@if($purchase->dedited_at)
<div class="card mb-4 overflow-hidden" style="border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);">
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
                <div class="fw-semibold text-dark">{{ $purchase->cedited_by ?: 'SPV' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted mb-1">Waktu Revisi</div>
                <div class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($purchase->dedited_at)->format('d M Y, H:i') }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted mb-1">Alasan Revisi</div>
                <div class="text-dark" style="white-space: pre-line;">{{ $purchase->cedit_reason ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal Tambah Item (Produk / Bahan Baku) -->
@if($canModifyData && $purchase->cstatus === 'PENDING')
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.add-item', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addProductModalLabel">Tambah Item ke Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tipe Item <span class="text-danger">*</span></label>
                        <select name="citem_type" id="add_item_type_select" class="form-select">
                            <option value="PRODUK" {{ old('citem_type', 'PRODUK') === 'PRODUK' ? 'selected' : '' }}>Produk (Barang Jadi)</option>
                            <option value="SETENGAH_JADI" {{ old('citem_type') === 'SETENGAH_JADI' ? 'selected' : '' }}>Setengah Jadi</option>
                            <option value="BAHAN_BAKU" {{ old('citem_type') === 'BAHAN_BAKU' ? 'selected' : '' }}>Bahan Baku</option>
                        </select>
                    </div>

                    <div class="mb-3" id="add_product_select_group">
                        <label class="form-label fw-semibold small">Pilih Item <span class="text-danger">*</span></label>
                        <select name="nid_product" id="product_select" class="form-select" required>
                            <option value="">-- Cari atau pilih item --</option>
                            @foreach($products as $p)
                            @php
                                $pType = strtoupper(trim((string) ($p->cproduct_type ?? 'PRODUK')));
                                if ($pType === 'PRODUCT') $pType = 'PRODUK';
                                if ($pType === 'INGREDIENT') $pType = 'BAHAN_BAKU';
                                $pSatuan = $p->csatuan ?: 'PCS';
                            @endphp
                            <option value="{{ $p->nid }}"
                                data-type="{{ $pType }}"
                                data-price="{{ $p->nprice_buy ?? 0 }}"
                                data-stock="{{ $p->nqty ?? 0 }}"
                                data-unit="{{ $pSatuan }}">
                                {{ $p->cname }} (Stok saat ini: {{ $p->nqty ?? 0 }} {{ $pSatuan }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Qty (Kuantitas) <span class="text-danger">*</span></label>
                            <input type="number" name="nqty" id="input_qty" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Harga Beli Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="ncost" id="input_cost" class="form-control" min="0" step="any" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Total Harga Beli</label>
                        <div class="form-control bg-light fw-bold text-primary" id="display_subtotal">
                            Rp 0
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Konfirmasi Terima Barang -->
@if($canModifyData && $purchase->cstatus === 'PENDING')
<div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.receive', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="receiveModalLabel">
                        <i class="bi bi-box-seam me-1"></i> Konfirmasi Barang Diterima
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Perhatian:</strong> Mengonfirmasi penerimaan barang akan mengubah status PO menjadi <strong>Telah Diproses</strong> dan <strong>otomatis menambahkan stok</strong> Produk / Bahan Baku ke outlet <strong>{{ $purchase->outlet?->cname }}</strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tanggal Diterima <span class="text-danger">*</span></label>
                        <input type="date" name="dreceived" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">No. Terima / Surat Jalan</label>
                        <input type="text" name="cno_reference" class="form-control" placeholder="Contoh: SJ-2026/10/001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Penerima</label>
                        <input type="text" name="creceived_by" class="form-control" value="{{ Auth::user()?->cname ?: 'Admin' }}" placeholder="Nama petugas penerima">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-check-lg me-1"></i> Ya, Konfirmasi Terima Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Batalkan PO -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.cancel', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Batalkan Pesanan Pembelian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin membatalkan pesanan pembelian <strong>{{ $purchase->cno_purchase }}</strong>?
                    Tindakan ini akan membatalkan seluruh pesanan ini dan stok tidak akan bertambah.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Batalkan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Edit Transaksi Selesai (SPV) -->
@if(($editEligibility['canEdit'] ?? false) && $purchase->cstatus === 'RECEIVED')
<div class="modal fade" id="editReceivedModal" tabindex="-1" aria-labelledby="editReceivedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('purchases.update-received', $purchase->nid) }}" method="POST" id="editReceivedForm" class="d-flex flex-column overflow-hidden" style="max-height: calc(100vh - 2rem);">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning-subtle flex-shrink-0">
                    <h5 class="modal-title fw-bold text-dark" id="editReceivedModalLabel">
                        <i class="bi bi-pencil-square me-1"></i> Edit Transaksi Pembelian ({{ $purchase->cno_purchase }})
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body overflow-y-auto" style="max-height: 72vh;">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-shield-check me-1"></i>
                        <strong>Perhatian:</strong> Perubahan akan otomatis menyesuaikan stok saat ini. Pengurangan qty atau penghapusan item akan ditolak jika stok saat ini sudah terpakai/terjual.
                    </div>

                    @if($errors->any() && session('error_modal') === 'edit_received')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Edit / Revisi <span class="text-danger">*</span></label>
                        <textarea name="cedit_reason" class="form-control form-control-sm" rows="2" required minlength="5" maxlength="500" placeholder="Jelaskan alasan perubahan transaksi (minimal 5 karakter)...">{{ old('cedit_reason') }}</textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Supplier <span class="text-danger">*</span></label>
                            <select name="nid_supplier" class="form-select form-select-sm" required>
                                @foreach($suppliers as $sup)
                                <option value="{{ $sup->nid }}" {{ (int) old('nid_supplier', $purchase->nid_supplier) === (int) $sup->nid ? 'selected' : '' }}>
                                    {{ $sup->cname }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Tanggal Beli <span class="text-danger">*</span></label>
                            <input type="date" name="dpurchase" class="form-control form-control-sm" value="{{ old('dpurchase', $purchase->dpurchase ? \Carbon\Carbon::parse($purchase->dpurchase)->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Tanggal Diterima <span class="text-danger">*</span></label>
                            <input type="date" name="dreceived" class="form-control form-control-sm" value="{{ old('dreceived', $purchase->dreceived ? \Carbon\Carbon::parse($purchase->dreceived)->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Jatuh Tempo</label>
                            <input type="date" name="ddue_date" class="form-control form-control-sm" value="{{ old('ddue_date', $purchase->ddue_date ? \Carbon\Carbon::parse($purchase->ddue_date)->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">No. Surat Jalan</label>
                            <input type="text" name="cno_reference" class="form-control form-control-sm" value="{{ old('cno_reference', $purchase->cno_reference) }}" maxlength="100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan</label>
                        <input type="text" name="cnote" class="form-control form-control-sm" value="{{ old('cnote', $purchase->cnote) }}" maxlength="1000" placeholder="Catatan tambahan...">
                    </div>

                    <hr class="my-3">

                    <div class="card bg-light border mb-3 p-2">
                        <div class="small fw-semibold mb-2"><i class="bi bi-plus-circle me-1 text-primary"></i> Tambah Item Baru ke Pembelian</div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Pilih Item</label>
                                <select id="editNewItemSelect" class="form-select form-select-sm">
                                    <option value="">-- Cari atau pilih Produk / Bahan Baku --</option>
                                    @foreach($outletProducts as $p)
                                    @php
                                        $pType = strtoupper(trim((string) ($p->cproduct_type ?? 'PRODUK')));
                                        if ($pType === 'PRODUCT') $pType = 'PRODUK';
                                        if ($pType === 'INGREDIENT') $pType = 'BAHAN_BAKU';
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
                                <input type="number" id="editNewItemQty" class="form-control form-control-sm" min="1" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Harga Beli (Rp)</label>
                                <input type="number" id="editNewItemCost" class="form-control form-control-sm" min="0" step="any" value="0">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btnAddEditItemRow" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Daftar Item Pembelian</h6>
                        <span class="text-muted small">Minimal 1 item harus tersisa</span>
                    </div>

                    <div class="table-responsive border rounded mb-2">
                        <table class="table table-sm align-middle mb-0" id="editReceivedItemsTable">
                            <thead class="table-light small">
                                <tr>
                                    <th>ITEM</th>
                                    <th class="text-center">TIPE</th>
                                    <th class="text-center">STOK SEKARANG</th>
                                    <th style="width: 120px;">QTY BELI</th>
                                    <th style="width: 160px;">HARGA BELI (RP)</th>
                                    <th class="text-end" style="width: 150px;">SUBTOTAL</th>
                                    <th class="text-center" style="width: 60px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="small" id="editReceivedItemsBody">
                                @foreach($purchase->details as $idx => $detail)
                                @php
                                    $isIng = $detail->isIngredient();
                                    $itemName = $isIng ? ($detail->ingredient?->cname ?? 'Bahan Baku') : ($detail->product?->cname ?? 'Produk');
                                    $itemUnit = $detail->getItemUnit();
                                    $normType = $detail->getItemTypeNormalized();
                                    $currStock = $isIng ? (float) ($detail->ingredient?->nstock ?? 0) : (float) ($detail->product?->nqty ?? 0);
                                @endphp
                                <tr class="edit-item-row">
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
                                        <input type="number" name="items[{{ $idx }}][nqty]" class="form-control form-control-sm edit-qty-input" min="1" value="{{ (int) $detail->nqty }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $idx }}][ncost]" class="form-control form-control-sm edit-cost-input" min="0" step="any" value="{{ (float) $detail->ncost }}" required>
                                    </td>
                                    <td class="text-end fw-bold edit-row-subtotal">
                                        Rp {{ rtrim(rtrim(number_format((float) $detail->nsubtotal, 2, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-edit-row" title="Hapus Baris">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">GRAND TOTAL REVISI:</td>
                                    <td class="text-end fw-bold text-primary" id="editReceivedGrandTotal">
                                        Rp {{ rtrim(rtrim(number_format((float) $purchase->ntotal_amount, 2, ',', '.'), '0'), ',') }}
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

<!-- Modal Update Status Bayar -->
@if($canModifyData)
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.update-payment', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentModalLabel">Status Pembayaran PO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Status Pembayaran</label>
                        <select name="cpayment_status" id="cpayment_status_select" class="form-select" required>
                            <option value="UNPAID" {{ $purchase->cpayment_status === 'UNPAID' ? 'selected' : '' }}>Belum Bayar</option>
                            <option value="PAID" {{ $purchase->cpayment_status === 'PAID' ? 'selected' : '' }}>Lunas (Telah Dibayar)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="payment_date_group">
                        <label class="form-label fw-semibold small">Tanggal Pembayaran</label>
                        <input type="date" name="dpayment" class="form-control" value="{{ $purchase->dpayment ? \Carbon\Carbon::parse($purchase->dpayment)->format('Y-m-d') : date('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Status Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputQty = document.getElementById('input_qty');
        const inputCost = document.getElementById('input_cost');
        const displaySubtotal = document.getElementById('display_subtotal');
        const typeSelect = document.getElementById('add_item_type_select');
        const prodSelectEl = document.getElementById('product_select');

        let prodTomSelect = null;

        function calculateSubtotal() {
            if (inputQty && inputCost && displaySubtotal) {
                const qty = parseFloat(inputQty.value) || 0;
                const cost = parseFloat(inputCost.value) || 0;
                const subtotal = qty * cost;
                displaySubtotal.innerText = 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(subtotal);
            }
        }

        // Cache all product options for filtering
        const originalProductOptions = [];
        if (prodSelectEl) {
            Array.from(prodSelectEl.options).forEach(opt => {
                if (opt.value) {
                    originalProductOptions.push({
                        value: opt.value,
                        text: opt.text,
                        type: (opt.getAttribute('data-type') || 'PRODUK').toUpperCase(),
                        price: parseFloat(opt.getAttribute('data-price') || 0),
                        stock: opt.getAttribute('data-stock') || '0',
                        unit: opt.getAttribute('data-unit') || 'PCS'
                    });
                }
            });
        }

        function filterOptionsForType(targetType) {
            if (!prodTomSelect) return;
            const normType = (targetType || 'PRODUK').toUpperCase();
            prodTomSelect.clear(true);
            prodTomSelect.clearOptions();

            const filtered = originalProductOptions.filter(item => item.type === normType);
            filtered.forEach(item => {
                prodTomSelect.addOption({
                    value: item.value,
                    text: item.text,
                    price: item.price,
                    stock: item.stock,
                    unit: item.unit
                });
            });
            prodTomSelect.refreshOptions(false);
        }

        if (prodSelectEl && typeof TomSelect !== 'undefined') {
            prodTomSelect = new TomSelect(prodSelectEl, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih item --',
                allowEmptyOption: true,
                onChange: function(val) {
                    if (val && prodTomSelect.options[val] && inputCost) {
                        const optData = prodTomSelect.options[val];
                        inputCost.value = parseFloat(optData.price || 0);
                        calculateSubtotal();
                    }
                }
            });

            if (typeSelect) {
                filterOptionsForType(typeSelect.value);
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                if (inputCost) inputCost.value = '';
                filterOptionsForType(this.value);
                calculateSubtotal();
            });
        }

        if (inputQty && inputCost) {
            inputQty.addEventListener('input', calculateSubtotal);
            inputCost.addEventListener('input', calculateSubtotal);
        }

        const paymentSelect = document.getElementById('cpayment_status_select');
        const paymentDateGroup = document.getElementById('payment_date_group');
        if (paymentSelect && paymentDateGroup) {
            function togglePaymentDate() {
                if (paymentSelect.value === 'PAID') {
                    paymentDateGroup.style.display = 'block';
                } else {
                    paymentDateGroup.style.display = 'none';
                }
            }
            paymentSelect.addEventListener('change', togglePaymentDate);
            togglePaymentDate();
        }

        // --- Edit Transaksi Selesai (SPV) ---
        const editItemsBody = document.getElementById('editReceivedItemsBody');
        const editGrandTotalEl = document.getElementById('editReceivedGrandTotal');
        const editNewItemSelect = document.getElementById('editNewItemSelect');
        const editNewItemQty = document.getElementById('editNewItemQty');
        const editNewItemCost = document.getElementById('editNewItemCost');
        const btnAddEditItemRow = document.getElementById('btnAddEditItemRow');

        function formatRupiah(num) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num || 0);
        }

        function recalcEditReceivedTotals() {
            if (!editItemsBody || !editGrandTotalEl) return;
            let grand = 0;
            editItemsBody.querySelectorAll('.edit-item-row').forEach((row, index) => {
                // Re-index inputs so array indices stay contiguous
                row.querySelectorAll('input[name^="items["]').forEach(inp => {
                    inp.name = inp.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                });
                const q = parseFloat(row.querySelector('.edit-qty-input')?.value || 0);
                const c = parseFloat(row.querySelector('.edit-cost-input')?.value || 0);
                const sub = q * c;
                grand += sub;
                const subCell = row.querySelector('.edit-row-subtotal');
                if (subCell) subCell.innerText = formatRupiah(sub);
            });
            editGrandTotalEl.innerText = formatRupiah(grand);
        }

        if (editItemsBody) {
            editItemsBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('edit-qty-input') || e.target.classList.contains('edit-cost-input')) {
                    recalcEditReceivedTotals();
                }
            });

            editItemsBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-edit-row');
                if (!btn) return;
                const rows = editItemsBody.querySelectorAll('.edit-item-row');
                if (rows.length <= 1) {
                    alert('Minimal harus ada 1 item dalam pembelian.');
                    return;
                }
                btn.closest('.edit-item-row').remove();
                recalcEditReceivedTotals();
            });
        }

        let editNewItemTomSelect = null;
        if (editNewItemSelect && typeof TomSelect !== 'undefined') {
            editNewItemTomSelect = new TomSelect(editNewItemSelect, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih Produk / Bahan Baku --',
                allowEmptyOption: true,
                onChange: function(val) {
                    if (val && editNewItemCost) {
                        const opt = editNewItemSelect.querySelector('option[value="' + CSS.escape(val) + '"]');
                        if (opt) {
                            editNewItemCost.value = parseFloat(opt.getAttribute('data-cost') || 0);
                        }
                    }
                }
            });
        } else if (editNewItemSelect) {
            editNewItemSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.value && editNewItemCost) {
                    editNewItemCost.value = parseFloat(opt.getAttribute('data-cost') || 0);
                }
            });
        }

        if (btnAddEditItemRow && editItemsBody && editNewItemSelect) {
            btnAddEditItemRow.addEventListener('click', function() {
                const selectedVal = editNewItemTomSelect ? editNewItemTomSelect.getValue() : editNewItemSelect.value;
                const opt = selectedVal
                    ? editNewItemSelect.querySelector('option[value="' + CSS.escape(selectedVal) + '"]')
                    : null;
                if (!opt || !opt.value) {
                    alert('Pilih item terlebih dahulu.');
                    return;
                }
                const kind = opt.getAttribute('data-kind');
                const itemId = opt.getAttribute('data-id');
                const itemType = opt.getAttribute('data-type') || 'PRODUK';
                const itemName = opt.getAttribute('data-name') || 'Item';
                const itemUnit = opt.getAttribute('data-unit') || 'PCS';
                const itemStock = opt.getAttribute('data-stock') || '0';
                const qty = Math.max(1, parseInt(editNewItemQty?.value || '1', 10));
                const cost = Math.max(0, parseFloat(editNewItemCost?.value || '0'));

                const idx = editItemsBody.querySelectorAll('.edit-item-row').length;
                const typeLabel = itemType === 'BAHAN_BAKU' ? 'Bahan Baku' : (itemType === 'SETENGAH_JADI' ? 'Setengah Jadi' : 'Produk');
                const prodIdVal = kind === 'product' ? itemId : '';
                const ingIdVal = kind === 'ingredient' ? itemId : '';

                const tr = document.createElement('tr');
                tr.className = 'edit-item-row';
                tr.innerHTML = `
                    <td>
                        <div class="fw-semibold text-dark">${itemName}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Satuan: ${itemUnit}</div>
                        <input type="hidden" name="items[${idx}][citem_type]" value="${itemType}">
                        <input type="hidden" name="items[${idx}][nid_product]" value="${prodIdVal}">
                        <input type="hidden" name="items[${idx}][nid_ingredient]" value="${ingIdVal}">
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">${typeLabel}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info-emphasis border">${itemStock} ${itemUnit}</span>
                    </td>
                    <td>
                        <input type="number" name="items[${idx}][nqty]" class="form-control form-control-sm edit-qty-input" min="1" value="${qty}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${idx}][ncost]" class="form-control form-control-sm edit-cost-input" min="0" step="any" value="${cost}" required>
                    </td>
                    <td class="text-end fw-bold edit-row-subtotal">${formatRupiah(qty * cost)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-edit-row" title="Hapus Baris">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                editItemsBody.appendChild(tr);
                recalcEditReceivedTotals();
                if (editNewItemTomSelect) {
                    editNewItemTomSelect.clear(true);
                } else {
                    editNewItemSelect.value = '';
                }
                if (editNewItemQty) editNewItemQty.value = '1';
                if (editNewItemCost) editNewItemCost.value = '0';
            });
        }

        @if(session('error_modal') === 'edit_received')
        const editModalEl = document.getElementById('editReceivedModal');
        if (editModalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(editModalEl).show();
        }
        @endif
    });
</script>
@endpush

@endsection