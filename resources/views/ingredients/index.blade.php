@extends('layouts.app')

@section('title', 'Bahan Baku')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Bahan Baku</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="10%">ID</th>
                        <th>Nama Bahan</th>
                        <th>Satuan</th>
                        <th>Stok</th>
                        <th width="20%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ingredients as $ingredient)
                    <tr>
                        <td>{{ $ingredient->nid }}</td>
                        <td>{{ $ingredient->cname }}</td>
                        <td>{{ $ingredient->csatuan }}</td>
                        <td>{{ $ingredient->nstock }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal{{ $ingredient->nid }}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $ingredient->nid }}">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
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
                                        <div class="mb-3">
                                            <label for="cname_{{ $ingredient->nid }}" class="form-label">Nama Bahan</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $ingredient->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $ingredient->nid }}" name="cname" value="{{ old('modal_id') == $ingredient->nid ? old('cname') : $ingredient->cname }}" required>
                                            @if(old('modal_id') == $ingredient->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="csatuan_{{ $ingredient->nid }}" class="form-label">Satuan</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $ingredient->nid) @error('csatuan') is-invalid @enderror @endif" id="csatuan_{{ $ingredient->nid }}" name="csatuan" value="{{ old('modal_id') == $ingredient->nid ? old('csatuan') : $ingredient->csatuan }}" required>
                                            @if(old('modal_id') == $ingredient->nid) @error('csatuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nstock_{{ $ingredient->nid }}" class="form-label">Stok</label>
                                            <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == $ingredient->nid) @error('nstock') is-invalid @enderror @endif" id="nstock_{{ $ingredient->nid }}" name="nstock" value="{{ old('modal_id') == $ingredient->nid ? old('nstock') : $ingredient->nstock }}" required>
                                            @if(old('modal_id') == $ingredient->nid) @error('nstock') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                                    <h5 class="mb-3">Apakah anda ingin menghapus bahan baku <strong>{{ $ingredient->cname }}</strong>?</h5>
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
                        <td colspan="5" class="text-center text-muted py-4">Tidak ada data bahan baku.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $ingredients->links('pagination::bootstrap-5') }}
        </div>
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
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Bahan</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="csatuan" class="form-label">Satuan</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('csatuan') is-invalid @enderror @endif" id="csatuan" name="csatuan" value="{{ old('modal_id') == 'create' ? old('csatuan') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('csatuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nstock" class="form-label">Stok</label>
                        <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == 'create') @error('nstock') is-invalid @enderror @endif" id="nstock" name="nstock" value="{{ old('modal_id') == 'create' ? old('nstock') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('nstock') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
