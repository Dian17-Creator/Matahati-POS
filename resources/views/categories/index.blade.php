@extends('layouts.app')

@section('title', 'Katalog Produk / Kategori Produk')

@section('sub_header')
@include('products.partials.catalog_tabs', ['activeTab' => 'categories'])
@endsection

@push('styles')
<style>
    .olsera-panel-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }

    .olsera-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .olsera-card-title {
        font-size: 0.96rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .olsera-save-btn {
        background-color: #e2e8f0;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 6px 20px;
        border-radius: 6px;
        border: none;
        transition: all 0.2s ease;
    }

    .olsera-save-btn.ready,
    .olsera-save-btn:hover {
        background-color: #b63352;
        color: #ffffff;
        box-shadow: 0 3px 8px rgba(182, 51, 82, 0.22);
    }

    .olsera-card-body {
        padding: 22px 24px;
    }

    .olsera-form-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 20px;
    }

    .olsera-field-info {
        flex: 0 0 42%;
        max-width: 42%;
        text-align: right;
        padding-top: 4px;
    }

    .olsera-field-control {
        flex: 1;
        min-width: 0;
    }

    .olsera-field-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 3px;
        line-height: 1.3;
    }

    .olsera-field-desc {
        font-size: 0.72rem;
        color: #94a3b8;
        line-height: 1.4;
    }

    .olsera-input {
        font-size: 0.84rem;
        padding: 8px 12px;
        border-color: #cbd5e1;
        border-radius: 6px;
        color: #1e293b;
    }

    .olsera-input:focus {
        border-color: #b63352;
        box-shadow: 0 0 0 3px rgba(182, 51, 82, 0.12);
    }

    .olsera-outlet-btn {
        font-size: 0.83rem;
        padding: 8px 12px;
        border-color: #cbd5e1;
        border-radius: 6px;
        color: #334155;
        background-color: #ffffff;
    }

    .olsera-outlet-btn:hover,
    .olsera-outlet-btn:focus,
    .show > .olsera-outlet-btn {
        background-color: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .olsera-section-divider {
        border: 0;
        border-top: 1px solid #f1f5f9;
        opacity: 1;
        margin: 22px 0 18px;
    }

    .olsera-switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .olsera-switch-row .olsera-field-info {
        flex: 0 0 72%;
        max-width: 72%;
        padding-top: 0;
    }

    .form-check-input.olsera-switch {
        width: 2.6em;
        height: 1.4em;
        cursor: pointer;
    }

    .form-check-input.olsera-switch:checked {
        background-color: #b63352;
        border-color: #b63352;
    }

    .form-check-input.olsera-switch:focus {
        border-color: #b63352;
        box-shadow: 0 0 0 0.2rem rgba(182, 51, 82, 0.18);
    }

    /* Right Card: Search & Category List */
    .olsera-search-box {
        position: relative;
    }

    .olsera-search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
    }

    .olsera-search-box input {
        width: 100%;
        padding: 8px 12px 8px 34px;
        font-size: 0.83rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        color: #1e293b;
        outline: none;
        transition: all 0.2s ease;
    }

    .olsera-search-box input:focus {
        border-color: #b63352;
        box-shadow: 0 0 0 3px rgba(182, 51, 82, 0.1);
    }

    .olsera-category-list {
        max-height: 620px;
        overflow-y: auto;
    }

    .olsera-category-list::-webkit-scrollbar {
        width: 5px;
    }

    .olsera-category-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .olsera-category-item {
        background: #ffffff;
        border: 1px solid #eef2f6;
        border-radius: 6px;
        padding: 13px 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
        transition: all 0.18s ease;
        user-select: none;
    }

    .olsera-category-item:last-child {
        margin-bottom: 0;
    }

    .olsera-category-item:hover {
        border-color: #cbd5e1;
        background-color: #fcfcfd;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        transform: translateY(-1px);
    }

    .olsera-cat-drag-icon {
        color: #334155;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .olsera-cat-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 0.1px;
    }

    .olsera-cat-count {
        font-size: 0.8rem;
        font-weight: 500;
        color: #94a3b8;
        margin-left: 3px;
    }

    .olsera-cat-chevron {
        font-size: 0.78rem;
        color: #b63352;
        flex-shrink: 0;
        transition: transform 0.18s ease;
    }

    .olsera-category-item:hover .olsera-cat-chevron {
        transform: translateX(2px);
    }

    /* Modal Styling */
    .btn-maroon {
        background-color: #b63352;
        border-color: #b63352;
        color: #ffffff;
        font-weight: 600;
    }

    .btn-maroon:hover,
    .btn-maroon:focus {
        background-color: #9e2a46;
        border-color: #9e2a46;
        color: #ffffff;
    }

    .detail-meta-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }

    @media (max-width: 575.98px) {
        .olsera-form-row,
        .olsera-switch-row {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .olsera-field-info,
        .olsera-switch-row .olsera-field-info {
            flex: 1 1 100%;
            max-width: 100%;
            text-align: left;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">
    <div class="row g-4 justify-content-center align-items-start">

        <!-- CARD KIRI: Tambah Kategori Produk -->
        <div class="col-12 col-lg-5 col-xl-5">
            <div class="olsera-panel-card">
                <form action="{{ route('categories.store') }}" method="POST" id="createCategoryForm">
                    @csrf
                    <input type="hidden" name="modal_id" value="create">

                    <div class="olsera-card-header">
                        <h6 class="olsera-card-title">Tambah Kategori Produk</h6>
                        <button type="submit" class="btn olsera-save-btn" id="btnCreateSave">
                            Simpan
                        </button>
                    </div>

                    <div class="olsera-card-body">
                        <!-- Field 1: Nama Kategori -->
                        <div class="olsera-form-row">
                            <div class="olsera-field-info">
                                <label for="cname_create" class="olsera-field-label">
                                    Nama Kategori <span class="text-danger">*</span>
                                </label>
                                <div class="olsera-field-desc">
                                    Grup/kategori produk Anda, untuk memudahkan pengelompokan produk di kasir
                                </div>
                            </div>
                            <div class="olsera-field-control">
                                <input type="text"
                                    class="form-control olsera-input @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif"
                                    id="cname_create"
                                    name="cname"
                                    placeholder="Masukkan"
                                    value="{{ old('modal_id') == 'create' ? old('cname') : '' }}"
                                    required>
                                @if(old('modal_id') == 'create')
                                    @error('cname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <!-- Field 2: Pilih Outlet -->
                        <div class="olsera-form-row mb-0">
                            <div class="olsera-field-info">
                                <label class="olsera-field-label">
                                    Pilih Outlet <span class="text-danger">*</span>
                                </label>
                                <div class="olsera-field-desc">
                                    Pilih cabang outlet tempat kategori produk ini tersedia
                                </div>
                            </div>
                            <div class="olsera-field-control">
                                <div class="dropdown outlet-multi-dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                                    <button class="btn olsera-outlet-btn w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false"
                                        id="dropdownBtn_create">
                                        <span class="text-truncate"><i class="bi bi-shop me-2 text-muted"></i><span class="selected-text">Pilih Outlet...</span></span>
                                    </button>
                                    <ul class="dropdown-menu w-100 p-2 shadow border-0" style="max-height: 260px; overflow-y: auto; border: 1px solid #e2e8f0 !important;">
                                        @if(count($outlets) > 1)
                                        <li class="border-bottom pb-1 mb-1">
                                            <div class="form-check dropdown-item rounded py-1 px-3 d-flex align-items-center">
                                                <input class="form-check-input me-2 select-all-outlets" style="margin-left: 0; margin-top: 0;" type="checkbox" id="create_select_all_outlets">
                                                <label class="form-check-label w-100 ms-2 fw-semibold small text-dark" for="create_select_all_outlets" style="cursor:pointer;">
                                                    Pilih Semua Outlet
                                                </label>
                                            </div>
                                        </li>
                                        @endif
                                        @foreach($outlets as $outlet)
                                        @php
                                            $createOutletChecked = false;
                                            if (old('modal_id') == 'create' && is_array(old('outlet_ids'))) {
                                                $createOutletChecked = in_array($outlet->nid, old('outlet_ids'));
                                            } elseif (old('modal_id') != 'create' && !empty($selectedOutletId) && (string)$selectedOutletId === (string)$outlet->nid) {
                                                $createOutletChecked = true;
                                            }
                                        @endphp
                                        <li>
                                            <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                <input class="form-check-input me-2 outlet-checkbox"
                                                    style="margin-left: 0; margin-top: 0;"
                                                    type="checkbox"
                                                    name="outlet_ids[]"
                                                    value="{{ $outlet->nid }}"
                                                    id="create_outlet_{{ $outlet->nid }}"
                                                    data-name="{{ $outlet->cname }}"
                                                    {{ $createOutletChecked ? 'checked' : '' }}>
                                                <label class="form-check-label w-100 ms-2 small" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                                    {{ $outlet->cname }}
                                                </label>
                                            </div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @if(old('modal_id') == 'create')
                                    @error('outlet_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <hr class="olsera-section-divider">

                        <!-- Field 3: Kelola Stok Produk -->
                        <div class="olsera-switch-row">
                            <div class="olsera-field-info">
                                <label class="olsera-field-label" for="ftrack_stock_create" style="cursor: pointer;">
                                    Kelola Stok Produk
                                </label>
                                <div class="olsera-field-desc">
                                    Aktifkan untuk barang retail fisik (IQOS/Device/Pod). Matikan untuk Makanan, Minuman, atau Snack agar kasir bisa bertransaksi tanpa batasan stok.
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input olsera-switch"
                                        type="checkbox"
                                        role="switch"
                                        id="ftrack_stock_create"
                                        name="ftrack_stock"
                                        value="1"
                                        {{ old('modal_id') == 'create' ? (old('ftrack_stock') ? 'checked' : '') : 'checked' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- CARD KANAN: Daftar Kategori Produk -->
        <div class="col-12 col-lg-5 col-xl-5">
            <div class="olsera-panel-card">
                <div class="olsera-card-header border-0 pb-2">
                    <h6 class="olsera-card-title">Daftar Kategori</h6>
                    <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.74rem; font-weight: 600;">
                        {{ $categories->count() }} Kategori
                    </span>
                </div>

                <!-- Search Kategori -->
                <div class="px-4 pb-3 pt-1">
                    <div class="olsera-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchCategoryInput" placeholder="Cari Kategori" autocomplete="off">
                    </div>
                </div>

                <!-- List Item Kategori -->
                <div class="olsera-category-list px-4 pb-4" id="categoryListContainer">
                    @forelse ($categories as $category)
                        @php
                            $prodCount = (int) ($productCounts[$category->cname] ?? 0);
                        @endphp
                        <div class="olsera-category-item"
                            data-category-name="{{ strtolower($category->cname) }}"
                            data-bs-toggle="modal"
                            data-bs-target="#detailModal{{ $category->nid }}"
                            role="button"
                            tabindex="0"
                            title="Klik untuk melihat detail & edit kategori {{ $category->cname }}">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <i class="bi bi-arrows-move olsera-cat-drag-icon"></i>
                                <div class="text-truncate">
                                    <span class="olsera-cat-name">{{ $category->cname }}</span>
                                    @if($prodCount > 0)
                                        <span class="olsera-cat-count">({{ $prodCount }})</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                @if(!$category->ftrack_stock)
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.66rem; font-weight: 500;">Tanpa Stok</span>
                                @endif
                                <i class="bi bi-chevron-right olsera-cat-chevron"></i>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            <div class="small">Belum ada kategori produk tersedia.</div>
                        </div>
                    @endforelse

                    <div id="emptySearchCategory" class="text-center text-muted py-4 d-none">
                        <i class="bi bi-search fs-4 d-block mb-2 opacity-50"></i>
                        <div class="small">Kategori yang dicari tidak ditemukan.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- POP-UP DETAIL / EDIT & KONFIRMASI HAPUS UNTUK SETIAP KATEGORI -->
@foreach ($categories as $category)
    @php
        $prodCount = (int) ($productCounts[$category->cname] ?? 0);
        $linkedRecords = $categoryOutletsMap->get($category->cname, collect());
        $categoryOutletIds = $linkedRecords->pluck('nid_outlet')->toArray();
    @endphp

    <!-- Detail & Edit Modal -->
    <div class="modal fade" id="detailModal{{ $category->nid }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $category->nid }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3 px-4">
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="detailModalLabel{{ $category->nid }}">Detail Kategori Produk</h6>
                        <div class="text-muted" style="font-size: 0.74rem;">Lihat informasi atau perbarui pengaturan kategori</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('categories.update', $category->nid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal_id" value="{{ $category->nid }}">

                    <div class="modal-body px-4 py-3 text-start">
                        <!-- Info Ringkas Kategori -->
                        <div class="detail-meta-box mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <span class="text-muted small fw-semibold">Jumlah Produk Terdaftar</span>
                                <span class="badge bg-white text-dark border">{{ $prodCount }} Produk</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <span class="text-muted small fw-semibold">Outlet Aktif Saat Ini</span>
                                <div class="d-flex flex-wrap gap-1 justify-content-end" style="max-width: 65%;">
                                    @php $hasOutlets = false; @endphp
                                    @foreach($linkedRecords as $co)
                                        @if($co->outlet)
                                            <span class="badge bg-white text-dark border" style="font-weight: 500;">{{ $co->outlet->cname }}</span>
                                            @php $hasOutlets = true; @endphp
                                        @endif
                                    @endforeach
                                    @if(!$hasOutlets)
                                        <span class="text-muted small">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Field 1: Nama Kategori -->
                        <div class="mb-3">
                            <label for="cname_{{ $category->nid }}" class="form-label small fw-bold text-dark mb-1">
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control olsera-input @if(old('modal_id') == $category->nid) @error('cname') is-invalid @enderror @endif"
                                id="cname_{{ $category->nid }}"
                                name="cname"
                                value="{{ old('modal_id') == $category->nid ? old('cname') : $category->cname }}"
                                required>
                            @if(old('modal_id') == $category->nid)
                                @error('cname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <!-- Field 2: Pilih Outlet -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Pilih Outlet <span class="text-danger">*</span>
                            </label>
                            <div class="dropdown outlet-multi-dropdown @if(old('modal_id') == $category->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                <button class="btn olsera-outlet-btn w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    id="dropdownBtn_{{ $category->nid }}">
                                    <span class="text-truncate"><i class="bi bi-shop me-2 text-muted"></i><span class="selected-text">Pilih Outlet...</span></span>
                                </button>
                                <ul class="dropdown-menu w-100 p-2 shadow border-0" style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0 !important;">
                                    @if(count($outlets) > 1)
                                    <li class="border-bottom pb-1 mb-1">
                                        <div class="form-check dropdown-item rounded py-1 px-3 d-flex align-items-center">
                                            <input class="form-check-input me-2 select-all-outlets" style="margin-left: 0; margin-top: 0;" type="checkbox" id="edit_select_all_{{ $category->nid }}">
                                            <label class="form-check-label w-100 ms-2 fw-semibold small text-dark" for="edit_select_all_{{ $category->nid }}" style="cursor:pointer;">
                                                Pilih Semua Outlet
                                            </label>
                                        </div>
                                    </li>
                                    @endif
                                    @foreach($outlets as $outlet)
                                        @php
                                            if (old('modal_id') == $category->nid && is_array(old('outlet_ids'))) {
                                                $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                            } else {
                                                $isChecked = in_array($outlet->nid, $categoryOutletIds);
                                            }
                                        @endphp
                                        <li>
                                            <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                <input class="form-check-input me-2 outlet-checkbox"
                                                    style="margin-left: 0; margin-top: 0;"
                                                    type="checkbox"
                                                    name="outlet_ids[]"
                                                    value="{{ $outlet->nid }}"
                                                    id="edit_outlet_{{ $category->nid }}_{{ $outlet->nid }}"
                                                    data-name="{{ $outlet->cname }}"
                                                    {{ $isChecked ? 'checked' : '' }}>
                                                <label class="form-check-label w-100 ms-2 small" for="edit_outlet_{{ $category->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                    {{ $outlet->cname }}
                                                    @if($category->nid_outlet == $outlet->nid)
                                                        <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.64rem;">Asal</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @if(old('modal_id') == $category->nid)
                                @error('outlet_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <!-- Field 3: Kelola Stok Produk -->
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-check-label fw-bold small text-dark" for="ftrack_stock_{{ $category->nid }}" style="cursor: pointer;">
                                    Kelola Stok Produk
                                </label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input olsera-switch"
                                        type="checkbox"
                                        role="switch"
                                        id="ftrack_stock_{{ $category->nid }}"
                                        name="ftrack_stock"
                                        value="1"
                                        {{ old('modal_id') == $category->nid ? (old('ftrack_stock') ? 'checked' : '') : ($category->ftrack_stock ? 'checked' : '') }}>
                                </div>
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem; line-height: 1.4;">
                                Aktifkan untuk barang retail fisik (IQOS/Device/Pod). Matikan untuk Makanan, Minuman, atau Snack agar kasir bisa bertransaksi tanpa batasan stok.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between bg-light px-4 py-2">
                        <button type="button"
                            class="btn btn-outline-danger btn-sm px-3"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal{{ $category->nid }}">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-maroon btn-sm px-4">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal{{ $category->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $category->nid }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0" id="deleteModalLabel{{ $category->nid }}">Hapus Kategori</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4 px-3">
                    <i class="bi bi-exclamation-circle text-danger mb-2 d-block" style="font-size: 2.5rem;"></i>
                    <div class="fw-semibold text-dark mb-1">Hapus kategori <strong>{{ $category->cname }}</strong>?</div>
                    <div class="text-muted small">Tindakan ini tidak dapat dibatalkan.</div>
                </div>
                <div class="modal-footer bg-light justify-content-center py-2">
                    <form action="{{ route('categories.destroy', $category->nid) }}" method="POST" class="d-flex gap-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#detailModal{{ $category->nid }}">Kembali</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3">Ya, Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Re-open detail modal if validation error occurred on update
        const hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        const modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId && modalId !== 'create') {
            const targetModal = document.getElementById('detailModal' + modalId);
            if (targetModal) {
                const bsModal = new bootstrap.Modal(targetModal);
                bsModal.show();
            }
        }

        // Toggle active state on "Simpan" button in Left Card when Nama Kategori is filled
        const cnameCreateInput = document.getElementById('cname_create');
        const btnCreateSave = document.getElementById('btnCreateSave');
        function syncSaveButtonState() {
            if (!cnameCreateInput || !btnCreateSave) return;
            if (cnameCreateInput.value.trim().length > 0) {
                btnCreateSave.classList.add('ready');
            } else {
                btnCreateSave.classList.remove('ready');
            }
        }
        if (cnameCreateInput) {
            cnameCreateInput.addEventListener('input', syncSaveButtonState);
            syncSaveButtonState();
        }

        // Real-time search filter on Right Card ("Daftar Kategori")
        const searchInput = document.getElementById('searchCategoryInput');
        const categoryItems = document.querySelectorAll('.olsera-category-item');
        const emptySearch = document.getElementById('emptySearchCategory');

        if (searchInput && categoryItems.length > 0) {
            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                let visibleCount = 0;

                categoryItems.forEach(function(item) {
                    const catName = item.getAttribute('data-category-name') || '';
                    if (catName.includes(keyword)) {
                        item.classList.remove('d-none');
                        visibleCount++;
                    } else {
                        item.classList.add('d-none');
                    }
                });

                if (emptySearch) {
                    emptySearch.classList.toggle('d-none', visibleCount > 0);
                }
            });
        }

        // Multi-outlet dropdown label & "Pilih Semua Outlet" sync
        function updateDropdownText(dropdown) {
            const button = dropdown.querySelector('.dropdown-toggle');
            if (!button) return;
            const allCheckboxes = dropdown.querySelectorAll('.outlet-checkbox');
            const checkedBoxes = dropdown.querySelectorAll('.outlet-checkbox:checked');
            const selectAllBox = dropdown.querySelector('.select-all-outlets');
            const selectedText = button.querySelector('.selected-text');
            if (!selectedText) return;

            if (checkedBoxes.length === 0) {
                selectedText.textContent = 'Pilih Outlet...';
            } else if (checkedBoxes.length === 1) {
                selectedText.textContent = checkedBoxes[0].getAttribute('data-name');
            } else if (allCheckboxes.length > 0 && checkedBoxes.length === allCheckboxes.length) {
                selectedText.textContent = 'Semua Outlet (' + checkedBoxes.length + ')';
            } else {
                selectedText.textContent = checkedBoxes.length + ' Outlet Terpilih';
            }

            if (selectAllBox && allCheckboxes.length > 0) {
                selectAllBox.checked = checkedBoxes.length === allCheckboxes.length;
                selectAllBox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allCheckboxes.length;
            }
        }

        document.querySelectorAll('.outlet-multi-dropdown').forEach(function(dropdown) {
            const allCheckboxes = dropdown.querySelectorAll('.outlet-checkbox');
            const selectAllBox = dropdown.querySelector('.select-all-outlets');

            allCheckboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    updateDropdownText(dropdown);
                });
            });

            if (selectAllBox) {
                selectAllBox.addEventListener('change', function() {
                    const checked = this.checked;
                    allCheckboxes.forEach(function(cb) {
                        cb.checked = checked;
                    });
                    updateDropdownText(dropdown);
                });
            }

            updateDropdownText(dropdown);
        });
    });
</script>
@endpush
