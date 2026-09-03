@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Pelanggan</h5>
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
                        <th class="py-3">Nama Pelanggan</th>
                        <th>No HP</th>
                        <th>Email</th>
                        <th>Alamat</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4 fw-bold text-muted">#{{ $customer->nid }}</td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill fs-5"></i>
                                </div>
                                <span class="fw-semibold text-dark">{{ $customer->cname }}</span>
                            </div>
                        </td>
                        <td>{{ $customer->cphone ?? '-' }}</td>
                        <td>{{ $customer->cemail ?? '-' }}</td>
                        <td>{{ $customer->caddress ? \Illuminate\Support\Str::limit($customer->caddress, 30) : '-' }}</td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 36px; height: 36px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $customer->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 36px; height: 36px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $customer->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $customer->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $customer->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $customer->nid }}">Edit Pelanggan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('customers.update', $customer->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $customer->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="cname_{{ $customer->nid }}" class="form-label">Nama Pelanggan</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $customer->nid }}" name="cname" value="{{ old('modal_id') == $customer->nid ? old('cname') : $customer->cname }}" required>
                                            @if(old('modal_id') == $customer->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="cphone_{{ $customer->nid }}" class="form-label">No HP</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cphone') is-invalid @enderror @endif" id="cphone_{{ $customer->nid }}" name="cphone" value="{{ old('modal_id') == $customer->nid ? old('cphone') : $customer->cphone }}">
                                            @if(old('modal_id') == $customer->nid) @error('cphone') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="cemail_{{ $customer->nid }}" class="form-label">Email</label>
                                            <input type="email" class="form-control @if(old('modal_id') == $customer->nid) @error('cemail') is-invalid @enderror @endif" id="cemail_{{ $customer->nid }}" name="cemail" value="{{ old('modal_id') == $customer->nid ? old('cemail') : $customer->cemail }}">
                                            @if(old('modal_id') == $customer->nid) @error('cemail') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="caddress_{{ $customer->nid }}" class="form-label">Alamat</label>
                                            <textarea class="form-control @if(old('modal_id') == $customer->nid) @error('caddress') is-invalid @enderror @endif" id="caddress_{{ $customer->nid }}" name="caddress" rows="3">{{ old('modal_id') == $customer->nid ? old('caddress') : $customer->caddress }}</textarea>
                                            @if(old('modal_id') == $customer->nid) @error('caddress') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $customer->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $customer->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $customer->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus pelanggan <strong>{{ $customer->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('customers.destroy', $customer->nid) }}" method="POST">
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
                        <td colspan="5" class="text-center text-muted py-4">Tidak ada data pelanggan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $customers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Pelanggan</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="cphone" class="form-label">No HP</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cphone') is-invalid @enderror @endif" id="cphone" name="cphone" value="{{ old('modal_id') == 'create' ? old('cphone') : '' }}">
                        @if(old('modal_id') == 'create') @error('cphone') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="cemail" class="form-label">Email</label>
                        <input type="email" class="form-control @if(old('modal_id') == 'create') @error('cemail') is-invalid @enderror @endif" id="cemail" name="cemail" value="{{ old('modal_id') == 'create' ? old('cemail') : '' }}">
                        @if(old('modal_id') == 'create') @error('cemail') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="caddress" class="form-label">Alamat</label>
                        <textarea class="form-control @if(old('modal_id') == 'create') @error('caddress') is-invalid @enderror @endif" id="caddress" name="caddress" rows="3">{{ old('modal_id') == 'create' ? old('caddress') : '' }}</textarea>
                        @if(old('modal_id') == 'create') @error('caddress') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
