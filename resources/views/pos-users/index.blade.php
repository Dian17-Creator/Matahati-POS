@extends('layouts.app')

@section('title', 'Pegawai POS')

@push('styles')
<style>
    .staff-role-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .staff-role-header {
        padding: 0.9rem 1.15rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .staff-role-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.15rem;
        letter-spacing: 0.01em;
    }

    .staff-role-desc {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0;
    }

    .staff-add-link {
        color: #0d6efd;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        background: transparent;
        border: 0;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        transition: background-color 0.15s ease;
    }

    .staff-add-link:hover {
        background-color: #eff6ff;
        color: #0b5ed7;
    }

    .staff-row {
        padding: 0.65rem 1.15rem;
        border-top: 1px solid #eef2f6;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-start;
        gap: 1rem;
        transition: background-color 0.15s ease;
    }

    .staff-row:hover {
        background-color: #f8fafc;
    }

    .staff-identity {
        width: 240px;
        flex: 0 0 240px;
    }

    .staff-name {
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        line-height: 1.3;
    }

    .staff-email {
        font-size: 0.78rem;
        color: #64748b;
        line-height: 1.3;
    }

    .staff-access-box {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 0.35rem 0.7rem;
        background: #ffffff;
        width: 210px;
        flex: 0 0 210px;
    }

    .staff-access-label {
        font-size: 0.7rem;
        color: #64748b;
        line-height: 1.2;
        margin-bottom: 0.1rem;
    }

    .staff-access-value {
        font-size: 0.82rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .staff-access-empty {
        font-size: 0.82rem;
        color: #94a3b8;
        font-weight: 400;
    }

    .staff-meta {
        flex: 0 0 auto;
        min-width: 95px;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .staff-meta-label {
        font-size: 0.72rem;
        color: #64748b;
        line-height: 1.2;
    }

    .staff-actions {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-left: 0.25rem;
    }

    @media (max-width: 768px) {
        .staff-identity,
        .staff-access-box {
            width: 100%;
            flex: 1 1 100%;
        }
    }

    .staff-icon-btn {
        border: 0;
        background: transparent;
        width: 34px;
        height: 34px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .staff-icon-btn.btn-delete {
        color: #475569;
    }

    .staff-icon-btn.btn-delete:hover {
        background-color: #fef2f2;
        color: #dc2626;
    }

    .staff-icon-btn.btn-edit {
        color: #0ea5e9;
    }

    .staff-icon-btn.btn-edit:hover {
        background-color: #f0f9ff;
        color: #0284c7;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    @foreach ($roleGroups as $group)
        <div class="staff-role-card">
            <div class="staff-role-header">
                <div>
                    <div class="staff-role-title">
                        {{ $group['title'] }}
                        <span class="badge bg-light text-secondary border ms-1 fw-normal">{{ $group['items']->count() }}</span>
                    </div>
                    <p class="staff-role-desc">{{ $group['description'] }}</p>
                </div>
                <button type="button"
                        class="staff-add-link add-role-btn"
                        data-role="{{ $group['key'] }}"
                        data-bs-toggle="modal"
                        data-bs-target="#createModal">
                    Tambah
                </button>
            </div>

            @forelse ($group['items'] as $pu)
                @php
                    $displayName = $pu->user->cname ?? $pu->user->cfullname ?? ('User #' . $pu->nid_user);
                    $displayEmail = $pu->user->cemail ?? ($pu->user->cmailaddress ?? '-');
                    $isActiveUser = (bool) ($pu->user->factive ?? true);

                    if (($pu->fspv || $pu->fcashier) && $pu->spvOutlets->isNotEmpty()) {
                        $outletNames = $pu->spvOutlets
                            ->map(fn ($so) => $so->outlet?->cname)
                            ->filter()
                            ->values();
                        $outletText = $outletNames->isNotEmpty() ? $outletNames->implode(', ') : null;
                    } else {
                        $outletText = $pu->outlet?->cname;
                    }

                    $otherRoles = collect([
                        'OWNER' => (bool) $pu->fowner,
                        'SUPERVISOR' => (bool) $pu->fspv,
                        'CAPTAIN' => (bool) $pu->fcaptain,
                        'CASHIER' => (bool) $pu->fcashier,
                    ])->filter()->keys()->reject(fn ($r) => $r === $group['title'])->values();
                @endphp

                <div class="staff-row">
                    <div class="staff-identity">
                        <div class="staff-name">{{ $displayName }}</div>
                        <div class="staff-email">{{ $displayEmail }}</div>
                    </div>

                    <div class="staff-access-box" title="{{ $outletText ?? 'Belum diatur' }}">
                        <div class="staff-access-label">POS - Outlet</div>
                        @if ($outletText)
                            <div class="staff-access-value">{{ $outletText }}</div>
                        @else
                            <div class="staff-access-empty">Belum diatur</div>
                        @endif
                    </div>

                    <div class="staff-meta">
                        <span class="staff-meta-label">Akses POS</span>
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            @if ($isActiveUser)
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-medium">Aktif</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-medium">Nonaktif</span>
                            @endif

                            @foreach ($otherRoles as $otherRole)
                                <span class="badge bg-light text-secondary border fw-normal">{{ $otherRole }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="staff-actions">
                        <button type="button"
                                class="staff-icon-btn btn-delete"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal{{ $pu->nid }}"
                                title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button"
                                class="staff-icon-btn btn-edit"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $pu->nid }}"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="staff-row text-muted small">
                    Belum ada pegawai pada level {{ $group['title'] }}.
                </div>
            @endforelse
        </div>
    @endforeach
</div>

{{-- Render Edit & Delete Modals once per POS User to avoid duplicate IDs --}}
@foreach ($posUsers as $pu)
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
                                <label class="form-check-label" for="fowner_{{ $pu->nid }}">OWNER (Pemilik)</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input fspv-switch multi-outlet-switch" type="checkbox" role="switch" id="fspv_{{ $pu->nid }}" name="fspv" value="1" data-modal="{{ $pu->nid }}" {{ (old('modal_id') == $pu->nid ? old('fspv') : $pu->fspv) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-primary" for="fspv_{{ $pu->nid }}">
                                    SUPERVISOR (SPV)
                                </label>
                                <div class="text-muted small">Aktifkan untuk akses multi-outlet supervisi.</div>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="fcaptain_{{ $pu->nid }}" name="fcaptain" value="1" {{ (old('modal_id') == $pu->nid ? old('fcaptain') : $pu->fcaptain) ? 'checked' : '' }}>
                                <label class="form-check-label" for="fcaptain_{{ $pu->nid }}">CAPTAIN (Kapten)</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input fcashier-switch multi-outlet-switch" type="checkbox" role="switch" id="fcashier_{{ $pu->nid }}" name="fcashier" value="1" data-modal="{{ $pu->nid }}" {{ (old('modal_id') == $pu->nid ? old('fcashier') : $pu->fcashier) ? 'checked' : '' }}>
                                <label class="form-check-label" for="fcashier_{{ $pu->nid }}">CASHIER (Kasir)</label>
                                <div class="text-muted small">Mendukung akses multi-outlet kasir.</div>
                            </div>
                        </div>

                        <!-- 3. Outlet -->
                        @php
                            $isSpv = old('modal_id') == $pu->nid ? (bool) old('fspv') : (bool) $pu->fspv;
                            $isCashier = old('modal_id') == $pu->nid ? (bool) old('fcashier') : (bool) $pu->fcashier;
                            $isMultiOutlet = $isSpv || $isCashier;
                            $userSpvOutletIds = old('modal_id') == $pu->nid && is_array(old('outlet_ids'))
                                ? old('outlet_ids')
                                : $pu->spvOutlets->pluck('nid_outlet')->toArray();
                            if (empty($userSpvOutletIds) && $pu->nid_outlet) {
                                $userSpvOutletIds = [$pu->nid_outlet];
                            }
                        @endphp

                        <!-- Single Outlet (Non-SPV/Kasir) -->
                        <div class="mb-3 single-outlet-group" id="single_outlet_group_{{ $pu->nid }}" style="{{ $isMultiOutlet ? 'display: none;' : '' }}">
                            <label for="nid_outlet_{{ $pu->nid }}" class="form-label fw-bold">Outlet <span class="text-danger">*</span></label>
                            <select class="form-select @if(old('modal_id') == $pu->nid) @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet_{{ $pu->nid }}" name="nid_outlet" {{ $isMultiOutlet ? 'disabled' : 'required' }}>
                                <option value="">Pilih Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->nid }}" {{ (old('modal_id') == $pu->nid ? old('nid_outlet') : $pu->nid_outlet) == $outlet->nid ? 'selected' : '' }}>
                                        {{ $outlet->cname }}
                                    </option>
                                @endforeach
                            </select>
                            @if(old('modal_id') == $pu->nid) @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>

                        <!-- Multi Outlet (SPV / Kasir) -->
                        <div class="mb-3 multi-outlet-group" id="multi_outlet_group_{{ $pu->nid }}" style="{{ $isMultiOutlet ? '' : 'display: none;' }}">
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
                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $pu->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }} {{ $isMultiOutlet ? '' : 'disabled' }}>
                                            <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $pu->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                {{ $outlet->cname }}
                                            </label>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @if(old('modal_id') == $pu->nid) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                            <div class="form-text small text-muted">Centang outlet yang dapat diakses oleh SPV/Kasir ini.</div>
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
@endforeach

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
                            <input class="form-check-input create-role-switch" type="checkbox" role="switch" id="fowner" name="fowner" value="1" {{ old('modal_id') == 'create' && old('fowner') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fowner">OWNER (Pemilik)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            @php
                                $isCreateSpv = old('modal_id') == 'create' && old('fspv');
                                $isCreateCashier = old('modal_id') == 'create' && old('fcashier');
                                $isCreateMultiOutlet = $isCreateSpv || $isCreateCashier;
                                $createOutletIds = (old('modal_id') == 'create' && is_array(old('outlet_ids'))) ? old('outlet_ids') : [];
                            @endphp
                            <input class="form-check-input create-role-switch fspv-switch multi-outlet-switch" type="checkbox" role="switch" id="fspv" name="fspv" value="1" data-modal="create" {{ $isCreateSpv ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold text-primary" for="fspv">
                                SUPERVISOR (SPV)
                            </label>
                            <div class="text-muted small">Aktifkan untuk supervisi multi-outlet.</div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input create-role-switch" type="checkbox" role="switch" id="fcaptain" name="fcaptain" value="1" {{ old('modal_id') == 'create' && old('fcaptain') ? 'checked' : '' }}>
                            <label class="form-check-label" for="fcaptain">CAPTAIN (Kapten)</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input create-role-switch fcashier-switch multi-outlet-switch" type="checkbox" role="switch" id="fcashier" name="fcashier" value="1" data-modal="create" {{ $isCreateCashier ? 'checked' : '' }}>
                            <label class="form-check-label" for="fcashier">CASHIER (Kasir)</label>
                            <div class="text-muted small">Mendukung akses multi-outlet kasir.</div>
                        </div>
                    </div>

                    <!-- 3. Outlet -->
                    <!-- Single Outlet (Non-SPV/Kasir) -->
                    <div class="mb-3 single-outlet-group" id="single_outlet_group_create" style="{{ $isCreateMultiOutlet ? 'display: none;' : '' }}">
                        <label for="nid_outlet" class="form-label fw-bold">Outlet <span class="text-danger">*</span></label>
                        <select class="form-select @if(old('modal_id') == 'create') @error('nid_outlet') is-invalid @enderror @endif" id="nid_outlet" name="nid_outlet" {{ $isCreateMultiOutlet ? 'disabled' : 'required' }}>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ (old('modal_id') == 'create' ? old('nid_outlet') : '') == $outlet->nid ? 'selected' : '' }}>
                                    {{ $outlet->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create') @error('nid_outlet') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>

                    <!-- Multi Outlet (SPV / Kasir) -->
                    <div class="mb-3 multi-outlet-group" id="multi_outlet_group_create" style="{{ $isCreateMultiOutlet ? '' : 'display: none;' }}">
                        <label class="form-label fw-bold">Pilih Outlet (Multi Outlet) <span class="text-danger">*</span></label>
                        <div class="dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_create">
                                <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                            </button>
                            <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                @foreach($outlets as $outlet)
                                <li>
                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                        <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array($outlet->nid, $createOutletIds) ? 'checked' : '' }} {{ $isCreateMultiOutlet ? '' : 'disabled' }}>
                                        <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                            {{ $outlet->cname }}
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @if(old('modal_id') == 'create') @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                        <div class="form-text small text-muted">Centang outlet yang dapat diakses oleh SPV/Kasir ini.</div>
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

        // Toggle Single vs Multi Outlet based on SPV / Kasir Switch
        function handleMultiOutletToggle(modalId) {
            const modalSwitches = document.querySelectorAll(`.multi-outlet-switch[data-modal="${modalId}"]`);
            const isMultiOutlet = Array.from(modalSwitches).some(sw => sw.checked);

            const singleGroup = document.getElementById('single_outlet_group_' + modalId);
            const multiGroup = document.getElementById('multi_outlet_group_' + modalId);

            if (!singleGroup || !multiGroup) return;

            const singleSelect = singleGroup.querySelector('select[name="nid_outlet"]');
            const multiCheckboxes = multiGroup.querySelectorAll('input[name="outlet_ids[]"]');

            if (isMultiOutlet) {
                singleGroup.style.display = 'none';
                multiGroup.style.display = 'block';

                if (singleSelect) {
                    singleSelect.removeAttribute('required');
                    singleSelect.disabled = true;

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

        // Pre-select role switch when clicking "Tambah" on a specific level card
        document.querySelectorAll('.add-role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetRole = this.getAttribute('data-role');
                document.querySelectorAll('.create-role-switch').forEach(sw => {
                    sw.checked = (sw.id === targetRole);
                });
                handleMultiOutletToggle('create');
            });
        });

        const processedModals = new Set();
        document.querySelectorAll('.multi-outlet-switch').forEach(switchEl => {
            const modalId = switchEl.getAttribute('data-modal');
            switchEl.addEventListener('change', function() {
                handleMultiOutletToggle(modalId);
            });
            if (!processedModals.has(modalId)) {
                processedModals.add(modalId);
                handleMultiOutletToggle(modalId);
            }
        });

        updateDropdownText();
    });
</script>
@endpush
