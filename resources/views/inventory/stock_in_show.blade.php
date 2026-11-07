@extends('layouts.app')

@section('title', 'Inventory / Detail Stok Masuk')

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}"
            class="catalog-nav-tab active">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}"
            class="catalog-nav-tab">
            <span>Stok Keluar</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}"
            class="catalog-nav-tab">
            <span>Produksi Stok</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}"
            class="catalog-nav-tab">
            <span>Stok Opname</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}"
            class="catalog-nav-tab">
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
</style>
@endsection

@section('content')
<div class="container-fluid px-0">
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

    {{-- Alert Info / Notice --}}
    <div class="alert alert-light border shadow-sm d-flex align-items-center mb-4 py-3 px-4 rounded-3 text-secondary" style="background-color: #f8fafc;">
        <i class="bi bi-exclamation-circle text-muted fs-5 me-3"></i>
        <span class="small">Pastikan data sudah benar sebelum diposting. Setelah terposting, data tidak diperbolehkan diubah.</span>
    </div>

    {{-- Header Banner Card --}}
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> {{ $stockIn->cstatus ?? 'Posted' }}
                </span>
                <div>
                    <div class="text-muted small">No. Stok Masuk</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $stockIn->cno_stock_in }}</h4>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="btn btn-outline-secondary px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="{{ route('inventory.stock-in.print', $stockIn->cno_stock_in) }}" target="_blank" class="btn btn-outline-dark px-3">
                    <i class="bi bi-printer me-1"></i> Cetak
                </a>
            </div>
        </div>
    </div>

    {{-- 3 Column Information Cards --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Tanggal --}}
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Tanggal</h6>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted small">Tanggal buat</span>
                        <span class="fw-semibold text-dark small">
                            {{ $stockIn->dstock_in ? \Carbon\Carbon::parse($stockIn->dstock_in)->format('d-M-Y') : '-' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-1 pt-2">
                        <span class="text-muted small">Dibuat oleh</span>
                        <span class="fw-semibold text-dark small text-truncate" style="max-width: 180px;" title="{{ $stockIn->creceived_by ?? ($stockIn->user?->email ?? '-') }}">
                            {{ $stockIn->creceived_by ?? ($stockIn->user?->email ?? '-') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Supplier & Outlet --}}
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Diterima Dari</h6>
                    <div class="py-1">
                        @if(!empty($stockIn->targetOutlet))
                            <span class="fw-bold fs-6 text-primary"><i class="bi bi-shop me-1"></i> {{ $stockIn->targetOutlet->cname }}</span>
                            <div class="text-muted small">Transfer Antar Outlet</div>
                        @elseif(!empty($stockIn->supplier))
                            <span class="fw-bold fs-6 text-dark">{{ $stockIn->supplier->cname }}</span>
                            <div class="text-muted small">Supplier</div>
                        @else
                            <span class="fw-bold fs-6 text-dark">-</span>
                        @endif
                    </div>
                    @if($stockIn->outlet)
                        <div class="d-flex justify-content-between py-1 mt-2 border-top">
                            <span class="text-muted small">Outlet Penerima</span>
                            <span class="badge bg-light text-dark border small">{{ $stockIn->outlet->cname }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Catatan --}}
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Catatan</h6>
                    <div class="py-1">
                        <span class="fw-semibold text-secondary small">{{ $stockIn->cnote ?? '-' }}</span>
                    </div>
                    @if(!empty($stockIn->creason))
                        <div class="d-flex justify-content-between py-1 mt-2 border-top">
                            <span class="text-muted small">Alasan</span>
                            <span class="badge bg-light text-secondary border small">{{ $stockIn->creason }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Items Table --}}
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-secondary small">
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Nama</th>
                            <th class="text-end" style="width: 180px;">Harga Beli</th>
                            <th class="text-center" style="width: 120px;">Qty</th>
                            <th class="text-end" style="width: 180px;">Total</th>
                            <th class="pe-4 text-center" style="width: 120px;">Rack</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockIn->details as $index => $detail)
                            @php
                                $itemName = $detail->product?->cname
                                    ?? ($detail->isIngredient()
                                        ? ($detail->ingredient?->cname ?? 'Bahan Baku #' . $detail->nid_ingredient)
                                        : 'Produk #' . $detail->nid_product);
                                $normType = $detail->getItemTypeNormalized();
                                $itemUnit = $detail->getItemUnit();
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded bg-light border p-2 text-center" style="width: 44px; height: 44px;">
                                            <i class="bi bi-box-seam text-secondary fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $itemName }}</div>
                                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                @if($normType === 'SETENGAH_JADI')
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;">Setengah Jadi</span>
                                                @elseif($normType === 'BAHAN_BAKU')
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 0.68rem;">Bahan Baku</span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">Produk</span>
                                                @endif
                                                @if($detail->product?->category?->cname)
                                                    <span class="text-muted small">&bull; {{ $detail->product->category->cname }}</span>
                                                @endif
                                            </div>
                                            @if(!empty($detail->cserial_no))
                                                <div class="text-muted small mt-1">
                                                    <span class="text-secondary">Seri (dipisahkan dengan baris baru):</span>
                                                    <div class="font-monospace text-dark">{{ $detail->cserial_no }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold text-secondary">
                                    IDR {{ number_format($detail->ncost, 0, ',', '.') }}
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ number_format($detail->nqty, 0, ',', '.') }} <span class="text-muted fw-normal small">{{ $itemUnit }}</span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    IDR {{ number_format($detail->nsubtotal, 0, ',', '.') }}
                                </td>
                                <td class="pe-4 text-center text-muted small">
                                    {{ $detail->crack ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Tidak ada rincian item barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top">
                        <tr class="fw-bold">
                            <td colspan="2" class="ps-4 text-dark fs-6">Total</td>
                            <td></td>
                            <td class="text-center text-dark fs-6">
                                {{ number_format($stockIn->details->sum('nqty'), 0, ',', '.') }}
                            </td>
                            <td class="text-end text-dark fs-6">
                                IDR {{ number_format($stockIn->details->sum('nsubtotal'), 0, ',', '.') }}
                            </td>
                            <td class="pe-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

