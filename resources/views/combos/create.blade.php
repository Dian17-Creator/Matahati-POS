@extends('layouts.app')

@section('title', 'Katalog Produk / Tambah Paket Produk')

@section('sub_header')
@include('products.partials.catalog_tabs', ['activeTab' => 'combos'])
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-wrapper {
        width: 100%;
    }
    .ts-control {
        min-height: 38px !important;
        padding: 6px 12px !important;
        border: 1px solid #94a3b8 !important;
        border-radius: 0.375rem !important;
        background-color: #ffffff !important;
        box-shadow: none !important;
    }
    .ts-control:focus,
    .ts-control.focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.15) !important;
    }
    .ts-dropdown {
        z-index: 1060 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        font-size: 0.875rem !important;
        border: 1px solid #e2e8f0 !important;
    }
    .ts-dropdown .option {
        padding: 6px 12px !important;
    }
    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }
</style>
@endpush

@section('content')
<form action="{{ route('combos.store') }}" method="POST" enctype="multipart/form-data" id="createComboForm">
    @csrf

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Header Form + Simpan di Outlet + Tombol Batal/Simpan (seperti Gambar 5) -->
    <div class="card olsera-panel-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            <h5 class="mb-0 fw-bold">Tambah Paket Produk</h5>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted fw-semibold">Simpan di:</span>
                    @if($isOutletLocked && !empty($selectedOutletId))
                        @php $lockedOutlet = $outlets->firstWhere('nid', $selectedOutletId); @endphp
                        <input type="hidden" name="nid_outlets[]" value="{{ $selectedOutletId }}">
                        <span class="badge bg-light text-dark border px-3 py-2">{{ $lockedOutlet?->cname ?? 'Outlet Aktif' }}</span>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($outlets as $outlet)
                            @php
                                $checked = !empty($selectedOutletId)
                                    ? (int) $selectedOutletId === (int) $outlet->nid
                                    : in_array($outlet->nid, old('nid_outlets', []), false);
                            @endphp
                            <label class="badge bg-light text-dark border px-2 py-2 d-inline-flex align-items-center gap-1" style="cursor: pointer;">
                                <input type="checkbox" class="form-check-input m-0" name="nid_outlets[]" value="{{ $outlet->nid }}" {{ $checked ? 'checked' : '' }}>
                                <span>{{ $outlet->cname }}</span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('combos.index') }}" class="btn btn-light border px-3">Batal</a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Kolom Kiri: Informasi Paket (Gambar 5 & 6) -->
        <div class="col-lg-6">
            <div class="card olsera-panel-card h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-success mb-4">Informasi Paket</h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Paket Produk <span class="text-danger">*</span></label>
                        <input type="text" name="cname" class="form-control" value="{{ old('cname') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto Paket</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Format JPG/PNG, maksimal 2MB.</small>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Beli</label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice_buy" class="form-control rupiah-input" value="{{ number_format((float) old('nprice_buy', 0), 0, ',', '.') }}">
                        </div>
                        <small class="text-muted">Biaya modal paket (opsional).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Jual Online</label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice_online" class="form-control rupiah-input" value="{{ number_format((float) old('nprice_online', 0), 0, ',', '.') }}">
                        </div>
                        <small class="text-muted">Isi 0 apabila sama dengan harga jual di toko.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Jual di Toko <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice" class="form-control rupiah-input" value="{{ number_format((float) old('nprice', 0), 0, ',', '.') }}" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="fw-semibold">Siap publikasikan untuk dijual</div>
                            <small class="text-muted">Produk tampil di POS sesuai tanggal mulai jual.</small>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_published" value="1" checked style="width: 2.5em; height: 1.3em;">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Tanggal mulai jual</label>
                        <input type="date" name="dpublish_date" class="form-control" value="{{ old('dpublish_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Produk dalam Paket (Gambar 5) -->
        <div class="col-lg-6">
            <div class="card olsera-panel-card h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-success mb-4">Produk dalam Paket</h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tambah Produk Isi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-stretch">
                            <div class="flex-grow-1">
                                <select id="productPicker">
                                    <option value=""></option>
                                    @foreach($regularProducts as $prod)
                                    <option value="{{ $prod->nid }}" data-name="{{ $prod->cname }}" data-photo="{{ !empty($prod->cphotos) ? asset($prod->cphotos) : '' }}">
                                        {{ $prod->cname }} (IDR {{ number_format((float) $prod->nprice, 0, ',', '.') }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-primary text-nowrap" id="btnAddChildProduct">
                                <i class="bi bi-plus-lg"></i> Tambah
                            </button>
                        </div>
                        <small class="text-muted">Produk-produk mana saja yang tergabung dalam paket ini.</small>
                    </div>

                    <div id="comboItemsList" class="list-group list-group-flush border rounded mt-3">
                        <div id="emptyComboItemsPlaceholder" class="text-center text-muted py-4 small">
                            Belum ada produk dalam paket. Pilih produk di atas lalu klik <strong>Tambah</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('productPicker');
    const btnAdd = document.getElementById('btnAddChildProduct');
    const listEl = document.getElementById('comboItemsList');
    const emptyEl = document.getElementById('emptyComboItemsPlaceholder');

    const productTomSelect = picker ? new TomSelect(picker, {
        create: false,
        maxItems: 1,
        allowEmptyOption: true,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    }) : null;

    function syncEmptyState() {
        const count = listEl.querySelectorAll('.combo-child-row').length;
        if (emptyEl) {
            emptyEl.classList.toggle('d-none', count > 0);
        }
    }

    function addProductRow(id, name, photo) {
        if (!id) {
            return;
        }
        const existing = listEl.querySelector('.combo-child-row[data-id="' + id + '"]');
        if (existing) {
            const qtyInput = existing.querySelector('.child-qty-input');
            qtyInput.value = String((parseInt(qtyInput.value || '1', 10) || 1) + 1);
            return;
        }

        const row = document.createElement('div');
        row.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 py-2 combo-child-row';
        row.dataset.id = id;

        const thumbHtml = photo
            ? '<img src="' + photo + '" class="rounded-circle border" style="width: 34px; height: 34px; object-fit: cover;">'
            : '<div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 34px; height: 34px;"><i class="bi bi-box-seam small"></i></div>';

        row.innerHTML =
            '<div class="d-flex align-items-center gap-2 flex-grow-1">' +
                thumbHtml +
                '<span class="fw-medium text-dark">' + name + '</span>' +
                '<input type="hidden" name="nid_product[]" value="' + id + '">' +
            '</div>' +
            '<div class="d-flex align-items-center gap-2">' +
                '<input type="number" name="nqty[]" class="form-control form-control-sm text-center child-qty-input" value="1" min="1" step="1" style="width: 72px;" required>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-child" title="Hapus"><i class="bi bi-trash"></i></button>' +
            '</div>';

        row.querySelector('.btn-remove-child').addEventListener('click', function () {
            row.remove();
            syncEmptyState();
        });

        listEl.appendChild(row);
        syncEmptyState();
    }

    btnAdd?.addEventListener('click', function () {
        const selectedVal = productTomSelect ? productTomSelect.getValue() : (picker?.value || '');
        if (!selectedVal) {
            return;
        }
        const opt = picker?.querySelector('option[value="' + selectedVal + '"]');
        if (!opt) {
            return;
        }
        addProductRow(selectedVal, opt.dataset.name || opt.text, opt.dataset.photo || '');
        if (productTomSelect) {
            productTomSelect.clear(true);
        } else if (picker) {
            picker.value = '';
        }
    });

    document.querySelectorAll('.rupiah-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits ? parseInt(digits, 10).toLocaleString('id-ID') : '0';
        });
    });

    document.getElementById('createComboForm')?.addEventListener('submit', function (e) {
        if (listEl.querySelectorAll('.combo-child-row').length === 0) {
            e.preventDefault();
            alert('Pilih minimal 1 produk isi untuk paket kombo ini.');
        }
    });
});
</script>
@endpush
@endsection
