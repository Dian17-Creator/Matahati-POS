@extends('layouts.app')

@php
$tabTitles = [
'stok_masuk' => 'Stok Masuk',
'stok_keluar' => 'Stok Keluar',
'produksi_stok' => 'Produksi Stok',
'stok_opname' => 'Stok Opname',
'pergerakan_stok' => 'Pergerakan Stok',
];
$currentTabName = $tabTitles[$activeTab] ?? 'Stok Masuk';
@endphp

@section('title', 'Inventori / ' . $currentTabName)

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_masuk' ? 'active' : '' }}">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_keluar' ? 'active' : '' }}">
            <span>Stok Keluar</span>
        </a>
        <!-- <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}"
            class="catalog-nav-tab {{ $activeTab === 'produksi_stok' ? 'active' : '' }}">
            <span>Produksi Stok</span>
        </a> -->
        <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_opname' ? 'active' : '' }}">
            <span>Stok Opname</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}"
            class="catalog-nav-tab {{ $activeTab === 'pergerakan_stok' ? 'active' : '' }}">
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

    @media (max-width: 575.98px) {
        .catalog-nav-tab {
            min-width: 0;
            padding: 11px 16px;
        }
    }

    /* Style Filter Chip Persis Halaman Laporan Penjualan */
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

    .filter-chip-date:hover {
        background: #f1f5f9;
        color: #0f172a;
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

    .filter-dropdown-menu .dropdown-item {
        padding: 7px 14px;
        font-weight: 500;
        color: #334155;
        display: flex;
        align-items: center;
        transition: background 0.15s ease;
    }

    .filter-dropdown-menu .dropdown-item:hover,
    .filter-dropdown-menu .dropdown-item:focus {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }

    .filter-dropdown-menu .dropdown-item.active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 600;
    }

    .dropdown-sync-date-btn {
        color: #64748b;
        text-decoration: none;
    }

    .dropdown-sync-date-btn:hover {
        color: #0ea5e9;
    }
</style>
@endsection

@section('content')
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

<div class="card shadow-sm border-0 mb-4 rounded-3">
    <div class="card-body p-4">
        {{-- ==================== TAB 1: STOK MASUK ==================== --}}
        @if($activeTab === 'stok_masuk')
        <!-- Baris Atas: Judul di kiri, Export/Import/Tambah di kanan -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">Daftar Stok Masuk</h5>
                <div class="text-muted small">{{ $totalStockIn }} Stok Masuk</div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
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
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $today, 'end_date' => $today])) }}">
                            Hari Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                            Kemarin
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                            7 Hari Lalu
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                            Bulan Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                            Bulan Lalu
                        </a>

                        <hr class="dropdown-divider my-2">
                        <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                        <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                            <input type="hidden" name="tab" value="stok_masuk">
                            @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                            @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif
                            @if(!empty($perPage)) <input type="hidden" name="per_page" value="{{ $perPage }}"> @endif

                            <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                                </div>
                                <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                    <button type="button" class="btn btn-link p-1 border-0 sync-date-btn dropdown-sync-date-btn" title="Samakan Tanggal">
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

                <!-- <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="alert('Fitur export data stok masuk segera aktif.')">
                    <i class="bi bi-download me-1"></i> Export
                </button>

                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="alert('Fitur import stok masuk segera aktif.')">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Import
                </button> -->

                <div class="dropdown">
                    <button class="btn btn-success btn-sm px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-1">
                        <li>
                            <a class="dropdown-item py-2 small fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#modalTerimaPembelian">
                                <i class="bi bi-receipt-cutoff me-2 text-primary"></i> Terima dari Pembelian
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <a class="dropdown-item py-2 small text-muted" href="#" onclick="alert('Fitur input stok masuk manual tanpa PO akan segera hadir.'); return false;">
                                <i class="bi bi-box-seam me-2"></i> Tambah Stok Masuk
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Baris Filter: Per Page & Search -->
        <!-- <div class="row align-items-center mb-3 g-2">
            <div class="col-auto">
                <form method="GET" action="{{ route('inventory.index') }}" class="d-inline">
                    <input type="hidden" name="tab" value="stok_masuk">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if($selectedOutletId)
                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    @endif
                    @if($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <select name="per_page" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Baris</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 Baris</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Baris</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Baris</option>
                    </select>
                </form>
            </div>
            <div class="col-12 col-md-4 ms-auto">
                <form method="GET" action="{{ route('inventory.index') }}">
                    <input type="hidden" name="tab" value="stok_masuk">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    @if($selectedOutletId)
                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    @endif
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari No. Stok Masuk / Catatan..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div> -->

        <!-- Tabel Daftar Stok Masuk Sesuai Layout Referensi Olsera -->
        <div class="table-responsive">
            <table class="table table-hover align-middle border mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small">
                        <th style="width: 18%;">No. <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th style="width: 14%;">Diterima dari <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th style="width: 14%;">Supplier <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th style="width: 12%;">Tanggal <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th style="width: 18%;">Catatan <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="text-center" style="width: 10%;">Status <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th style="width: 14%;">Diterima Oleh <i class="bi bi-arrow-down-up small text-muted"></i></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockIns as $item)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.stock-in.show', $item->cno_stock_in) }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $item->cno_stock_in }}
                            </a>
                        </td>
                        <td class="text-muted small">
                            @if(!empty($item->targetOutlet))
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    <i class="bi bi-shop me-1"></i>{{ $item->targetOutlet->cname }}
                                </span>
                            @else
                                {{ $item->outlet?->cname ?? '-' }}
                            @endif
                        </td>
                        <td class="fw-medium text-dark small">
                            {{ $item->supplier?->cname ?? '-' }}
                        </td>
                        <td class="text-secondary small">
                            {{ $item->dstock_in ? \Carbon\Carbon::parse($item->dstock_in)->format('d-M-Y') : '-' }}
                        </td>
                        <td class="text-muted small">
                            {{ $item->cnote ?? '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small rounded-pill">
                                {{ $item->cstatus ?? 'Posted' }}
                            </span>
                        </td>
                        <td class="text-secondary small text-truncate" style="max-width: 180px;" title="{{ $item->creceived_by ?? ($item->user?->email ?? '-') }}">
                            {{ $item->creceived_by ?? ($item->user?->email ?? '-') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada riwayat stok masuk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Menampilkan {{ $stockIns->firstItem() ?? 0 }} - {{ $stockIns->lastItem() ?? 0 }} dari {{ $stockIns->total() }} stok masuk
            </div>
            <div>
                {{ $stockIns->links() }}
            </div>
        </div>

        <!-- MODAL: Terima dari Pembelian -->
        <div class="modal fade" id="modalTerimaPembelian" tabindex="-1" aria-labelledby="modalTerimaPembelianLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalTerimaPembelianLabel">Terima dari Pembelian</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Input Pencarian PO / Supplier -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchPurchaseInput" class="form-control border-start-0" placeholder="Cari No. Pembelian / Nama Supplier..." onkeyup="filterPendingPurchases()">
                            </div>
                        </div>

                        <!-- Tabel List PO Pending -->
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle border mb-0" id="tablePendingPurchases">
                                <thead class="table-light sticky-top">
                                    <tr class="text-secondary small">
                                        <th>No. Pembelian</th>
                                        <th>Nama Supplier</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center">Tgl. Beli</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingPurchases as $po)
                                    <tr class="purchase-row">
                                        <td class="fw-semibold text-dark po-number">{{ $po->cno_purchase }}</td>
                                        <td class="text-dark po-supplier">{{ $po->supplier?->cname ?? '-' }}</td>
                                        <td class="text-end fw-semibold text-secondary">
                                            IDR {{ number_format($po->ntotal_amount, 2, ',', '.') }}
                                        </td>
                                        <td class="text-center text-secondary small">
                                            {{ $po->dpurchase ? \Carbon\Carbon::parse($po->dpurchase)->format('d-M-Y') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('inventory.stock-in.receive-purchase', $po->nid) }}" onsubmit="return confirm('Apakah Anda yakin ingin menerima stok dari pesanan pembelian {{ $po->cno_purchase }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                                                    Terima
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr id="emptyPurchaseRow">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                            Tidak ada pesanan pembelian pending yang perlu diterima.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function filterPendingPurchases() {
                const input = document.getElementById('searchPurchaseInput');
                const filter = input.value.toLowerCase().trim();
                const table = document.getElementById('tablePendingPurchases');
                const rows = table.getElementsByClassName('purchase-row');

                for (let i = 0; i < rows.length; i++) {
                    const poNum = rows[i].querySelector('.po-number')?.textContent.toLowerCase() || '';
                    const supplier = rows[i].querySelector('.po-supplier')?.textContent.toLowerCase() || '';

                    if (poNum.includes(filter) || supplier.includes(filter)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        </script>

        {{-- ==================== TAB 2: STOK KELUAR ==================== --}}
        @elseif($activeTab === 'stok_keluar')
        <!-- Baris Atas: Judul di kiri, Filter Tanggal, Export, Import, dan Dropdown + Tambah di kanan (Persis Olsera) -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">Daftar Stok Keluar</h5>
                <div class="text-muted small">{{ $totalStockOut }} Stok Keluar</div>
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
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $today, 'end_date' => $today])) }}">
                            Hari Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                            Kemarin
                        </a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                            7 Hari Lalu
                        </a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                            Bulan Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                            Bulan Lalu
                        </a>

                        <div class="dropdown-divider my-2"></div>
                        <div class="px-2 py-1 text-muted text-center small fw-bold">Custom Tanggal</div>
                        <form action="{{ route('inventory.index') }}" method="GET" class="px-2 py-1">
                            <input type="hidden" name="tab" value="stok_keluar">
                            @if(request('nid_outlet'))
                            <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                            @endif
                            @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Dari</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1">Sampai</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">Terapkan Tanggal</button>
                        </form>
                    </div>
                </div>

                <!-- Tombol Export -->
                <!-- <button type="button" class="btn btn-primary btn-sm px-3" onclick="alert('Fitur export data stok keluar siap diintegrasikan.')">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Export
                </button> -->

                <!-- Tombol Import -->
                <!-- <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="alert('Fitur import data stok keluar siap diintegrasikan.')">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Import
                </button> -->

                <!-- Tombol + Tambah dengan Dropdown (Persis Olsera Image 2) -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-success btn-sm px-3 dropdown-toggle shadow-sm fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="javascript:void(0)" onclick="openStockOutModal('TRANSFER')">
                                <i class="bi bi-arrow-left-right me-2 text-primary"></i> Transfer ke toko
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="javascript:void(0)" onclick="openStockOutModal('OUT')">
                                <i class="bi bi-box-arrow-up me-2 text-danger"></i> Tambah Stok Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Baris Filter Baris & Pencarian No Stok Keluar (Persis Olsera) -->
        <!-- <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <form action="{{ route('inventory.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="stok_keluar">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if(request('nid_outlet'))
                    <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                    @endif
                    @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <select name="per_page" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Baris</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 Baris</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Baris</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Baris</option>
                    </select>
                </form>
            </div>

            <div>
                <form action="{{ route('inventory.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="stok_keluar">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if(request('nid_outlet'))
                    <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                    @endif
                    @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari No. Stok Keluar..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div> -->

        <!-- Tabel Daftar Stok Keluar (Persis Kolom Olsera Image 1 & Image 5) -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light">
                    <tr class="text-secondary small">
                        <th class="py-3 px-3">No. <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3">Transfer ke <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3">Tanggal <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3">Catatan <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3">Alasan <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3 text-center">Status <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                        <th class="py-3">Diterima / Dibuat Oleh</th>
                        <th class="py-3 text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($stockOuts as $so)
                    <tr>
                        <td class="px-3">
                            <a href="{{ route('inventory.stock-out.show', $so->cno_stock_out) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $so->cno_stock_out }}
                            </a>
                        </td>
                        <td class="text-secondary">
                            @if($so->targetOutlet)
                            <span class="fw-semibold text-primary"><i class="bi bi-shop me-1"></i>{{ $so->targetOutlet->cname }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-dark fw-semibold">
                            {{ $so->dstock_out ? \Carbon\Carbon::parse($so->dstock_out)->format('d-M-Y') : '-' }}
                        </td>
                        <td class="text-secondary">
                            {{ $so->cnote ?: '-' }}
                        </td>
                        <td class="text-secondary">
                            {{ $so->creason ?: '-' }}
                        </td>
                        <td class="text-center">
                            @if(strtoupper($so->cstatus) === 'POSTED')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Posted</span>
                            @elseif(strtoupper($so->cstatus) === 'DRAFT')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">Draft</span>
                            @else
                            <span class="badge bg-secondary px-2 py-1 rounded-pill">{{ $so->cstatus }}</span>
                            @endif
                        </td>
                        <td class="text-secondary">
                            {{ $so->user?->email ?: ($so->user?->cname ?: 'admin') }}
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('inventory.stock-out.show', $so->cno_stock_out) }}" class="btn btn-outline-secondary btn-sm" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('inventory.stock-out.print', $so->cno_stock_out) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Cetak Nota">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Tidak ada riwayat transaksi stok keluar pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        @if($stockOuts && $stockOuts->hasPages())
        <div class="mt-3">
            {{ $stockOuts->links() }}
        </div>
        @endif

        <!-- ==================== MODAL TAMBAH STOK KELUAR (MIRIP MODAL PEMBELIAN BARU) ==================== -->
        <div class="modal fade" id="createStockOutModal" tabindex="-1" aria-labelledby="createStockOutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('inventory.stock-out.store') }}" method="POST" id="createStockOutForm">
                        @csrf
                        <input type="hidden" name="ctype" id="stockOutTypeInput" value="OUT">

                        <div class="modal-header bg-light-subtle py-3 border-bottom">
                            <h5 class="modal-title fw-bold" id="createStockOutModalLabel">
                                <i class="bi bi-box-arrow-up me-2 text-danger"></i><span id="stockOutModalTitle">Tambah Stok Keluar</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- Baris 1: Outlet Asal, Outlet Tujuan (jika transfer), Tanggal, Alasan -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-secondary">Outlet Asal <span class="text-danger">*</span></label>
                                    <select name="nid_outlet" id="modalStockOutOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
                                        @foreach($outlets as $ot)
                                        <option value="{{ $ot->nid }}" {{ (string)($selectedOutletId ?: $outlets->first()?->nid) === (string)$ot->nid ? 'selected' : '' }}>
                                            {{ $ot->cname }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if($isOutletLocked)
                                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                                    @endif
                                </div>

                                <div class="col-md-3" id="wrapperTargetOutlet" style="display: none;">
                                    <label class="form-label fw-semibold small text-secondary">Transfer ke Outlet <span class="text-danger">*</span></label>
                                    <select name="nid_target_outlet" id="modalStockOutTargetOutlet" class="form-select">
                                        <option value="">-- Pilih Outlet Tujuan --</option>
                                        @foreach($outlets as $ot)
                                        <option value="{{ $ot->nid }}">{{ $ot->cname }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-secondary">Tanggal Keluar <span class="text-danger">*</span></label>
                                    <input type="date" name="dstock_out" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-3" id="wrapperStockOutReason">
                                    <label class="form-label fw-semibold small text-secondary">Alasan Pengeluaran <span class="text-danger">*</span></label>
                                    <select name="creason" id="modalStockOutReason" class="form-select">
                                        <option value="Rusak / Cacat">Rusak / Cacat</option>
                                        <option value="Kedaluwarsa">Kedaluwarsa</option>
                                        <option value="Pemakaian Bar / Kitchen">Pemakaian Bar / Kitchen</option>
                                        <option value="Pemakaian Internal / Promosi">Pemakaian Internal / Promosi</option>
                                        <option value="Koreksi Selisih Stok">Koreksi Selisih Stok</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Baris 2: Catatan Pengeluaran -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">Catatan Pengeluaran</label>
                                    <textarea name="cnote" id="modalStockOutNote" class="form-control" rows="2" placeholder="Catatan stok keluar (misal: SALAH INPUT BARANG DI INVOICE, BARANG GUDANG DIAMBIL SERVER, dll)..."></textarea>
                                </div>
                            </div>

                            <!-- Header Section Item Stok Keluar (Persis Modal Pembelian) -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <div>
                                    <span class="fw-bold text-dark fs-6">
                                        <i class="bi bi-box-seam me-1 text-danger"></i> Item Barang Keluar
                                    </span>
                                    <span class="text-muted small ms-2 d-none d-sm-inline">Pilih tipe <strong>Produk</strong> atau <strong>Bahan Baku</strong> yang dikeluarkan</span>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold shadow-sm" id="btnAddNewStockOutRow">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                                </button>
                            </div>

                            <!-- Table Item Dinamis (Persis Modal Pembelian) -->
                            <div class="table-responsive mt-2" style="overflow: visible;">
                                <table class="table align-middle mb-0" id="stockOutItemsTable">
                                    <thead>
                                        <tr class="bg-light">
                                            <th style="width: 35px;" class="text-center py-2 text-muted text-nowrap">No</th>
                                            <th style="width: 140px;" class="py-2 text-center text-nowrap">TIPE ITEM</th>
                                            <th class="py-2 text-center text-nowrap">PRODUK / BAHAN BAKU</th>
                                            <th style="width: 110px;" class="text-center py-2 text-nowrap">STOK SAAT INI</th>
                                            <th style="width: 90px;" class="text-center py-2 text-nowrap">QTY KELUAR</th>
                                            <th style="width: 140px;" class="text-center py-1 text-nowrap">HARGA MODAL</th>
                                            <th style="width: 130px;" class="text-end py-2 text-nowrap">SUBTOTAL</th>
                                            <th style="width: 35px;" class="text-center py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="stockOutItemsTableBody" class="small">
                                        <!-- Baris produk / bahan baku di-generate via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Baris Ringkasan Total Kuantitas & Nilai Modal -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Stok produk atau bahan baku akan langsung dipotong secara otomatis setelah disimpan.
                                </div>
                                <div class="d-flex align-items-center gap-4 text-end">
                                    <div>
                                        <span class="text-secondary small d-block">Total Kuantitas:</span>
                                        <span class="fw-bold fs-6 text-dark" id="stockOutTotalQty">0</span> <span class="small text-muted">unit</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary small d-block">Total Nilai Modal:</span>
                                        <span class="fw-bold fs-5 text-danger" id="stockOutTotalAmount">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Stok Keluar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Data Master Produk & Bahan Baku untuk Modal Stok Keluar
            const stockOutProducts = @json($modalProducts);
            const stockOutIngredients = @json($modalIngredients);

            let stockOutRowCounter = 0;

            function openStockOutModal(type) {
                const modalEl = document.getElementById('createStockOutModal');
                const modal = new bootstrap.Modal(modalEl);
                const titleSpan = document.getElementById('stockOutModalTitle');
                const typeInput = document.getElementById('stockOutTypeInput');
                const wrapperTarget = document.getElementById('wrapperTargetOutlet');
                const selectTarget = document.getElementById('modalStockOutTargetOutlet');
                const selectReason = document.getElementById('modalStockOutReason');

                if (type === 'TRANSFER') {
                    titleSpan.textContent = 'Transfer Stok ke Toko';
                    typeInput.value = 'TRANSFER';
                    wrapperTarget.style.display = 'block';
                    selectTarget.required = true;
                    selectReason.value = 'Transfer Antar Toko';
                } else {
                    titleSpan.textContent = 'Tambah Stok Keluar';
                    typeInput.value = 'OUT';
                    wrapperTarget.style.display = 'none';
                    selectTarget.required = false;
                    selectTarget.value = '';
                    selectReason.value = 'Rusak / Cacat';
                }

                // Reset baris tabel jika kosong
                const tbody = document.getElementById('stockOutItemsTableBody');
                if (tbody.children.length === 0) {
                    addStockOutRow();
                }

                modal.show();
            }

            function addStockOutRow() {
                stockOutRowCounter++;
                const tbody = document.getElementById('stockOutItemsTableBody');
                const tr = document.createElement('tr');
                tr.id = 'stock-out-row-' + stockOutRowCounter;

                tr.innerHTML = `
                    <td class="text-center text-muted fw-semibold row-number">${tbody.children.length + 1}</td>
                    <td>
                        <select name="items[${stockOutRowCounter}][citem_type]" class="form-select form-select-sm item-type-select" onchange="handleStockOutTypeChange(${stockOutRowCounter})">
                            <option value="PRODUCT" selected>Produk</option>
                            <option value="INGREDIENT">Bahan Baku</option>
                        </select>
                    </td>
                    <td>
                        <select name="items[${stockOutRowCounter}][nid_item]" class="form-select form-select-sm item-select" required onchange="handleStockOutItemSelect(${stockOutRowCounter})">
                            <option value="">-- Pilih Item --</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-secondary border current-stock-badge">-</span>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" name="items[${stockOutRowCounter}][nqty]" class="form-control form-control-sm text-center item-qty-input" value="1" required oninput="calculateStockOutTotals()">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" step="1" min="0" name="items[${stockOutRowCounter}][ncost]" class="form-control form-control-sm text-end item-cost-input" value="0" oninput="calculateStockOutTotals()">
                        </div>
                    </td>
                    <td class="text-end fw-semibold text-danger item-subtotal-display">
                        Rp 0
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm border-0 py-0 px-1" onclick="removeStockOutRow(${stockOutRowCounter})" title="Hapus Baris">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
                populateStockOutItemDropdown(stockOutRowCounter, 'PRODUCT');
                renumberStockOutRows();
                calculateStockOutTotals();
            }

            function removeStockOutRow(rowId) {
                const row = document.getElementById('stock-out-row-' + rowId);
                if (row) {
                    row.remove();
                    renumberStockOutRows();
                    calculateStockOutTotals();
                }
            }

            function renumberStockOutRows() {
                const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
                rows.forEach((r, idx) => {
                    const numCell = r.querySelector('.row-number');
                    if (numCell) numCell.textContent = idx + 1;
                });
            }

            function handleStockOutTypeChange(rowId) {
                const row = document.getElementById('stock-out-row-' + rowId);
                const typeSelect = row.querySelector('.item-type-select');
                populateStockOutItemDropdown(rowId, typeSelect.value);
            }

            function populateStockOutItemDropdown(rowId, type) {
                const row = document.getElementById('stock-out-row-' + rowId);
                const itemSelect = row.querySelector('.item-select');
                itemSelect.innerHTML = '<option value="">-- Cari atau pilih ' + (type === 'PRODUCT' ? 'produk' : 'bahan baku') + ' --</option>';

                if (type === 'PRODUCT') {
                    stockOutProducts.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.nid;
                        opt.textContent = p.cname + ' (Stok: ' + (p.nqty || 0) + ')';
                        opt.dataset.stock = p.nqty || 0;
                        opt.dataset.cost = p.nprice_buy || 0;
                        itemSelect.appendChild(opt);
                    });
                } else {
                    stockOutIngredients.forEach(ing => {
                        const opt = document.createElement('option');
                        opt.value = ing.nid;
                        const satuan = ing.csatuan ? ' ' + ing.csatuan : '';
                        opt.textContent = ing.cname + ' (Stok: ' + (ing.nstock || 0) + satuan + ')';
                        opt.dataset.stock = ing.nstock || 0;
                        opt.dataset.cost = ing.ncost || 0;
                        itemSelect.appendChild(opt);
                    });
                }

                // Reset nilai baris
                const stockBadge = row.querySelector('.current-stock-badge');
                stockBadge.textContent = '-';
                stockBadge.className = 'badge bg-light text-secondary border current-stock-badge';
                row.querySelector('.item-cost-input').value = 0;
                calculateStockOutTotals();
            }

            function handleStockOutItemSelect(rowId) {
                const row = document.getElementById('stock-out-row-' + rowId);
                const itemSelect = row.querySelector('.item-select');
                const selectedOpt = itemSelect.options[itemSelect.selectedIndex];
                const stockBadge = row.querySelector('.current-stock-badge');
                const costInput = row.querySelector('.item-cost-input');

                if (selectedOpt && selectedOpt.value) {
                    const stock = parseFloat(selectedOpt.dataset.stock || 0);
                    const cost = parseFloat(selectedOpt.dataset.cost || 0);

                    stockBadge.textContent = stock;
                    if (stock <= 0) {
                        stockBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle current-stock-badge';
                    } else if (stock <= 5) {
                        stockBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle current-stock-badge';
                    } else {
                        stockBadge.className = 'badge bg-success-subtle text-success border border-success-subtle current-stock-badge';
                    }

                    costInput.value = Math.round(cost);
                } else {
                    stockBadge.textContent = '-';
                    stockBadge.className = 'badge bg-light text-secondary border current-stock-badge';
                    costInput.value = 0;
                }

                calculateStockOutTotals();
            }

            function calculateStockOutTotals() {
                const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
                let grandQty = 0;
                let grandAmount = 0;

                rows.forEach(r => {
                    const qtyInput = r.querySelector('.item-qty-input');
                    const costInput = r.querySelector('.item-cost-input');
                    const subtotalDisplay = r.querySelector('.item-subtotal-display');

                    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
                    const cost = parseFloat(costInput ? costInput.value : 0) || 0;
                    const subtotal = qty * cost;

                    grandQty += qty;
                    grandAmount += subtotal;

                    if (subtotalDisplay) {
                        subtotalDisplay.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
                    }
                });

                const totalQtyEl = document.getElementById('stockOutTotalQty');
                const totalAmountEl = document.getElementById('stockOutTotalAmount');

                if (totalQtyEl) totalQtyEl.textContent = grandQty.toFixed(grandQty % 1 === 0 ? 0 : 2);
                if (totalAmountEl) totalAmountEl.textContent = 'Rp ' + Math.round(grandAmount).toLocaleString('id-ID');
            }

            document.addEventListener('DOMContentLoaded', function() {
                const btnAdd = document.getElementById('btnAddNewStockOutRow');
                if (btnAdd) {
                    btnAdd.addEventListener('click', addStockOutRow);
                }
            });
        </script>

        {{-- ==================== TAB 3: PRODUKSI STOK ==================== --}}
        @elseif($activeTab === 'produksi_stok')
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h5 class="fw-bold mb-1">Produksi Stok</h5>
                <p class="text-muted small mb-0">Konversi bahan baku menjadi produk jadi melalui resep produksi.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> Buat Produksi Baru
                </button>
            </div>
        </div>

        <div class="card bg-light border-0 p-5 text-center my-3">
            <div class="mb-3">
                <i class="bi bi-gear-wide-connected text-warning" style="font-size: 3rem;"></i>
            </div>
            <h6 class="fw-bold text-dark">Fitur Produksi Stok</h6>
            <p class="text-muted small mx-auto" style="max-width: 500px;">
                Modul produksi stok disiapkan untuk memotong stok bahan baku dan menambahkan stok produk jadi secara otomatis berdasarkan resep.
            </p>
        </div>

        {{-- ==================== TAB 4: STOK OPNAME ==================== --}}
        @elseif($activeTab === 'stok_opname')
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h5 class="fw-bold mb-1">Stok Opname</h5>
                <p class="text-muted small mb-0">Lakukan penyesuaian antara stok fisik di outlet dan stok sistem.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> Mulai Stok Opname
                </button>
            </div>
        </div>

        <div class="card bg-light border-0 p-5 text-center my-3">
            <div class="mb-3">
                <i class="bi bi-clipboard-check text-success" style="font-size: 3rem;"></i>
            </div>
            <h6 class="fw-bold text-dark">Fitur Stok Opname</h6>
            <p class="text-muted small mx-auto" style="max-width: 500px;">
                Modul stok opname disiapkan untuk verifikasi fisik berkala dan penyesuaian selisih stok secara otomatis.
            </p>
        </div>

        {{-- ==================== TAB 5: PERGERAKAN STOK ==================== --}}
        @elseif($activeTab === 'pergerakan_stok')
        <!-- Baris Header Tab: Judul & Subtitle -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
            <div>
                <h5 class="fw-bold mb-1">Pergerakan Stok</h5>
                <div class="text-muted small"><span id="movement-total-count">{{ $products->total() }}</span> Item Terdata</div>
            </div>

            <div>
                <button type="button" class="btn btn-success btn-sm px-3" onclick="alert('Fitur download excel pergerakan stok siap diintegrasikan.')">
                    <i class="bi bi-download me-1"></i> Download Excel
                </button>
            </div>
        </div>

        <!-- Baris FILTER Persis Gaya Halaman Laporan Penjualan -->
        <div class="px-3 py-2 bg-light rounded-3 border mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="position: relative; z-index: 20;">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold me-1 text-secondary" style="font-size: 0.82rem; text-transform: uppercase;">FILTER :</span>

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
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $today, 'end_date' => $today])) }}">
                            Hari Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                            Kemarin
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                            7 Hari Lalu
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                            Bulan Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                            Bulan Lalu
                        </a>

                        <hr class="dropdown-divider my-2">
                        <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                        <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                            <input type="hidden" name="tab" value="pergerakan_stok">
                            @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                            @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif

                            <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                                </div>
                                <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                    <button type="button" class="btn btn-link p-1 border-0 sync-date-btn dropdown-sync-date-btn" title="Samakan Tanggal">
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

                @if(!$isOutletLocked && count($outlets) > 1)
                <form method="GET" action="{{ route('inventory.index') }}" class="d-inline">
                    <input type="hidden" name="tab" value="pergerakan_stok">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif

                    <select name="nid_outlet" class="form-select form-select-sm" style="width: auto; border-radius: 20px; font-size: 0.84rem;" onchange="this.form.submit()">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $o)
                        <option value="{{ $o->nid }}" {{ (string)$selectedOutletId === (string)$o->nid ? 'selected' : '' }}>
                            {{ $o->cname }}
                        </option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <div>
                <form id="movementSearchForm" method="GET" action="{{ route('inventory.index') }}" onsubmit="return false;">
                    <input type="hidden" name="tab" value="pergerakan_stok">
                    <input type="hidden" name="start_date" id="movementStartDate" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" id="movementEndDate" value="{{ $endDate }}">
                    @if($selectedOutletId)
                    <input type="hidden" name="nid_outlet" id="movementOutletId" value="{{ $selectedOutletId }}">
                    @endif
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="movementSearchInput" name="search" class="form-control" placeholder="Cari produk..." value="{{ $search }}" autocomplete="off">
                        <span class="input-group-text bg-white" id="movementSearchSpinner" style="display: none;">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                        </span>
                        <button class="btn btn-outline-secondary" type="button" id="movementSearchBtn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Container Tabel Pergerakan Stok untuk Auto-Fetching AJAX -->
        <div id="movement-container" style="position: relative; transition: opacity 0.2s ease;">
            @include('inventory.partials.movement_table', ['products' => $products])
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Sync Date Button pada dropdown custom tanggal
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

        // Auto-fetching Live Search untuk Pergerakan Stok
        const movementSearchInput = document.getElementById('movementSearchInput');
        const movementContainer = document.getElementById('movement-container');
        const movementTotalCount = document.getElementById('movement-total-count');
        const movementSpinner = document.getElementById('movementSearchSpinner');
        const movementSearchBtn = document.getElementById('movementSearchBtn');
        let movementDebounceTimer = null;
        let movementAbortController = null;

        function fetchMovementData(fetchUrl = null) {
            if (!movementContainer) return;

            if (movementAbortController) {
                movementAbortController.abort();
            }
            movementAbortController = new AbortController();

            const searchVal = movementSearchInput ? movementSearchInput.value.trim() : '';
            const startDate = document.getElementById('movementStartDate')?.value || '{{ $startDate }}';
            const endDate = document.getElementById('movementEndDate')?.value || '{{ $endDate }}';
            const outletId = document.getElementById('movementOutletId')?.value || '{{ $selectedOutletId }}';

            let url;
            if (fetchUrl) {
                url = new URL(fetchUrl, window.location.origin);
            } else {
                url = new URL('{{ route("inventory.index") }}', window.location.origin);
                url.searchParams.set('tab', 'pergerakan_stok');
                url.searchParams.set('start_date', startDate);
                url.searchParams.set('end_date', endDate);
                if (outletId) url.searchParams.set('nid_outlet', outletId);
                if (searchVal) {
                    url.searchParams.set('search', searchVal);
                } else {
                    url.searchParams.delete('search');
                }
            }

            if (movementSpinner) movementSpinner.style.display = 'inline-flex';
            if (movementSearchBtn) movementSearchBtn.style.display = 'none';
            movementContainer.style.opacity = '0.5';

            fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: movementAbortController.signal
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data && data.html) {
                        movementContainer.innerHTML = data.html;
                        if (movementTotalCount && data.total_count !== undefined) {
                            movementTotalCount.textContent = data.total_count;
                        }
                        window.history.replaceState({}, '', url.toString());
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Error fetching movement data:', err);
                    }
                })
                .finally(() => {
                    if (movementSpinner) movementSpinner.style.display = 'none';
                    if (movementSearchBtn) movementSearchBtn.style.display = 'inline-flex';
                    movementContainer.style.opacity = '1';
                });
        }

        if (movementSearchInput) {
            movementSearchInput.addEventListener('input', function() {
                clearTimeout(movementDebounceTimer);
                movementDebounceTimer = setTimeout(() => {
                    fetchMovementData();
                }, 350);
            });

            movementSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(movementDebounceTimer);
                    fetchMovementData();
                }
            });
        }

        if (movementSearchBtn) {
            movementSearchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearTimeout(movementDebounceTimer);
                fetchMovementData();
            });
        }

        // Intercept pagination clicks di dalam movementContainer
        if (movementContainer) {
            movementContainer.addEventListener('click', function(e) {
                const pageLink = e.target.closest('a.page-link');
                if (pageLink && pageLink.href) {
                    e.preventDefault();
                    fetchMovementData(pageLink.href);
                }
            });
        }
    });
</script>
@endsection