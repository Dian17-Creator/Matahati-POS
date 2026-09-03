@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <!-- Modern Welcome Banner matching sidebar color -->
        <div class="card shadow-sm border-0 text-white" style="background: linear-gradient(135deg, #b63352 0%, #e04a70 100%) !important;">
            <div class="card-body p-4 p-md-5 d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="fw-bold mb-2">Selamat Datang di Admin Matahati POS ✨</h3>
                    <p class="mb-0 text-white" style="opacity: 0.85; font-size: 1.05rem;">Kelola master data, pelanggan, promo, dan menu kasir Anda dengan mudah dari satu tempat.</p>
                </div>
                <div class="d-none d-md-block opacity-50">
                    <i class="bi bi-shop" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<h5 class="mb-3 text-secondary fw-semibold">Ringkasan Sistem</h5>
<div class="row g-4">
    <!-- Card Kategori -->
    <div class="col-md-3">
        <a href="{{ route('categories.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border h-100 hover-lift" style="border-color: #b63352 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-tags-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="card-title fw-bold text-muted mb-1">Kategori</h6>
                            <h3 class="fw-bold text-dark mb-0">{{ $counts['categories'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small mt-2 pt-2 border-top">
                        <i class="bi bi-info-circle text-primary me-1"></i> Total grup pengelompokan
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card Produk -->
    <div class="col-md-3">
        <a href="{{ route('products.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border h-100 hover-lift" style="border-color: #b63352 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-box-seam-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="card-title fw-bold text-muted mb-1">Produk</h6>
                            <h3 class="fw-bold text-dark mb-0">{{ $counts['products_total'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small mt-2 pt-2 border-top d-flex justify-content-between">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> {{ $counts['products_active'] }} Aktif</span>
                        <span><i class="bi bi-boxes text-warning me-1"></i> {{ $counts['products_combo'] }} Kombo</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card Pelanggan -->
    <div class="col-md-3">
        <a href="{{ route('customers.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border h-100 hover-lift" style="border-color: #b63352 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="card-title fw-bold text-muted mb-1">Pelanggan</h6>
                            <h3 class="fw-bold text-dark mb-0">{{ $counts['customers'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small mt-2 pt-2 border-top">
                        <i class="bi bi-person-lines-fill text-info me-1"></i> Basis data kontak
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card Voucher -->
    <div class="col-md-3">
        <a href="{{ route('vouchers.index') }}" class="text-decoration-none">
            <div class="card shadow-sm border h-100 hover-lift" style="border-color: #b63352 !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-ticket-perforated-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="card-title fw-bold text-muted mb-1">Voucher</h6>
                            <h3 class="fw-bold text-dark mb-0">{{ $counts['vouchers_total'] }}</h3>
                        </div>
                    </div>
                    <div class="text-muted small mt-2 pt-2 border-top">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> {{ $counts['vouchers_active'] }} Promo sedang aktif
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-clock-history me-2"></i>Produk Terakhir Ditambahkan</h6>
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-borderless align-middle mb-0">
                        <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                            <tr>
                                <th class="ps-4 py-3">Nama Produk</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Tipe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentProducts as $rp)
                            <tr style="border-bottom: 1px solid #f8f9fa;">
                                <td class="ps-4 fw-semibold py-3 text-dark">{{ $rp->cname }}</td>
                                <td>Rp {{ number_format($rp->nprice, 0, ',', '.') }}</td>
                                <td>
                                    @if($rp->cstatus == 'Active' || strtoupper($rp->cstatus) == 'ACTIVE' || $rp->cstatus == '1')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    @if($rp->fcombo)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-boxes me-1"></i>Kombo</span>
                                    @else
                                        <span class="badge bg-light text-dark border"><i class="bi bi-box me-1"></i>Reguler</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada produk yang ditambahkan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
