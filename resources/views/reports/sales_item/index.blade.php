@extends('layouts.app')

@section('title', 'Laporan Item Penjualan')

@push('styles')
<style>
    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 8px;
        margin-bottom: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }
    .filter-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
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
        color: #b63352;
    }
    .filter-chip.dropdown-toggle::after {
        display: none !important;
    }
    .filter-dropdown-menu {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        font-size: 0.85rem;
        z-index: 1050;
    }
    .filter-dropdown-menu .dropdown-item {
        padding: 7px 14px;
        font-weight: 500;
        color: #334155;
        display: flex;
        align-items: center;
        transition: background 0.15s ease;
    }
    .filter-dropdown-menu .dropdown-item:hover, .filter-dropdown-menu .dropdown-item:focus {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }
    .filter-dropdown-menu .dropdown-item.active, .filter-dropdown-menu .dropdown-item:active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 600;
    }
    
    .table-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important;
    }
    .table-light th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .table td {
        padding: 0.9rem 0.75rem !important;
    }
    


    /* Modal Modernization */
    .modern-modal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .modern-modal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 20px 24px;
    }
    .modern-modal .modal-body {
        padding: 24px;
    }
    .modern-modal .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 16px 24px;
        background-color: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }
    .modern-modal .form-control, .modern-modal .form-select, .ts-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .modern-modal .form-control:focus, .ts-control.focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
    }
    .modern-modal .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .ts-dropdown {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        margin-top: 4px;
    }
    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 0.95rem;
    }
    .ts-dropdown .option.active {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    
    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
    }
    .btn-primary {
        background-color: #0ea5e9;
        border-color: #0ea5e9;
    }
    .btn-primary:hover, .btn-primary:focus {
        background-color: #0284c7;
        border-color: #0284c7;
        box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25);
    }
    
    .sync-date-btn {
        color: #94a3b8 !important;
        transition: color 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sync-date-btn:hover {
        color: #0ea5e9 !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<!-- Print Header (Hidden on Screen) -->
<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="mb-1" style="color: #000; font-weight: 600; font-size: 1rem;">Laporan Item Penjualan</h6>
            <div style="font-size: 0.7rem; color: #000;">
                <div>Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</div>
                <div>Dibuat pada {{ \Carbon\Carbon::now()->format('d F Y H:i') }}</div>
            </div>
        </div>
        <div class="text-end">
            @php
                $mposUser = auth()->check() ? \App\Models\MposUser::with('outlet')->where('nid_user', auth()->id())->first() : null;
                $currentOutletName = $mposUser?->outlet?->cname ?? 'Matahati POS';
            @endphp
            <div style="color: #000; font-weight: 700; font-size: 0.8rem;">{{ $currentOutletName }}</div>
        </div>
    </div>
</div>


<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Laporan Item Penjualan</h5>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importOlseraItemModal">
                <i class="bi bi-cloud-arrow-up"></i> Import Olsera
            </button>
            @php
                $isAnyFilterActive = !empty($outletId) || !empty($productId) || !empty($search);
            @endphp
            <button type="button" class="btn {{ $isAnyFilterActive ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <a href="{{ route('reports.sales-item.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <div class="card-body p-0 d-flex flex-column">
        <!-- Active Filters -->
        <div class="px-4 py-3 bg-light d-print-none order-1" style="border-bottom: 2px solid #f1f5f9;">
            <span class="fw-bold me-2 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Filter Aktif:</span>

            <!-- Dropdown Periode Tanggal -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip filter-chip-date dropdown-toggle border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                    <i class="bi bi-calendar"></i> {{ $startDate === $endDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 350px;">
                    <div class="px-2 py-1 text-muted text-center small fw-bold">Pilih Cepat</div>
                    @php
                        $today = \Carbon\Carbon::today()->format('Y-m-d');
                        $yesterday = \Carbon\Carbon::yesterday()->format('Y-m-d');
                        $last7Days = \Carbon\Carbon::today()->subDays(6)->format('Y-m-d');
                        $thisMonthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                        $thisMonthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
                        $lastMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                        $lastMonthEnd = \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    @endphp
                    <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['start_date' => $today, 'end_date' => $today])) }}">
                        Hari Ini
                    </a>
                    <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                        Kemarin
                    </a>
                    <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['start_date' => $last7Days, 'end_date' => $today])) }}">
                        7 Hari Lalu
                    </a>
                    <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                        Bulan Ini
                    </a>
                    <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                        Bulan Lalu
                    </a>

                    <hr class="dropdown-divider my-2">
                    <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                    <form action="{{ route('reports.sales-item') }}" method="GET" class="px-1">
                        @if(!empty($outletId)) <input type="hidden" name="outlet_id" value="{{ $outletId }}"> @endif
                        @if(!empty($productId)) <input type="hidden" name="product_id" value="{{ $productId }}"> @endif
                        @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif

                        <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                <input type="date" id="dropdownStartDate" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                            </div>
                            <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                <button type="button" class="btn btn-link p-1 border-0 sync-date-btn dropdown-sync-date-btn" title="Samakan Tanggal">
                                    <i class="bi bi-arrow-left-right fs-6"></i>
                                </button>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                                <input type="date" id="dropdownEndDate" name="end_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                            Terapkan Tanggal
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dropdown Outlet -->
            <div class="dropdown d-inline-block">
                @php
                    $outletNameDisplay = !empty($outletId) ? (collect($outlets)->firstWhere('nid', $outletId)?->cname ?? $outletId) : 'Semua Outlet';
                @endphp
                <button type="button" class="filter-chip {{ !empty($outletId) ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih Outlet" {{ ($isOutletLocked ?? false) ? 'disabled' : '' }}>
                    <i class="bi bi-shop"></i> {{ $outletNameDisplay }}
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 py-1" style="min-width: 220px; max-height: 280px; overflow-y: auto;">
                    <a class="dropdown-item {{ empty($outletId) ? 'active' : '' }}" href="{{ route('reports.sales-item', request()->except(['outlet_id', 'page'])) }}">
                        Semua Outlet
                        @if(empty($outletId)) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                    </a>
                    @foreach($outlets as $outlet)
                        <a class="dropdown-item {{ (string)($outletId ?? '') === (string)$outlet->nid ? 'active' : '' }}" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['outlet_id' => $outlet->nid])) }}">
                            {{ $outlet->cname }}
                            @if((string)($outletId ?? '') === (string)$outlet->nid) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Dropdown Produk -->
            <div class="dropdown d-inline-block">
                @php
                    $productNameDisplay = !empty($productId) ? (collect($products)->firstWhere('nid', $productId)?->cname ?? $productId) : 'Semua Produk';
                @endphp
                <button type="button" class="filter-chip {{ $productId ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Pilih Produk">
                    <i class="bi bi-cup-hot"></i> {{ $productNameDisplay }}
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-0" style="min-width: 250px;">
                    <div class="p-2 border-bottom">
                        <input type="text" class="form-control form-control-sm quick-dropdown-search" placeholder="Cari produk...">
                    </div>
                    <div class="py-1" style="max-height: 250px; overflow-y: auto;">
                        <a class="dropdown-item filter-list-item {{ empty($productId) ? 'active' : '' }}" href="{{ route('reports.sales-item', request()->except(['product_id', 'page'])) }}">
                            Semua Produk
                            @if(empty($productId)) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                        </a>
                        @foreach($products as $product)
                            <a class="dropdown-item filter-list-item {{ (string)$productId === (string)$product->nid ? 'active' : '' }}" href="{{ route('reports.sales-item', array_merge(request()->except(['page']), ['product_id' => $product->nid])) }}">
                                <span>{{ $product->cname }}</span>
                                @if((string)$productId === (string)$product->nid) <i class="bi bi-check-lg text-primary ms-auto"></i> @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Dropdown Pencarian -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip {{ $search ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Pencarian Item">
                    <i class="bi bi-search"></i> {{ $search ? '"' . $search . '"' : 'Cari Item...' }}
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 260px;">
                    <form action="{{ route('reports.sales-item') }}" method="GET">
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        @if(!empty($outletId)) <input type="hidden" name="outlet_id" value="{{ $outletId }}"> @endif
                        @if(!empty($productId)) <input type="hidden" name="product_id" value="{{ $productId }}"> @endif

                        <label class="form-label small text-muted mb-1">Cari No. Pesanan / Item / Pelanggan:</label>
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Ketik kata kunci...">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                        @if($search)
                            <a href="{{ route('reports.sales-item', request()->except(['search', 'page'])) }}" class="btn btn-outline-secondary btn-sm w-100 py-1" style="font-size: 0.75rem;">
                                Hapus Filter Pencarian
                            </a>
                        @endif
                    </form>
                </div>
            </div>

        </div>

        <!-- Grand Total Summary Bar -->
        <div class="px-4 py-3 print-grand-total order-2 order-print-3" style="background-color: #1e293b; color: #fff;">
            <div class="fw-bold mb-2" style="font-size: 0.85rem; text-transform: uppercase; color: #94a3b8;">Grand Total</div>
            <div class="row w-100 m-0 g-3">
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Total Qty</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.95rem;">{{ number_format($summary->total_qty ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Total Diskon Penjualan</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.95rem; color: #f87171;">Rp {{ number_format($summary->total_discount ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Total Penjualan</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.95rem; color: #34d399;">Rp {{ number_format($summary->total_penjualan ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Modal Produk</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.95rem; color: #fbbf24;">Rp {{ number_format($summary->total_modal ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Total Laba</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.95rem; color: #60a5fa;">Rp {{ number_format($summary->total_laba ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Table Area -->
        <div class="table-responsive order-3 order-print-2">
            <table class="table table-hover table-borderless align-middle mb-0 text-nowrap" style="min-width: 1200px;">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th class="py-3 text-center">NO. PESANAN</th>
                        <th class="py-3 text-center">TANGGAL</th>
                        <th class="py-3 text-center">PENJUALAN OLEH</th>
                        <th class="py-3 text-start">ITEM</th>
                        <th class="py-3 text-center">PELANGGAN</th>
                        <th class="py-3 text-center">QTY</th>
                        <th class="py-3 text-center">DISKON</th>
                        <th class="py-3 text-center fw-bold text-primary">TOTAL PENJUALAN</th>
                        <th class="py-3 text-center">MODAL PRODUK</th>
                        <th class="py-3 text-center fw-bold text-success">LABA</th>
                        <th class="py-3 text-center">KOMISI</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($items as $row)
                        @php
                            $netQty = $row->nqty - $row->nqty_void - $row->nqty_refund;
                            $grossSubtotal = $netQty * $row->nprice;
                            $prorateDiscount = ($row->transaction_subtotal > 0 && $row->transaction_discount > 0)
                                ? ($grossSubtotal / $row->transaction_subtotal) * $row->transaction_discount
                                : 0;
                            $totalPenjualan = max(0, $grossSubtotal - $prorateDiscount);
                            $totalModal = $netQty * $row->ncost;
                            $laba = $totalPenjualan - $totalModal;
                            $itemDiscount = 0; // Sesuai Olsera, kolom diskon item hanya untuk diskon langsung per-produk (0.00 untuk voucher/diskon nota)
                        @endphp
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td class="text-center">
                                <strong>{{ $row->cnotransaction }}</strong>
                                @if($row->csource === 'OLSERA')
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;">OLSERA</span>
                                @endif
                            </td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($row->dtransaction)->format('d/m/Y H:i') }}</td>
                            <td class="text-center">{{ $row->cashier_name ?? '-' }}</td>
                            <td class="text-start">
                                <span class="fw-semibold text-dark">{{ $row->item_name }}</span>
                            </td>
                            <td class="text-center">{{ $row->customer_name }}</td>
                            <td class="text-center fw-bold">{{ number_format($netQty, 0, ',', '.') }}</td>
                            <td class="text-center text-muted">
                                {{ $itemDiscount > 0 ? 'Rp ' . number_format($itemDiscount, 0, ',', '.') : 'Rp 0' }}
                            </td>
                            <td class="text-center fw-bold text-primary">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</td>
                            <td class="text-center text-muted">
                                {{ $totalModal > 0 ? 'Rp ' . number_format($totalModal, 0, ',', '.') : 'Rp 0' }}
                            </td>
                            <td class="text-center fw-bold {{ $laba >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($laba, 0, ',', '.') }}
                            </td>
                            <td class="text-center text-muted">Rp 0</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                Tidak ada data item penjualan yang ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!request()->has('print') && method_exists($items, 'hasPages'))
        <div class="d-flex justify-content-center mt-3 order-4">
            {{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Modal 1: Filter Modal -->
<div class="modal fade modern-modal" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('reports.sales-item') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Laporan Item Penjualan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label text-muted small mb-1">Dari</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                            </div>
                            <div class="col-auto d-flex align-items-end" style="padding-bottom: 2px;">
                                <button type="button" class="btn btn-link p-1 border-0 sync-date-btn" id="syncDateBtn" title="Samakan Tanggal">
                                    <i class="bi bi-arrow-left-right fs-5"></i>
                                </button>
                            </div>
                            <div class="col">
                                <label class="form-label text-muted small mb-1">Sampai</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="form-select tom-select-filter" placeholder="Semua Outlet" {{ ($isOutletLocked ?? false) ? 'disabled' : '' }}>
                            <option value="" {{ empty($outletId) ? 'selected' : '' }}>Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ (string)($outletId ?? '') === (string)$outlet->nid ? 'selected' : '' }}>{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @if($isOutletLocked ?? false)
                            <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="product_id" id="product_id" class="form-select tom-select-filter" placeholder="Semua Produk">
                            <option value="">Semua Produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->nid }}" {{ $productId == $product->nid ? 'selected' : '' }}>{{ $product->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pencarian</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="No. Pesanan, Nama Item, atau Pelanggan...">
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="{{ route('reports.sales-item') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal Olsera Item -->
<div class="modal fade" id="importOlseraItemModal" tabindex="-1" aria-labelledby="importOlseraItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importOlseraItemModalLabel">Import Detail Item Olsera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('reports.sales-item.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_outlet_id" class="form-label">Pilih Outlet <span class="text-danger">*</span></label>
                        <select class="form-select @error('outlet_id') is-invalid @enderror" id="import_outlet_id" name="outlet_id" required {{ ($isOutletLocked ?? false) ? 'disabled' : '' }}>
                            @if(!($isOutletLocked ?? false))
                                <option value="">-- Pilih Outlet --</option>
                            @endif
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ ($outletId == $outlet->nid || (count($outlets) == 1)) ? 'selected' : '' }}>{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @if($isOutletLocked ?? false)
                            <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        @endif
                        @error('outlet_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="import_file" class="form-label">File Excel (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file" id="import_file" name="file" accept=".xlsx,.xls,.csv" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Live Search for Dropdown Items (Product)
        document.querySelectorAll('.quick-dropdown-search').forEach(input => {
            input.addEventListener('input', function(e) {
                e.stopPropagation();
                const filter = this.value.toLowerCase().trim();
                const menu = this.closest('.dropdown-menu');
                menu.querySelectorAll('.filter-list-item').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(filter) ? '' : 'none';
                });
            });
            input.addEventListener('click', function(e) { e.stopPropagation(); });
            input.addEventListener('keydown', function(e) { e.stopPropagation(); });
        });

        document.querySelectorAll('.tom-select-filter').forEach((el) => {
            if (!el.disabled) {
                new TomSelect(el, {
                    create: false,
                    maxItems: 1,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    placeholder: el.getAttribute('placeholder'),
                    allowEmptyOption: true
                });
            }
        });

        // Add print styling
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                .sidebar, nav.navbar, .btn, .filter-chip, .card-header, .pagination, .page-item {
                    display: none !important;
                }
                .main-content {
                    padding: 0 !important;
                }
                .container-fluid {
                    padding: 0 !important;
                }
                .row {
                    margin: 0 !important;
                }
                .col-md-10 {
                    width: 100% !important;
                    padding: 0 !important;
                }
                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
                .card-body {
                    padding: 0 !important;
                }
                .table-responsive {
                    overflow-x: visible !important;
                }
                table th {
                    color: #000 !important;
                    border-bottom: 1px solid #e5e7eb !important;
                    border: 1px solid #e5e7eb !important;
                    background-color: transparent !important;
                    white-space: nowrap !important;
                }
                table td {
                    color: #000 !important;
                    white-space: normal !important;
                    padding: 4px !important;
                    border: 1px solid #e5e7eb !important;
                }
                table {
                    width: 100% !important;
                    font-size: 0.65rem !important;
                    border-collapse: collapse !important;
                }
                .d-print-block {
                    display: block !important;
                }
                a {
                    text-decoration: none !important;
                    color: #000 !important;
                }
                body {
                    margin-top: 1.5cm;
                    margin-bottom: 1.5cm;
                    margin-left: 1cm;
                    margin-right: 1cm;
                }
            }
            @page {
                size: landscape;
                margin: 0;
            }
        `;
        document.head.appendChild(style);

        // Sync Date Button (Modal)
        const syncBtn = document.getElementById('syncDateBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function() {
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                if (startInput && endInput && startInput.value) {
                    endInput.value = startInput.value;
                }
            });
        }

        // Sync Date Button (Dropdown)
        document.querySelectorAll('.dropdown-sync-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const form = this.closest('form');
                if (form) {
                    const startInp = form.querySelector('input[name="start_date"]');
                    const endInp = form.querySelector('input[name="end_date"]');
                    if (startInp && endInp) {
                        if (startInp.value) {
                            endInp.value = startInp.value;
                        } else if (endInp.value) {
                            startInp.value = endInp.value;
                        }
                    }
                }
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            window.print();
        }
    });
</script>
@endpush
