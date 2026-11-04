@extends('layouts.app')

@section('title', 'Katalog Produk / Detail Produk')

@section('sub_header')
@include('products.partials.catalog_tabs', ['activeTab' => 'products'])
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .product-detail-wrapper {
        max-width: 1080px;
        margin: 0 auto;
        padding: 0.25rem 3.5rem 1.5rem;
    }

    @media (max-width: 991.98px) {
        .product-detail-wrapper {
            padding: 0.25rem 1.25rem 1rem;
        }
    }

    @media (max-width: 575.98px) {
        .product-detail-wrapper {
            padding: 0;
        }
    }

    .product-detail-card {
        background-color: #ffffff;
        border: 1px solid #d8dee4 !important;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .product-detail-card .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 0.9rem 1.25rem;
    }

    .modern-nav-container {
        width: 100%;
        display: flex;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .modern-tabs {
        width: 100%;
        background-color: #ffffff;
        border: 1px solid #d8dee4;
        border-radius: 8px;
        padding: 0.35rem;
        display: flex;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        margin: 0;
    }

    .modern-tabs .back-link {
        text-decoration: none !important;
    }

    .modern-tabs .nav-item {
        flex: 1 1 0;
        min-width: 0;
        display: flex;
        margin: 0;
    }

    .modern-tabs .nav-link {
        width: 100%;
        color: #64748b;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: 6px;
        padding: 0.65rem 0.75rem;
        transition: all 0.2s ease;
        border: none;
        background: transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        white-space: nowrap;
    }

    .modern-tabs .nav-link:hover {
        color: #1e293b;
        background-color: #f8fafc;
    }

    .modern-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #e7f1ff;
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.25);
    }

    @media (max-width: 767.98px) {
        .modern-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .modern-tabs .nav-item {
            flex: 0 0 auto;
            min-width: 130px;
        }
    }

    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.2rem;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: #212529;
        margin-bottom: 0.8rem;
    }

    .harga-box {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .harga-box .title {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .harga-box .price {
        font-size: 1.25rem;
        font-weight: 600;
        color: #0d6efd;
    }

    /* Olsera Style - Daftar Tingkat Harga */
    .olsera-price-card {
        background: #ffffff;
        border: 1px solid #d8dee4 !important;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    }

    .olsera-price-header {
        padding: 20px 26px;
        border-bottom: 1px solid #eef2f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .olsera-price-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0 0 2px 0;
        line-height: 1.3;
    }

    .olsera-price-subtitle {
        font-size: 0.78rem;
        color: #94a3b8;
        font-weight: 500;
    }

    .btn-olsera-add {
        background-color: #007ac2;
        color: #ffffff;
        font-size: 0.84rem;
        font-weight: 600;
        padding: 8px 22px;
        border-radius: 4px;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 122, 194, 0.25);
        transition: all 0.18s ease;
    }

    .btn-olsera-add:hover,
    .btn-olsera-add:focus {
        background-color: #0066a4;
        color: #ffffff;
    }

    .olsera-price-body {
        padding: 22px 26px;
    }

    .olsera-perpage-select {
        width: 170px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        padding: 7px 32px 7px 12px;
        font-size: 0.82rem;
        font-weight: 500;
        color: #334155;
        cursor: pointer;
    }

    .olsera-perpage-select:focus {
        border-color: #007ac2;
        box-shadow: 0 0 0 2px rgba(0, 122, 194, 0.12);
    }

    .olsera-price-divider {
        border: 0;
        border-top: 1px solid #eef2f6;
        opacity: 1;
        margin: 20px 0 8px;
    }

    .olsera-price-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .olsera-price-table thead th {
        background-color: #ffffff;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 12px 10px;
        border-bottom: 1px solid #eef2f6;
        white-space: nowrap;
    }

    .olsera-price-table thead th:first-child,
    .olsera-price-table tbody td:first-child {
        padding-left: 4px;
    }

    .olsera-price-table thead th:last-child,
    .olsera-price-table tbody td:last-child {
        padding-right: 4px;
    }

    .olsera-price-table tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    .olsera-tier-box {
        background-color: #f4f6f9;
        border: 1px solid #e5e9f0;
        border-radius: 4px;
        padding: 8px 14px;
        min-height: 38px;
        display: flex;
        align-items: center;
        font-size: 0.83rem;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-olsera-edit {
        background-color: #007ac2;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 6px 15px;
        border-radius: 4px;
        border: none;
        box-shadow: 0 1px 3px rgba(0, 122, 194, 0.25);
        transition: all 0.18s ease;
    }

    .btn-olsera-edit:hover,
    .btn-olsera-edit:focus {
        background-color: #0066a4;
        color: #ffffff;
    }

    .btn-olsera-delete {
        background-color: #f26666;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 6px 15px;
        border-radius: 4px;
        border: none;
        box-shadow: 0 1px 3px rgba(242, 102, 102, 0.25);
        transition: all 0.18s ease;
    }

    .btn-olsera-delete:hover,
    .btn-olsera-delete:focus {
        background-color: #e05252;
        color: #ffffff;
    }

    .olsera-pagination-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        padding-top: 16px;
        font-size: 0.82rem;
        color: #64748b;
    }

    .olsera-page-nav {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .olsera-page-btn {
        border: none;
        background: transparent;
        color: #94a3b8;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .olsera-page-btn:hover:not(:disabled) {
        color: #007ac2;
        background-color: #f1f5f9;
    }

    .olsera-page-btn:disabled {
        opacity: 0.45;
        cursor: default;
    }

    .olsera-page-btn.active {
        color: #007ac2;
        font-weight: 700;
    }

    .olsera-goto-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        font-size: 0.8rem;
    }

    .olsera-goto-input {
        width: 44px;
        height: 30px;
        text-align: center;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        outline: none;
    }

    .olsera-goto-input:focus {
        border-color: #007ac2;
    }

    /* Olsera Style - Bahan/Resep Tab & Modal */
    .olsera-recipe-group-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 0 22px;
    }

    .olsera-recipe-line-short {
        width: 24px;
        height: 1px;
        background-color: #e2e8f0;
        flex-shrink: 0;
    }

    .olsera-recipe-group-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.25px;
        white-space: nowrap;
    }

    .olsera-recipe-line-long {
        flex: 0 1 340px;
        height: 1px;
        background-color: #e2e8f0;
    }

    .olsera-recipe-card-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 8px 6px;
    }

    .olsera-recipe-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background-color: #f1f5f9;
        color: #cbd5e1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .olsera-recipe-name {
        font-size: 0.83rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        margin-bottom: 6px;
        line-height: 1.3;
    }

    .olsera-qty-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .olsera-qty-label {
        font-size: 0.78rem;
        color: #64748b;
        font-weight: 500;
    }

    .olsera-qty-stepper {
        display: inline-flex;
        align-items: stretch;
        width: 148px;
        height: 36px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background-color: #ffffff;
        overflow: hidden;
    }

    .olsera-qty-stepper:focus-within {
        border-color: #007ac2;
        box-shadow: 0 0 0 2px rgba(0, 122, 194, 0.12);
    }

    .olsera-qty-input {
        flex: 1;
        width: 100%;
        min-width: 0;
        border: none;
        outline: none;
        text-align: center;
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
        padding: 0 6px;
        background: transparent;
        -moz-appearance: textfield;
    }

    .olsera-qty-input::-webkit-outer-spin-button,
    .olsera-qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .olsera-qty-arrows {
        width: 28px;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #e2e8f0;
        background-color: #f8fafc;
        flex-shrink: 0;
    }

    .olsera-qty-arrows button {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 0.62rem;
        line-height: 1;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .olsera-qty-arrows button:first-child {
        border-bottom: 1px solid #e2e8f0;
    }

    .olsera-qty-arrows button:hover {
        background-color: #eef2f6;
        color: #334155;
    }

    .olsera-recipe-unit {
        font-size: 0.76rem;
        color: #64748b;
        margin-top: 6px;
    }

    .olsera-recipe-del-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: #ffffff;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.09);
        color: #f05252;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.18s ease;
    }

    .olsera-recipe-del-btn:hover {
        background-color: #fef2f2;
        border-color: #fecaca;
        transform: translateY(-1px);
    }

    /* Modal Tambah Bahan atau Resep (Olsera Style) */
    .olsera-recipe-modal .modal-dialog {
        max-width: 760px;
    }

    .olsera-recipe-modal .modal-content {
        border-radius: 8px;
        border: none;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
        overflow: visible;
    }

    .olsera-recipe-modal .modal-body {
        overflow: visible;
    }

    .olsera-recipe-modal .ts-wrapper {
        width: 100%;
    }

    .olsera-recipe-modal .ts-control {
        border-radius: 6px !important;
        font-size: 0.85rem !important;
        min-height: 38px !important;
        padding: 6px 12px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        box-shadow: none !important;
    }

    .olsera-recipe-modal .ts-control:focus,
    .olsera-recipe-modal .ts-control.focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.15) !important;
    }

    .olsera-recipe-modal .ts-dropdown {
        z-index: 1065 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        font-size: 0.85rem !important;
        border: 1px solid #e2e8f0 !important;
    }

    .olsera-recipe-modal .ts-dropdown .option {
        padding: 7px 12px !important;
    }

    .olsera-recipe-modal .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }

    .olsera-modal-row {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 16px;
    }

    .olsera-modal-label-col {
        flex: 0 0 42%;
        max-width: 42%;
        text-align: right;
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }

    .olsera-modal-input-col {
        flex: 1;
        min-width: 0;
    }

    .olsera-modal-control {
        font-size: 0.84rem;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        color: #1e293b;
        box-shadow: none;
    }

    .olsera-modal-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
    }

    .olsera-outlet-tagbox {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 10px;
        width: 215px;
        min-height: 68px;
        background-color: #ffffff;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        align-content: flex-start;
        gap: 6px;
    }

    .olsera-outlet-chip {
        background-color: #f4f6f9;
        color: #64748b;
        font-size: 0.74rem;
        line-height: 1.3;
        padding: 5px 8px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        max-width: 100%;
    }

    .olsera-outlet-chip .chip-remove {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background-color: #cbd5e1;
        color: #ffffff;
        font-size: 0.65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        border: none;
        padding: 0;
    }

    .olsera-outlet-chip .chip-remove:hover {
        background-color: #94a3b8;
    }

    .btn-olsera-modal-submit {
        background-color: #007ac2;
        color: #ffffff;
        font-size: 0.84rem;
        font-weight: 700;
        padding: 9px 24px;
        min-width: 195px;
        border-radius: 4px;
        border: none;
        transition: all 0.18s ease;
    }

    .btn-olsera-modal-submit:hover {
        background-color: #0066a4;
        color: #ffffff;
    }

    .btn-olsera-modal-cancel {
        background-color: #ffffff;
        color: #1e293b;
        font-size: 0.84rem;
        font-weight: 700;
        padding: 9px 24px;
        min-width: 195px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        transition: all 0.18s ease;
    }

    .btn-olsera-modal-cancel:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }

    @media (max-width: 767.98px) {
        .olsera-modal-row {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
        }

        .olsera-modal-label-col {
            flex: 1 1 100%;
            max-width: 100%;
            text-align: left;
        }

        .olsera-outlet-tagbox {
            width: 100%;
        }

        .btn-olsera-modal-submit,
        .btn-olsera-modal-cancel {
            min-width: 120px;
            flex: 1;
        }
    }
</style>
@endpush

@section('content')
@php
$sortedPrices = $product->productPrices->sortBy(['customerType.cname', 'nqty_start'])->values();
$isHargaTabActive = session('active_tab') === 'harga'
|| old('modal_id') === 'create_price'
|| (old('modal_id') && $sortedPrices->contains('nid', old('modal_id')));
$isResepTabActive = session('active_tab') === 'resep';
$isProfilTabActive = !$isHargaTabActive && !$isResepTabActive;
@endphp

<div class="product-detail-wrapper">
    <div class="modern-nav-container">
        <ul class="nav nav-pills modern-tabs" id="productDetailTabs" role="tablist">
            <li class="nav-item">
                <a href="{{ route('products.index') }}" class="nav-link back-link">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isProfilTabActive ? 'active' : '' }}" id="profil-tab" data-bs-toggle="tab" data-bs-target="#profil" type="button" role="tab" aria-controls="profil" aria-selected="{{ $isProfilTabActive ? 'true' : 'false' }}">
                    <i class="bi bi-person-lines-fill me-1"></i> Profil
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isHargaTabActive ? 'active' : '' }}" id="harga-tab" data-bs-toggle="tab" data-bs-target="#harga" type="button" role="tab" aria-controls="harga" aria-selected="{{ $isHargaTabActive ? 'true' : 'false' }}">
                    <i class="bi bi-tags-fill me-1"></i> Tingkatan Harga
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isResepTabActive ? 'active' : '' }}" id="resep-tab" data-bs-toggle="tab" data-bs-target="#resep" type="button" role="tab" aria-controls="resep" aria-selected="{{ $isResepTabActive ? 'true' : 'false' }}">
                    <i class="bi bi-journal-text me-1"></i> Bahan/Resep
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="productDetailTabsContent">

        <!-- Profil Tab -->
        <div class="tab-pane fade {{ $isProfilTabActive ? 'show active' : '' }}" id="profil" role="tabpanel" aria-labelledby="profil-tab">

            <!-- Info Umum Card -->
            <div class="card product-detail-card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                    <h5 class="mb-0">Info Umum</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditInfo">
                            <i class="bi bi-pencil"></i> Ubah
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary d-none" id="btnCancelInfo">
                            Batal
                        </button>
                        <button type="submit" form="formUpdateInfo" class="btn btn-sm btn-success d-none" id="btnSaveInfo">
                            Simpan
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.update', $product->nid) }}" method="POST" enctype="multipart/form-data" id="formUpdateInfo">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_id" value="{{ $product->nid }}_info">
                        <input type="hidden" name="nprice" value="{{ $product->nprice }}">
                        <input type="hidden" name="nprice_online" value="{{ $product->nprice_online }}">
                        <input type="hidden" name="nprice_buy" value="{{ $product->nprice_buy }}">

                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                @if($product->cphotos)
                                <img src="{{ asset($product->cphotos) }}" alt="{{ $product->cname }}" class="img-fluid rounded border p-2 mb-3" style="max-height: 250px; width: 100%; object-fit: contain; background-color: #fff;">
                                @else
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded border mb-3" style="height: 250px; width: 100%;">
                                    <i class="bi bi-image" style="font-size: 4rem;"></i>
                                </div>
                                @endif
                                <div class="info-input d-none text-start">
                                    <label class="form-label small text-muted">Ganti Foto Produk</label>
                                    <input type="file" class="form-control form-control-sm" name="photo" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-label">Nama Produk</div>
                                        <div class="info-value info-display">{{ $product->cname }}</div>
                                        <input type="text" class="form-control d-none info-input mb-3" name="cname" value="{{ $product->cname }}" required>
                                        <hr class="mt-0 mb-3 info-display">
                                    </div>
                                    <div class="col-12">
                                        <div class="info-label">Kategori</div>
                                        <div class="info-value info-display">{{ $product->category->cname ?? '-' }}</div>
                                        <select class="form-select d-none info-input mb-3" name="nid_category" required>
                                            <option value="">Pilih Kategori</option>
                                            @foreach($categories as $category)
                                            <option value="{{ $category->nid }}" {{ (optional($product->category)->cname == $category->cname) ? 'selected' : '' }}>
                                                {{ $category->cname }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <hr class="mt-0 mb-3 info-display">
                                    </div>
                                    <div class="col-12">
                                        <div class="info-label">Outlet</div>
                                        <div class="info-value info-display">
                                            @php
                                            $productOutlets = \App\Models\MposProduct::where('cname', $product->cname)->with('outlet')->get();
                                            @endphp
                                            @foreach($productOutlets as $po)
                                            @if($po->outlet)
                                            <span class="badge bg-light text-dark border">{{ $po->outlet->cname }}</span>
                                            @endif
                                            @endforeach
                                        </div>
                                        <div class="dropdown d-none info-input mb-3">
                                            <button class="btn border dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                                <span class="selected-text-info">Pilih Outlet...</span>
                                            </button>
                                            <ul class="dropdown-menu w-100 p-2 shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                                @php
                                                $selectedOutletIds = $productOutlets->pluck('nid_outlet')->toArray();
                                                @endphp
                                                @foreach($outlets as $outlet)
                                                <li>
                                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center" style="cursor: pointer;">
                                                        <input class="form-check-input outlet-checkbox-info" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="info_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array($outlet->nid, $selectedOutletIds) ? 'checked' : '' }}>
                                                        <label class="form-check-label w-100 ms-2" for="info_outlet_{{ $outlet->nid }}" style="cursor: pointer;">
                                                            {{ $outlet->cname }}
                                                            @if($product->nid_outlet == $outlet->nid)
                                                            <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <hr class="mt-0 mb-3 info-display">
                                    </div>
                                    <div class="col-12">
                                        <div class="info-label">Status</div>
                                        <div class="info-value info-display">
                                            @if(strtolower($product->cstatus) == 'active' || strtolower($product->cstatus) == 'aktif' || $product->cstatus == '1')
                                            <span class="badge bg-success">{{ $product->cstatus }}</span>
                                            @else
                                            <span class="badge bg-secondary">{{ $product->cstatus }}</span>
                                            @endif
                                        </div>
                                        <select class="form-select d-none info-input mb-3" name="cstatus" required>
                                            <option value="ACTIVE" {{ strtoupper($product->cstatus) == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                            <option value="INACTIVE" {{ strtoupper($product->cstatus) == 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                                        </select>
                                        <hr class="mt-0 mb-3 info-display">
                                    </div>
                                    <div class="col-12">
                                        <div class="info-label">Kombo</div>
                                        <div class="info-value info-display">
                                            @if($product->fcombo)
                                            <span class="badge bg-info text-white">Ya</span>
                                            @else
                                            <span class="badge bg-light text-dark border">Tidak</span>
                                            @endif
                                        </div>
                                        <div class="form-check form-switch mt-2 d-none info-input mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="info_fcombo" name="fcombo" value="1" {{ $product->fcombo ? 'checked' : '' }}>
                                            <label class="form-check-label" for="info_fcombo">Jadikan Produk Kombo</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3 pt-3 border-top">
                                        <div class="row text-muted small g-2">
                                            <div class="col-sm-6">
                                                <i class="bi bi-person-check me-1 text-primary"></i> Diubah oleh:
                                                <span class="fw-semibold text-dark">{{ $product->updatedBy?->cname ?? ($product->updatedBy?->cemail ?? '-') }}</span>
                                            </div>
                                            <div class="col-sm-6">
                                                <i class="bi bi-clock-history me-1 text-primary"></i> Terakhir diupdate:
                                                <span class="fw-semibold text-dark">{{ $product->updated_at ? \Carbon\Carbon::parse($product->updated_at)->format('d M Y H:i:s') : '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Harga Card -->
            <div class="card product-detail-card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                    <h5 class="mb-0">Harga</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditHarga">
                            <i class="bi bi-pencil"></i> Ubah
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary d-none" id="btnCancelHarga">
                            Batal
                        </button>
                        <button type="submit" form="formUpdateHarga" class="btn btn-sm btn-success d-none" id="btnSaveHarga">
                            Simpan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.update', $product->nid) }}" method="POST" id="formUpdateHarga">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_id" value="{{ $product->nid }}">
                        <input type="hidden" name="cname" value="{{ $product->cname }}">
                        <input type="hidden" name="nid_category" value="{{ $product->nid_category }}">
                        <input type="hidden" name="cstatus" value="{{ $product->cstatus }}">

                        @php
                        $productOutlets = \App\Models\MposProduct::where('cname', $product->cname)->pluck('nid_outlet');
                        @endphp
                        @foreach($productOutlets as $oid)
                        <input type="hidden" name="outlet_ids[]" value="{{ $oid }}">
                        @endforeach

                        @if($product->fcombo)
                        <input type="hidden" name="fcombo" value="1">
                        @endif

                        <div class="row">
                            <div class="col-md-4">
                                <div class="harga-box mb-3">
                                    <div class="title mb-1">Harga Beli</div>
                                    <div class="price price-display">Rp {{ number_format($product->nprice_buy ?? 0, 0, ',', '.') }}</div>
                                    <input type="number" step="any" min="0" class="form-control d-none price-input" name="nprice_buy" value="{{ $product->nprice_buy ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="harga-box mb-3">
                                    <div class="title mb-1">Harga POS</div>
                                    <div class="price price-display">Rp {{ number_format($product->nprice, 0, ',', '.') }}</div>
                                    <input type="number" step="any" min="0" class="form-control d-none price-input" name="nprice" value="{{ $product->nprice }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="harga-box mb-3">
                                    <div class="title mb-1">Harga Online</div>
                                    <div class="price price-display">Rp {{ number_format($product->nprice_online, 0, ',', '.') }}</div>
                                    <input type="number" step="any" min="0" class="form-control d-none price-input" name="nprice_online" value="{{ $product->nprice_online }}">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="stock_qty" value="{{ $product->nqty }}">
                    </form>
                </div>
            </div>

            <!-- Stok Card -->
            <div class="card product-detail-card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                    <h5 class="mb-0">Stok</h5>
                    <div>
                        <!-- Tombol edit stok telah dihilangkan -->
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.update', $product->nid) }}" method="POST" id="formUpdateStok">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_id" value="{{ $product->nid }}_stok">
                        <input type="hidden" name="cname" value="{{ $product->cname }}">
                        <input type="hidden" name="nid_category" value="{{ $product->nid_category }}">
                        <input type="hidden" name="cstatus" value="{{ $product->cstatus }}">
                        <input type="hidden" name="nprice" value="{{ $product->nprice }}">
                        <input type="hidden" name="nprice_online" value="{{ $product->nprice_online }}">
                        <input type="hidden" name="nprice_buy" value="{{ $product->nprice_buy }}">

                        @foreach($productOutlets as $oid)
                        <input type="hidden" name="outlet_ids[]" value="{{ $oid }}">
                        @endforeach

                        @if($product->fcombo)
                        <input type="hidden" name="fcombo" value="1">
                        @endif

                        <div class="row">
                            <div class="col-md-12">
                                <div class="harga-box mb-0">
                                    <div class="title mb-1">Stok / Qty</div>
                                    <div class="price stok-display">
                                        {{ $product->nqty }}
                                    </div>
                                    <input type="number" min="0" class="form-control d-none stok-input" name="stock_qty" value="{{ $product->nqty }}">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Tingkatan Harga Tab -->
        <div class="tab-pane fade {{ $isHargaTabActive ? 'show active' : '' }}" id="harga" role="tabpanel" aria-labelledby="harga-tab">
            <div class="olsera-price-card mb-4">
                <!-- Header Card: Daftar Tingkat Harga & Button Tambah -->
                <div class="olsera-price-header">
                    <div>
                        <h5 class="olsera-price-title">Daftar Tingkat Harga</h5>
                        <div class="olsera-price-subtitle">{{ $sortedPrices->count() }} Tingkatan Harga</div>
                    </div>
                    <button type="button" class="btn btn-olsera-add" data-bs-toggle="modal" data-bs-target="#createPriceModal">
                        Tambah
                    </button>
                </div>

                <!-- Body Card: Filter Baris, Tabel Tingkatan Harga & Pagination -->
                <div class="olsera-price-body">
                    <div class="table-responsive">
                        <table class="olsera-price-table" id="olseraPriceTable">
                            <thead>
                                <tr>
                                    <th style="width: 23%;">Tipe Pelanggan</th>
                                    <th style="width: 22%;">Variant</th>
                                    <th style="width: 21%;">Qty Mulai</th>
                                    <th style="width: 22%;">Harga Jual</th>
                                    <th style="width: 12%;"></th>
                                </tr>
                            </thead>
                            <tbody id="olseraPriceTbody">
                                @forelse($sortedPrices as $price)
                                <tr class="olsera-price-row">
                                    <td>
                                        <div class="olsera-tier-box">{{ $price->customerType->cname ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="olsera-tier-box">Semua</div>
                                    </td>
                                    <td>
                                        <div class="olsera-tier-box">{{ $price->nqty_start }}</div>
                                    </td>
                                    <td>
                                        <div class="olsera-tier-box">IDR {{ number_format($price->nprice, 0, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <button type="button" class="btn-olsera-edit" data-bs-toggle="modal" data-bs-target="#editPriceModal{{ $price->nid }}">
                                                Ubah
                                            </button>
                                            <button type="button" class="btn-olsera-delete" data-bs-toggle="modal" data-bs-target="#deletePriceModal{{ $price->nid }}">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada tingkatan harga.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($sortedPrices->count() > 0)
                    <div class="olsera-pagination-bar" id="olseraPricePagination">
                        <div class="olsera-page-nav">
                            <button type="button" class="olsera-page-btn" id="btnPricePrev" aria-label="Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div id="pricePageNumbers" class="d-inline-flex align-items-center gap-1"></div>
                            <button type="button" class="olsera-page-btn" id="btnPriceNext" aria-label="Berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <div class="olsera-goto-wrap">
                            <span>Go to</span>
                            <input type="number" min="1" value="1" id="inputPriceGoto" class="olsera-goto-input">
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Edit & Delete Price Modals -->
            @foreach($sortedPrices as $price)
            <div class="modal fade" id="editPriceModal{{ $price->nid }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <h5 class="modal-title">Ubah Tingkat Harga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('products.prices.update', ['product' => $product->nid, 'price' => $price->nid]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="modal_id" value="{{ $price->nid }}">
                            <div class="modal-body text-start">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Pelanggan</label>
                                    <select class="form-select @if(old('modal_id') == $price->nid) @error('nid_cust_type') is-invalid @enderror @endif" name="nid_cust_type" required>
                                        <option value="">Pilih Tipe Pelanggan</option>
                                        @foreach($customerTypes as $cat)
                                        <option value="{{ $cat->nid }}" {{ (old('modal_id') == $price->nid ? old('nid_cust_type') : $price->nid_cust_type) == $cat->nid ? 'selected' : '' }}>
                                            {{ $cat->cname }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if(old('modal_id') == $price->nid) @error('nid_cust_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Qty Mulai</label>
                                    <input type="number" min="1" class="form-control @if(old('modal_id') == $price->nid) @error('nqty_start') is-invalid @enderror @endif" name="nqty_start" value="{{ old('modal_id') == $price->nid ? old('nqty_start') : $price->nqty_start }}" required>
                                    @if(old('modal_id') == $price->nid) @error('nqty_start') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Harga Jual</label>
                                    <input type="number" min="0" step="any" class="form-control @if(old('modal_id') == $price->nid) @error('nprice') is-invalid @enderror @endif" name="nprice" value="{{ old('modal_id') == $price->nid ? old('nprice') : $price->nprice }}" required>
                                    @if(old('modal_id') == $price->nid) @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-olsera-add">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deletePriceModal{{ $price->nid }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Konfirmasi Hapus</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                            <h5 class="mb-3">Apakah Anda yakin?</h5>
                            <p class="text-muted">Menghapus tingkat harga untuk tipe pelanggan <strong>{{ $price->customerType->cname ?? '-' }}</strong> mulai Qty <strong>{{ $price->nqty_start }}</strong>.</p>
                        </div>
                        <div class="modal-footer bg-light justify-content-center">
                            <form action="{{ route('products.prices.destroy', ['product' => $product->nid, 'price' => $price->nid]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Bahan/Resep Tab (Desain Olsera - Tersimpan di mpos_recipe) -->
        <div class="tab-pane fade {{ $isResepTabActive ? 'show active' : '' }}" id="resep" role="tabpanel" aria-labelledby="resep-tab">
            <div class="olsera-price-card mb-4">
                <!-- Header Card: Daftar Bahan/Resep + Button Import & Tambah -->
                <div class="olsera-price-header">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="olsera-price-title mb-0">Daftar Bahan/Resep</h5>
                            <span id="recipeSyncBadge" class="badge bg-success-subtle text-success border border-success-subtle d-none" style="font-size: 0.7rem; font-weight: 600;">
                                <i class="bi bi-check2-circle me-1"></i>Tersimpan
                            </span>
                        </div>
                        <div class="olsera-price-subtitle mt-1">
                            <span id="recipeItemCountText">{{ $product->recipes->count() }}</span> Item Bahan/Resep
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-olsera-add d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importRecipeModal">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            <span>Import</span>
                        </button>
                        <button type="button" class="btn btn-olsera-add" data-bs-toggle="modal" data-bs-target="#addRecipeModal">
                            Tambah
                        </button>
                    </div>
                </div>

                <!-- Alert Feedback Resep -->
                <div id="recipeTabAlert" class="alert alert-danger mx-4 mt-3 mb-0 py-2 small d-none" role="alert"></div>

                <!-- Body Card: Group Nama Produk, Grid Item Bahan Baku & Pagination -->
                <div class="olsera-price-body">
                    <div id="recipeGroupWrapper">
                        <div class="olsera-recipe-group-header">
                            <span class="olsera-recipe-line-short"></span>
                            <span class="olsera-recipe-group-title">{{ $product->cname }}</span>
                            <span class="olsera-recipe-line-long"></span>
                        </div>

                        <div class="row g-4" id="recipeItemsContainer">
                            <!-- Di-render secara dinamis oleh script UI Bahan/Resep -->
                        </div>
                    </div>

                    <div id="recipeEmptyState" class="text-center text-muted py-5 d-none">
                        <i class="bi bi-journal-plus fs-2 d-block mb-2 opacity-50"></i>
                        <div class="fw-semibold text-dark small mb-1">Belum ada bahan atau resep</div>
                        <div class="small">Klik tombol <strong>Tambah</strong> di kanan atas untuk menambahkan bahan baku produk ini.</div>
                    </div>

                    <!-- Pagination Bar (Centered sesuai Olsera) -->
                    <div class="olsera-pagination-bar justify-content-center mt-3" id="recipePaginationBar">
                        <div class="olsera-page-nav">
                            <button type="button" class="olsera-page-btn" id="btnRecipePrev" aria-label="Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div id="recipePageNumbers" class="d-inline-flex align-items-center gap-1"></div>
                            <button type="button" class="olsera-page-btn" id="btnRecipeNext" aria-label="Berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <div class="olsera-goto-wrap">
                            <span>Go to</span>
                            <input type="number" min="1" value="1" id="inputRecipeGoto" class="olsera-goto-input">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Bahan atau Resep (Gaya Olsera Gambar 2) -->
@php
$recipeProductOutlets = \App\Models\MposProduct::where('cname', $product->cname)->with('outlet')->get();
@endphp
<div class="modal fade olsera-recipe-modal" id="addRecipeModal" tabindex="-1" aria-labelledby="addRecipeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pt-4 pb-2 px-4">
                <h6 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.02rem;" id="addRecipeModalLabel">
                    Tambah Bahan atau Resep
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formAddRecipePreview" action="{{ route('products.recipes.store', $product->nid) }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-3">
                    <div id="recipeModalAlert" class="alert alert-danger py-2 px-3 small mb-3 d-none" role="alert"></div>

                    <!-- Row 1: Cari Bahan Baku (TomSelect Linked ke mpos_ingredients) -->
                    <div class="olsera-modal-row align-items-start">
                        <div class="olsera-modal-label-col pt-2">
                            <label for="recipeIngredientSelect" class="mb-0">Bahan Baku</label>
                        </div>
                        <div class="olsera-modal-input-col">
                            <select id="recipeIngredientSelect"
                                name="nid_ingredient"
                                class="form-select olsera-modal-control"
                                autocomplete="off"
                                required>
                                <option value="">-- Cari atau pilih bahan baku --</option>
                                @foreach($availableMaterials ?? [] as $mat)
                                <option value="{{ $mat->nid }}"
                                    data-name="{{ $mat->cname }}"
                                    data-stock="{{ $mat->nstock ?? 0 }}"
                                    data-unit="{{ $mat->csatuan ?: 'PCS' }}">
                                    {{ $mat->cname }}
                                </option>
                                @endforeach
                            </select>
                            <div id="recipeSelectedStockInfo" class="small text-muted mt-1 d-none" style="font-size: 0.76rem;"></div>
                        </div>
                    </div>

                    <hr class="my-3" style="border-color: #eef2f6; opacity: 1;">

                    <!-- Sub-header: Informasi Detail (opsional) -->
                    <div class="olsera-modal-row mb-3">
                        <div class="olsera-modal-label-col" style="color: #64748b;">
                            Informasi Detail (opsional)
                        </div>
                        <div class="olsera-modal-input-col"></div>
                    </div>

                    <!-- Row 2: Unit Pengukuran -->
                    <div class="olsera-modal-row">
                        <div class="olsera-modal-label-col">
                            <label for="recipeUnitSelect" class="mb-0">Unit Pengukuran</label>
                        </div>
                        <div class="olsera-modal-input-col">
                            <select id="recipeUnitSelect" class="form-select olsera-modal-control">
                                <option value="">Pilih</option>
                                <option value="gr">gr (Gram)</option>
                                <option value="ml">ml (Ml)</option>
                                <option value="PCS">PCS</option>
                                <option value="kg">kg (Kilogram)</option>
                                <option value="liter">liter (Liter)</option>
                                <option value="oz">oz</option>
                                <option value="sachet">sachet</option>
                                <option value="porsi">porsi</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Nilai Konversi - Unit Pengukuran -->
                    <div class="olsera-modal-row align-items-start mb-1">
                        <div class="olsera-modal-label-col"></div>
                        <div class="olsera-modal-input-col">
                            <label for="recipeConversionInput" class="d-block fw-bold text-dark mb-2" style="font-size: 0.82rem;">
                                Nilai Konversi - Unit Pengukuran
                            </label>
                            <input type="number"
                                id="recipeConversionInput"
                                class="form-control olsera-modal-control"
                                min="0.01"
                                step="any"
                                value="1"
                                placeholder="1">
                            <div class="small text-muted mt-1" style="font-size: 0.74rem;">
                                Biarkan bernilai <strong>1</strong> jika satuan sama dengan satuan dasar produk. Atur jumlah takaran di kolom <strong>Qty</strong> setelah ditambahkan.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer: Simpan di Outlet + Tombol Tambah & Batal -->
                <div class="modal-footer border-top px-4 py-3 d-flex flex-wrap align-items-end justify-content-between gap-3" style="border-color: #eef2f6 !important;">
                    <div>
                        <div class="small text-secondary mb-1" style="font-size: 0.78rem;">Simpan di:</div>
                        <div class="olsera-outlet-tagbox" id="recipeOutletChipsBox">
                            @forelse($recipeProductOutlets as $rpo)
                            @if($rpo->outlet)
                            <span class="olsera-outlet-chip">
                                <input type="hidden" name="outlet_ids[]" value="{{ $rpo->outlet->nid }}">
                                <span>{{ $rpo->outlet->cname }}</span>
                                <button type="button" class="chip-remove" title="Hapus outlet dari pilihan" onclick="this.parentElement.remove()">&times;</button>
                            </span>
                            @endif
                            @empty
                            @if($product->outlet)
                            <span class="olsera-outlet-chip">
                                <input type="hidden" name="outlet_ids[]" value="{{ $product->outlet->nid }}">
                                <span>{{ $product->outlet->cname }}</span>
                                <button type="button" class="chip-remove" title="Hapus outlet dari pilihan" onclick="this.parentElement.remove()">&times;</button>
                            </span>
                            @endif
                            @endforelse
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <button type="submit" id="btnSubmitRecipeModal" class="btn-olsera-modal-submit">
                            Tambah
                        </button>
                        <button type="button" class="btn-olsera-modal-cancel" data-bs-dismiss="modal">
                            Batal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Bahan/Resep (Preview UI) -->
<div class="modal fade" id="importRecipeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold mb-0">Import Bahan / Resep</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Pilih File Excel / CSV</label>
                    <input type="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv">
                </div>
                <div class="small text-muted">
                    Fitur sinkronisasi import bahan baku akan aktif setelah modul Inventory terhubung.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-olsera-add btn-sm px-3" data-bs-dismiss="modal">Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Price Modal -->
<div class="modal fade" id="createPriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tingkat Harga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.prices.store', $product->nid) }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create_price">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipe Pelanggan</label>
                        <select class="form-select @if(old('modal_id') == 'create_price') @error('nid_cust_type') is-invalid @enderror @endif" name="nid_cust_type" required>
                            <option value="">Pilih Tipe Pelanggan</option>
                            @foreach($customerTypes as $cat)
                            <option value="{{ $cat->nid }}" {{ old('modal_id') == 'create_price' && old('nid_cust_type') == $cat->nid ? 'selected' : '' }}>
                                {{ $cat->cname }}
                            </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create_price') @error('nid_cust_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty Mulai</label>
                        <input type="number" min="1" class="form-control @if(old('modal_id') == 'create_price') @error('nqty_start') is-invalid @enderror @endif" name="nqty_start" value="{{ old('modal_id') == 'create_price' ? old('nqty_start') : '1' }}" required>
                        @if(old('modal_id') == 'create_price') @error('nqty_start') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" min="0" step="any" class="form-control @if(old('modal_id') == 'create_price') @error('nprice') is-invalid @enderror @endif" name="nprice" value="{{ old('modal_id') == 'create_price' ? old('nprice') : '' }}" required>
                        @if(old('modal_id') == 'create_price') @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-olsera-add">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId) {
            if (modalId === 'create_price') {
                var myModal = new bootstrap.Modal(document.getElementById('createPriceModal'));
                myModal.show();
            } else if (modalId) {
                // Ignore if the error is from the inline Harga form (which has the product nid as modal_id)
                // because it doesn't use a Bootstrap Modal
                var editPriceModal = document.getElementById('editPriceModal' + modalId);
                if (editPriceModal) {
                    var myModal = new bootstrap.Modal(editPriceModal);
                    myModal.show();
                }
            }
        }

        // Olsera Table Pagination ("5 Baris" & "< 1 > Go to [ 1 ]")
        const priceRows = Array.from(document.querySelectorAll('.olsera-price-row'));
        const perPageSelect = document.getElementById('pricePerPageSelect');
        const btnPrev = document.getElementById('btnPricePrev');
        const btnNext = document.getElementById('btnPriceNext');
        const pageNumbersWrap = document.getElementById('pricePageNumbers');
        const gotoInput = document.getElementById('inputPriceGoto');

        if (priceRows.length > 0 && pageNumbersWrap) {
            let currentPage = 1;
            let perPage = perPageSelect ? (parseInt(perPageSelect.value, 10) || 10) : 10;

            function renderPriceTablePage() {
                const totalPages = Math.max(1, Math.ceil(priceRows.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                const startIdx = (currentPage - 1) * perPage;
                const endIdx = startIdx + perPage;

                priceRows.forEach(function(row, idx) {
                    row.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
                });

                pageNumbersWrap.innerHTML = '';
                for (let p = 1; p <= totalPages; p++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'olsera-page-btn' + (p === currentPage ? ' active' : '');
                    btn.textContent = p;
                    btn.addEventListener('click', function() {
                        currentPage = p;
                        renderPriceTablePage();
                    });
                    pageNumbersWrap.appendChild(btn);
                }

                if (btnPrev) btnPrev.disabled = currentPage <= 1;
                if (btnNext) btnNext.disabled = currentPage >= totalPages;
                if (gotoInput) {
                    gotoInput.max = totalPages;
                    gotoInput.value = currentPage;
                }
            }

            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    perPage = parseInt(this.value, 10) || 10;
                    currentPage = 1;
                    renderPriceTablePage();
                });
            }

            if (btnPrev) {
                btnPrev.addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        renderPriceTablePage();
                    }
                });
            }

            if (btnNext) {
                btnNext.addEventListener('click', function() {
                    const totalPages = Math.max(1, Math.ceil(priceRows.length / perPage));
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderPriceTablePage();
                    }
                });
            }

            if (gotoInput) {
                gotoInput.addEventListener('change', function() {
                    const target = parseInt(this.value, 10);
                    if (!isNaN(target)) {
                        currentPage = target;
                        renderPriceTablePage();
                    }
                });
            }

            renderPriceTablePage();
        }

        // Inline Harga Edit Logic
        const btnEditHarga = document.getElementById('btnEditHarga');
        const btnCancelHarga = document.getElementById('btnCancelHarga');
        const btnSaveHarga = document.getElementById('btnSaveHarga');
        const priceDisplays = document.querySelectorAll('.price-display');
        const priceInputs = document.querySelectorAll('.price-input');

        if (btnEditHarga) {
            btnEditHarga.addEventListener('click', function() {
                priceDisplays.forEach(d => d.classList.add('d-none'));
                priceInputs.forEach(i => i.classList.remove('d-none'));
                btnEditHarga.classList.add('d-none');
                btnCancelHarga.classList.remove('d-none');
                btnSaveHarga.classList.remove('d-none');
            });

            btnCancelHarga.addEventListener('click', function() {
                priceDisplays.forEach(d => d.classList.remove('d-none'));
                priceInputs.forEach(i => {
                    i.classList.add('d-none');
                    if (i.tagName !== 'SELECT' && i.type !== 'checkbox' && i.type !== 'radio' && i.type !== 'file') {
                        i.value = i.defaultValue;
                    }
                });
                btnEditHarga.classList.remove('d-none');
                btnCancelHarga.classList.add('d-none');
                btnSaveHarga.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}') {
                btnEditHarga.click();
            }
        }

        // Inline Stok Edit Logic
        const btnEditStok = document.getElementById('btnEditStok');
        const btnCancelStok = document.getElementById('btnCancelStok');
        const btnSaveStok = document.getElementById('btnSaveStok');
        const stokDisplays = document.querySelectorAll('.stok-display');
        const stokInputs = document.querySelectorAll('.stok-input');

        if (btnEditStok) {
            btnEditStok.addEventListener('click', function() {
                stokDisplays.forEach(d => d.classList.add('d-none'));
                stokInputs.forEach(i => i.classList.remove('d-none'));
                btnEditStok.classList.add('d-none');
                btnCancelStok.classList.remove('d-none');
                btnSaveStok.classList.remove('d-none');
            });

            btnCancelStok.addEventListener('click', function() {
                stokDisplays.forEach(d => d.classList.remove('d-none'));
                stokInputs.forEach(i => {
                    i.classList.add('d-none');
                    if (i.tagName !== 'SELECT' && i.type !== 'checkbox' && i.type !== 'radio' && i.type !== 'file') {
                        i.value = i.defaultValue;
                    }
                });
                btnEditStok.classList.remove('d-none');
                btnCancelStok.classList.add('d-none');
                btnSaveStok.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}_stok') {
                btnEditStok.click();
            }
        }

        // Inline Info Umum Edit Logic
        const btnEditInfo = document.getElementById('btnEditInfo');
        const btnCancelInfo = document.getElementById('btnCancelInfo');
        const btnSaveInfo = document.getElementById('btnSaveInfo');
        const infoDisplays = document.querySelectorAll('.info-display');
        const infoInputs = document.querySelectorAll('.info-input');

        if (btnEditInfo) {
            btnEditInfo.addEventListener('click', function() {
                infoDisplays.forEach(d => d.classList.add('d-none'));
                infoInputs.forEach(i => i.classList.remove('d-none'));
                btnEditInfo.classList.add('d-none');
                btnCancelInfo.classList.remove('d-none');
                btnSaveInfo.classList.remove('d-none');
            });

            btnCancelInfo.addEventListener('click', function() {
                infoDisplays.forEach(d => d.classList.remove('d-none'));
                infoInputs.forEach(i => {
                    i.classList.add('d-none');
                });
                btnEditInfo.classList.remove('d-none');
                btnCancelInfo.classList.add('d-none');
                btnSaveInfo.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}_info') {
                btnEditInfo.click();
            }
        }

        // Update Dropdown Outlet Text for Info Umum
        function updateInfoDropdownText() {
            const dropdown = document.querySelector('.info-input.dropdown');
            if (!dropdown) return;
            const button = dropdown.querySelector('.dropdown-toggle');
            if (!button) return;
            const checkboxes = dropdown.querySelectorAll('.outlet-checkbox-info:checked');
            const selectedText = button.querySelector('.selected-text-info');
            if (!selectedText) return;

            if (checkboxes.length === 0) {
                selectedText.textContent = 'Pilih Outlet...';
            } else if (checkboxes.length === 1) {
                selectedText.textContent = checkboxes[0].getAttribute('data-name');
            } else {
                selectedText.textContent = checkboxes.length + ' Outlet Terpilih';
            }
        }

        document.querySelectorAll('.outlet-checkbox-info').forEach(checkbox => {
            checkbox.addEventListener('change', updateInfoDropdownText);
        });

        // Initial update
        updateInfoDropdownText();

        // ============================================================
        // Sync Tab dengan URL Hash (#profil, #harga, #resep)
        // ============================================================
        const tabButtons = document.querySelectorAll('#productDetailTabs button[data-bs-toggle="tab"]');
        if (window.location.hash) {
            const hashTarget = document.querySelector(`#productDetailTabs button[data-bs-target="${window.location.hash}"]`);
            if (hashTarget) {
                bootstrap.Tab.getOrCreateInstance(hashTarget).show();
            }
        }
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function(e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target) {
                    history.replaceState(null, '', target);
                }
            });
        });

        // ============================================================
        // Bahan / Resep (Tersimpan Permanen ke mpos_recipe)
        // ============================================================
        const csrfToken = "{{ csrf_token() }}";
        const recipeStoreUrl = "{{ route('products.recipes.store', $product->nid) }}";
        const recipeBaseUrl = "{{ url('products/' . $product->nid . '/recipes') }}";

        let recipeItems = @json($existingRecipeData ?? []);
        let recipeCurrentPage = 1;
        const recipePerPage = 6;
        const qtySaveTimers = {};

        const recipeContainer = document.getElementById('recipeItemsContainer');
        const recipeGroupWrapper = document.getElementById('recipeGroupWrapper');
        const recipeEmptyState = document.getElementById('recipeEmptyState');
        const recipeCountText = document.getElementById('recipeItemCountText');
        const recipePaginationBar = document.getElementById('recipePaginationBar');
        const recipePageNumbers = document.getElementById('recipePageNumbers');
        const btnRecipePrev = document.getElementById('btnRecipePrev');
        const btnRecipeNext = document.getElementById('btnRecipeNext');
        const inputRecipeGoto = document.getElementById('inputRecipeGoto');
        const recipeSyncBadge = document.getElementById('recipeSyncBadge');
        const recipeTabAlert = document.getElementById('recipeTabAlert');
        const recipeModalAlert = document.getElementById('recipeModalAlert');
        const recipeOutletChipsBox = document.getElementById('recipeOutletChipsBox');
        const initialOutletChipsHtml = recipeOutletChipsBox ? recipeOutletChipsBox.innerHTML : '';

        let syncBadgeHideTimer = null;

        function showSyncStatus(state, message) {
            if (!recipeSyncBadge) return;
            clearTimeout(syncBadgeHideTimer);
            recipeSyncBadge.classList.remove('d-none', 'bg-success-subtle', 'text-success', 'border-success-subtle', 'bg-secondary-subtle', 'text-secondary', 'border-secondary-subtle');

            if (state === 'saving') {
                recipeSyncBadge.classList.add('bg-secondary-subtle', 'text-secondary', 'border-secondary-subtle');
                recipeSyncBadge.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 0.65rem; height: 0.65rem;"></span>' + escapeHtml(message || 'Menyimpan...');
            } else {
                recipeSyncBadge.classList.add('bg-success-subtle', 'text-success', 'border-success-subtle');
                recipeSyncBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>' + escapeHtml(message || 'Tersimpan');
                syncBadgeHideTimer = setTimeout(() => {
                    recipeSyncBadge.classList.add('d-none');
                }, 2200);
            }
        }

        function showRecipeTabError(msg) {
            if (!recipeTabAlert) return;
            if (!msg) {
                recipeTabAlert.textContent = '';
                recipeTabAlert.classList.add('d-none');
                return;
            }
            recipeTabAlert.textContent = msg;
            recipeTabAlert.classList.remove('d-none');
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        function persistRecipeQty(item) {
            if (!item || !item.nid) return;
            const recipeId = item.nid;
            clearTimeout(qtySaveTimers[recipeId]);
            showSyncStatus('saving', 'Menyimpan...');
            showRecipeTabError('');

            qtySaveTimers[recipeId] = setTimeout(function() {
                fetch(`${recipeBaseUrl}/${recipeId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            nqty: item.qty
                        })
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Gagal menyimpan perubahan Qty resep.');
                        }
                        showSyncStatus('saved', 'Tersimpan');
                    })
                    .catch(err => {
                        recipeSyncBadge?.classList.add('d-none');
                        showRecipeTabError(err.message || 'Gagal menyimpan perubahan Qty resep.');
                    });
            }, 350);
        }

        function deleteRecipeFromServer(item) {
            if (!item || !item.nid) return;
            showSyncStatus('saving', 'Menghapus...');
            showRecipeTabError('');

            fetch(`${recipeBaseUrl}/${item.nid}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Gagal menghapus bahan dari resep.');
                    }
                    recipeItems = recipeItems.filter(it => String(it.id) !== String(item.id));
                    renderRecipeItems();
                    showSyncStatus('saved', 'Terhapus');
                })
                .catch(err => {
                    recipeSyncBadge?.classList.add('d-none');
                    showRecipeTabError(err.message || 'Gagal menghapus bahan dari resep.');
                });
        }

        function renderRecipeItems() {
            if (!recipeContainer) return;

            if (recipeCountText) {
                recipeCountText.textContent = recipeItems.length;
            }

            if (recipeItems.length === 0) {
                recipeContainer.innerHTML = '';
                if (recipeGroupWrapper) recipeGroupWrapper.classList.add('d-none');
                if (recipeEmptyState) recipeEmptyState.classList.remove('d-none');
                if (recipePaginationBar) recipePaginationBar.classList.add('d-none');
                return;
            }

            if (recipeGroupWrapper) recipeGroupWrapper.classList.remove('d-none');
            if (recipeEmptyState) recipeEmptyState.classList.add('d-none');
            if (recipePaginationBar) recipePaginationBar.classList.remove('d-none');

            const totalPages = Math.max(1, Math.ceil(recipeItems.length / recipePerPage));
            if (recipeCurrentPage > totalPages) recipeCurrentPage = totalPages;
            if (recipeCurrentPage < 1) recipeCurrentPage = 1;

            const startIdx = (recipeCurrentPage - 1) * recipePerPage;
            const visibleItems = recipeItems.slice(startIdx, startIdx + recipePerPage);

            recipeContainer.innerHTML = visibleItems.map(function(item) {
                const unitText = item.unit ?
                    'Unit Pengukuran: (1 ' + escapeHtml(item.unit) + ' - ' + escapeHtml(String(item.conversion || 1)) + ')' :
                    'Unit Pengukuran: (-)';

                return `
                    <div class="col-12 col-md-6" data-recipe-id="${escapeHtml(String(item.id))}">
                        <div class="olsera-recipe-card-item">
                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                <span class="olsera-recipe-avatar">
                                    <i class="bi bi-box2-fill"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <div class="olsera-recipe-name text-truncate">${escapeHtml(item.name)}</div>
                                    <div class="olsera-qty-row">
                                        <span class="olsera-qty-label">Qty</span>
                                        <div class="olsera-qty-stepper">
                                            <input type="number"
                                                class="olsera-qty-input"
                                                value="${item.qty}"
                                                min="0.01"
                                                step="any"
                                                data-action="input-qty"
                                                data-id="${escapeHtml(String(item.id))}">
                                            <div class="olsera-qty-arrows">
                                                <button type="button" data-action="step-up" data-id="${escapeHtml(String(item.id))}" aria-label="Tambah Qty">
                                                    <i class="bi bi-chevron-up"></i>
                                                </button>
                                                <button type="button" data-action="step-down" data-id="${escapeHtml(String(item.id))}" aria-label="Kurangi Qty">
                                                    <i class="bi bi-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="olsera-recipe-unit">${unitText}</div>
                                </div>
                            </div>
                            <button type="button"
                                class="olsera-recipe-del-btn"
                                data-action="delete-recipe"
                                data-id="${escapeHtml(String(item.id))}"
                                title="Hapus bahan dari resep">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            if (recipePageNumbers) {
                recipePageNumbers.innerHTML = '';
                for (let p = 1; p <= totalPages; p++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'olsera-page-btn' + (p === recipeCurrentPage ? ' active' : '');
                    btn.textContent = p;
                    btn.addEventListener('click', function() {
                        recipeCurrentPage = p;
                        renderRecipeItems();
                    });
                    recipePageNumbers.appendChild(btn);
                }
            }

            if (btnRecipePrev) btnRecipePrev.disabled = recipeCurrentPage <= 1;
            if (btnRecipeNext) btnRecipeNext.disabled = recipeCurrentPage >= totalPages;
            if (inputRecipeGoto) {
                inputRecipeGoto.max = totalPages;
                inputRecipeGoto.value = recipeCurrentPage;
            }
        }

        if (recipeContainer) {
            recipeContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');
                const targetItem = recipeItems.find(it => String(it.id) === String(id));
                if (!targetItem) return;

                if (action === 'step-up') {
                    targetItem.qty = Number((parseFloat(targetItem.qty || 0) + 1).toFixed(2));
                    renderRecipeItems();
                    persistRecipeQty(targetItem);
                } else if (action === 'step-down') {
                    targetItem.qty = Math.max(0.01, Number((parseFloat(targetItem.qty || 1) - 1).toFixed(2)));
                    renderRecipeItems();
                    persistRecipeQty(targetItem);
                } else if (action === 'delete-recipe') {
                    deleteRecipeFromServer(targetItem);
                }
            });

            recipeContainer.addEventListener('change', function(e) {
                const input = e.target.closest('input[data-action="input-qty"]');
                if (!input) return;
                const id = input.getAttribute('data-id');
                const targetItem = recipeItems.find(it => String(it.id) === String(id));
                if (targetItem) {
                    const val = parseFloat(input.value);
                    targetItem.qty = isNaN(val) || val <= 0 ? 1 : Number(val.toFixed(2));
                    renderRecipeItems();
                    persistRecipeQty(targetItem);
                }
            });
        }

        if (btnRecipePrev) {
            btnRecipePrev.addEventListener('click', function() {
                if (recipeCurrentPage > 1) {
                    recipeCurrentPage--;
                    renderRecipeItems();
                }
            });
        }

        if (btnRecipeNext) {
            btnRecipeNext.addEventListener('click', function() {
                const totalPages = Math.max(1, Math.ceil(recipeItems.length / recipePerPage));
                if (recipeCurrentPage < totalPages) {
                    recipeCurrentPage++;
                    renderRecipeItems();
                }
            });
        }

        if (inputRecipeGoto) {
            inputRecipeGoto.addEventListener('change', function() {
                const p = parseInt(this.value, 10);
                if (!isNaN(p)) {
                    recipeCurrentPage = p;
                    renderRecipeItems();
                }
            });
        }

        // TomSelect Dropdown + Searching (Linked ke mpos_ingredients)
        const availableMaterialsMap = new Map(
            (@json($materialsJson ?? [])).map(item => [String(item.nid), item])
        );

        const recipeSelectEl = document.getElementById('recipeIngredientSelect');
        const recipeStockInfoEl = document.getElementById('recipeSelectedStockInfo');
        const recipeUnitSelectEl = document.getElementById('recipeUnitSelect');
        const recipeConvInputEl = document.getElementById('recipeConversionInput');
        const btnSubmitRecipeModal = document.getElementById('btnSubmitRecipeModal');
        let recipeTomSelect = null;

        if (recipeSelectEl && typeof TomSelect !== 'undefined') {
            recipeTomSelect = new TomSelect(recipeSelectEl, {
                create: false,
                maxItems: 1,
                placeholder: '-- Cari atau pilih bahan baku --',
                allowEmptyOption: true,
                sortField: {
                    field: 'text',
                    direction: 'asc'
                },
                render: {
                    item: function(data, escape) {
                        const meta = availableMaterialsMap.get(String(data.value));
                        const cleanName = meta ? meta.cname : (data.text || '');
                        return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                    },
                    option: function(data, escape) {
                        if (!data.value) {
                            return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                        }
                        const meta = availableMaterialsMap.get(String(data.value));
                        const cleanName = meta ? meta.cname : (data.text || '');
                        const stockLabel = meta ? `${meta.nstock} ${meta.csatuan}` : '-';
                        return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                            '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                            '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                            '</div>';
                    }
                }
            });

            recipeTomSelect.on('change', function(val) {
                const selectedMat = availableMaterialsMap.get(String(val));
                if (recipeModalAlert) {
                    recipeModalAlert.classList.add('d-none');
                    recipeModalAlert.textContent = '';
                }
                if (selectedMat) {
                    if (recipeStockInfoEl) {
                        recipeStockInfoEl.innerHTML = `<i class="bi bi-box-seam me-1 text-primary"></i>Stok saat ini: <strong class="text-dark">${escapeHtml(String(selectedMat.nstock))} ${escapeHtml(selectedMat.csatuan)}</strong> &bull; Satuan dasar: <strong class="text-dark">${escapeHtml(selectedMat.csatuan)}</strong>`;
                        recipeStockInfoEl.classList.remove('d-none');
                    }

                    // Auto-select Unit Pengukuran sesuai csatuan di mpos_ingredients
                    if (recipeUnitSelectEl && selectedMat.csatuan) {
                        const unitLower = selectedMat.csatuan.toLowerCase();
                        let matchedOption = Array.from(recipeUnitSelectEl.options).find(
                            opt => opt.value.toLowerCase() === unitLower
                        );
                        if (!matchedOption) {
                            matchedOption = new Option(selectedMat.csatuan, selectedMat.csatuan);
                            recipeUnitSelectEl.add(matchedOption);
                        }
                        recipeUnitSelectEl.value = matchedOption.value;
                    }

                    if (recipeConvInputEl) {
                        recipeConvInputEl.value = 1;
                    }
                } else {
                    if (recipeStockInfoEl) {
                        recipeStockInfoEl.innerHTML = '';
                        recipeStockInfoEl.classList.add('d-none');
                    }
                }
            });
        }

        const addModalEl = document.getElementById('addRecipeModal');
        if (addModalEl) {
            addModalEl.addEventListener('hidden.bs.modal', function() {
                if (recipeTomSelect) {
                    recipeTomSelect.clear(true);
                }
                if (recipeStockInfoEl) {
                    recipeStockInfoEl.innerHTML = '';
                    recipeStockInfoEl.classList.add('d-none');
                }
                if (recipeModalAlert) {
                    recipeModalAlert.textContent = '';
                    recipeModalAlert.classList.add('d-none');
                }
                if (recipeOutletChipsBox && initialOutletChipsHtml) {
                    recipeOutletChipsBox.innerHTML = initialOutletChipsHtml;
                }
            });
        }

        // Form Tambah Bahan atau Resep -> Simpan Permanen ke mpos_recipe
        const formAddRecipePreview = document.getElementById('formAddRecipePreview');
        if (formAddRecipePreview) {
            formAddRecipePreview.addEventListener('submit', function(e) {
                e.preventDefault();
                const selectedId = recipeTomSelect ?
                    recipeTomSelect.getValue() :
                    (recipeSelectEl ? recipeSelectEl.value : '');

                const selectedMat = availableMaterialsMap.get(String(selectedId));
                if (!selectedMat) {
                    if (recipeModalAlert) {
                        recipeModalAlert.textContent = 'Silakan pilih bahan baku terlebih dahulu.';
                        recipeModalAlert.classList.remove('d-none');
                    }
                    if (recipeTomSelect) {
                        recipeTomSelect.focus();
                    }
                    return;
                }

                const formData = new FormData(formAddRecipePreview);
                formData.set('nid_ingredient', selectedMat.nid);
                formData.set('nqty', '1');

                if (btnSubmitRecipeModal) {
                    btnSubmitRecipeModal.disabled = true;
                    btnSubmitRecipeModal.textContent = 'Menyimpan...';
                }
                if (recipeModalAlert) {
                    recipeModalAlert.classList.add('d-none');
                }

                fetch(recipeStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(async res => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Gagal menyimpan bahan atau resep.');
                        }

                        const saved = data.item;
                        const convVal = recipeConvInputEl && recipeConvInputEl.value ?
                            parseFloat(recipeConvInputEl.value) :
                            1;
                        const unitVal = recipeUnitSelectEl && recipeUnitSelectEl.value ?
                            recipeUnitSelectEl.value :
                            (saved.unit || 'PCS');

                        const existingIdx = recipeItems.findIndex(it => String(it.nid) === String(saved.nid));
                        const mappedItem = {
                            id: String(saved.nid),
                            nid: saved.nid,
                            ingredientId: saved.ingredientId || saved.productId,
                            name: saved.name,
                            qty: saved.qty,
                            unit: unitVal,
                            conversion: isNaN(convVal) || convVal <= 0 ? 1 : convVal,
                        };

                        if (existingIdx !== -1) {
                            recipeItems[existingIdx] = mappedItem;
                        } else {
                            recipeItems.push(mappedItem);
                        }

                        recipeCurrentPage = Math.ceil(recipeItems.length / recipePerPage);
                        renderRecipeItems();
                        showSyncStatus('saved', 'Tersimpan');

                        formAddRecipePreview.reset();
                        if (recipeConvInputEl) recipeConvInputEl.value = 1;
                        if (recipeTomSelect) recipeTomSelect.clear(true);
                        if (recipeStockInfoEl) {
                            recipeStockInfoEl.innerHTML = '';
                            recipeStockInfoEl.classList.add('d-none');
                        }
                        if (addModalEl) {
                            const modalInstance = bootstrap.Modal.getInstance(addModalEl) || new bootstrap.Modal(addModalEl);
                            modalInstance.hide();
                        }
                    })
                    .catch(err => {
                        if (recipeModalAlert) {
                            recipeModalAlert.textContent = err.message || 'Terjadi kesalahan saat menyimpan resep.';
                            recipeModalAlert.classList.remove('d-none');
                        }
                    })
                    .finally(() => {
                        if (btnSubmitRecipeModal) {
                            btnSubmitRecipeModal.disabled = false;
                            btnSubmitRecipeModal.textContent = 'Tambah';
                        }
                    });
            });
        }

        renderRecipeItems();
    });
</script>
@endpush