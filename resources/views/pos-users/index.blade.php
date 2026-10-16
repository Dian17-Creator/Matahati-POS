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
                        <th width="22%" class="py-3 ps-4">Pengguna Sistem</th>
                        <th width="26%">Outlet</th>
                        <th class="text-center">Akses Owner</th>
                        <th class="text-center">Akses SPV</th>
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
                            @if($pu->fspv && $pu->spvOutlets->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($pu->spvOutlets as $spvOutlet)
                                        @if($spvOutlet->outlet)
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-shop me-1 text-primary"></i> {{ $spvOutlet->outlet->cname }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i> {{ $pu->outlet->cname ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pu->fowner)
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @else
                                <i class="bi bi-x-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pu->fspv)
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
                                        <!-- 1. Pengguna Sistem -->
                                        <div class="mb-3">
                                            <label for="nid_user_{{ $pu->nid }}" class="form-label fw-bold">Pengguna Sistem <span class="text-danger">*</span></label>
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

                                        <!-- 2. Hak Akses -->
                                        <div class="mb-3 p-3 bg-light rounded border">
                                            <label class="form-label fw-bold text-dark mb-2">Hak Akses:</label>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" role="switch" id="fowner_{{ $pu->nid }}" name="fowner" value="1" {{ (old('modal_id') == $pu->nid ? old('fowner') : $pu->fowner) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="fowner_{{ $pu->nid }}">Owner (Pemilik)</label>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input fspv-switch" type="checkbox" role="switch" id="fspv_{{ $pu->nid }}" name="fspv" value="1" data-modal="{{ $pu->nid }}" {{ (old('modal_id') == $pu->nid ? old('fspv') : $pu->fspv) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-primary" for="fspv_{{ $pu->nid }}">
                                                    Supervisor (SPV)
                                                </label>
                                                <div class="text-muted small">Aktifkan untuk akses multi-outlet supervisi.</div>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" role="switch" id="fcashier_{{ $pu->nid }}" name="fcashier" value="1" {{ (old('modal_id') == $pu->nid ? old('fcashier') : $pu->fcashier) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="fcashier_{{ $pu->nid }}">Cashier (Kasir)</label>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="fcaptain_{{ $pu->nid }}" name="fcaptain" value="1" {{ (old('modal_id') == $pu->nid ? old('fcaptain') : $pu->fcaptain) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="fcaptain_{{ $pu->nid }}">Captain (Kapten)</label>
                                            </div>
                                        </div>

                                        <!-- 3. Outlet -->
                                        @php
                                            $isSpv = old('modal_id') == $pu->nid ? (bool) old('fspv') : (bool) $pu->fspv;
                                            $userSpvOutletIds = old('modal_id') == $pu->nid && is_array(old('outlet_ids'))
                                                ? old('outlet_ids')
                                                : $pu->spvOutlets->pluck('nid_outlet')->toArray();
                                            if (empty($userSpvOutletIds) && $pu->nid_outlet) {
                                                $userSpvOutletIds = [$pu->nid_outlet];
                                            }
                                        @endphp

                                        <!-- Single Outlet (Non-SPV) -->
                                        <div class="mb-3 single-outlet-group" id="single_outlet_group_{{ $pu->nid }}" style="{{ $isSpv ? 'display: none;' : '' }}">
                                            <label for="nid_outlet_{{ $pu->nid }}" class="form-label fw-bold">Outlet <span class="text-danger">*</span></label>
                                            <select class="form-select @if(old('modal_id') == $pu->nid) @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet_{{ $pu->nid }}" name="nid_outlet" {{ $isSpv ? 'disabled' : 'required' }}>
                                                <option value="">Pilih Outlet</option>
                                                @foreach($outlets as $outlet)
                                                    <option value="{{ $outlet->nid }}" {{ (old('modal_id') == $pu->nid ? old('nid_outlet') : $pu->nid_outlet) == $outlet->nid ? 'selected' : '' }}>
                                                        {{ $outlet->cname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(old('modal_id') == $pu->nid) @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>

                                        <!-- Multi Outlet (SPV) -->
                                        <div class="mb-3 multi-outlet-group" id="multi_outlet_group_{{ $pu->nid }}" style="{{ $isSpv ? '' : 'display: none;' }}">
                                            <label class="form-label fw-bold">Pilih Outlet (Multi Outlet) <span class="text-danger">*</span></label>
                                            <div class="dropdown @if(old('modal_id') == $pu->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                                <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_{{ $pu->nid }}">
                                                    <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                </button>
                                                <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                                    @foreach($outlets as $outlet)
                                                    @php
                                                        $isChecked = in_array($outlet->nid, $userSpvOutletIds);
                                                    @endphp
                                                    <li>
                                                        <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $pu->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }} {{ $isSpv ? '' : 'disabled' }}>
                                                            <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $pu->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                {{ $outlet->cname }}
                                                            </label>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @if(old('modal_id') == $pu->nid) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                            <div class="form-text small text-muted">Centang outlet yang dapat diakses oleh SPV ini.</div>
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
                        <td colspan="7" class="text-center text-muted py-4">Tidak ada data pengguna POS.</td>
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
                    <!-- 1. Pengguna Sistem -->
                    <div class="mb-3">
                        <label for="nid_user" class="form-label fw-bold">Pengguna Sistem <span class="text-danger">*</span></label>
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

                    <!-- 2. Hak Akses -->
                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold text-dark mb-2">Hak Akses:</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="fowner" name="fowner" value="1" {{ old('modal_id') == 'create' && old('fowner') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fowner">Owner (Pemilik)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            @php
                                $isCreateSpv = old('modal_id') == 'create' && old('fspv');
                                $createOutletIds = (old('modal_id') == 'create' && is_array(old('outlet_ids'))) ? old('outlet_ids') : [];
                            @endphp
                            <input class="form-check-input fspv-switch" type="checkbox" role="switch" id="fspv" name="fspv" value="1" data-modal="create" {{ $isCreateSpv ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold text-primary" for="fspv">
                                Supervisor (SPV)
                            </label>
                            <div class="text-muted small">Aktifkan untuk supervisi multi-outlet.</div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="fcashier" name="fcashier" value="1" {{ old('modal_id') == 'create' && old('fcashier') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fcashier">Cashier (Kasir)</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="fcaptain" name="fcaptain" value="1" {{ old('modal_id') == 'create' && old('fcaptain') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fcaptain">Captain (Kapten)</label>
                        </div>
                    </div>

                    <!-- 3. Outlet -->
                    <!-- Single Outlet (Non-SPV) -->
                    <div class="mb-3 single-outlet-group" id="single_outlet_group_create" style="{{ $isCreateSpv ? 'display: none;' : '' }}">
                        <label for="nid_outlet" class="form-label fw-bold">Outlet <span class="text-danger">*</span></label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet" name="nid_outlet" {{ $isCreateSpv ? 'disabled' : 'required' }}>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ (old('modal_id') == 'create' ? old('nid_outlet') : '') == $outlet->nid ? 'selected' : '' }}>
                                    {{ $outlet->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>

                    <!-- Multi Outlet (SPV) -->
                    <div class="mb-3 multi-outlet-group" id="multi_outlet_group_create" style="{{ $isCreateSpv ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold">Pilih Outlet (Multi Outlet) <span class="text-danger">*</span></label>
                        <div class="dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_create">
                                <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                            </button>
                            <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                @foreach($outlets as $outlet)
                                <li>
                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                        <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array($outlet->nid, $createOutletIds) ? 'checked' : '' }} {{ $isCreateSpv ? '' : 'disabled' }}>
                                        <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                            {{ $outlet->cname }}
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @if(old('modal_id') == 'create') @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                        <div class="form-text small text-muted">Centang outlet yang dapat diakses oleh SPV ini.</div>
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
                var createEl = document.getElementById('createModal');
                if (createEl) {
                    var myModal = new bootstrap.Modal(createEl);
                    myModal.show();
                }
            } else {
                var editEl = document.getElementById('editModal' + modalId);
                if (editEl) {
                    var myModal = new bootstrap.Modal(editEl);
                    myModal.show();
                }
            }
        }

        // Dropdown text update helper for multi-outlet
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

        // Toggle Single vs Multi Outlet based on SPV Switch
        function handleSpvToggle(switchEl) {
            const modalId = switchEl.getAttribute('data-modal');
            const isSpv = switchEl.checked;
            
            const singleGroup = document.getElementById('single_outlet_group_' + modalId);
            const multiGroup = document.getElementById('multi_outlet_group_' + modalId);

            if (!singleGroup || !multiGroup) return;

            const singleSelect = singleGroup.querySelector('select[name="nid_outlet"]');
            const multiCheckboxes = multiGroup.querySelectorAll('input[name="outlet_ids[]"]');

            if (isSpv) {
                singleGroup.style.display = 'none';
                multiGroup.style.display = 'block';

                if (singleSelect) {
                    singleSelect.removeAttribute('required');
                    singleSelect.disabled = true;

                    // If single select had a value and no multi-checkbox is checked, auto-check it
                    if (singleSelect.value) {
                        const anyChecked = Array.from(multiCheckboxes).some(cb => cb.checked);
                        if (!anyChecked) {
                            const targetCb = multiGroup.querySelector(`input[name="outlet_ids[]"][value="${singleSelect.value}"]`);
                            if (targetCb) {
                                targetCb.checked = true;
                            }
                        }
                    }
                }

                multiCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
            } else {
                singleGroup.style.display = 'block';
                multiGroup.style.display = 'none';

                if (singleSelect) {
                    singleSelect.setAttribute('required', 'required');
                    singleSelect.disabled = false;

                    // If single select is empty, pick first checked multi-checkbox
                    if (!singleSelect.value) {
                        const checkedCb = Array.from(multiCheckboxes).find(cb => cb.checked);
                        if (checkedCb) {
                            singleSelect.value = checkedCb.value;
                        }
                    }
                }

                multiCheckboxes.forEach(cb => {
                    cb.disabled = true;
                });
            }

            updateDropdownText();
        }

        document.querySelectorAll('.fspv-switch').forEach(switchEl => {
            switchEl.addEventListener('change', function() {
                handleSpvToggle(this);
            });
        });

        // Run initial setup for all modals
        document.querySelectorAll('.fspv-switch').forEach(switchEl => {
            handleSpvToggle(switchEl);
        });

        updateDropdownText();
    });
</script>
@endpush
