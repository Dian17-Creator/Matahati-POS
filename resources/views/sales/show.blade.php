@extends('layouts.app')

@section('title', 'Penjualan / Detail Penjualan')

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('sales.index', ['tab' => 'butuh_proses']) }}" class="catalog-nav-tab">
            <span>Butuh Proses</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'selesai']) }}" class="catalog-nav-tab active">
            <span>Selesai</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'pengembalian']) }}" class="catalog-nav-tab">
            <span>Pengembalian</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'dibatalkan']) }}" class="catalog-nav-tab">
            <span>Dibatalkan</span>
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
</style>
@endsection

@push('styles')
<style>
    .olsera-panel-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03) !important;
    }

    .sales-detail-table thead th {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        color: #475569;
        background-color: #f8fafc;
    }
</style>
@endpush

@section('content')
@php
    $statusUpper = strtoupper((string) ($sale->cstatus ?? 'PAID'));
    $isVoidOrCancel = in_array($statusUpper, ['VOID', 'CANCELLED', 'REFUND'], true);
    $customerDisplay = trim((string) ($sale->cname_customer ?: ($sale->customer?->cname ?? '')));
    if ($customerDisplay === '' || strtolower($customerDisplay) === 'umum') {
        $customerDisplay = '-';
    }
    $cashierName = $sale->posUser?->user?->cname ?: ($sale->posUser?->user?->cemail ?: 'Kasir');
@endphp

<!-- Status Banner -->
<div class="alert {{ $isVoidOrCancel ? 'alert-danger' : 'alert-light border' }} d-flex align-items-center gap-2 mb-3 py-2 px-3 shadow-sm" style="border-radius: 8px;">
    <i class="bi {{ $isVoidOrCancel ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle text-secondary' }}"></i>
    <span class="small">
        <strong>Status {{ ucfirst(strtolower($statusUpper)) }}:</strong>
        @if($isVoidOrCancel)
            Transaksi penjualan ini berstatus {{ $statusUpper }}.
        @else
            Transaksi penjualan ini telah selesai dan tercatat di sistem.
        @endif
    </span>
</div>

<!-- Kartu Header Dokumen -->
<div class="card olsera-panel-card mb-3">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            @if($isVoidOrCancel)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-x-circle me-1"></i> {{ $statusUpper }}
                </span>
            @else
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> {{ $statusUpper }}
                </span>
            @endif
            <div>
                <div class="text-muted small">No. Transaksi</div>
                <h4 class="fw-bold mb-0 text-dark">{{ $sale->cnotransaction }}</h4>
                <div class="text-muted small mt-1">
                    <i class="bi bi-shop me-1"></i>{{ $sale->outlet?->cname ?? '-' }}
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

<!-- 4 Kartu Ringkasan Info -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card olsera-panel-card h-100">
            <div class="card-body p-3">
                <div class="text-muted small mb-1">Tanggal Beli</div>
                <div class="fw-bold fs-6 text-dark">
                    {{ $sale->dtransaction ? \Carbon\Carbon::parse($sale->dtransaction)->format('d-M-Y H:i') : '-' }}
                </div>
                <div class="text-muted small mt-1">Kasir: {{ $cashierName }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card olsera-panel-card h-100">
            <div class="card-body p-3">
                <div class="text-muted small mb-1">Pelanggan</div>
                <div class="fw-bold fs-6 text-dark">{{ $customerDisplay }}</div>
                <div class="text-muted small mt-1">
                    @if($sale->customer?->cphone)
                        {{ $sale->customer->cphone }}
                    @elseif($sale->customer?->cemail)
                        {{ $sale->customer->cemail }}
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card olsera-panel-card h-100">
            <div class="card-body p-3">
                <div class="text-muted small mb-1">Pembayaran</div>
                <div class="fw-bold fs-6 text-dark">
                    {{ $sale->payment?->cname ?? '-' }}
                    @if($sale->paymentDual)
                        + {{ $sale->paymentDual->cname }}
                    @endif
                </div>
                <div class="text-muted small mt-1">
                    {{ $sale->cordertype ? str_replace('_', ' ', $sale->cordertype) : 'POS' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card olsera-panel-card h-100">
            <div class="card-body p-3">
                <div class="text-muted small mb-1">Total Transaksi</div>
                <div class="fw-bold fs-5 text-primary">
                    Rp {{ number_format((float) $sale->ngrandtotal, 0, ',', '.') }}
                </div>
                <div class="text-muted small mt-1">
                    {{ $sale->details->count() }} Item
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Item Penjualan (Header Biru seperti Detail Stok Masuk) -->
<div class="card olsera-panel-card overflow-hidden">
    <div class="card-header text-white fw-bold py-3 px-4" style="background-color: #0d6efd;">
        Item Penjualan ({{ $sale->details->count() }})
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sales-detail-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 60px;">NO</th>
                        <th class="px-3 py-3" style="width: 120px;">KATEGORI</th>
                        <th class="px-3 py-3">PRODUK / ITEM</th>
                        <th class="px-3 py-3 text-center" style="width: 130px;">QTY</th>
                        <th class="px-3 py-3 text-end" style="width: 150px;">HARGA SATUAN</th>
                        <th class="px-4 py-3 text-end" style="width: 160px;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sale->details as $index => $detail)
                        @php
                            $satuan = $detail->product?->csatuan ?: 'pcs';
                            $netQty = (int) $detail->nqty - (int) ($detail->nqty_void ?? 0) - (int) ($detail->nqty_refund ?? 0);
                            $lineSubtotal = (float) ($detail->nsubtotal ?: ($netQty * (float) $detail->nprice));
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-center text-muted">{{ $index + 1 }}</td>
                            <td class="px-3 py-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-normal">
                                    {{ $detail->product?->category?->cname ?? 'Produk' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="border rounded d-flex align-items-center justify-content-center text-primary bg-light" style="width: 34px; height: 34px; flex-shrink: 0;">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $detail->cname ?? $detail->product?->cname ?? '-' }}</div>
                                        @if(!empty($detail->cnote))
                                            <div class="text-muted small">{{ $detail->cnote }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="fw-bold text-dark">{{ number_format($detail->nqty, 0, ',', '.') }}</span>
                                <span class="text-muted small">{{ strtolower($satuan) }}</span>
                                @if(($detail->nqty_void ?? 0) > 0 || ($detail->nqty_refund ?? 0) > 0)
                                    <div class="text-danger" style="font-size: 0.72rem;">
                                        @if(($detail->nqty_void ?? 0) > 0) Void: {{ $detail->nqty_void }} @endif
                                        @if(($detail->nqty_refund ?? 0) > 0) Refund: {{ $detail->nqty_refund }} @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-end text-secondary">
                                Rp {{ number_format((float) $detail->nprice, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-end fw-bold text-dark">
                                Rp {{ number_format($lineSubtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada item pada transaksi ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Ringkasan Pembayaran di Bawah Tabel -->
        <div class="border-top bg-light-subtle px-4 py-3">
            <div class="row justify-content-end">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="d-flex justify-content-between small text-secondary mb-1">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format((float) $sale->nsubtotal, 0, ',', '.') }}</span>
                    </div>
                    @if((float) $sale->ndiscount > 0)
                        <div class="d-flex justify-content-between small text-danger mb-1">
                            <span>Diskon</span>
                            <span>-Rp {{ number_format((float) $sale->ndiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if((float) $sale->ntax > 0)
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Pajak</span>
                            <span>Rp {{ number_format((float) $sale->ntax, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if((float) ($sale->nservice_charge ?? 0) > 0)
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Service</span>
                            <span>Rp {{ number_format((float) $sale->nservice_charge, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold text-dark fs-6">
                        <span>Total Akhir</span>
                        <span class="text-primary">Rp {{ number_format((float) $sale->ngrandtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
