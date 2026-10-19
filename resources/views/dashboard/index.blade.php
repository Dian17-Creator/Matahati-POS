@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .sekilas-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .sekilas-left-col {
        background: linear-gradient(135deg, #b63352 0%, #c93b5d 100%);
        color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
        text-align: center;
    }

    .sekilas-right-col {
        padding: 0;
    }

    .sekilas-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 28px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .sekilas-row:hover {
        background-color: #f8fafc;
    }

    .sekilas-row:last-child {
        border-bottom: none;
    }

    .sekilas-row-label {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #475569;
        font-weight: 500;
    }

    .sekilas-row-label i {
        font-size: 1.2rem;
        margin-right: 14px;
        color: #64748b;
        width: 24px;
        text-align: center;
    }

    .sekilas-row-value {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* Modal Filter Tanggal Styling */
    .filter-date-modal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .filter-date-modal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 18px 24px;
    }

    .filter-date-modal .modal-body {
        padding: 20px 24px 24px 24px;
    }

    .preset-date-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 12px 16px;
        color: #334155;
        font-weight: 500;
        font-size: 0.95rem;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.15s ease;
        border: none;
        background: transparent;
    }

    .preset-date-btn:hover {
        background-color: #f8fafc;
        color: #b63352;
        font-weight: 600;
    }

    .preset-date-btn.active-preset {
        background-color: #fdf2f4;
        color: #b63352;
        font-weight: 700;
    }

    .sync-date-btn {
        color: #94a3b8 !important;
        transition: color 0.2s ease;
        text-decoration: none;
        border: none;
        background: transparent;
        box-shadow: none !important;
    }

    .sync-date-btn:hover,
    .sync-date-btn:focus {
        color: #b63352 !important;
        transform: none !important;
    }
</style>
@endpush

@section('content')
<!-- Welcome Card (Pertahankan) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 text-white" style="background: linear-gradient(135deg, #b63352 0%, #e04a70 100%) !important; border-radius: 14px;">
            <div class="card-body p-4 p-md-5 d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-2">Selamat Datang di Backoffice Matahati POS ✨</h3>
                    <p class="mb-0 text-white" style="opacity: 0.85; font-size: 1.05rem;">Kelola master data, pelanggan, promo, dan menu kasir Anda dengan mudah dari satu tempat.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header Sekilas & Filter Tanggal -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.15rem;">
        Sekilas {{ $activeOutletName }}
    </h5>

    <div class="d-flex align-items-center gap-2">
        @if(($isSuperAdmin ?? false) || !($isOutletLocked ?? false))
        <form action="{{ route('dashboard') }}" method="GET" class="d-inline-block">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            <select name="nid_outlet" class="form-select form-select-sm rounded-pill shadow-sm bg-white" onchange="this.form.submit()" style="font-size: 0.85rem; font-weight: 500; min-width: 160px; border-color: #cbd5e1;">
                <option value="" {{ empty($selectedOutletId) ? 'selected' : '' }}>Semua Outlet</option>
                @foreach($outlets as $outlet)
                    <option value="{{ $outlet->nid }}" {{ (string)($selectedOutletId ?? '') === (string)$outlet->nid ? 'selected' : '' }}>
                        {{ $outlet->cname }}
                    </option>
                @endforeach
            </select>
        </form>
        @endif

        <!-- Tombol Filter Tanggal (Pill) -->
        <button type="button" class="btn btn-light border bg-white shadow-sm rounded-pill px-3 py-1 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#filterTanggalModal" style="font-size: 0.85rem; font-weight: 500; border-color: #cbd5e1 !important;">
            <i class="bi bi-funnel text-secondary"></i>
            <span class="text-secondary fw-semibold">{{ $dateLabel }}</span>
        </button>
    </div>
</div>

<!-- Card Sekilas (Sesuai Desain Olsera) -->
<div class="sekilas-card">
    <div class="row g-0">
        <!-- Kolom Kiri: Sekilas Toko Anda -->
        <div class="col-12 col-md-3 sekilas-left-col">
            <i class="bi bi-brightness-high text-white" style="font-size: 2.4rem;"></i>
            <div class="fw-semibold text-white mt-2" style="font-size: 1.05rem;">
                Sekilas Toko Anda
            </div>
        </div>

        <!-- Kolom Kanan: Rincian 6 Metrik -->
        <div class="col-12 col-md-9 sekilas-right-col">
            <!-- 1. Pelanggan Baru -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-person-plus"></i>
                    <span>Pelanggan Baru</span>
                </div>
                <div class="sekilas-row-value">
                    {{ number_format($newCustomersCount, 0, ',', '.') }}
                </div>
            </div>

            <!-- 2. Pesanan -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-cart3"></i>
                    <span>Pesanan</span>
                </div>
                <div class="sekilas-row-value">
                    {{ number_format($totalPesanan, 0, ',', '.') }}
                </div>
            </div>

            <!-- 3. Pendapatan -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-graph-up"></i>
                    <span>Pendapatan</span>
                </div>
                <div class="sekilas-row-value">
                    IDR {{ number_format($totalPendapatan, 0, ',', '.') }}
                </div>
            </div>

            <!-- 4. Pengeluaran -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-cash-stack"></i>
                    <span>Pengeluaran</span>
                </div>
                <div class="sekilas-row-value">
                    IDR {{ number_format($totalPengeluaran, 0, ',', '.') }}
                </div>
            </div>

            <!-- 5. Pengembalian -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-arrow-down-left-square"></i>
                    <span>Pengembalian / Pembatalan</span>
                </div>
                <div class="sekilas-row-value">
                    IDR {{ number_format($totalPengembalian, 0, ',', '.') }}
                </div>
            </div>

            <!-- 6. Rata-rata penjualan per transaksi -->
            <div class="sekilas-row">
                <div class="sekilas-row-label">
                    <i class="bi bi-bar-chart"></i>
                    <span>Rata-rata penjualan per transaksi</span>
                </div>
                <div class="sekilas-row-value">
                    @php
                        $avgFormatted = fmod($averageSales, 1) !== 0.0 ? number_format($averageSales, 3, ',', '.') : number_format($averageSales, 0, ',', '.');
                    @endphp
                    IDR {{ $avgFormatted }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Grup Produk Terjual Terbanyak (Sesuai Referensi Olsera) -->
<div class="card shadow-sm border-0 mt-4" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4" style="font-size: 1.05rem;">Grup Produk Terjual Terbanyak</h5>

        @forelse($topGroups as $group)
            @php
                $pct = round(($group->total_qty / $maxGroupQty) * 100);
            @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-secondary" style="font-size: 0.88rem; text-transform: uppercase; letter-spacing: 0.3px;">{{ $group->category_name }}</span>
                    <span class="fw-bold text-dark" style="font-size: 0.88rem;">{{ number_format($group->total_qty, 0, ',', '.') }} Produk</span>
                </div>
                <div class="progress" style="height: 10px; background-color: #f1f5f9; border-radius: 10px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%; background-color: #0ea5e9; border-radius: 10px;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data penjualan produk pada rentang tanggal ini.
            </div>
        @endforelse
    </div>
</div>

<!-- Cards Rincian Produk per Grup (Breakdown Grid) -->
@if($topGroups->count() > 0)
<div class="row g-4 mt-1 mb-4">
    @foreach($topGroups as $group)
        @php
            $groupItems = $groupedProducts->get($group->category_id, collect());
            $maxItemQty = $groupItems->max('total_qty') ?: 1;
        @endphp
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9; border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <div>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 1rem; text-transform: uppercase;">{{ $group->category_name }}</h6>
                        <span class="text-secondary small fw-medium">{{ number_format($group->total_qty, 0, ',', '.') }} Produk</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    @forelse($groupItems as $item)
                        @php
                            $itemPct = round(($item->total_qty / $maxItemQty) * 100);
                            $minItemPct = max(2, $itemPct);
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-secondary text-truncate me-2" style="font-size: 0.85rem; font-weight: 500;" title="{{ $item->product_name }}">
                                    {{ $item->product_name }}
                                </span>
                                <span class="text-dark fw-bold text-nowrap" style="font-size: 0.85rem;">
                                    {{ number_format($item->total_qty, 0, ',', '.') }} Produk
                                </span>
                            </div>
                            <div class="progress" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $minItemPct }}%; background-color: #0ea5e9; border-radius: 10px;" aria-valuenow="{{ $itemPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small">
                            Tidak ada produk terjual.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- Modal Filter Tanggal (Sesuai Desain Gambar 3 & Gambar 4) -->
<div class="modal fade filter-date-modal" id="filterTanggalModal" tabindex="-1" aria-labelledby="filterTanggalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark" id="filterTanggalModalLabel" style="font-size: 1.15rem;">Filter Tanggal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Preset Options List (Gambar 3) -->
                @php
                    $isHariIni = ($startDate === $today && $endDate === $today);
                    $isKemarin = ($startDate === $yesterday && $endDate === $yesterday);
                    $is7Hari = ($startDate === $sevenDaysAgo && $endDate === $today);
                    $isBulanIni = ($startDate === $thisMonthStart && $endDate === $thisMonthEnd);
                    $isBulanLalu = ($startDate === $lastMonthStart && $endDate === $lastMonthEnd);
                @endphp

                <div class="d-flex flex-column gap-1 mb-2">
                    <button type="button" class="preset-date-btn {{ $isHariIni ? 'active-preset' : '' }}" data-start="{{ $today }}" data-end="{{ $today }}">
                        Hari Ini
                    </button>
                    <button type="button" class="preset-date-btn {{ $isKemarin ? 'active-preset' : '' }}" data-start="{{ $yesterday }}" data-end="{{ $yesterday }}">
                        Kemarin
                    </button>
                    <button type="button" class="preset-date-btn {{ $is7Hari ? 'active-preset' : '' }}" data-start="{{ $sevenDaysAgo }}" data-end="{{ $today }}">
                        7 Hari Terakhir
                    </button>
                    <button type="button" class="preset-date-btn {{ $isBulanIni ? 'active-preset' : '' }}" data-start="{{ $thisMonthStart }}" data-end="{{ $thisMonthEnd }}">
                        Bulan Ini
                    </button>
                    <button type="button" class="preset-date-btn {{ $isBulanLalu ? 'active-preset' : '' }}" data-start="{{ $lastMonthStart }}" data-end="{{ $lastMonthEnd }}">
                        Bulan Lalu
                    </button>
                </div>

                <hr class="my-3" style="color: #e2e8f0;">

                <!-- Form Custom Date Range (Gambar 4) -->
                <form action="{{ route('dashboard') }}" method="GET" id="filterDateForm">
                    @if(!empty($selectedOutletId))
                        <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    @endif

                    <div class="row g-2 align-items-center mb-3">
                        <div class="col">
                            <label class="form-label text-muted small mb-1 fw-semibold">Dari</label>
                            <input type="date" id="modal_start_date" name="start_date" class="form-control form-control-sm rounded-3 py-2" value="{{ $startDate }}" required>
                        </div>
                        <div class="col-auto d-flex align-items-end" style="padding-top: 22px;">
                            <button type="button" class="btn btn-link p-1 sync-date-btn" id="syncModalDateBtn" title="Samakan Tanggal">
                                <i class="bi bi-arrow-left-right fs-5"></i>
                            </button>
                        </div>
                        <div class="col">
                            <label class="form-label text-muted small mb-1 fw-semibold">Sampai</label>
                            <input type="date" id="modal_end_date" name="end_date" class="form-control form-control-sm rounded-3 py-2" value="{{ $endDate }}" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3" style="background-color: #b63352; border-color: #b63352;">
                        Terapkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Klik tombol preset langsung mengisi form dan submit
        const presetButtons = document.querySelectorAll('.preset-date-btn');
        const startDateInput = document.getElementById('modal_start_date');
        const endDateInput = document.getElementById('modal_end_date');
        const filterForm = document.getElementById('filterDateForm');

        presetButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const start = this.getAttribute('data-start');
                const end = this.getAttribute('data-end');
                if (start && end && startDateInput && endDateInput && filterForm) {
                    startDateInput.value = start;
                    endDateInput.value = end;
                    filterForm.submit();
                }
            });
        });

        // Tombol sync / panah bolak-balik untuk menyamakan tanggal Dari ke Sampai
        const syncBtn = document.getElementById('syncModalDateBtn');
        if (syncBtn && startDateInput && endDateInput) {
            syncBtn.addEventListener('click', function() {
                if (startDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
            });
        }
    });
</script>
@endpush