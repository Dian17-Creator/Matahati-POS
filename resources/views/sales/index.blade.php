@extends('layouts.app')

@php
    $tabTitles = [
        'butuh_proses' => 'Butuh Proses',
        'selesai' => 'Selesai',
        'pengembalian' => 'Pengembalian',
        'dibatalkan' => 'Dibatalkan',
    ];
    $currentTabName = $tabTitles[$activeTab] ?? 'Selesai';
@endphp

@section('title', 'Penjualan / ' . $currentTabName)

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('sales.index', ['tab' => 'butuh_proses']) }}"
            class="catalog-nav-tab {{ $activeTab === 'butuh_proses' ? 'active' : '' }}">
            <span>Butuh Proses</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'selesai']) }}"
            class="catalog-nav-tab {{ $activeTab === 'selesai' ? 'active' : '' }}">
            <span>Selesai</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'pengembalian']) }}"
            class="catalog-nav-tab {{ $activeTab === 'pengembalian' ? 'active' : '' }}">
            <span>Pengembalian</span>
        </a>
        <a href="{{ route('sales.index', ['tab' => 'dibatalkan']) }}"
            class="catalog-nav-tab {{ $activeTab === 'dibatalkan' ? 'active' : '' }}">
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

    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.84rem;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
        text-decoration: none;
    }

    .filter-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        filter: brightness(0.96);
    }

    .filter-chip i {
        margin-right: 6px;
    }

    .filter-chip-date {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .filter-chip-date i.bi-calendar {
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
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<div class="card olsera-panel-card">
    <div class="card-body p-4">
        @if($activeTab === 'selesai')
        <!-- Header & Filter Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">Daftar Penjualan</h5>
                <div class="text-muted small">{{ $sales->total() }} Transaksi</div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Dropdown Periode Tanggal -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="filter-chip filter-chip-date dropdown-toggle border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                        <i class="bi bi-calendar"></i>
                        <span>{{ $startDate === $endDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
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
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('sales.index', array_merge(request()->except(['page']), ['tab' => 'selesai', 'start_date' => $today, 'end_date' => $today])) }}">Hari Ini</a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('sales.index', array_merge(request()->except(['page']), ['tab' => 'selesai', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">Kemarin</a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('sales.index', array_merge(request()->except(['page']), ['tab' => 'selesai', 'start_date' => $last7Days, 'end_date' => $today])) }}">7 Hari Lalu</a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('sales.index', array_merge(request()->except(['page']), ['tab' => 'selesai', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">Bulan Ini</a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('sales.index', array_merge(request()->except(['page']), ['tab' => 'selesai', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">Bulan Lalu</a>

                        <hr class="dropdown-divider my-2">
                        <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                        <form action="{{ route('sales.index') }}" method="GET" class="px-1">
                            <input type="hidden" name="tab" value="selesai">
                            @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                            @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif
                            @if(!empty($perPage)) <input type="hidden" name="per_page" value="{{ $perPage }}"> @endif
                            @if(!empty($sortCol)) <input type="hidden" name="sort_col" value="{{ $sortCol }}"> @endif
                            @if(!empty($sortDir)) <input type="hidden" name="sort_dir" value="{{ $sortDir }}"> @endif

                            <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                                </div>
                                <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                    <button type="button" class="btn btn-link p-1 border-0 text-secondary dropdown-sync-date-btn" title="Samakan Tanggal">
                                        <i class="bi bi-arrow-left-right fs-6"></i>
                                    </button>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                Terapkan Tanggal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Baris Per Page & Search -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <form method="GET" action="{{ route('sales.index') }}" class="d-inline">
                <input type="hidden" name="tab" value="selesai">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif
                @if(!empty($sortCol)) <input type="hidden" name="sort_col" value="{{ $sortCol }}"> @endif
                @if(!empty($sortDir)) <input type="hidden" name="sort_dir" value="{{ $sortDir }}"> @endif
                <select name="per_page" class="form-select form-select-sm text-secondary" style="width: auto; font-size: 0.82rem; border-color: #cbd5e1;" onchange="this.form.submit()">
                    <option value="10" {{ $perPage === 10 ? 'selected' : '' }}>10 Baris</option>
                    <option value="15" {{ $perPage === 15 ? 'selected' : '' }}>15 Baris</option>
                    <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25 Baris</option>
                    <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50 Baris</option>
                </select>
            </form>

            <form method="GET" action="{{ route('sales.index') }}" class="d-flex align-items-center">
                <input type="hidden" name="tab" value="selesai">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                @if(!empty($sortCol)) <input type="hidden" name="sort_col" value="{{ $sortCol }}"> @endif
                @if(!empty($sortDir)) <input type="hidden" name="sort_dir" value="{{ $sortDir }}"> @endif
                <div class="input-group input-group-sm" style="width: 280px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari No. Transaksi / Pelanggan..." value="{{ $search }}" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Penjualan -->
        @php
            $sortUrl = function (string $col, string $defaultDir = 'asc') use ($sortCol, $sortDir): string {
                $nextDir = $sortCol === $col ? ($sortDir === 'asc' ? 'desc' : 'asc') : $defaultDir;
                return request()->fullUrlWithQuery(['tab' => 'selesai', 'sort_col' => $col, 'sort_dir' => $nextDir, 'page' => 1]);
            };
            $sortIcon = function (string $col, bool $isNumeric = false) use ($sortCol, $sortDir): string {
                if ($sortCol === $col) {
                    if ($isNumeric) {
                        return $sortDir === 'asc'
                            ? '<i class="bi bi-sort-numeric-down ms-1 text-dark"></i>'
                            : '<i class="bi bi-sort-numeric-down-alt ms-1 text-dark"></i>';
                    }
                    return $sortDir === 'asc'
                        ? '<i class="bi bi-sort-alpha-down ms-1 text-dark"></i>'
                        : '<i class="bi bi-sort-alpha-down-alt ms-1 text-dark"></i>';
                }
                return '<i class="bi bi-arrow-down-up ms-1 text-muted opacity-50" style="font-size: 0.75rem;"></i>';
            };
        @endphp
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small fw-semibold">
                        <th class="px-3 py-3" style="width: 28%;">
                            <a href="{{ $sortUrl('cnotransaction', 'asc') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center">No. Transaksi {!! $sortIcon('cnotransaction') !!}</a>
                        </th>
                        <th class="px-3 py-3 text-center" style="width: 22%;">
                            <a href="{{ $sortUrl('dtransaction', 'desc') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center justify-content-center">Tanggal Beli {!! $sortIcon('dtransaction', true) !!}</a>
                        </th>
                        <th class="px-3 py-3" style="width: 30%;">
                            <a href="{{ $sortUrl('customer_name', 'asc') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center">Pelanggan {!! $sortIcon('customer_name') !!}</a>
                        </th>
                        <th class="px-3 py-3 text-end" style="width: 20%;">
                            <a href="{{ $sortUrl('ngrandtotal', 'desc') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center justify-content-end">Total {!! $sortIcon('ngrandtotal', true) !!}</a>
                        </th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($sales as $sale)
                        <tr>
                            <td class="px-3 py-3">
                                <a href="{{ route('sales.show', ['id' => $sale->nid, 'category' => $sale->ctransaction_category ?? null]) }}" class="fw-semibold text-primary text-decoration-none">
                                    {{ $sale->cnotransaction }}
                                </a>
                                @if(empty($selectedOutletId) && !empty($sale->outlet_name))
                                    <div class="text-muted mt-1" style="font-size: 0.76rem;">
                                        <i class="bi bi-shop me-1"></i>{{ $sale->outlet_name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center text-dark fw-medium">
                                {{ $sale->dtransaction ? \Carbon\Carbon::parse($sale->dtransaction)->format('d-M-Y H:i') : '-' }}
                            </td>
                            <td class="px-3 py-3 text-dark">
                                {{ $sale->customer_name ?? '-' }}
                            </td>
                            <td class="px-3 py-3 text-end fw-bold text-dark">
                                Rp {{ number_format((float) $sale->ngrandtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2 opacity-50"></i>
                                Tidak ada data penjualan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 flex-wrap gap-2">
                <div class="text-muted small">
                    Menampilkan {{ $sales->firstItem() ?? 0 }} - {{ $sales->lastItem() ?? 0 }} dari {{ $sales->total() }} transaksi
                </div>
                <div>
                    {{ $sales->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
            <h6 class="fw-semibold text-dark mb-1">{{ $currentTabName }}</h6>
            <p class="small mb-0">Belum ada data pada menu {{ $currentTabName }}.</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.dropdown-sync-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const form = this.closest('form');
                if (form) {
                    const startInp = form.querySelector('input[name="start_date"]');
                    const endInp = form.querySelector('input[name="end_date"]');
                    if (startInp && endInp) {
                        endInp.value = startInp.value;
                    }
                }
            });
        });
    });
</script>
@endpush
