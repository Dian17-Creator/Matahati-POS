@extends('layouts.app')

@section('title', 'Kategori Produk')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Daftar Kategori</h5>
            <form action="{{ route('categories.index') }}" method="GET" class="d-flex">
                <select name="nid_outlet" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                    <option value="{{ $outlet->nid }}" {{ request('nid_outlet') == $outlet->nid ? 'selected' : '' }}>
                        {{ $outlet->cname }}
                    </option>
                    @endforeach
                </select>
            </form>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <!-- <th width="10%">ID</th> -->
                        <th>Nama Kategori</th>
                        <th>Kelola Stok</th>
                        <th>Outlet</th>
                        <th width="20%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                    <tr>
                        <!-- <td>{{ $category->nid }}</td> -->
                        <td>{{ $category->cname }}</td>
                        <td>
                            @if($category->ftrack_stock)
                            <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-box-seam me-1"></i> Ya (Kelola Stok)</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="bi bi-infinity me-1"></i> Tidak (F&B / Tanpa Stok)</span>
                            @endif
                        </td>
                        <td>
                            @php
                            $categoryOutlets = \App\Models\MposGrpProduct::where('cname', $category->cname)->with('outlet')->get();
                            $hasOutlets = false;
                            @endphp
                            @foreach($categoryOutlets as $co)
                            @if($co->outlet)
                            <span class="badge bg-light text-dark border mb-1">{{ $co->outlet->cname }}</span><br>
                            @php $hasOutlets = true; @endphp
                            @endif
                            @endforeach
                            @if(!$hasOutlets)
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->nid }}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $category->nid }}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $category->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $category->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $category->nid }}">Edit Kategori</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('categories.update', $category->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $category->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="cname_{{ $category->nid }}" class="form-label">Nama Kategori</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $category->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $category->nid }}" name="cname" value="{{ old('modal_id') == $category->nid ? old('cname') : $category->cname }}" required>
                                            @if(old('modal_id') == $category->nid)
                                            @error('cname')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @endif
                                        </div>
                                        <div class="mb-3 p-2 bg-light rounded border">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" id="ftrack_stock_{{ $category->nid }}" name="ftrack_stock" value="1" {{ old('modal_id') == $category->nid ? (old('ftrack_stock') ? 'checked' : '') : ($category->ftrack_stock ? 'checked' : '') }}>
                                                <label class="form-check-label fw-bold" for="ftrack_stock_{{ $category->nid }}">
                                                    Kelola Stok Produk
                                                </label>
                                            </div>
                                            <div class="form-text small text-muted" style="font-size: 0.78rem;">
                                                Aktifkan untuk barang retail fisik (IQOS/Device/Pod). Matikan untuk Makanan, Minuman, atau Snack agar kasir bisa bertransaksi tanpa batasan stok.
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                                            <div class="dropdown @if(old('modal_id') == $category->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                                <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_{{ $category->nid }}">
                                                    <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                </button>
                                                <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                                    @php
                                                    $categoryOutletIds = \App\Models\MposGrpProduct::where('cname', $category->cname)->pluck('nid_outlet')->toArray();
                                                    @endphp
                                                    @foreach($outlets as $outlet)
                                                    @php
                                                    $isChecked = false;
                                                    if (old('modal_id') == $category->nid && old('outlet_ids')) {
                                                    $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                                    } else {
                                                    $isChecked = in_array($outlet->nid, $categoryOutletIds);
                                                    }
                                                    @endphp
                                                    <li>
                                                        <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $category->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }}>
                                                            <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $category->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                {{ $outlet->cname }}
                                                                @if($category->nid_outlet == $outlet->nid)
                                                                <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @if(old('modal_id') == $category->nid) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal{{ $category->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $category->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $category->nid }}">Confirm Delete</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus kategori <strong>{{ $category->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('categories.destroy', $category->nid) }}" method="POST">
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
                        <td colspan="4" class="text-center text-muted py-4">No categories found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $categories->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create')
                        @error('cname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @endif
                    </div>
                    <div class="mb-3 p-2 bg-light rounded border">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="ftrack_stock_create" name="ftrack_stock" value="1" {{ old('modal_id') == 'create' ? (old('ftrack_stock') ? 'checked' : '') : 'checked' }}>
                            <label class="form-check-label fw-bold" for="ftrack_stock_create">
                                Kelola Stok Produk
                            </label>
                        </div>
                        <div class="form-text small text-muted" style="font-size: 0.78rem;">
                            Aktifkan untuk barang retail fisik (IQOS/Device/Pod). Matikan untuk Makanan, Minuman, atau Snack agar kasir bisa bertransaksi tanpa batasan stok.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                        <div class="dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_create">
                                <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                            </button>
                            <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                @foreach($outlets as $outlet)
                                <li>
                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                        <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ (is_array(old('outlet_ids')) && in_array($outlet->nid, old('outlet_ids')) && old('modal_id') == 'create') ? 'checked' : '' }}>
                                        <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                            {{ $outlet->cname }}
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @if(old('modal_id') == 'create')
                        @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @endif
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
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId) {
            if (modalId === 'create') {
                var myModal = new bootstrap.Modal(document.getElementById('createModal'));
                myModal.show();
            } else {
                var myModal = new bootstrap.Modal(document.getElementById('editModal' + modalId));
                myModal.show();
            }
        }

        // Handle Dropdown Outlet Text Update
        function updateDropdownText() {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                const button = dropdown.querySelector('.dropdown-toggle');
                if (!button) return;
                const checkboxes = dropdown.querySelectorAll('.outlet-checkbox:checked');
                const selectedText = button.querySelector('.selected-text');
                if (!selectedText) return;

                if (checkboxes.length === 0) {
                    selectedText.textContent = 'Pilih Outlet...';
                } else if (checkboxes.length === 1) {
                    selectedText.textContent = checkboxes[0].getAttribute('data-name');
                } else {
                    selectedText.textContent = checkboxes.length + ' Outlet Terpilih';
                }
            });
        }

        document.querySelectorAll('.outlet-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateDropdownText);
        });

        // Initial update
        updateDropdownText();
    });
</script>
@endpush