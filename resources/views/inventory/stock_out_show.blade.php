@extends('layouts.app')

@section('title', 'Detail Stok Keluar ' . $stockOut->cno_stock_out)

@section('content')
<div class="mb-4">
    <!-- Header Navigasi Tab Inventori -->
    <div class="catalog-headbar-tabs border-bottom mb-4">
        <div class="catalog-horizontal-nav d-flex align-items-center">
            <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="catalog-nav-tab">
                <span>Stok Masuk</span>
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="catalog-nav-tab active">
                <span>Stok Keluar</span>
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}" class="catalog-nav-tab">
                <span>Produksi Stock</span>
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}" class="catalog-nav-tab">
                <span>Stok Opname</span>
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}" class="catalog-nav-tab">
                <span>Pergerakan Stok</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Banner Utama: Status & No Stok Keluar (Gaya Olsera) -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 rounded-pill">
                        <i class="bi bi-check2-circle me-1"></i> {{ $stockOut->cstatus ?: 'Posted' }}
                    </span>
                    <div>
                        <div class="text-muted small">No. Stok Keluar</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stockOut->cno_stock_out }}</h4>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inventory.stock-out.print', $stockOut->cno_stock_out) }}" target="_blank" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </a>
                    <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="btn btn-outline-success btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Kotak Info Atas (Tanggal, Transfer ke, Catatan, Alasan) -->
    <div class="row g-3 mb-4">
        <!-- Kotak 1: Tanggal & User -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold mb-1">Tanggal</div>
                    <div class="fw-bold text-dark">{{ $stockOut->dstock_out ? $stockOut->dstock_out->format('d-M-Y') : '-' }}</div>
                    <div class="text-muted small">{{ $stockOut->user?->email ?: ($stockOut->user?->cname ?: 'admin') }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 2: Transfer ke -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 rounded-3">
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
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold mb-1">Catatan</div>
                    <div class="fw-bold text-dark">{{ $stockOut->cnote ?: '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Kotak 4: Alasan -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-3">
                    <div class="text-muted small fw-semibold mb-1">Alasan</div>
                    <div class="fw-bold text-dark">{{ $stockOut->creason ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Biru Tabel Produk (Persis Olsera Image 3 & 4) -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-3 px-4 border-0">
            <h6 class="fw-bold mb-0 text-white">
                Produk Stok Keluar ({{ $stockOut->details->count() }})
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 50px;" class="text-center py-3">No</th>
                        <th class="py-3">Produk / Bahan Baku</th>
                        <th class="py-3">Stock dari</th>
                        <th class="text-center py-3">Qty</th>
                        <th class="text-end py-3">Harga Beli / Modal</th>
                        <th class="text-center py-3">Tgl. Kadaluwarsa</th>
                        <th class="py-3">Rak Penyimpanan</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalDetailQty = 0; @endphp
                    @forelse($stockOut->details as $idx => $detail)
                    @php
                        $isIng = $detail->isIngredient();
                        $itemName = $isIng ? ($detail->ingredient?->cname ?: 'Bahan Baku') : ($detail->product?->cname ?: 'Produk');
                        $itemSku = !$isIng ? ($detail->product?->csku ?: '-') : '-';
                        $satuan = $isIng ? ($detail->ingredient?->csatuan ?: '') : 'pcs';
                        $totalDetailQty += (float) $detail->nqty;
                    @endphp
                    <tr>
                        <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-light d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px; min-width: 38px;">
                                    <i class="bi bi-{{ $isIng ? 'droplet text-success' : 'box-seam text-primary' }} fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $itemName }}</div>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-secondary border me-1">{{ $isIng ? 'Bahan Baku' : 'Produk' }}</span>
                                        SKU: {{ $itemSku }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary small">{{ $detail->cstock_from ?: '-' }}</td>
                        <td class="text-center fw-bold fs-6 text-dark">
                            {{ rtrim(rtrim(number_format((float) $detail->nqty, 2, '.', ''), '0'), '.') }}
                            @if($satuan)<span class="small text-muted fw-normal ms-1">{{ $satuan }}</span>@endif
                        </td>
                        <td class="text-end text-secondary fw-semibold">
                            Rp {{ number_format((float) $detail->ncost, 0, ',', '.') }}
                        </td>
                        <td class="text-center text-secondary small">
                            {{ $detail->dexpired ? $detail->dexpired->format('d-M-Y') : '-' }}
                        </td>
                        <td class="text-secondary small">
                            {{ $detail->crack ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada item detail pada dokumen stok keluar ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($stockOut->details->isNotEmpty())
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end py-3">Jumlah:</td>
                        <td class="text-center text-primary py-3 fs-6">
                            {{ rtrim(rtrim(number_format($totalDetailQty, 2, '.', ''), '0'), '.') }}
                        </td>
                        <td class="text-end text-primary py-3">
                            Rp {{ number_format((float) $stockOut->ntotal_cost, 0, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

