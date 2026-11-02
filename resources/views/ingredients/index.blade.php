@extends('layouts.app')

@section('title', 'Bahan Baku')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h5 class="mb-0 me-2">Daftar Bahan Baku</h5>
            <form action="{{ route('ingredients.index') }}" method="GET" class="d-flex gap-2" id="ingredientFilterForm">
                <div class="input-group input-group-sm" style="width: 300px;">
                    <input type="text" name="search" id="ingredientSearchInput" class="form-control" placeholder="Cari bahan baku..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-lg"></i> Tambah Bahan Baku
            </button>
        </div>
    </div>

    <div class="card-body p-0" id="ingredient-table-container">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0" style="white-space: nowrap;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Bahan Baku</th>
                        <th>Outlet</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
                        <th class="text-center">Stok Saat Ini</th>
                        <th width="16%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ingredients as $ingredient)
                    @php
                    $linkedRecords = isset($ingredientOutletsMap)
                    ? $ingredientOutletsMap->get($ingredient->cname, collect([$ingredient]))
                    : collect([$ingredient]);
                    $ingredientOutletIds = $linkedRecords->pluck('nid_outlet')->filter()->toArray();
                    @endphp
                    <tr>
                        <td class="ps-4 fw-semibold text-dark">{{ $ingredient->cname }}</td>
                        <td>
                            @if(!empty($selectedOutletId) && $ingredient->outlet)
                            <span class="badge bg-light text-dark border">{{ $ingredient->outlet->cname }}</span>
                            @else
                            @php $hasOutlets = false; @endphp
                            @foreach($linkedRecords as $io)
                            @if($io->outlet)
                            <span class="badge bg-light text-dark border mb-1">{{ $io->outlet->cname }}</span><br>
                            @php $hasOutlets = true; @endphp
                            @endif
                            @endforeach
                            @if(!$hasOutlets)
                            <span class="text-muted">-</span>
                            @endif
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-medium">
                                {{ $ingredient->csatuan ?: 'PCS' }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold text-dark">
                            Rp {{ rtrim(rtrim(number_format((float) ($ingredient->nprice ?? 0), 2, ',', '.'), '0'), ',') }}
                        </td>
                        <td class="text-center fw-semibold text-dark">
                            {{ rtrim(rtrim(number_format((float) $ingredient->nstock, 2, ',', '.'), '0'), ',') }}
                            <span class="text-muted fw-normal small">{{ $ingredient->csatuan ?: 'PCS' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $ingredient->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $ingredient->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $ingredient->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $ingredient->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $ingredient->nid }}">Edit Bahan Baku</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('ingredients.update', $ingredient->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $ingredient->nid }}">
                                    <div class="modal-body text-start">
                                        @if ($errors->any() && old('modal_id') == $ingredient->nid)
                                        <div class="alert alert-danger py-2 small">
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif

                                        <div class="mb-3">
                                            <label for="cname_{{ $ingredient->nid }}" class="form-label">Nama Bahan Baku <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @if(old('modal_id') == $ingredient->nid) @error('cname') is-invalid @enderror @endif"
                                                id="cname_{{ $ingredient->nid }}"
                                                name="cname"
                                                value="{{ old('modal_id') == $ingredient->nid ? old('cname') : $ingredient->cname }}"
                                                required>
                                            @if(old('modal_id') == $ingredient->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                                            <div class="dropdown ingredient-outlet-dropdown @if(old('modal_id') == $ingredient->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                                <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    data-bs-auto-close="outside"
                                                    aria-expanded="false"
                                                    id="dropdownBtn_ing_{{ $ingredient->nid }}">
                                                    <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                </button>
                                                <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 240px; overflow-y: auto;">
                                                    @if(count($outlets) > 1)
                                                    <li class="border-bottom pb-1 mb-1">
                                                        <div class="form-check dropdown-item rounded py-1 px-3 d-flex align-items-center">
                                                            <input class="form-check-input me-2 select-all-outlets" style="margin-left: 0; margin-top: 0;" type="checkbox" id="ing_select_all_{{ $ingredient->nid }}">
                                                            <label class="form-check-label w-100 ms-2 fw-semibold small text-dark" for="ing_select_all_{{ $ingredient->nid }}" style="cursor:pointer;">
                                                                Pilih Semua Outlet
                                                            </label>
                                                        </div>
                                                    </li>
                                                    @endif
                                                    @foreach($outlets as $outlet)
                                                    @php
                                                    if (old('modal_id') == $ingredient->nid && is_array(old('outlet_ids'))) {
                                                    $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                                    } else {
                                                    $isChecked = in_array($outlet->nid, $ingredientOutletIds);
                                                    }
                                                    @endphp
                                                    <li>
                                                        <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                            <input class="form-check-input me-2 outlet-checkbox"
                                                                style="margin-left: 0; margin-top: 0;"
                                                                type="checkbox"
                                                                name="outlet_ids[]"
                                                                value="{{ $outlet->nid }}"
                                                                id="edit_ing_outlet_{{ $ingredient->nid }}_{{ $outlet->nid }}"
                                                                data-name="{{ $outlet->cname }}"
                                                                {{ $isChecked ? 'checked' : '' }}>
                                                            <label class="form-check-label w-100 ms-2" for="edit_ing_outlet_{{ $ingredient->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                {{ $outlet->cname }}
                                                                @if($ingredient->nid_outlet == $outlet->nid)
                                                                <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @if(old('modal_id') == $ingredient->nid) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                        </div>

                                        <div class="mb-3">
                                            <label for="nprice_{{ $ingredient->nid }}" class="form-label">Harga Bahan Baku (Rp)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted">Rp</span>
                                                <input type="number"
                                                    step="any"
                                                    min="0"
                                                    class="form-control @if(old('modal_id') == $ingredient->nid) @error('nprice') is-invalid @enderror @endif"
                                                    id="nprice_{{ $ingredient->nid }}"
                                                    name="nprice"
                                                    placeholder="0"
                                                    value="{{ old('modal_id') == $ingredient->nid ? old('nprice') : (float) ($ingredient->nprice ?? 0) }}">
                                                @if(old('modal_id') == $ingredient->nid) @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="csatuan_{{ $ingredient->nid }}" class="form-label">Satuan <span class="text-danger">*</span></label>
                                                <select class="form-select @if(old('modal_id') == $ingredient->nid) @error('csatuan') is-invalid @enderror @endif"
                                                    id="csatuan_{{ $ingredient->nid }}"
                                                    name="csatuan"
                                                    required>
                                                    <option value="">-- Pilih Satuan --</option>
                                                    @php
                                                        $currentSatuan = old('modal_id') == $ingredient->nid ? old('csatuan') : ($ingredient->csatuan ?: 'PCS');
                                                        $satuanMatched = false;
                                                    @endphp
                                                    @foreach($satuans as $satuan)
                                                        @php
                                                            $isSelected = strcasecmp($currentSatuan, $satuan) === 0;
                                                            if ($isSelected) $satuanMatched = true;
                                                        @endphp
                                                        <option value="{{ $satuan }}" {{ $isSelected ? 'selected' : '' }}>
                                                            {{ $satuan }}
                                                        </option>
                                                    @endforeach
                                                    @if(!$satuanMatched && !empty($currentSatuan))
                                                        <option value="{{ $currentSatuan }}" selected>{{ $currentSatuan }}</option>
                                                    @endif
                                                </select>
                                                @if(old('modal_id') == $ingredient->nid) @error('csatuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="nstock_{{ $ingredient->nid }}" class="form-label">Stok Saat Ini</label>
                                                <input type="number"
                                                    step="any"
                                                    min="0"
                                                    class="form-control @if(old('modal_id') == $ingredient->nid) @error('nstock') is-invalid @enderror @endif"
                                                    id="nstock_{{ $ingredient->nid }}"
                                                    name="nstock"
                                                    value="{{ old('modal_id') == $ingredient->nid ? old('nstock') : (float) $ingredient->nstock }}"
                                                    required>
                                                @if(old('modal_id') == $ingredient->nid) @error('nstock') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
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

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal{{ $ingredient->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $ingredient->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $ingredient->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah Anda ingin menghapus bahan baku <strong>{{ $ingredient->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('ingredients.destroy', $ingredient->nid) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data bahan baku.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ingredients->hasPages())
        <div class="d-flex justify-content-end p-3">
            {{ $ingredients->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Bahan Baku Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ingredients.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    @if ($errors->any() && old('modal_id') == 'create')
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Bahan Baku <span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif"
                            id="cname"
                            name="cname"
                            placeholder="Contoh: Biji Kopi Arabica, Susu UHT, Gula Aren"
                            value="{{ old('modal_id') == 'create' ? old('cname') : '' }}"
                            required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                        <div class="dropdown ingredient-outlet-dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                id="dropdownBtn_ing_create">
                                <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                            </button>
                            <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 240px; overflow-y: auto;">
                                @if(count($outlets) > 1)
                                <li class="border-bottom pb-1 mb-1">
                                    <div class="form-check dropdown-item rounded py-1 px-3 d-flex align-items-center">
                                        <input class="form-check-input me-2 select-all-outlets" style="margin-left: 0; margin-top: 0;" type="checkbox" id="ing_select_all_create">
                                        <label class="form-check-label w-100 ms-2 fw-semibold small text-dark" for="ing_select_all_create" style="cursor:pointer;">
                                            Pilih Semua Outlet
                                        </label>
                                    </div>
                                </li>
                                @endif
                                @foreach($outlets as $outlet)
                                @php
                                $createChecked = old('modal_id') == 'create'
                                ? (is_array(old('outlet_ids')) && in_array($outlet->nid, old('outlet_ids')))
                                : (!empty($selectedOutletId)
                                ? ((string) $selectedOutletId === (string) $outlet->nid)
                                : (count($outlets) === 1));
                                @endphp
                                <li>
                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                        <input class="form-check-input me-2 outlet-checkbox"
                                            style="margin-left: 0; margin-top: 0;"
                                            type="checkbox"
                                            name="outlet_ids[]"
                                            value="{{ $outlet->nid }}"
                                            id="create_ing_outlet_{{ $outlet->nid }}"
                                            data-name="{{ $outlet->cname }}"
                                            {{ $createChecked ? 'checked' : '' }}>
                                        <label class="form-check-label w-100 ms-2" for="create_ing_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                            {{ $outlet->cname }}
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @if(old('modal_id') == 'create') @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>

                    <div class="mb-3">
                        <label for="nprice" class="form-label">Harga Bahan Baku (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number"
                                step="any"
                                min="0"
                                class="form-control @if(old('modal_id') == 'create') @error('nprice') is-invalid @enderror @endif"
                                id="nprice"
                                name="nprice"
                                placeholder="0"
                                value="{{ old('modal_id') == 'create' ? old('nprice', '0') : '0' }}">
                            @if(old('modal_id') == 'create') @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="csatuan" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select class="form-select @if(old('modal_id') == 'create') @error('csatuan') is-invalid @enderror @endif"
                                id="csatuan"
                                name="csatuan"
                                required>
                                <option value="">-- Pilih Satuan --</option>
                                @php
                                    $createSatuan = old('modal_id') == 'create' ? old('csatuan', 'PCS') : 'PCS';
                                @endphp
                                @foreach($satuans as $satuan)
                                    <option value="{{ $satuan }}" {{ strcasecmp($createSatuan, $satuan) === 0 ? 'selected' : '' }}>
                                        {{ $satuan }}
                                    </option>
                                @endforeach
                            </select>
                            @if(old('modal_id') == 'create') @error('csatuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nstock" class="form-label">Stok Awal</label>
                            <input type="number"
                                step="any"
                                min="0"
                                class="form-control @if(old('modal_id') == 'create') @error('nstock') is-invalid @enderror @endif"
                                id="nstock"
                                name="nstock"
                                value="{{ old('modal_id') == 'create' ? old('nstock', '0') : '0' }}">
                            @if(old('modal_id') == 'create') @error('nstock') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
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

<datalist id="ingredientSatuanOptions">
    @foreach($satuans as $satuan)
    <option value="{{ $satuan }}"></option>
    @endforeach
</datalist>
@endsection

@push('scripts')
<script>
    function initIngredientModalsJS() {
        function updateDropdownText(dropdown) {
            const button = dropdown.querySelector('.dropdown-toggle');
            if (!button) return;
            const checkboxes = dropdown.querySelectorAll('.outlet-checkbox');
            const checkedBoxes = dropdown.querySelectorAll('.outlet-checkbox:checked');
            const selectAll = dropdown.querySelector('.select-all-outlets');
            const selectedText = button.querySelector('.selected-text');
            if (!selectedText) return;

            if (selectAll && checkboxes.length > 0) {
                selectAll.checked = checkedBoxes.length === checkboxes.length;
                selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
            }

            if (checkedBoxes.length === 0) {
                selectedText.textContent = 'Pilih Outlet...';
            } else if (checkedBoxes.length === 1) {
                selectedText.textContent = checkedBoxes[0].getAttribute('data-name');
            } else if (checkedBoxes.length === checkboxes.length && checkboxes.length > 1) {
                selectedText.textContent = 'Semua Outlet (' + checkedBoxes.length + ')';
            } else {
                selectedText.textContent = checkedBoxes.length + ' Outlet Terpilih';
            }
        }

        document.querySelectorAll('.ingredient-outlet-dropdown').forEach(dropdown => {
            dropdown.querySelectorAll('.outlet-checkbox').forEach(cb => {
                cb.onchange = () => updateDropdownText(dropdown);
            });

            const selectAll = dropdown.querySelector('.select-all-outlets');
            if (selectAll) {
                selectAll.onchange = function() {
                    dropdown.querySelectorAll('.outlet-checkbox').forEach(cb => {
                        cb.checked = selectAll.checked;
                    });
                    updateDropdownText(dropdown);
                };
            }

            updateDropdownText(dropdown);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId) {
            if (modalId === 'create') {
                var createModal = document.getElementById('createModal');
                if (createModal) {
                    new bootstrap.Modal(createModal).show();
                }
            } else {
                var editModal = document.getElementById('editModal' + modalId);
                if (editModal) {
                    new bootstrap.Modal(editModal).show();
                }
            }
        }

        initIngredientModalsJS();

        const searchInput = document.getElementById('ingredientSearchInput');
        const filterForm = document.getElementById('ingredientFilterForm');
        const tableContainer = document.getElementById('ingredient-table-container');
        let searchTimeout;

        function fetchIngredientResults() {
            if (!filterForm || !tableContainer) return;
            const url = new URL(filterForm.action);
            const params = new URLSearchParams(new FormData(filterForm));
            url.search = params.toString();

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('ingredient-table-container');
                    if (newContent) {
                        tableContainer.innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url);
                        initIngredientModalsJS();
                    }
                })
                .catch(err => console.error("Error fetching ingredients:", err));
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchIngredientResults, 450);
            });
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchIngredientResults();
            });
        }
    });
</script>
@endpush