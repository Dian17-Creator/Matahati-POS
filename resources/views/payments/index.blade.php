@extends('layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Metode Pembayaran</h5>
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
                        <th>Nama Metode</th>
                        <th width="20%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->nid }}</td>
                        <td>{{ $payment->cname }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editModal{{ $payment->nid }}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $payment->nid }}">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $payment->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $payment->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $payment->nid }}">Edit Metode Pembayaran</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('payments.update', $payment->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $payment->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="cname_{{ $payment->nid }}" class="form-label">Nama Metode</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $payment->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $payment->nid }}" name="cname" value="{{ old('modal_id') == $payment->nid ? old('cname') : $payment->cname }}" required>
                                            @if(old('modal_id') == $payment->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $payment->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $payment->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $payment->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus metode pembayaran <strong>{{ $payment->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('payments.destroy', $payment->nid) }}" method="POST">
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
                        <td colspan="3" class="text-center text-muted py-4">Tidak ada metode pembayaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $payments->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Metode Pembayaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Metode</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
