@extends('layouts.app')

@section('title', 'Produk')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Produk</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="8%">Foto</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Kombo</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td>
                            @if($product->cphotos)
                                <img src="{{ asset($product->cphotos) }}" alt="Foto" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center img-thumbnail" style="width: 50px; height: 50px;">
                                    <i class="bi bi-image"></i>
                                </div>
                            @endif
                        </td>
                        <td>{{ $product->cname }}</td>
                        <td>{{ $product->category->cname ?? '-' }}</td>
                        <td>Rp {{ number_format($product->nprice, 0, ',', '.') }}</td>
                        <td>
                            @if($product->cstatus == 'Active' || $product->cstatus == '1' || strtolower($product->cstatus) == 'aktif')
                                <span class="badge bg-success">{{ $product->cstatus }}</span>
                            @else
                                <span class="badge bg-secondary">{{ $product->cstatus }}</span>
                            @endif
                        </td>
                        <td>
                            @if($product->fcombo)
                                <span class="badge bg-info">Ya</span>
                            @else
                                <span class="badge bg-light text-dark border">Tidak</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal{{ $product->nid }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $product->nid }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $product->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $product->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $product->nid }}">Edit Produk</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('products.update', $product->nid) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $product->nid }}">
                                    <div class="modal-body text-start row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cname_{{ $product->nid }}" class="form-label">Nama Produk</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $product->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $product->nid }}" name="cname" value="{{ old('modal_id') == $product->nid ? old('cname') : $product->cname }}" required>
                                            @if(old('modal_id') == $product->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nid_category_{{ $product->nid }}" class="form-label">Kategori</label>
                                            <select class="form-select @if(old('modal_id') == $product->nid) @error('nid_category') is-invalid @enderror @endif" id="nid_category_{{ $product->nid }}" name="nid_category" required>
                                                <option value="">Pilih Kategori</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->nid }}" {{ (old('modal_id') == $product->nid ? old('nid_category') : $product->nid_category) == $category->nid ? 'selected' : '' }}>
                                                        {{ $category->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $product->nid) @error('nid_category') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nprice_{{ $product->nid }}" class="form-label">Harga</label>
                                            <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == $product->nid) @error('nprice') is-invalid @enderror @endif" id="nprice_{{ $product->nid }}" name="nprice" value="{{ old('modal_id') == $product->nid ? old('nprice') : $product->nprice }}" required>
                                            @if(old('modal_id') == $product->nid) @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cstatus_{{ $product->nid }}" class="form-label">Status</label>
                                            <select class="form-select @if(old('modal_id') == $product->nid) @error('cstatus') is-invalid @enderror @endif" id="cstatus_{{ $product->nid }}" name="cstatus" required>
                                                <option value="Active" {{ (old('modal_id') == $product->nid ? old('cstatus') : $product->cstatus) == 'Active' ? 'selected' : '' }}>Aktif</option>
                                                <option value="Inactive" {{ (old('modal_id') == $product->nid ? old('cstatus') : $product->cstatus) == 'Inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                            </select>
                                            @if(old('modal_id') == $product->nid) @error('cstatus') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="photo_{{ $product->nid }}" class="form-label">Foto Produk (Kosongkan jika tidak ingin mengubah)</label>
                                            <input type="file" class="form-control @if(old('modal_id') == $product->nid) @error('photo') is-invalid @enderror @endif" id="photo_{{ $product->nid }}" name="photo" accept="image/*">
                                            @if(old('modal_id') == $product->nid) @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            @if($product->cphotos)
                                                <div class="mt-2">
                                                    <img src="{{ asset($product->cphotos) }}" class="img-thumbnail" style="height: 100px;">
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" role="switch" id="fcombo_{{ $product->nid }}" name="fcombo" value="1" {{ (old('modal_id') == $product->nid ? old('fcombo') : $product->fcombo) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="fcombo_{{ $product->nid }}">Apakah Produk Kombo?</label>
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
                    <div class="modal fade" id="deleteModal{{ $product->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $product->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $product->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus produk <strong>{{ $product->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan dan akan menghapus foto terkait.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('products.destroy', $product->nid) }}" method="POST">
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
                        <td colspan="7" class="text-center text-muted py-4">Tidak ada data produk.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body row">
                    <div class="col-md-6 mb-3">
                        <label for="cname" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nid_category" class="form-label">Kategori</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_category') is-invalid @enderror @endif" id="nid_category" name="nid_category" required>
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->nid }}" {{ (old('modal_id') == 'create' ? old('nid_category') : '') == $category->nid ? 'selected' : '' }}>
                                    {{ $category->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_category') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nprice" class="form-label">Harga</label>
                        <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == 'create') @error('nprice') is-invalid @enderror @endif" id="nprice" name="nprice" value="{{ old('modal_id') == 'create' ? old('nprice') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cstatus" class="form-label">Status</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('cstatus') is-invalid @enderror @endif" id="cstatus" name="cstatus" required>
                            <option value="Active" {{ (old('modal_id') == 'create' ? old('cstatus') : '') == 'Active' ? 'selected' : '' }}>Aktif</option>
                            <option value="Inactive" {{ (old('modal_id') == 'create' ? old('cstatus') : '') == 'Inactive' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                        @if(old('modal_id') == 'create') @error('cstatus') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="photo" class="form-label">Foto Produk</label>
                        <input type="file" class="form-control @if(old('modal_id') == 'create') @error('photo') is-invalid @enderror @endif" id="photo" name="photo" accept="image/*">
                        @if(old('modal_id') == 'create') @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="fcombo" name="fcombo" value="1" {{ old('modal_id') == 'create' && old('fcombo') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fcombo">Apakah Produk Kombo?</label>
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
