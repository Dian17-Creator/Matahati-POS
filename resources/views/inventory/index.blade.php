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
                    <button class="btn btn-primary btn-sm px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <a href="{{ route('inventory.stock-in.show', $item->nid) }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $item->cno_stock_in }}
                            </a>
                        </td>
                        <td class="text-muted small">
                            {{ $item->outlet?->cname ?? '-' }}
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
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h5 class="fw-bold mb-1">Stok Keluar</h5>
                <p class="text-muted small mb-0">Kelola dan pantau barang keluar karena kerusakan, kedaluwarsa, atau retur.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Stok Keluar
                </button>
            </div>
        </div>

        <div class="card bg-light border-0 p-5 text-center my-3">
            <div class="mb-3">
                <i class="bi bi-box-arrow-up text-danger" style="font-size: 3rem;"></i>
            </div>
            <h6 class="fw-bold text-dark">Fitur Stok Keluar</h6>
            <p class="text-muted small mx-auto" style="max-width: 500px;">
                Modul stok keluar disiapkan untuk mencatat pengeluaran stok akibat barang rusak, pemakaian internal, atau retur ke supplier.
            </p>
        </div>

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