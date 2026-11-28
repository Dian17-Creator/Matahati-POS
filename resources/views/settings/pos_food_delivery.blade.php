@extends('layouts.app')

@section('title', 'Point Of Sales / Food Delivery')

@section('sub_header')
@include('settings.partials.pos_tabs', ['activeTab' => 'food-delivery'])
@endsection

@section('content')
<div class="container-fluid py-2">
    @if($selectedOutletId <= 0)
        <div class="alert alert-warning py-2 small mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Saat ini Anda berada pada mode <strong>Semua Outlet</strong>. Silakan pilih salah satu outlet pada dropdown di pojok kanan atas untuk mengatur Food Delivery outlet tersebut.
</div>
@else
@if(session('import_warnings'))
@php $importWarnings = session('import_warnings'); @endphp
<div class="alert alert-warning alert-dismissible fade show small" role="alert">
    <div class="fw-semibold mb-1">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        {{ $importWarnings['count'] ?? 0 }} baris dilewati saat import:
    </div>
    <ul class="mb-0 ps-3">
        @foreach(($importWarnings['reasons'] ?? []) as $reason)
        <li>{{ $reason }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Banner Hero Card (seperti Gambar 1) -->
<div class="card border-0 shadow-sm mb-3 overflow-hidden" style="background: linear-gradient(135deg, #f8ecdf 0%, #f5e6d3 100%); min-height: 175px;">
    <div class="card-body px-4 px-md-5 py-4 d-flex align-items-center justify-content-between gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Food Delivery</h4>
            <p class="mb-0 text-secondary">Atur harga jual produk untuk Ojek Online di POS</p>
        </div>
        <!-- <div class="d-none d-sm-flex align-items-center flex-shrink-0">
            <img src="{{ asset('images/ill_takeaway.svg') }}" alt="Food Delivery" style="height: 115px; width: auto; object-fit: contain;">
        </div> -->
    </div>
</div>

<!-- Daftar Platform Food Delivery -->
<div class="card olsera-panel-card">
    <div class="list-group list-group-flush">
        @php
        $brandStyles = [
        'GOFOOD' => ['bg' => '#ef4444', 'icon' => 'bi-cup-hot-fill'],
        'GRABFOOD' => ['bg' => '#22c55e', 'icon' => 'bi-bag-heart-fill'],
        'SHOPEEFOOD' => ['bg' => '#f97316', 'icon' => 'bi-basket-fill'],
        'TRAVELOKAEATS' => ['bg' => '#64748b', 'icon' => 'bi-utensils'],
        ];
        @endphp
        @foreach($platforms as $platform)
        @php
        $code = strtoupper((string) $platform->cplatform);
        $brand = $brandStyles[$code] ?? ['bg' => '#64748b', 'icon' => 'bi-shop'];
        $detailUrl = route('settings.pos.food-delivery.show', ['platform' => strtolower($code)]);
        @endphp
        <div class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between gap-3 food-delivery-row">
            <a href="{{ $detailUrl }}" class="d-flex align-items-center gap-3 text-decoration-none text-dark flex-grow-1">
                @if($code === 'GRABFOOD')
                <span class="position-relative d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    <img src="{{ asset('images/ic_kotak.svg') }}" alt="" class="position-absolute top-50 start-50 translate-middle" style="width: 44px; height: 44px; object-fit: contain;">
                    <img src="{{ asset('images/ic_food2.svg') }}" alt="{{ $platform->cname }}" class="position-relative" style="width: 18px; height: 18px; margin-top: 12px;">
                </span>
                @elseif($code === 'SHOPEEFOOD')
                <span class="position-relative d-inline-flex align-items-end justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    <svg width="36" height="38" viewBox="0 0 36 38" fill="none" xmlns="http://www.w3.org/2000/svg" class="position-absolute top-50 start-50 translate-middle">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.5 4C4.84315 4 3.43757 5.2179 3.20412 6.85206L1.20412 20.8521C1.0683 21.8028 1 22.7623 1 23.7226V31.5C1 34.2614 3.23858 36.5 6 36.5H30C32.7614 36.5 35 34.2614 35 31.5V23.7226C35 22.7623 34.9317 21.8028 34.7959 20.8521L32.7959 6.85206C32.5624 5.2179 31.1569 4 29.5 4H6.5ZM13.5 8.5C12.6716 8.5 12 9.17157 12 10C12 10.8284 12.6716 11.5 13.5 11.5H22.5C23.3284 11.5 24 10.8284 24 10C24 9.17157 23.3284 8.5 22.5 8.5H13.5Z" fill="#ee4d2d" />
                    </svg>
                    <img src="{{ asset('images/ic_food3.svg') }}" alt="{{ $platform->cname }}" class="position-relative" style="width: 18px; height: 18px; margin-bottom: 3px;">
                </span>
                @else
                @php
                $circleIcon = $code === 'GOFOOD' ? 'images/ic_food6.svg' : ($code === 'TRAVELOKAEATS' ? 'images/ic_food5.svg' : 'images/ic_food.svg');
                $circleIconSize = $code === 'GOFOOD' ? 'width: 22px; height: 22px;' : 'width: 19px; height: 19px;';
                @endphp
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: {{ $brand['bg'] }};">
                    <img src="{{ asset($circleIcon) }}" alt="{{ $platform->cname }}" style="{{ $circleIconSize }}">
                </span>
                @endif
                <div>
                    <div class="fw-semibold">{{ $platform->cname }}</div>
                    <div class="small text-muted">{{ $platform->active_products_count ?? 0 }} Produk</div>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <form method="POST" action="{{ route('settings.pos.food-delivery.toggle', ['platform' => strtolower($code)]) }}" class="d-flex align-items-center gap-2 m-0">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    <div class="form-check form-switch m-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            name="bis_active"
                            value="1"
                            title="Aktifkan / Nonaktifkan {{ $platform->cname }}"
                            onchange="this.form.submit()"
                            {{ $platform->bis_active ? 'checked' : '' }}
                            style="cursor: pointer;">
                    </div>
                </form>

                @if($platform->bis_active)
                <span class="badge rounded-1 px-2 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0284c7; min-width: 78px;">Aktif</span>
                @else
                <span class="badge rounded-1 px-2 py-1 fw-semibold" style="background-color: #f1f5f9; color: #64748b; min-width: 78px;">Tidak aktif</span>
                @endif

                <a href="{{ $detailUrl }}" class="text-muted text-decoration-none ps-1" title="Atur Produk & Harga {{ $platform->cname }}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
</div>

@if($selectedOutletId > 0)
@include('settings.partials.food_delivery_import_modal', [
'importAction' => route('settings.pos.food-delivery.import-all'),
'selectedOutletId' => $selectedOutletId,
'selectedOutlet' => $selectedOutlet,
])
@endif

<style>
    .food-delivery-row {
        transition: background-color 0.15s ease;
    }

    .food-delivery-row:hover {
        background-color: #f8fafc;
    }
</style>
@endsection