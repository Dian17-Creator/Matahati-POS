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
<!-- Banner Hero Card (seperti Gambar 1) -->
<div class="card border-0 shadow-sm mb-3 overflow-hidden" style="background: linear-gradient(135deg, #f8ecdf 0%, #f5e6d3 100%);">
    <div class="card-body px-4 py-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Food Delivery</h4>
            <p class="mb-0 text-secondary">Atur harga jual produk untuk Ojek Online di POS</p>
        </div>
    </div>
</div>

<!-- Daftar Platform Food Delivery -->
<div class="card border-0 shadow-sm">
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
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: {{ $brand['bg'] }};">
                    <img src="{{ asset('images/food.svg') }}" alt="{{ $platform->cname }}" style="width: 14px; height: 15px;">
                </span>
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

<style>
    .food-delivery-row {
        transition: background-color 0.15s ease;
    }

    .food-delivery-row:hover {
        background-color: #f8fafc;
    }
</style>
@endsection