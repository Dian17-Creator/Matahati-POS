@extends('layouts.app')

@section('title', 'Point Of Sales / Food Delivery')

@section('sub_header')
@include('settings.partials.pos_tabs', ['activeTab' => 'food-delivery'])
@endsection

@section('content')
<div class="container-fluid py-2">
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

    @if($selectedOutletId <= 0)
        <div class="alert alert-warning py-2 small mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Saat ini Anda berada pada mode <strong>Semua Outlet</strong>. Silakan pilih salah satu outlet pada dropdown di pojok kanan atas terlebih dahulu.
</div>
@else
@php
$platformSlug = strtolower($normalizedPlatform);
$presetSign = ($preset['mode'] === 'markdown' && $preset['value'] > 0) ? '-' : '+';
$presetDisplay = $preset['unit'] === 'percent'
? $presetSign . ' ' . number_format($preset['value'], 2, '.', '') . ' %'
: $presetSign . ' IDR ' . number_format($preset['value'], 0, ',', '.');
@endphp

<!-- Top Bar Detail Merchant (seperti Gambar 1) -->
<div class="d-flex align-items-center justify-content-between bg-white border rounded-3 px-4 py-3 mb-3 shadow-sm" style="min-height: 68px;">
    <a href="{{ route('settings.pos.food-delivery') }}" class="btn btn-sm btn-link text-dark text-decoration-none p-1" title="Kembali">
        <i class="bi bi-arrow-left fs-5"></i>
    </a>
    <h5 class="mb-0 fw-semibold">{{ $deliverySetting->cname }}</h5>
    <div style="width: 32px;"></div>
</div>

<!-- Toolbar Search + Hapus Terpilih + Tambah (1 baris) -->
<div class="d-flex align-items-center justify-content-between gap-3 mb-3">
    <div class="d-flex align-items-center gap-2 flex-grow-1">
        <div class="input-group bg-white rounded shadow-sm">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="addedProductSearch" class="form-control border-start-0 ps-0" placeholder="Cari produk...">
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger text-nowrap d-none" id="btnBulkDelete">
            <i class="bi bi-trash me-1"></i>Hapus Terpilih (<span id="selectedCount">0</span>)
        </button>
    </div>
    <button type="button" class="btn text-white fw-medium px-3 text-nowrap" style="background-color: #65a30d;" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah
    </button>
</div>

<!-- Card Daftar Produk Merchant -->
<div class="card shadow-sm" style="border: 1px solid #cbd5e1 !important;">
    <!-- Switch Aktif -->
    <div class="card-header bg-white px-4 py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #cbd5e1;">
        <span class="fw-semibold text-dark">Aktif</span>
        <form method="POST" action="{{ route('settings.pos.food-delivery.toggle', ['platform' => $platformSlug]) }}" class="m-0">
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
                    onchange="this.form.submit()"
                    {{ $deliverySetting->bis_active ? 'checked' : '' }}
                    style="width: 2.5em; height: 1.3em; cursor: pointer;">
            </div>
        </form>
    </div>

    <form id="bulkDeleteForm" method="POST" action="{{ route('settings.pos.food-delivery.products.destroy', ['platform' => $platformSlug]) }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 48px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="selectAllAdded">
                        </th>
                        <th class="small text-muted fw-semibold">Nama Produk</th>
                        <th class="small text-muted fw-semibold">Kategori</th>
                        <th class="small text-muted fw-semibold text-end">Harga normal</th>
                        <th class="small text-muted fw-semibold text-end" style="width: 200px;">Harga {{ $deliverySetting->cname }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($addedDetails as $detail)
                    @php
                    $product = $detail->product;
                    $categoryName = $product->category?->cname ?? 'Others';
                    @endphp
                    <tr class="added-product-row" data-name="{{ strtolower($product->cname) }}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input added-product-cb" name="product_ids[]" value="{{ $product->nid }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if(!empty($product->cphotos))
                                <img src="{{ asset($product->cphotos) }}" alt="{{ $product->cname }}" class="rounded" style="width: 38px; height: 38px; object-fit: cover;">
                                @else
                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                @endif
                                <span class="fw-medium text-dark">{{ $product->cname }}</span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $categoryName }}</td>
                        <td class="text-end text-dark">IDR {{ number_format((float) $product->nprice, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="fw-medium text-dark me-2">IDR {{ number_format((float) $detail->nprice, 0, ',', '.') }}</span>
                            <button
                                type="button"
                                class="btn btn-sm btn-link p-0 text-primary btn-edit-price"
                                title="Ubah Harga {{ $deliverySetting->cname }}"
                                data-product-id="{{ $product->nid }}"
                                data-product-name="{{ $product->cname }}"
                                data-product-photo="{{ !empty($product->cphotos) ? asset($product->cphotos) : '' }}"
                                data-base-price="{{ (float) $product->nprice }}"
                                data-current-price="{{ (float) $detail->nprice }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            Belum ada produk yang ditambahkan untuk {{ $deliverySetting->cname }}. Klik <strong>+ Tambah</strong> untuk menambahkan produk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Modal 1: Preset Harga (Markup / Markdown) -->
<div class="modal fade" id="presetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('settings.pos.food-delivery.preset', ['platform' => $platformSlug]) }}">
                @csrf
                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                <input type="hidden" name="mode" id="presetModeInput" value="{{ $preset['mode'] }}">
                <input type="hidden" name="unit" id="presetUnitInput" value="{{ $preset['unit'] }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Preset Harga {{ $deliverySetting->cname }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="rounded-3 bg-light border px-3 py-2 mb-3 d-flex align-items-center gap-2 text-secondary small">
                        <i class="bi bi-info-circle"></i>
                        <span>Diterapkan saat penambahan produk</span>
                    </div>

                    <!-- Toggle Markup / Markdown -->
                    <div class="btn-group w-100 mb-3" role="group">
                        <button type="button" class="btn preset-mode-btn {{ $preset['mode'] === 'markup' ? 'btn-primary text-white' : 'btn-light border text-dark' }}" data-mode="markup">
                            Markup Harga
                        </button>
                        <button type="button" class="btn preset-mode-btn {{ $preset['mode'] === 'markdown' ? 'btn-primary text-white' : 'btn-light border text-dark' }}" data-mode="markdown">
                            Markdown Harga
                        </button>
                    </div>

                    <!-- Input Nilai + Toggle IDR / % -->
                    <div class="input-group mb-3">
                        <input type="number" step="0.01" min="0" name="value" class="form-control" value="{{ $preset['value'] }}" required>
                        <button type="button" class="btn preset-unit-btn {{ $preset['unit'] === 'idr' ? 'btn-primary text-white' : 'btn-light border text-dark' }}" data-unit="idr" style="min-width: 64px;">
                            IDR
                        </button>
                        <button type="button" class="btn preset-unit-btn {{ $preset['unit'] === 'percent' ? 'btn-primary text-white' : 'btn-light border text-dark' }}" data-unit="percent" style="min-width: 64px;">
                            %
                        </button>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="apply_existing" value="1" id="applyExistingCheck">
                        <label class="form-check-label small text-muted" for="applyExistingCheck">
                            Terapkan juga ke semua produk yang sudah ditambahkan
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Tambah Produk (1 Pop-up: Markup/Markdown di atas + Pilih Produk di bawah) -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true" data-platform-label="{{ $deliverySetting?->cname ?? 'Delivery' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('settings.pos.food-delivery.add-products', ['platform' => $platformSlug]) }}">
                @csrf
                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                <input type="hidden" name="mode" id="addModalModeInput" value="{{ $preset['mode'] }}">
                <input type="hidden" name="unit" id="addModalUnitInput" value="{{ $preset['unit'] }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Produk {{ $deliverySetting->cname }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Bagian Atas: Opsi Markup / Markdown -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="small fw-semibold text-secondary mb-2">
                            Pengaturan Harga (Markup / Markdown)
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm add-mode-btn {{ $preset['mode'] === 'markup' ? 'btn-primary text-white' : 'btn-white border text-dark' }}" data-mode="markup">
                                        Markup Harga
                                    </button>
                                    <button type="button" class="btn btn-sm add-mode-btn {{ $preset['mode'] === 'markdown' ? 'btn-primary text-white' : 'btn-white border text-dark' }}" data-mode="markdown">
                                        Markdown Harga
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" name="value" id="addModalValueInput" class="form-control" value="{{ $preset['value'] }}">
                                    <button type="button" class="btn add-unit-btn {{ $preset['unit'] === 'idr' ? 'btn-primary text-white' : 'btn-white border text-dark' }}" data-unit="idr" style="min-width: 52px;">
                                        IDR
                                    </button>
                                    <button type="button" class="btn add-unit-btn {{ $preset['unit'] === 'percent' ? 'btn-primary text-white' : 'btn-white border text-dark' }}" data-unit="percent" style="min-width: 52px;">
                                        %
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-check mt-2 mb-0">
                            <input class="form-check-input" type="checkbox" name="apply_existing" value="1" id="addModalApplyExistingCheck">
                            <label class="form-check-label small text-muted" for="addModalApplyExistingCheck">
                                Terapkan juga ke semua produk yang sudah ditambahkan
                            </label>
                        </div>
                    </div>

                    <!-- Bagian Bawah: Pencarian, Kategori & Daftar Produk -->
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="modalProductSearch" class="form-control border-start-0 ps-0" placeholder="Cari produk...">
                    </div>

                    <!-- Filter Kategori Pills -->
                    <div class="d-flex flex-nowrap gap-2 overflow-auto pb-2 mb-2">
                        <button type="button" class="btn btn-sm rounded-pill px-3 category-pill btn-secondary text-white active" data-category="all">Semua</button>
                        @foreach($categories as $cat)
                        <button type="button" class="btn btn-sm rounded-pill px-3 category-pill btn-light border text-dark text-nowrap" data-category="{{ $cat->nid }}">{{ $cat->cname }}</button>
                        @endforeach
                    </div>

                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small fw-semibold text-secondary">Pilih Produk</span>
                        <input type="checkbox" class="form-check-input" id="selectAllModalProducts">
                    </div>

                    <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                        @forelse($availableProducts as $prod)
                        <label class="list-group-item d-flex align-items-center justify-content-between gap-3 py-2 modal-product-item" data-name="{{ strtolower($prod->cname) }}" data-category="{{ $prod->nid_category ?? 'none' }}" data-base-price="{{ (float) $prod->nprice }}" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                @if(!empty($prod->cphotos))
                                <img src="{{ asset($prod->cphotos) }}" alt="{{ $prod->cname }}" class="rounded" style="width: 38px; height: 38px; object-fit: cover;">
                                @else
                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-medium text-dark">{{ $prod->cname }}</div>
                                    <div class="small text-muted">
                                        Normal: IDR {{ number_format((float) $prod->nprice, 0, ',', '.') }}
                                        • {{ number_format((float) $prod->nqty, 0, ',', '.') }} stock
                                        • <span class="text-primary fw-semibold modal-preview-price">{{ $deliverySetting->cname }}: IDR {{ number_format((float) $prod->nprice, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <input type="checkbox" class="form-check-input modal-product-cb" name="product_ids[]" value="{{ $prod->nid }}">
                        </label>
                        @empty
                        <div class="text-center text-muted py-4 small">Semua produk aktif di outlet ini sudah ditambahkan.</div>
                        @endforelse
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAddProducts" disabled>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Edit Harga Individual (gaya Olsera) -->
@php
    $brandColors = [
        'GOFOOD' => '#ef4444',
        'GRABFOOD' => '#22c55e',
        'SHOPEEFOOD' => '#f97316',
        'TRAVELOKAEATS' => '#64748b',
    ];
    $brandBg = $brandColors[strtoupper((string) $normalizedPlatform)] ?? '#ef4444';
@endphp
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="editPriceForm" action="">
                @csrf
                @method('PATCH')
                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                <input type="hidden" name="nprice" id="editPriceFinalInput" value="0">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Harga {{ $deliverySetting->cname }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Summary Card Pink -->
                    <div class="rounded-3 p-3 mb-3" style="background-color: #fff1f2;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img id="editPricePhotoImg" src="" alt="" class="rounded-circle d-none" style="width: 30px; height: 30px; object-fit: cover;">
                            <div id="editPricePhotoPlaceholder" class="rounded-circle bg-white border d-flex align-items-center justify-content-center text-muted" style="width: 30px; height: 30px;">
                                <i class="bi bi-image small"></i>
                            </div>
                            <span class="fw-semibold text-dark" id="editPriceProductName"></span>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-6">
                                <div class="small text-muted">Harga Normal</div>
                                <div class="fw-bold text-dark fs-6" id="editPriceBaseText">IDR 0</div>
                            </div>
                            <div class="col-6 d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background-color: {{ $brandBg }};">
                                    <img src="{{ asset('images/food.svg') }}" alt="{{ $deliverySetting->cname }}" style="width: 14px; height: 16px;">
                                </span>
                                <div>
                                    <div class="small text-muted">Harga {{ $deliverySetting->cname }}</div>
                                    <div class="fw-bold text-dark fs-6" id="editPricePreviewText">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Markup / Markdown -->
                    <div class="btn-group w-100 mb-3" role="group">
                        <button type="button" class="btn edit-mode-btn btn-primary text-white" data-mode="markup">
                            Markup Harga
                        </button>
                        <button type="button" class="btn edit-mode-btn btn-light border text-dark" data-mode="markdown">
                            Markdown Harga
                        </button>
                    </div>

                    <!-- Input Nilai + Toggle IDR / % -->
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white" id="editPricePrefix">Rp.</span>
                        <input type="number" step="0.01" min="0" id="editPriceDeltaInput" class="form-control" value="0" required>
                        <button type="button" class="btn edit-unit-btn btn-primary text-white" data-unit="idr" style="min-width: 64px;">
                            IDR
                        </button>
                        <button type="button" class="btn edit-unit-btn btn-light border text-dark" data-unit="percent" style="min-width: 64px;">
                            %
                        </button>
                    </div>

                    <!-- Tombol Hapus Produk dari Merchant -->
                    <button type="button" id="btnDeleteSingleProduct" class="btn btn-outline-light border text-danger w-100 py-2 fw-medium">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>

            <form method="POST" id="deleteSingleProductForm" action="{{ route('settings.pos.food-delivery.products.destroy', ['platform' => $platformSlug]) }}" class="d-none">
                @csrf
                @method('DELETE')
                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                <input type="hidden" name="product_ids[]" id="deleteSingleProductIdInput" value="">
            </form>
        </div>
    </div>
</div>
@endif
</div>

<style>
    #presetModal .btn-primary,
    #addProductModal .btn-primary,
    #editPriceModal .btn-primary {
        background-color: #60a5fa !important;
        border-color: #60a5fa !important;
        color: #ffffff !important;
    }
    #presetModal .btn-primary:hover,
    #addProductModal .btn-primary:hover,
    #editPriceModal .btn-primary:hover,
    #presetModal .btn-primary:focus,
    #addProductModal .btn-primary:focus,
    #editPriceModal .btn-primary:focus {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
    }
    #presetModal .btn-primary:disabled,
    #addProductModal .btn-primary:disabled,
    #editPriceModal .btn-primary:disabled {
        background-color: #93c5fd !important;
        border-color: #93c5fd !important;
        opacity: 0.75;
    }
    #addProductModal .text-primary {
        color: #3b82f6 !important;
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('addedProductSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                document.querySelectorAll('.added-product-row').forEach(function(row) {
                    row.style.display = (row.dataset.name || '').includes(keyword) ? '' : 'none';
                });
            });
        }

        // Bulk select & delete
        const selectAllAdded = document.getElementById('selectAllAdded');
        const addedCheckboxes = document.querySelectorAll('.added-product-cb');
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        const selectedCount = document.getElementById('selectedCount');

        function syncBulkDeleteBtn() {
            const checked = document.querySelectorAll('.added-product-cb:checked').length;
            if (selectedCount) {
                selectedCount.textContent = checked;
            }
            if (btnBulkDelete) {
                btnBulkDelete.classList.toggle('d-none', checked === 0);
            }
        }

        selectAllAdded?.addEventListener('change', function() {
            addedCheckboxes.forEach(function(cb) {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAllAdded.checked;
                }
            });
            syncBulkDeleteBtn();
        });

        addedCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', syncBulkDeleteBtn);
        });

        btnBulkDelete?.addEventListener('click', function() {
            if (confirm('Hapus produk terpilih dari daftar {{ $deliverySetting?->cname }}?')) {
                document.getElementById('bulkDeleteForm')?.submit();
            }
        });

        // Preset modal toggles
        const modeInput = document.getElementById('presetModeInput');
        document.querySelectorAll('.preset-mode-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                modeInput.value = btn.dataset.mode;
                document.querySelectorAll('.preset-mode-btn').forEach(function(b) {
                    const active = b === btn;
                    b.classList.toggle('btn-primary', active);
                    b.classList.toggle('text-white', active);
                    b.classList.toggle('btn-light', !active);
                    b.classList.toggle('border', !active);
                    b.classList.toggle('text-dark', !active);
                });
            });
        });

        const unitInput = document.getElementById('presetUnitInput');
        document.querySelectorAll('.preset-unit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                unitInput.value = btn.dataset.unit;
                document.querySelectorAll('.preset-unit-btn').forEach(function(b) {
                    const active = b === btn;
                    b.classList.toggle('btn-primary', active);
                    b.classList.toggle('text-white', active);
                    b.classList.toggle('btn-light', !active);
                    b.classList.toggle('border', !active);
                    b.classList.toggle('text-dark', !active);
                });
            });
        });

        // Add-product modal markup/markdown toggles & live preview
        const addModeInput = document.getElementById('addModalModeInput');
        const addUnitInput = document.getElementById('addModalUnitInput');
        const addValueInput = document.getElementById('addModalValueInput');
        const platformLabel = document.getElementById('addProductModal')?.dataset.platformLabel || 'Delivery';

        function updateAddModalPreviewPrices() {
            const mode = addModeInput?.value || 'markup';
            const unit = addUnitInput?.value || 'percent';
            const val = Math.max(0, parseFloat(addValueInput?.value || '0') || 0);

            document.querySelectorAll('.modal-product-item').forEach(function(item) {
                const base = parseFloat(item.dataset.basePrice || '0') || 0;
                const delta = unit === 'percent' ? (base * (val / 100)) : val;
                const raw = mode === 'markdown' ? (base - delta) : (base + delta);
                const finalPrice = Math.max(0, Math.round(raw));
                const previewEl = item.querySelector('.modal-preview-price');
                if (previewEl) {
                    previewEl.textContent = platformLabel + ': IDR ' + finalPrice.toLocaleString('id-ID');
                }
            });
        }

        document.querySelectorAll('.add-mode-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                addModeInput.value = btn.dataset.mode;
                document.querySelectorAll('.add-mode-btn').forEach(function(b) {
                    const active = b === btn;
                    b.classList.toggle('btn-primary', active);
                    b.classList.toggle('text-white', active);
                    b.classList.toggle('btn-white', !active);
                    b.classList.toggle('border', !active);
                    b.classList.toggle('text-dark', !active);
                });
                updateAddModalPreviewPrices();
            });
        });

        document.querySelectorAll('.add-unit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                addUnitInput.value = btn.dataset.unit;
                document.querySelectorAll('.add-unit-btn').forEach(function(b) {
                    const active = b === btn;
                    b.classList.toggle('btn-primary', active);
                    b.classList.toggle('text-white', active);
                    b.classList.toggle('btn-white', !active);
                    b.classList.toggle('border', !active);
                    b.classList.toggle('text-dark', !active);
                });
                updateAddModalPreviewPrices();
            });
        });

        addValueInput?.addEventListener('input', updateAddModalPreviewPrices);
        updateAddModalPreviewPrices();

        // Modal Tambah Produk: filter & select
        let activeCategory = 'all';
        const modalSearch = document.getElementById('modalProductSearch');
        const modalItems = document.querySelectorAll('.modal-product-item');

        function filterModalProducts() {
            const keyword = (modalSearch?.value || '').trim().toLowerCase();
            modalItems.forEach(function(item) {
                const matchName = (item.dataset.name || '').includes(keyword);
                const matchCat = activeCategory === 'all' || item.dataset.category === activeCategory;
                item.classList.toggle('d-none', !(matchName && matchCat));
                item.classList.toggle('d-flex', matchName && matchCat);
            });
        }

        modalSearch?.addEventListener('input', filterModalProducts);

        document.querySelectorAll('.category-pill').forEach(function(pill) {
            pill.addEventListener('click', function() {
                activeCategory = pill.dataset.category;
                document.querySelectorAll('.category-pill').forEach(function(p) {
                    const active = p === pill;
                    p.classList.toggle('btn-secondary', active);
                    p.classList.toggle('text-white', active);
                    p.classList.toggle('btn-light', !active);
                    p.classList.toggle('border', !active);
                    p.classList.toggle('text-dark', !active);
                });
                filterModalProducts();
            });
        });

        const selectAllModal = document.getElementById('selectAllModalProducts');
        const modalCbs = document.querySelectorAll('.modal-product-cb');
        const btnSubmitAdd = document.getElementById('btnSubmitAddProducts');
        const applyExistingModalCb = document.getElementById('addModalApplyExistingCheck');

        function syncAddBtn() {
            const checked = document.querySelectorAll('.modal-product-cb:checked').length;
            const applyExisting = applyExistingModalCb?.checked || false;
            if (btnSubmitAdd) {
                btnSubmitAdd.disabled = (checked === 0 && !applyExisting);
            }
        }

        applyExistingModalCb?.addEventListener('change', syncAddBtn);

        selectAllModal?.addEventListener('change', function() {
            modalCbs.forEach(function(cb) {
                const row = cb.closest('.modal-product-item');
                if (row && !row.classList.contains('d-none')) {
                    cb.checked = selectAllModal.checked;
                }
            });
            syncAddBtn();
        });

        modalCbs.forEach(function(cb) {
            cb.addEventListener('change', syncAddBtn);
        });

        // Modal Edit Harga Individual (gaya Olsera)
        const baseUpdateUrl = "{{ url('settings/pos/food-delivery/' . strtolower($normalizedPlatform) . '/products') }}";
        const editModalEl = document.getElementById('editPriceModal');
        const editForm = document.getElementById('editPriceForm');
        const editNameEl = document.getElementById('editPriceProductName');
        const editPhotoImg = document.getElementById('editPricePhotoImg');
        const editPhotoPlaceholder = document.getElementById('editPricePhotoPlaceholder');
        const editBaseText = document.getElementById('editPriceBaseText');
        const editPreviewText = document.getElementById('editPricePreviewText');
        const editDeltaInput = document.getElementById('editPriceDeltaInput');
        const editFinalInput = document.getElementById('editPriceFinalInput');
        const editPrefix = document.getElementById('editPricePrefix');
        const deleteSingleIdInput = document.getElementById('deleteSingleProductIdInput');
        const deleteSingleForm = document.getElementById('deleteSingleProductForm');

        let editBasePrice = 0;
        let editMode = 'markup';
        let editUnit = 'idr';

        function syncEditModeButtons() {
            document.querySelectorAll('.edit-mode-btn').forEach(function(b) {
                const active = b.dataset.mode === editMode;
                b.classList.toggle('btn-primary', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('btn-light', !active);
                b.classList.toggle('border', !active);
                b.classList.toggle('text-dark', !active);
            });
        }

        function syncEditUnitButtons() {
            document.querySelectorAll('.edit-unit-btn').forEach(function(b) {
                const active = b.dataset.unit === editUnit;
                b.classList.toggle('btn-primary', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('btn-light', !active);
                b.classList.toggle('border', !active);
                b.classList.toggle('text-dark', !active);
            });
            if (editPrefix) {
                editPrefix.textContent = editUnit === 'idr' ? 'Rp.' : '%';
            }
        }

        function recalcEditModalPrice() {
            const val = Math.max(0, parseFloat(editDeltaInput?.value || '0') || 0);
            const delta = editUnit === 'percent' ? (editBasePrice * (val / 100)) : val;
            const raw = editMode === 'markdown' ? (editBasePrice - delta) : (editBasePrice + delta);
            const finalPrice = Math.max(0, Math.round(raw));
            if (editFinalInput) {
                editFinalInput.value = String(finalPrice);
            }
            if (editPreviewText) {
                editPreviewText.textContent = 'Rp ' + finalPrice.toLocaleString('id-ID');
            }
        }

        document.querySelectorAll('.edit-mode-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                editMode = btn.dataset.mode || 'markup';
                syncEditModeButtons();
                recalcEditModalPrice();
            });
        });

        document.querySelectorAll('.edit-unit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const prevUnit = editUnit;
                editUnit = btn.dataset.unit || 'idr';
                if (prevUnit !== editUnit && editBasePrice > 0) {
                    const currentVal = Math.max(0, parseFloat(editDeltaInput?.value || '0') || 0);
                    if (editUnit === 'percent') {
                        editDeltaInput.value = ((currentVal / editBasePrice) * 100).toFixed(2);
                    } else {
                        editDeltaInput.value = String(Math.round(editBasePrice * (currentVal / 100)));
                    }
                }
                syncEditUnitButtons();
                recalcEditModalPrice();
            });
        });

        editDeltaInput?.addEventListener('input', recalcEditModalPrice);

        document.querySelectorAll('.btn-edit-price').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!editModalEl || !editForm) {
                    return;
                }
                const productId = btn.dataset.productId || '';
                const productName = btn.dataset.productName || '';
                const photoUrl = btn.dataset.productPhoto || '';
                editBasePrice = parseFloat(btn.dataset.basePrice || '0') || 0;
                const currentPrice = parseFloat(btn.dataset.currentPrice || '0') || 0;

                editForm.action = baseUpdateUrl + '/' + productId;
                if (deleteSingleIdInput) {
                    deleteSingleIdInput.value = productId;
                }
                if (editNameEl) {
                    editNameEl.textContent = productName;
                }
                if (editBaseText) {
                    editBaseText.textContent = 'IDR ' + Math.round(editBasePrice).toLocaleString('id-ID');
                }

                if (photoUrl) {
                    editPhotoImg?.setAttribute('src', photoUrl);
                    editPhotoImg?.classList.remove('d-none');
                    editPhotoPlaceholder?.classList.add('d-none');
                } else {
                    editPhotoImg?.classList.add('d-none');
                    editPhotoPlaceholder?.classList.remove('d-none');
                }

                const diff = currentPrice - editBasePrice;
                editMode = diff < 0 ? 'markdown' : 'markup';
                editUnit = 'idr';
                if (editDeltaInput) {
                    editDeltaInput.value = String(Math.round(Math.abs(diff)));
                }
                syncEditModeButtons();
                syncEditUnitButtons();
                recalcEditModalPrice();

                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            });
        });

        document.getElementById('btnDeleteSingleProduct')?.addEventListener('click', function() {
            const name = editNameEl?.textContent || 'produk ini';
            if (confirm('Hapus ' + name + ' dari daftar merchant?')) {
                deleteSingleForm?.submit();
            }
        });
    });
</script>
@endpush
@endsection