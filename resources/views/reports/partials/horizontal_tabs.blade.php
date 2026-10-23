@php
$currentCategory = $currentCategory ?? 'penjualan';
@endphp

<div class="report-headbar-tabs bg-white border-bottom px-3 px-md-4">
    <div class="d-flex align-items-center flex-nowrap report-horizontal-nav">
        <!-- <a href="{{ route('reports.customer-product') }}"
            class="report-category-tab {{ $currentCategory === 'produk' || request()->routeIs('reports.customer-product*') ? 'active' : '' }}">
            <i class="bi bi-box-seam me-1"></i> Laporan Produk & Pelanggan
        </a>
        <span class="report-category-tab text-muted opacity-60" style="cursor: not-allowed;" title="Segera Hadir">
            <i class="bi bi-cart-check me-1"></i> Laporan Pembelian
        </span> -->
        <a href="{{ route('reports.sales-detail') }}"
            class="report-category-tab {{ $currentCategory === 'penjualan' || request()->routeIs('reports.sales-detail*', 'reports.sales-item*') ? 'active' : '' }}">
            <i class="bi bi-receipt me-1"></i> Laporan Penjualan
        </a>
        <!-- <span class="report-category-tab text-muted opacity-60" style="cursor: not-allowed;" title="Segera Hadir">
            <i class="bi bi-clock-history me-1"></i> Laporan Penjualan Per Shift
        </span>
        <span class="report-category-tab text-muted opacity-60" style="cursor: not-allowed;" title="Segera Hadir">
            <i class="bi bi-credit-card me-1"></i> Laporan Pembayaran
        </span>
        <span class="report-category-tab text-muted opacity-60" style="cursor: not-allowed;" title="Segera Hadir">
            <i class="bi bi-buildings me-1"></i> Laporan Multi Outlet
        </span> -->
    </div>
</div>

<style>
    .report-headbar-tabs {
        background-color: #fff;
        overflow: hidden;
    }

    .report-horizontal-nav {
        padding: 0;
        margin: 0;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE / Edge */
    }

    .report-horizontal-nav::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    .report-category-tab {
        padding: 12px 18px;
        font-size: 0.88rem;
        font-weight: 500;
        color: #475569;
        text-decoration: none;
        white-space: nowrap;
        border-bottom: 3px solid transparent;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }

    .report-category-tab:hover:not(.opacity-60) {
        color: #0284c7;
        background-color: #f8fafc;
    }

    .report-category-tab.active {
        color: #0284c7;
        font-weight: 700;
        background-color: transparent;
        border-bottom: 3px solid #0284c7;
    }
</style>