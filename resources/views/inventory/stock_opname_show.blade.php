@extends('layouts.app')

@section('title', 'Inventory / Detail Stok Opname')

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}" class="catalog-nav-tab">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}" class="catalog-nav-tab">
            <span>Stok Keluar</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}" class="catalog-nav-tab active">
            <span>Stok Opname</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}" class="catalog-nav-tab">
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

    /* Qty Stepper Widget Gaya Modern Olsera (Button Putih dengan Overlay Biru saat Hover) */
    .qty-stepper {
        display: inline-flex;
        align-items: stretch;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: all 0.2s ease;
        position: relative;
        max-width: 135px;
        height: 33px;
    }

    .qty-stepper:hover,
    .qty-stepper:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .qty-stepper-btn {
        border: none;
        outline: none;
        width: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ffffff;
        color: #64748b;
        cursor: pointer;
        position: relative;
        transition: all 0.18s ease;
        padding: 0;
        font-size: 0.85rem;
    }

    .qty-stepper-minus {
        border-right: 1px solid #e2e8f0;
    }

    .qty-stepper-plus {
        border-left: 1px solid #e2e8f0;
    }

    /* Efek Hover: Overlay Warna Biru Olsera */
    .qty-stepper-btn:hover {
        background-color: #0ea5e9;
        color: #ffffff;
    }

    .qty-stepper-btn:active {
        background-color: #0284c7;
        color: #ffffff;
        transform: scale(0.92);
    }

    /* Input Angka di Tengah */
    .qty-stepper-input {
        border: none !important;
        outline: none !important;
        width: 100%;
        min-width: 50px;
        text-align: center;
        font-weight: 700;
        font-size: 0.92rem;
        color: #0f172a;
        background-color: #ffffff;
        padding: 0 4px;
        margin: 0;
        box-shadow: none !important;
    }

    .qty-stepper-input:focus {
        background-color: #ffffff;
        box-shadow: none !important;
        outline: none !important;
    }

    /* Hilangkan panah spinner atas bawah pada input number */
    .qty-stepper-input::-webkit-outer-spin-button,
    .qty-stepper-input::-webkit-inner-spin-button,
    .no-spinners::-webkit-outer-spin-button,
    .no-spinners::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    .qty-stepper-input,
    .no-spinners {
        -moz-appearance: textfield !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Alert Notice Olsera --}}
    <div class="alert alert-light border shadow-sm d-flex align-items-center mb-3 py-3 px-4 rounded-3 text-secondary" style="background-color: #f8fafc;">
        <i class="bi bi-exclamation-circle text-muted fs-5 me-3"></i>
        <span class="small">Pastikan data sudah benar sebelum diposting. Setelah terposting, data tidak diperbolehkan diubah.</span>
    </div>

    {{-- Header Card Dokumen (Persis Gambar 2 Olsera) --}}
    <div class="card shadow-sm border-0 mb-3 rounded-3">
        <div class="card-body p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                @if($opname->isPosted())
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-2 fw-semibold fs-6">
                        <i class="bi bi-check2-circle me-1"></i> Posted
                    </span>
                @elseif($opname->isDraft())
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-2 fw-semibold fs-6">
                        <i class="bi bi-file-earmark me-1"></i> Draft
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-2 fw-semibold fs-6">
                        <i class="bi bi-x-circle me-1"></i> Cancelled
                    </span>
                @endif
                <div>
                    <div class="text-muted small">No. Opname Stok</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $opname->cno_opname }}</h4>
                    <div class="text-muted" style="font-size: 0.8rem;">
                        <i class="bi bi-shop me-1"></i> {{ $opname->outlet?->cname ?: 'Outlet #' . $opname->nid_outlet }}
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="{{ route('inventory.stock-opname.print', $opname->nid) }}" target="_blank" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-printer me-1"></i> Cetak
                </a>
                <a href="{{ route('inventory.stock-opname.export-excel', $opname->nid) }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-download me-1"></i> Download Excel
                </a>

                @if($opname->isDraft())
                    <button type="button" class="btn btn-warning btn-sm px-3 text-white fw-medium" title="Data tersimpan sebagai Draf">
                        <i class="bi bi-save me-1"></i> Draf
                    </button>
                    <form action="{{ route('inventory.stock-opname.cancel', $opname->nid) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin membatalkan dokumen Stok Opname ini?');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="bi bi-x-lg me-1"></i> Batalkan
                        </button>
                    </form>
                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPostingConfirm">
                        <i class="bi bi-check2-circle me-1"></i> Posting Sekarang
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Dua Card: Tanggal & Catatan (Persis Gambar 2 Olsera) --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Tanggal</div>
                        <div class="fw-bold text-dark fs-6">{{ \Carbon\Carbon::parse($opname->dopname)->format('d-M-Y') }}</div>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-person me-1"></i> {{ $opname->user?->email ?: ($opname->user?->cname ?: 'Admin') }}
                        </div>
                    </div>
                    @if($opname->isDraft())
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil me-1"></i> Ubah
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Catatan</div>
                        <div class="fw-medium text-dark">{{ $opname->cnote ?: 'Tidak ada catatan' }}</div>
                    </div>
                    @if($opname->isDraft())
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalEditHeader">
                            <i class="bi bi-pencil me-1"></i> Ubah
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Section Tabel Produk Stok Opname (Persis Gambar 2 & 3 Olsera) --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center rounded-top-3">
            <h6 class="mb-0 fw-bold">
                Produk Stok Opname ( {{ $opname->details->count() }} )
            </h6>
            @if($opname->isDraft())
                <button type="button" class="btn btn-light btn-sm px-3 fw-semibold text-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                </button>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead class="bg-light text-muted fw-semibold">
                    <tr>
                        <th class="ps-3" style="min-width: 280px;">Produk</th>
                        <th style="width: 100px;">Waktu</th>
                        <th class="text-center" style="width: 110px;">Qty System</th>
                        <th class="text-center" style="width: 120px;">Qty Aktual</th>
                        <th class="text-center" style="width: 110px;">Qty Selisih</th>
                        <th class="text-center" style="width: 130px;">Selisih Harga</th>
                        <th style="width: 140px;">Tgl. Kadaluwarsa</th>
                        <th style="width: 100px;">Rack</th>
                        @if($opname->isDraft())
                            <th class="text-center" style="width: 60px;">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($opname->details as $d)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; min-width: 44px; overflow: hidden;">
                                        @php
                                            $photoPath = $d->product?->cphotos;
                                            $photoSrc = null;
                                            if ($photoPath) {
                                                $photoSrc = \Illuminate\Support\Str::startsWith($photoPath, ['http://', 'https://'])
                                                    ? $photoPath
                                                    : asset($photoPath);
                                            }
                                        @endphp
                                        @if($photoSrc)
                                            <img src="{{ $photoSrc }}"
                                                 alt="{{ $d->item_name }}"
                                                 class="rounded"
                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                 onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                            <div class="align-items-center justify-content-center text-muted" style="display: none; width: 100%; height: 100%; font-size: 1.1rem;">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        @else
                                            <i class="bi bi-box-seam text-secondary" style="font-size: 1.1rem;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $d->item_name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ $d->product?->category?->cname ?: 'Produk' }}
                                            @if($d->product?->csatuan)
                                                &bull; {{ $d->product->csatuan }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $d->dtime ?: '-' }}</span>
                            </td>
                            <td class="text-center fw-medium text-dark">
                                {{ number_format($d->nqty_system, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($opname->isDraft())
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-bold" onclick="openEditItemModal({{ $d->nid }}, {{ $d->nqty_actual }}, '{{ $d->dexpired }}', '{{ addslashes($d->crack ?? '') }}', '{{ addslashes($d->cnote ?? '') }}', '{{ $d->dtime }}', '{{ addslashes($d->item_name) }}')">
                                        {{ number_format($d->nqty_actual, 0, ',', '.') }}
                                        <i class="bi bi-pencil ms-1 text-primary" style="font-size: 0.75rem;"></i>
                                    </button>
                                @else
                                    <span class="fw-bold text-dark">{{ number_format($d->nqty_actual, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($d->nqty_diff > 0)
                                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">+{{ number_format($d->nqty_diff, 0, ',', '.') }}</span>
                                @elseif($d->nqty_diff < 0)
                                    <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">{{ number_format($d->nqty_diff, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-1">0</span>
                                @endif
                            </td>
                            <td class="text-center text-muted">
                                -
                            </td>
                            <td>
                                <span class="text-muted small">{{ $d->dexpired ? \Carbon\Carbon::parse($d->dexpired)->format('d-M-Y') : '-' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $d->crack ?: '-' }}</span>
                            </td>
                            @if($opname->isDraft())
                                <td class="text-center">
                                    <form action="{{ route('inventory.stock-opname.items.destroy', [$opname->nid, $d->nid]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus {{ addslashes($d->item_name) }} dari stok opname?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger p-0" title="Hapus Produk">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $opname->isDraft() ? 9 : 8 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam display-6 d-block mb-2 text-secondary"></i>
                                <p class="mb-2">Belum ada produk yang dimasukkan ke dalam dokumen Stok Opname ini.</p>
                                @if($opname->isDraft())
                                    <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Produk Sekarang
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($opname->details->isNotEmpty())
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="ps-3" colspan="2">TOTAL</td>
                            <td class="text-center">{{ number_format($opname->ntotal_system_qty, 0, ',', '.') }}</td>
                            <td class="text-center">{{ number_format($opname->ntotal_actual_qty, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($opname->ntotal_diff_qty > 0)
                                    <span class="text-success">+{{ number_format($opname->ntotal_diff_qty, 0, ',', '.') }}</span>
                                @elseif($opname->ntotal_diff_qty < 0)
                                    <span class="text-danger">{{ number_format($opname->ntotal_diff_qty, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-center text-muted">
                                -
                            </td>
                            <td colspan="{{ $opname->isDraft() ? 3 : 2 }}"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ==================== MODAL TAMBAH PRODUK (GAMBAR 4 OLSERA) ==================== --}}
@if($opname->isDraft())
<div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-labelledby="modalTambahProdukLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-opname.items.store', $opname->nid) }}" method="POST" id="formAddProducts">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTambahProdukLabel">Tambah Produk</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm px-3" id="btnSubmitAddProducts" disabled>
                            Tambah (<span id="modalSelectedCount">0</span>)
                        </button>
                    </div>
                </div>

                <div class="modal-body p-3">
                    {{-- Filter Bar --}}
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-auto" style="width: 140px;">
                            <input type="time" step="1" name="default_time" id="modalFilterTime" class="form-control form-control-sm" value="{{ date('H:i:s') }}">
                        </div>
                        <div class="col-auto" style="min-width: 160px;">
                            <select id="modalFilterCategory" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->nid }}">{{ $cat->cname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="modalSearchProduct" class="form-control border-start-0" placeholder="Cari Produk / SKU / Barcode...">
                            </div>
                        </div>
                    </div>

                    {{-- Counter info --}}
                    <div class="d-flex align-items-center mb-2">
                        <span class="text-success small fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>
                            <span id="modalCounterText">0 Terpilih (Maksimal 500)</span>
                        </span>
                    </div>

                    {{-- Tabel Produk --}}
                    <div class="table-responsive border rounded-2" style="max-height: 480px;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;" id="modalProductTable">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="checkAllProducts">
                                    </th>
                                    <th>Product</th>
                                    <th style="width: 140px;">SKU</th>
                                    <th style="width: 150px;">Kategori</th>
                                    <th class="text-center" style="width: 110px;">Stok Sistem</th>
                                    <th class="text-center" style="width: 160px;">Stok Aktual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableProducts as $idx => $p)
                                    @php
                                        $existingDetail = $opname->details->firstWhere('nid_product', $p->nid);
                                        $initActual = $existingDetail ? (float)$existingDetail->nqty_actual : (float)$p->nqty;
                                        $isChecked = $existingDetail ? true : false;
                                    @endphp
                                    <tr class="product-row" data-cat-id="{{ $p->nid_category }}" data-name="{{ strtolower($p->cname) }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input product-checkbox" name="items[{{ $idx }}][nid_product]" value="{{ $p->nid }}" {{ $isChecked ? 'checked' : '' }} onchange="updateSelectedCount()">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; min-width: 36px; overflow: hidden;">
                                                    @php
                                                        $mPhotoSrc = null;
                                                        if ($p->cphotos) {
                                                            $mPhotoSrc = \Illuminate\Support\Str::startsWith($p->cphotos, ['http://', 'https://'])
                                                                ? $p->cphotos
                                                                : asset($p->cphotos);
                                                        }
                                                    @endphp
                                                    @if($mPhotoSrc)
                                                        <img src="{{ $mPhotoSrc }}"
                                                             alt="{{ $p->cname }}"
                                                             class="rounded"
                                                             style="width: 100%; height: 100%; object-fit: cover;"
                                                             onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                                        <div class="align-items-center justify-content-center text-muted" style="display: none; width: 100%; height: 100%; font-size: 0.9rem;">
                                                            <i class="bi bi-box-seam"></i>
                                                        </div>
                                                    @else
                                                        <i class="bi bi-box-seam text-secondary" style="font-size: 0.9rem;"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 360px;">{{ $p->cname }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $p->nid }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $p->category?->cname ?: 'Produk' }}</span>
                                        </td>
                                        <td class="text-center fw-medium text-muted">
                                            {{ number_format($p->nqty, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="qty-stepper mx-auto">
                                                <button type="button" class="qty-stepper-btn qty-stepper-minus" onclick="stepQty('qty_input_{{ $p->nid }}', -1)" title="Kurangi 1">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                                <input type="number" step="any" min="0" name="items[{{ $idx }}][nqty_actual]" id="qty_input_{{ $p->nid }}" class="qty-stepper-input" value="{{ $initActual }}" oninput="autoCheckRow({{ $p->nid }})">
                                                <button type="button" class="qty-stepper-btn qty-stepper-plus" onclick="stepQty('qty_input_{{ $p->nid }}', 1)" title="Tambah 1">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="items[{{ $idx }}][dtime]" class="row-time-input" value="{{ date('H:i:s') }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold" id="btnSubmitAddProductsFooter" disabled>
                        Tambah (<span id="modalSelectedCountFooter">0</span>)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Item Tunggal --}}
<div class="modal fade" id="modalEditItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditItem" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Ubah Hitungan Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="fw-semibold text-primary mb-3" id="editItemName">Nama Produk</div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Qty Aktual (Fisik) <span class="text-danger">*</span></label>
                        <input type="number" step="any" min="0" name="nqty_actual" id="editActualQty" class="form-control form-control-sm" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Waktu Cek</label>
                            <input type="time" step="1" name="dtime" id="editTime" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tgl. Kadaluwarsa</label>
                            <input type="date" name="dexpired" id="editExpired" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Rack / Lokasi Rak</label>
                        <input type="text" name="crack" id="editRack" class="form-control form-control-sm" placeholder="Contoh: Rak A-02">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan</label>
                        <input type="text" name="cnote" id="editNote" class="form-control form-control-sm" placeholder="Kondisi barang / keterangan...">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ubah Tanggal & Catatan Dokumen --}}
<div class="modal fade" id="modalEditHeader" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-opname.update', $opname->nid) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Ubah Dokumen Opname</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Opname <span class="text-danger">*</span></label>
                        <input type="date" name="dopname" class="form-control form-control-sm" value="{{ $opname->dopname ? \Carbon\Carbon::parse($opname->dopname)->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan</label>
                        <textarea name="cnote" class="form-control form-control-sm" rows="3">{{ $opname->cnote }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Posting Sekarang --}}
<div class="modal fade" id="modalPostingConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-opname.post', $opname->nid) }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-success">
                        <i class="bi bi-check2-circle me-1"></i> Konfirmasi Posting Stok Opname
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small border-0 mb-3">
                        <strong>Perhatian:</strong> Setelah diposting, dokumen ini <strong>tidak dapat diubah lagi</strong>.
                    </div>
                    <p class="mb-2">Sistem akan melakukan hal-hal berikut secara otomatis:</p>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>Mengubah stok master produk di outlet ke angka <strong>Qty Aktual</strong> hasil hitungan fisik Anda.</li>
                        <li>Mencatat seluruh selisih (lebih atau kurang) ke tabel <strong>Pergerakan Stok</strong>.</li>
                        <li>Mengunci dokumen dengan status <strong>Posted</strong>.</li>
                    </ul>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Kembali Cek</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                        Ya, Posting Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
function stepQty(inputId, delta) {
    const el = document.getElementById(inputId);
    if (!el) return;
    let val = parseFloat(el.value) || 0;
    val += delta;
    if (val < 0) val = 0;
    el.value = val;
    // Auto-check checkbox terkait
    const prodId = inputId.replace('qty_input_', '');
    autoCheckRow(prodId);
}

function autoCheckRow(prodId) {
    const row = document.querySelector(`.product-checkbox[value="${prodId}"]`);
    if (row && !row.checked) {
        row.checked = true;
        updateSelectedCount();
    }
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.product-checkbox:checked').length;
    document.getElementById('modalSelectedCount').innerText = checked;
    document.getElementById('modalSelectedCountFooter').innerText = checked;
    document.getElementById('modalCounterText').innerText = `${checked} Terpilih (Maksimal 500)`;

    const btn1 = document.getElementById('btnSubmitAddProducts');
    const btn2 = document.getElementById('btnSubmitAddProductsFooter');
    if (btn1) btn1.disabled = checked === 0;
    if (btn2) btn2.disabled = checked === 0;
}

document.addEventListener('DOMContentLoaded', function() {
    // Select all handler
    const checkAll = document.getElementById('checkAllProducts');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const visibleRows = document.querySelectorAll('#modalProductTable tbody tr:not([style*="display: none"]) .product-checkbox');
            visibleRows.forEach(cb => cb.checked = checkAll.checked);
            updateSelectedCount();
        });
    }

    // Filter Kategori & Search
    const searchInput = document.getElementById('modalSearchProduct');
    const catSelect = document.getElementById('modalFilterCategory');

    function filterRows() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const catId = catSelect ? catSelect.value : '';

        const rows = document.querySelectorAll('#modalProductTable tbody tr.product-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const rowCat = row.getAttribute('data-cat-id') || '';

            const matchQuery = !query || name.includes(query);
            const matchCat = !catId || rowCat === catId;

            if (matchQuery && matchCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterRows);
    if (catSelect) catSelect.addEventListener('change', filterRows);

    // Sync time input
    const timeInput = document.getElementById('modalFilterTime');
    if (timeInput) {
        timeInput.addEventListener('change', function() {
            document.querySelectorAll('.row-time-input').forEach(input => input.value = timeInput.value);
        });
    }

    // Submit form modal: disable input baris yang tidak dicentang agar hanya produk terpilih yang dikirim
    const formAdd = document.getElementById('formAddProducts');
    if (formAdd) {
        formAdd.addEventListener('submit', function() {
            document.querySelectorAll('#modalProductTable tbody tr.product-row').forEach(row => {
                const cb = row.querySelector('.product-checkbox');
                if (!cb || !cb.checked) {
                    row.querySelectorAll('input').forEach(inp => inp.disabled = true);
                }
            });
        });
    }

    const modalAddEl = document.getElementById('modalTambahProduk');
    if (modalAddEl) {
        modalAddEl.addEventListener('show.bs.modal', function() {
            document.querySelectorAll('#modalProductTable tbody tr.product-row input').forEach(inp => inp.disabled = false);
            updateSelectedCount();
        });
    }

    updateSelectedCount();
});

function openEditItemModal(itemId, actualQty, expired, rack, note, time, itemName) {
    const form = document.getElementById('formEditItem');
    form.action = `{{ url('inventory/stock-opname/' . $opname->nid . '/items') }}/${itemId}`;
    document.getElementById('editItemName').innerText = itemName;
    document.getElementById('editActualQty').value = actualQty;
    document.getElementById('editTime').value = time || '';
    document.getElementById('editExpired').value = expired || '';
    document.getElementById('editRack').value = rack || '';
    document.getElementById('editNote').value = note || '';

    const modal = new bootstrap.Modal(document.getElementById('modalEditItem'));
    modal.show();
}
</script>
@endsection

