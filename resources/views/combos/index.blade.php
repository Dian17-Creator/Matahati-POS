@extends('layouts.app')

@section('title', 'Produk Kombo')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Produk Kombo</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th width="30%" class="py-3 ps-4">Produk Kombo Utama</th>
                        <th>Isi Produk</th>
                        <th>Kuantitas (Qty)</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($combos as $combo)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-boxes fs-5"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $combo->comboProduct->cname ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <i class="bi bi-box-seam me-1 text-muted"></i> 
                            {{ $combo->product->cname ?? '-' }}
                        </td>
                        <td><span class="badge bg-light text-dark border px-2 py-1">{{ $combo->nqty }} Pcs</span></td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $combo->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $combo->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $combo->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $combo->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $combo->nid }}">Edit Kombo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('combos.update', $combo->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $combo->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="nid_combo_product_{{ $combo->nid }}" class="form-label">Produk Kombo Utama</label>
                                            <select class="form-select @if(old('modal_id') == $combo->nid) @error('nid_combo_product') is-invalid @enderror @endif" id="nid_combo_product_{{ $combo->nid }}" name="nid_combo_product" required>
                                                <option value="">Pilih Produk Kombo</option>
                                                @foreach($comboProducts as $cp)
                                                    <option value="{{ $cp->nid }}" {{ (old('modal_id') == $combo->nid ? old('nid_combo_product') : $combo->nid_combo_product) == $cp->nid ? 'selected' : '' }}>
                                                        {{ $cp->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $combo->nid) @error('nid_combo_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nid_product_{{ $combo->nid }}" class="form-label">Isi Produk</label>
                                            <select class="form-select @if(old('modal_id') == $combo->nid) @error('nid_product') is-invalid @enderror @endif" id="nid_product_{{ $combo->nid }}" name="nid_product" required>
                                                <option value="">Pilih Isi Produk</option>
                                                @foreach($regularProducts as $rp)
                                                    <option value="{{ $rp->nid }}" {{ (old('modal_id') == $combo->nid ? old('nid_product') : $combo->nid_product) == $rp->nid ? 'selected' : '' }}>
                                                        {{ $rp->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $combo->nid) @error('nid_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nqty_{{ $combo->nid }}" class="form-label">Kuantitas (Qty)</label>
                                            <input type="number" step="any" min="1" class="form-control @if(old('modal_id') == $combo->nid) @error('nqty') is-invalid @enderror @endif" id="nqty_{{ $combo->nid }}" name="nqty" value="{{ old('modal_id') == $combo->nid ? old('nqty') : $combo->nqty }}" required>
                                            @if(old('modal_id') == $combo->nid) @error('nqty') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $combo->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $combo->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $combo->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Hapus isi kombo ini?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('combos.destroy', $combo->nid) }}" method="POST">
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
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data produk kombo.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $combos->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Isi Kombo Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('combos.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nid_combo_product" class="form-label">Produk Kombo Utama</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_combo_product') is-invalid @enderror @endif" id="nid_combo_product" name="nid_combo_product" required>
                            <option value="">Pilih Produk Kombo</option>
                            @foreach($comboProducts as $cp)
                                <option value="{{ $cp->nid }}" {{ (old('modal_id') == 'create' ? old('nid_combo_product') : '') == $cp->nid ? 'selected' : '' }}>
                                    {{ $cp->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_combo_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nid_product" class="form-label">Isi Produk</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_product') is-invalid @enderror @endif" id="nid_product" name="nid_product" required>
                            <option value="">Pilih Isi Produk</option>
                            @foreach($regularProducts as $rp)
                                <option value="{{ $rp->nid }}" {{ (old('modal_id') == 'create' ? old('nid_product') : '') == $rp->nid ? 'selected' : '' }}>
                                    {{ $rp->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_product') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nqty" class="form-label">Kuantitas (Qty)</label>
                        <input type="number" step="any" min="1" class="form-control @if(old('modal_id') == 'create') @error('nqty') is-invalid @enderror @endif" id="nqty" name="nqty" value="{{ old('modal_id') == 'create' ? old('nqty') : '' }}" required>
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
