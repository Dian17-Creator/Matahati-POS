@extends('layouts.app')

@section('title', 'Pengguna POS')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Pengguna POS</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th width="25%" class="py-3 ps-4">Pengguna Sistem</th>
                        <th>Outlet</th>
                        <th class="text-center">Akses Owner</th>
                        <th class="text-center">Akses Cashier</th>
                        <th class="text-center">Akses Captain</th>
                        <th width="12%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posUsers as $pu)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-person-badge fs-5"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $pu->user->cname ?? 'User #'.$pu->nid_user }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i> {{ $pu->outlet->cname ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            @if($pu->fowner)
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-x-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pu->fcashier)
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-x-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pu->fcaptain)
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-x-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $pu->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $pu->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $pu->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $pu->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $pu->nid }}">Edit Pengguna POS</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('pos-users.update', $pu->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $pu->nid }}">
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label for="nid_user_{{ $pu->nid }}" class="form-label">Pengguna Sistem</label>
                                            <select class="form-select @if(old('modal_id') == $pu->nid) @error('nid_user') is-invalid @enderror @endif" id="nid_user_{{ $pu->nid }}" name="nid_user" required>
                                                <option value="">Pilih Pengguna</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->nid }}" {{ (old('modal_id') == $pu->nid ? old('nid_user') : $pu->nid_user) == $user->nid ? 'selected' : '' }}>
                                                        {{ $user->cname ?? $user->cfullname }} ({{ $user->cemail }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $pu->nid) @error('nid_user') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="mb-3">
                                            <label for="nid_outlet_{{ $pu->nid }}" class="form-label">Outlet</label>
                                            <select class="form-select @if(old('modal_id') == $pu->nid) @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet_{{ $pu->nid }}" name="nid_outlet" required>
                                                <option value="">Pilih Outlet</option>
                                                @foreach($outlets as $outlet)
                                                    <option value="{{ $outlet->nid }}" {{ (old('modal_id') == $pu->nid ? old('nid_outlet') : $pu->nid_outlet) == $outlet->nid ? 'selected' : '' }}>
                                                        {{ $outlet->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $pu->nid) @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        
                                        <div class="mb-2"><strong>Hak Akses:</strong></div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="fowner_{{ $pu->nid }}" name="fowner" value="1" {{ (old('modal_id') == $pu->nid ? old('fowner') : $pu->fowner) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fowner_{{ $pu->nid }}">Owner (Pemilik)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="fcashier_{{ $pu->nid }}" name="fcashier" value="1" {{ (old('modal_id') == $pu->nid ? old('fcashier') : $pu->fcashier) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fcashier_{{ $pu->nid }}">Cashier (Kasir)</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="fcaptain_{{ $pu->nid }}" name="fcaptain" value="1" {{ (old('modal_id') == $pu->nid ? old('fcaptain') : $pu->fcaptain) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fcaptain_{{ $pu->nid }}">Captain (Kapten)</label>
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
                    <div class="modal fade" id="deleteModal{{ $pu->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $pu->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $pu->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Hapus akses POS untuk pengguna ini?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('pos-users.destroy', $pu->nid) }}" method="POST">
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
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data pengguna POS.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $posUsers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Akses Pengguna POS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pos-users.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nid_user" class="form-label">Pengguna Sistem</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_user') is-invalid @enderror @endif" id="nid_user" name="nid_user" required>
                            <option value="">Pilih Pengguna</option>
                            @foreach($users as $user)
                                <option value="{{ $user->nid }}" {{ (old('modal_id') == 'create' ? old('nid_user') : '') == $user->nid ? 'selected' : '' }}>
                                    {{ $user->cname ?? $user->cfullname }} ({{ $user->cemail }})
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_user') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label for="nid_outlet" class="form-label">Outlet</label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet" name="nid_outlet" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ (old('modal_id') == 'create' ? old('nid_outlet') : '') == $outlet->nid ? 'selected' : '' }}>
                                    {{ $outlet->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    
                    <div class="mb-2"><strong>Hak Akses:</strong></div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="fowner" name="fowner" value="1" {{ old('modal_id') == 'create' && old('fowner') ? 'checked' : '' }}>
                        <label class="form-check-label" for="fowner">Owner (Pemilik)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="fcashier" name="fcashier" value="1" {{ old('modal_id') == 'create' && old('fcashier') ? 'checked' : '' }}>
                        <label class="form-check-label" for="fcashier">Cashier (Kasir)</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="fcaptain" name="fcaptain" value="1" {{ old('modal_id') == 'create' && old('fcaptain') ? 'checked' : '' }}>
                        <label class="form-check-label" for="fcaptain">Captain (Kapten)</label>
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
