@extends('layouts.app')

@section('title', 'Detail Paket Produk')

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
    .ts-dropdown {
        z-index: 1065 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        font-size: 0.875rem !important;
        border: 1px solid #e2e8f0 !important;
    }
    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }
</style>
@endpush

@section('content')
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

<div class="row g-3">
    <!-- Kolom Kiri: Info Paket (seperti Gambar 2) -->
    <div class="col-lg-7">
        <div class="card olsera-panel-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">{{ $combo->cname }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('combos.index') }}" class="btn btn-sm btn-outline-success px-3">Kembali</a>
                    <button type="button" class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#editComboInfoModal">
                        <i class="bi bi-pencil me-1"></i> Ubah
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="mb-3" style="max-width: 320px;">
                    @if(!empty($combo->cphotos))
                        <img src="{{ asset($combo->cphotos) }}" alt="{{ $combo->cname }}" class="img-fluid rounded border w-100" style="max-height: 220px; object-fit: cover;">
                    @else
                        <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="height: 180px;">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    @endif
                </div>

                <form action="{{ route('combos.update', $combo->nid) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    @method('PUT')
                    <label class="btn btn-light border w-100 py-2 fw-medium" style="cursor: pointer;">
                        <i class="bi bi-image me-1"></i> Ganti Foto
                        <input type="file" name="photo" class="d-none" accept="image/*" onchange="this.form.submit()">
                    </label>
                </form>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="small text-muted">Nama Paket Produk</div>
                            <div class="fw-semibold">{{ $combo->cname }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="small text-muted">Outlet</div>
                            <div class="fw-semibold">{{ $combo->outlet?->cname ?? '-' }}</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="small text-muted">Harga Beli</div>
                                <div class="fw-semibold">IDR {{ number_format((float) $combo->nprice_buy, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Harga Jual Online</div>
                                <div class="fw-semibold">IDR {{ number_format((float) $combo->nprice_online, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6 mt-2">
                                <div class="small text-muted">Harga Jual di Toko</div>
                                <div class="fw-semibold text-primary">IDR {{ number_format((float) $combo->nprice, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Informasi Tambahan</h6>
                        <div class="mb-3">
                            <div class="small text-muted">Status Publikasi</div>
                            @if(strtoupper((string) $combo->cstatus) === 'ACTIVE')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktif / Dipublikasikan</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">Nonaktif</span>
                            @endif
                        </div>
                        <div>
                            <div class="small text-muted">Tanggal Mulai Jual (Publikasi)</div>
                            <div class="fw-semibold">
                                {{ $combo->dpublish_date ? $combo->dpublish_date->format('Y-m-d') : ($combo->dcreated ? $combo->dcreated->format('Y-m-d') : '-') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Produk dalam Paket (seperti Gambar 2) -->
    <div class="col-lg-5">
        <div class="card olsera-panel-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold">Produk dalam Paket</h6>
                <button type="button" class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#editComboItemsModal">
                    <i class="bi bi-pencil me-1"></i> Ubah
                </button>
            </div>

            <div class="card-body p-3">
                <button type="button" class="btn btn-light border w-100 py-2 mb-3 fw-medium" data-bs-toggle="modal" data-bs-target="#editComboItemsModal">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                </button>

                <div class="list-group list-group-flush">
                    @forelse($combo->comboItems as $item)
                    <div class="list-group-item border rounded mb-2 d-flex align-items-center gap-3 py-2">
                        <i class="bi bi-arrows-move text-muted"></i>
                        @if(!empty($item->product?->cphotos))
                            <img src="{{ asset($item->product->cphotos) }}" alt="{{ $item->product->cname }}" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 36px; height: 36px;">
                                <i class="bi bi-box-seam small"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">{{ $item->product?->cname ?? 'Produk Dihapus' }}</div>
                            <div class="small text-muted">{{ (int) $item->nqty }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4 small">Belum ada produk di dalam paket ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ubah Informasi Paket -->
<div class="modal fade" id="editComboInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('combos.update', $combo->nid) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="has_publish_toggle" value="1">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ubah Informasi Paket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Paket Produk</label>
                        <input type="text" name="cname" class="form-control" value="{{ $combo->cname }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Beli</label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice_buy" class="form-control rupiah-input" value="{{ number_format((float) $combo->nprice_buy, 0, ',', '.') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Jual Online</label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice_online" class="form-control rupiah-input" value="{{ number_format((float) $combo->nprice_online, 0, ',', '.') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga Jual di Toko</label>
                        <div class="input-group">
                            <span class="input-group-text">IDR</span>
                            <input type="text" inputmode="numeric" name="nprice" class="form-control rupiah-input" value="{{ number_format((float) $combo->nprice, 0, ',', '.') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Mulai Jual (Publikasi)</label>
                        <input type="date" name="dpublish_date" class="form-control" value="{{ $combo->dpublish_date ? $combo->dpublish_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="editPublishSwitch" name="is_published" value="1" {{ strtoupper((string) $combo->cstatus) === 'ACTIVE' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="editPublishSwitch">Siap publikasikan untuk dijual</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ubah / Tambah Produk dalam Paket -->
<div class="modal fade" id="editComboItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('combos.items.update', $combo->nid) }}" method="POST" id="editItemsForm">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Atur Produk dalam Paket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 align-items-stretch mb-3">
                        <div class="flex-grow-1">
                            <select id="editItemPicker">
                                <option value=""></option>
                                @foreach($regularProducts as $prod)
                                <option value="{{ $prod->nid }}" data-name="{{ $prod->cname }}" data-photo="{{ !empty($prod->cphotos) ? asset($prod->cphotos) : '' }}">
                                    {{ $prod->cname }} (IDR {{ number_format((float) $prod->nprice, 0, ',', '.') }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-primary text-nowrap" id="btnAddEditItem">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </div>

                    <div id="editComboItemsContainer" class="list-group list-group-flush border rounded">
                        @foreach($combo->comboItems as $item)
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2 edit-combo-item-row" data-id="{{ $item->nid_product }}">
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                @if(!empty($item->product?->cphotos))
                                    <img src="{{ asset($item->product->cphotos) }}" class="rounded-circle border" style="width: 34px; height: 34px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 34px; height: 34px;"><i class="bi bi-box-seam small"></i></div>
                                @endif
                                <span class="fw-medium text-dark">{{ $item->product?->cname ?? 'Produk' }}</span>
                                <input type="hidden" name="nid_product[]" value="{{ $item->nid_product }}">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" name="nqty[]" class="form-control form-control-sm text-center edit-qty-input" value="{{ (int) $item->nqty }}" min="1" step="1" style="width: 72px;" required>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-edit-item" title="Hapus"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('editItemPicker');
    const btnAdd = document.getElementById('btnAddEditItem');
    const container = document.getElementById('editComboItemsContainer');

    const editTomSelect = picker ? new TomSelect(picker, {
        create: false,
        maxItems: 1,
        allowEmptyOption: true,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    }) : null;

    function bindRemove(btn) {
        btn.addEventListener('click', function () {
            btn.closest('.edit-combo-item-row')?.remove();
        });
    }

    container?.querySelectorAll('.btn-remove-edit-item').forEach(bindRemove);

    btnAdd?.addEventListener('click', function () {
        const id = editTomSelect ? editTomSelect.getValue() : (picker?.value || '');
        if (!id) {
            return;
        }
        const opt = picker?.querySelector('option[value="' + id + '"]');
        if (!opt) {
            return;
        }
        const name = opt.dataset.name || opt.text;
        const photo = opt.dataset.photo || '';

        const existing = container.querySelector('.edit-combo-item-row[data-id="' + id + '"]');
        if (existing) {
            const qtyInput = existing.querySelector('.edit-qty-input');
            qtyInput.value = String((parseInt(qtyInput.value || '1', 10) || 1) + 1);
            if (editTomSelect) {
                editTomSelect.clear(true);
            } else if (picker) {
                picker.value = '';
            }
            return;
        }

        const row = document.createElement('div');
        row.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 py-2 edit-combo-item-row';
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
                '<input type="number" name="nqty[]" class="form-control form-control-sm text-center edit-qty-input" value="1" min="1" step="1" style="width: 72px;" required>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-edit-item" title="Hapus"><i class="bi bi-trash"></i></button>' +
            '</div>';

        bindRemove(row.querySelector('.btn-remove-edit-item'));
        container.appendChild(row);
        if (editTomSelect) {
            editTomSelect.clear(true);
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

    document.getElementById('editItemsForm')?.addEventListener('submit', function (e) {
        if (container.querySelectorAll('.edit-combo-item-row').length === 0) {
            e.preventDefault();
            alert('Paket harus memiliki minimal 1 produk isi.');
        }
    });
});
</script>
@endpush
@endsection
