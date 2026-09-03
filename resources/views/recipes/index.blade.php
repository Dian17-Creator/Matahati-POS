@extends('layouts.app')

@section('title', 'Resep Produk')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Resep Produk</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th width="30%" class="py-3 ps-4">Produk Utama</th>
                        <th>Bahan Baku</th>
                        <th>Kuantitas (Qty)</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recipes as $recipe)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-journal-text fs-5"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $recipe->product->cname ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $recipe->ingredient->cname ?? '-' }}</td>
                        <td>{{ $recipe->nqty }} <small class="text-muted">{{ $recipe->ingredient->csatuan ?? '' }}</small></td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $recipe->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $recipe->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $recipe->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $recipe->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $recipe->nid }}">Edit Resep</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('recipes.update', $recipe->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $recipe->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="nid_product_{{ $recipe->nid }}" class="form-label">Produk Utama</label>
                                            <select class="form-select @if(old('modal_id') == $recipe->nid) @error('nid_product') is-invalid @enderror @endif" id="nid_product_{{ $recipe->nid }}" name="nid_product" required>
                                                <option value="">Pilih Produk</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->nid }}" {{ (old('modal_id') == $recipe->nid ? old('nid_product') : $recipe->nid_product) == $product->nid ? 'selected' : '' }}>
                                                        {{ $product->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $recipe->nid) @error('nid_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nid_ingredient_{{ $recipe->nid }}" class="form-label">Bahan Baku</label>
                                            <select class="form-select @if(old('modal_id') == $recipe->nid) @error('nid_ingredient') is-invalid @enderror @endif" id="nid_ingredient_{{ $recipe->nid }}" name="nid_ingredient" required>
                                                <option value="">Pilih Bahan Baku</option>
                                                @foreach($ingredients as $ingredient)
                                                    <option value="{{ $ingredient->nid }}" {{ (old('modal_id') == $recipe->nid ? old('nid_ingredient') : $recipe->nid_ingredient) == $ingredient->nid ? 'selected' : '' }}>
                                                        {{ $ingredient->cname }} ({{ $ingredient->csatuan }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $recipe->nid) @error('nid_ingredient') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nqty_{{ $recipe->nid }}" class="form-label">Kuantitas (Qty)</label>
                                            <input type="number" step="any" min="0.01" class="form-control @if(old('modal_id') == $recipe->nid) @error('nqty') is-invalid @enderror @endif" id="nqty_{{ $recipe->nid }}" name="nqty" value="{{ old('modal_id') == $recipe->nid ? old('nqty') : $recipe->nqty }}" required>
                                            @if(old('modal_id') == $recipe->nid) @error('nqty') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $recipe->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $recipe->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $recipe->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Hapus bahan resep ini?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('recipes.destroy', $recipe->nid) }}" method="POST">
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
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data resep.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $recipes->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Resep Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('recipes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nid_product" class="form-label">Produk Utama</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_product') is-invalid @enderror @endif" id="nid_product" name="nid_product" required>
                            <option value="">Pilih Produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->nid }}" {{ (old('modal_id') == 'create' ? old('nid_product') : '') == $product->nid ? 'selected' : '' }}>
                                    {{ $product->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nid_ingredient" class="form-label">Bahan Baku</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_ingredient') is-invalid @enderror @endif" id="nid_ingredient" name="nid_ingredient" required>
                            <option value="">Pilih Bahan Baku</option>
                            @foreach($ingredients as $ingredient)
                                <option value="{{ $ingredient->nid }}" {{ (old('modal_id') == 'create' ? old('nid_ingredient') : '') == $ingredient->nid ? 'selected' : '' }}>
                                    {{ $ingredient->cname }} ({{ $ingredient->csatuan }})
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_ingredient') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nqty" class="form-label">Kuantitas (Qty)</label>
                        <input type="number" step="any" min="0.01" class="form-control @if(old('modal_id') == 'create') @error('nqty') is-invalid @enderror @endif" id="nqty" name="nqty" value="{{ old('modal_id') == 'create' ? old('nqty') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('nqty') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
    });
</script>
@endpush
