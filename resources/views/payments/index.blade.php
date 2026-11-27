@extends('layouts.app')

@section('title', 'Point Of Sales / Settings')

@section('sub_header')
@include('settings.partials.pos_tabs', ['activeTab' => 'settings'])
@endsection

@section('content')
<div class="container-fluid py-2">
    <div class="row g-3">
        <!-- Menu Kiri -->
        <div class="col-lg-3 col-xl-2">
            @include('settings.partials.pos_menu')
        </div>

        <!-- Konten Utama (Gaya Olsera: Kiri Form Tambah, Kanan Daftar Cara Pembayaran) -->
        <div class="col-lg-9 col-xl-10">
            <div class="row g-3 align-items-start">
                @if($canModifyData)
                <div class="col-lg-6">
                    <div class="card olsera-panel-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold">Tambahkan cara pembayaran</h6>
                            <button type="submit" form="createPaymentForm" class="btn btn-success btn-sm px-3">
                                <i class="bi bi-check2 me-1"></i> Simpan
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <form id="createPaymentForm" action="{{ route('payments.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="modal_id" value="create">
                                @php
                                $isCreateOld = old('modal_id') == 'create';
                                $createAllOutlets = $isCreateOld
                                ? (bool) old('all_outlets')
                                : (empty($selectedOutletId) && ($isSuperAdmin || $outlets->count() > 1));
                                $createOutletIds = $isCreateOld
                                ? array_map('intval', (array) old('outlet_ids', []))
                                : (!empty($selectedOutletId)
                                ? [(int) $selectedOutletId]
                                : ($outlets->count() === 1 ? [(int) $outlets->first()->nid] : []));
                                @endphp
                                <div class="mb-3">
                                    <label for="cname" class="form-label fw-semibold">Nama Metode <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @if($isCreateOld) @error('cname') is-invalid @enderror @endif"
                                        id="cname"
                                        name="cname"
                                        value="{{ $isCreateOld ? old('cname') : '' }}"
                                        required>
                                    @if($isCreateOld)
                                    @error('cname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @endif
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-semibold">Pilih Outlet (Multi Outlet) <span class="text-danger">*</span></label>
                                    <div class="dropdown payment-outlet-dropdown @if($isCreateOld) @error('outlet_ids') is-invalid @enderror @endif">
                                        <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                        </button>
                                        <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                            @if($isSuperAdmin || $outlets->count() > 1)
                                            <li>
                                                <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                    <input class="form-check-input me-2 all-outlets-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="all_outlets" value="1" id="create_all_outlets" {{ $createAllOutlets ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100 ms-2 fw-semibold" for="create_all_outlets" style="cursor:pointer;">
                                                        Semua Outlet
                                                    </label>
                                                </div>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            @endif
                                            @foreach($outlets as $outlet)
                                            <li>
                                                <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                    <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array((int) $outlet->nid, $createOutletIds, true) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                                        {{ $outlet->cname }}
                                                    </label>
                                                </div>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @if($isCreateOld)
                                    @error('outlet_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @endif
                                    <div class="form-text small text-muted">Centang satu atau beberapa outlet, atau pilih Semua Outlet.</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                <div class="{{ $canModifyData ? 'col-lg-6' : 'col-12' }}">
                    <div class="card olsera-panel-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-bold">Cara Pembayaran</h6>
                            <span class="badge bg-light text-secondary border">{{ $payments->total() }} Metode</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-column gap-2">
                                @forelse ($payments as $payment)
                                <div class="border rounded-2 px-3 py-2 bg-white d-flex align-items-center justify-content-between gap-2" style="border-color: #e2e8f0 !important;">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <span class="text-muted"><i class="bi bi-wallet2"></i></span>
                                        <div class="fw-semibold text-dark text-truncate">{{ $payment->cname }}</div>
                                    </div>
                                    @if($canModifyData)
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-light text-primary border" data-bs-toggle="modal" data-bs-target="#editModal{{ $payment->nid }}" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light text-danger border" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $payment->nid }}" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>

                                @if($canModifyData)
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
                                                @php
                                                $isEditOld = old('modal_id') == $payment->nid;
                                                $editAllOutlets = $isEditOld ? (bool) old('all_outlets') : (bool) $payment->is_all_outlets;
                                                $editOutletIds = $isEditOld
                                                ? array_map('intval', (array) old('outlet_ids', []))
                                                : ($payment->is_all_outlets ? [] : ($payment->outlet_ids ?? []));
                                                @endphp
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label for="cname_{{ $payment->nid }}" class="form-label">Nama Metode</label>
                                                        <input type="text" class="form-control @if($isEditOld) @error('cname') is-invalid @enderror @endif" id="cname_{{ $payment->nid }}" name="cname" value="{{ $isEditOld ? old('cname') : $payment->cname }}" required>
                                                        @if($isEditOld) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Pilih Outlet (Multi Outlet) <span class="text-danger">*</span></label>
                                                        <div class="dropdown payment-outlet-dropdown @if($isEditOld) @error('outlet_ids') is-invalid @enderror @endif">
                                                            <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                                                <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                            </button>
                                                            <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                                                @if($isSuperAdmin || $outlets->count() > 1)
                                                                <li>
                                                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                                        <input class="form-check-input me-2 all-outlets-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="all_outlets" value="1" id="edit_all_outlets_{{ $payment->nid }}" {{ $editAllOutlets ? 'checked' : '' }}>
                                                                        <label class="form-check-label w-100 ms-2 fw-semibold" for="edit_all_outlets_{{ $payment->nid }}" style="cursor:pointer;">
                                                                            Semua Outlet
                                                                        </label>
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <hr class="dropdown-divider my-1">
                                                                </li>
                                                                @endif
                                                                @foreach($outlets as $outlet)
                                                                <li>
                                                                    <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                                        <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $payment->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array((int) $outlet->nid, $editOutletIds, true) ? 'checked' : '' }}>
                                                                        <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $payment->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                            {{ $outlet->cname }}
                                                                        </label>
                                                                    </div>
                                                                </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        @if($isEditOld) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                                        <div class="form-text small text-muted">Centang outlet tujuan. Jika memilih lebih dari 1 outlet, metode pembayaran baru akan dibuat untuk outlet tambahan.</div>
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
                                @endif
                                @empty
                                <div class="text-center text-muted py-4 border rounded-2 bg-light">
                                    Tidak ada metode pembayaran.
                                </div>
                                @endforelse
                            </div>

                            @if($payments->hasPages())
                            <div class="d-flex justify-content-end mt-3">
                                {{ $payments->links('pagination::bootstrap-5') }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        function updateOutletDropdownText(dropdown) {
            var selectedText = dropdown.querySelector('.selected-text');
            if (!selectedText) {
                return;
            }

            var allCheckbox = dropdown.querySelector('.all-outlets-checkbox');
            var checkedOutlets = dropdown.querySelectorAll('.outlet-checkbox:checked');

            if (allCheckbox && allCheckbox.checked) {
                selectedText.textContent = 'Semua Outlet';
            } else if (checkedOutlets.length === 0) {
                selectedText.textContent = 'Pilih Outlet...';
            } else if (checkedOutlets.length === 1) {
                selectedText.textContent = checkedOutlets[0].getAttribute('data-name');
            } else {
                selectedText.textContent = checkedOutlets.length + ' Outlet Terpilih';
            }
        }

        document.querySelectorAll('.payment-outlet-dropdown').forEach(function(dropdown) {
            var allCheckbox = dropdown.querySelector('.all-outlets-checkbox');
            var outletCheckboxes = dropdown.querySelectorAll('.outlet-checkbox');

            if (allCheckbox) {
                allCheckbox.addEventListener('change', function() {
                    if (allCheckbox.checked) {
                        outletCheckboxes.forEach(function(cb) {
                            cb.checked = false;
                        });
                    }
                    updateOutletDropdownText(dropdown);
                });
            }

            outletCheckboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    if (cb.checked && allCheckbox) {
                        allCheckbox.checked = false;
                    }
                    updateOutletDropdownText(dropdown);
                });
            });

            updateOutletDropdownText(dropdown);
        });

        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId && modalId !== 'create') {
            var editEl = document.getElementById('editModal' + modalId);
            if (editEl) {
                new bootstrap.Modal(editEl).show();
            }
        }
    });
</script>
@endpush