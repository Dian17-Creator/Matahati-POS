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
                        <th>Bahan Baku & Kuantitas</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recipes as $nid_product => $recipeGroup)
                    @php $mainProduct = $recipeGroup->first()->product; @endphp
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-journal-text fs-5"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $mainProduct->cname ?? 'ID: ' . $nid_product }}</span>
                            </div>
                        </td>
                        <td class="py-3">
                            <ul class="list-unstyled mb-0">
                                @foreach($recipeGroup as $item)
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="bi bi-check2 text-success me-2 fw-bold"></i> 
                                    {{ $item->ingredient->cname ?? 'ID: ' . $item->nid_ingredient }}
                                    <span class="badge bg-light text-dark border ms-2">{{ $item->nqty }} <small class="text-muted">{{ $item->ingredient->csatuan ?? '' }}</small></span>
                                </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $nid_product }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $nid_product }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            <i class="bi bi-journal-x fs-1 d-block mb-3 opacity-50"></i>
                            Tidak ada data resep produk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $paginatedRecipes->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@foreach ($recipes as $nid_product => $recipeGroup)
@php $mainProduct = $recipeGroup->first()->product; @endphp
<!-- Edit Modal -->
<div class="modal fade" id="editModal{{ $nid_product }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Edit Resep Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('recipes.update', $nid_product) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="modal_id" value="{{ $nid_product }}">
                <input type="hidden" name="nid_product" value="{{ $nid_product }}">
                <div class="modal-body text-start">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Produk Utama</label>
                        <div class="form-control bg-light border-0 fw-bold">{{ $mainProduct->cname ?? 'ID: ' . $nid_product }}</div>
                    </div>
                    
                    <label class="form-label text-muted small fw-bold text-uppercase">Bahan Baku</label>
                    <div id="edit-ingredients-container-{{ $nid_product }}">
                        @foreach($recipeGroup as $item)
                        <div class="row mb-2 recipe-item-row align-items-center">
                            <div class="col-7">
                                <select class="form-select" name="nid_ingredient[]" required>
                                    <option value="">Pilih Bahan Baku</option>
                                    @foreach($ingredients as $ingredient)
                                        <option value="{{ $ingredient->nid }}" {{ $item->nid_ingredient == $ingredient->nid ? 'selected' : '' }}>
                                            {{ $ingredient->cname }} ({{ $ingredient->csatuan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" step="any" min="0.01" class="form-control" name="nqty[]" value="{{ $item->nqty }}" required placeholder="Qty">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.recipe-item-row').remove()" title="Hapus baris">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="addRecipeIngredient('edit-ingredients-container-{{ $nid_product }}')">
                        <i class="bi bi-plus-lg"></i> Tambah Bahan Baku
                    </button>
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
<div class="modal fade" id="deleteModal{{ $nid_product }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                <h5 class="mb-3">Hapus resep ini?</h5>
                <p class="text-muted mb-0">Seluruh bahan baku untuk produk ini akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <form action="{{ route('recipes.destroy', $nid_product) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Ya, Hapus Semua</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Resep Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('recipes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Produk Utama</label>
                        <select class="form-select" name="nid_product" required>
                            <option value="">Pilih Produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->nid }}">{{ $product->cname }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <label class="form-label text-muted small fw-bold text-uppercase">Bahan Baku</label>
                    <div id="create-ingredients-container">
                        <div class="row mb-2 recipe-item-row align-items-center">
                            <div class="col-7">
                                <select class="form-select" name="nid_ingredient[]" required>
                                    <option value="">Pilih Bahan Baku</option>
                                    @foreach($ingredients as $ingredient)
                                        <option value="{{ $ingredient->nid }}">{{ $ingredient->cname }} ({{ $ingredient->csatuan }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" step="any" min="0.01" class="form-control" name="nqty[]" value="1" required placeholder="Qty">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.recipe-item-row').remove()" title="Hapus baris">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="addRecipeIngredient('create-ingredients-container')">
                        <i class="bi bi-plus-lg"></i> Tambah Bahan Baku
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ingredientOptions = `
        <option value="">Pilih Bahan Baku</option>
        @foreach($ingredients as $ingredient)
            <option value="{{ $ingredient->nid }}">{{ $ingredient->cname }} ({{ $ingredient->csatuan }})</option>
        @endforeach
    `;

    function addRecipeIngredient(containerId) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'row mb-2 recipe-item-row align-items-center';
        row.innerHTML = `
            <div class="col-7">
                <select class="form-select" name="nid_ingredient[]" required>
                    ${ingredientOptions}
                </select>
            </div>
            <div class="col-3">
                <input type="number" step="any" min="0.01" class="form-control" name="nqty[]" value="1" required placeholder="Qty">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.recipe-item-row').remove()" title="Hapus baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    document.addEventListener("DOMContentLoaded", function() {
        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";
        
        if (hasErrors && modalId) {
            if (modalId === 'create') {
                var myModal = new bootstrap.Modal(document.getElementById('createModal'));
                myModal.show();
            } else {
                var editModal = document.getElementById('editModal' + modalId);
                if(editModal) {
                    var myModal = new bootstrap.Modal(editModal);
                    myModal.show();
                }
            }
        }
    });
</script>
@endpush
