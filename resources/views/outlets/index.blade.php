@extends('layouts.app')

@section('title', 'Outlet')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Outlet</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th width="10%" class="py-3 ps-4">ID</th>
                        <th>Nama Outlet</th>
                        <th>Departemen</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outlets as $outlet)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4 fw-bold text-muted">#{{ $outlet->nid }}</td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-shop fs-5"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $outlet->cname }}</span>
                            </div>
                        </td>
                        <td>
                            @if($outlet->department)
                                <span class="badge bg-light text-dark border">{{ $outlet->department->cname }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $outlet->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $outlet->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $outlet->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $outlet->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $outlet->nid }}">Edit Outlet</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('outlets.update', $outlet->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $outlet->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="cname_{{ $outlet->nid }}" class="form-label">Nama Outlet</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $outlet->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $outlet->nid }}" name="cname" value="{{ old('modal_id') == $outlet->nid ? old('cname') : $outlet->cname }}" required>
                                            @if(old('modal_id') == $outlet->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nid_dept_{{ $outlet->nid }}" class="form-label">Departemen (Opsional)</label>
                                            <select class="form-select @if(old('modal_id') == $outlet->nid) @error('nid_dept') is-invalid @enderror @endif" id="nid_dept_{{ $outlet->nid }}" name="nid_dept">
                                                <option value="">Tidak ada departemen</option>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->nid }}" {{ (old('modal_id') == $outlet->nid ? old('nid_dept') : $outlet->nid_dept) == $dept->nid ? 'selected' : '' }}>
                                                        {{ $dept->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $outlet->nid) @error('nid_dept') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $outlet->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $outlet->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $outlet->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus outlet <strong>{{ $outlet->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('outlets.destroy', $outlet->nid) }}" method="POST">
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
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data outlet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $outlets->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Outlet Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('outlets.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Outlet</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nid_dept" class="form-label">Departemen (Opsional)</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_dept') is-invalid @enderror @endif" id="nid_dept" name="nid_dept">
                            <option value="">Tidak ada departemen</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->nid }}" {{ (old('modal_id') == 'create' ? old('nid_dept') : '') == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_dept') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
