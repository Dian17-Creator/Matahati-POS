@extends('layouts.app')

@section('title', 'Voucher')

@section('content')
<div class="card olsera-panel-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Voucher</h5>
        @if($canModifyData)
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
        @endif
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="text-center py-3 ps-4">Kode</th>
                        <th class="text-center py-3 ps-4">Deskripsi</th>
                        <th class="text-center py-3 ps-4">Periode</th>
                        <th class="text-center py-3 ps-4">Kuota / Terpakai</th>
                        <th class="text-center py-3 ps-4">Diskon</th>
                        <th class="text-center py-3 ps-4">Status</th>
                        @if($canModifyData)
                        <th width="12%" class="text-center py-3 pe-4">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="bi bi-ticket-perforated fs-5"></i>
                                </div>
                                <span class="fw-bold text-primary">{{ $voucher->ckode }}</span>
                            </div>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($voucher->cdesc, 30) }}</td>
                        <td>
                            <small class="d-block">{{ \Carbon\Carbon::parse($voucher->dstart)->format('d M Y') }}</small>
                            <small class="d-block text-muted">
                                s/d {{ $voucher->dend ? \Carbon\Carbon::parse($voucher->dend)->format('d M Y') : 'Tidak ada kadaluarsa' }}
                            </small>
                        </td>
                        <td>
                            @if(is_null($voucher->nqty))
                            <div class="progress" style="height: 6px; width: 80px;" title="{{ $voucher->nredeem ?? 0 }} / - Terpakai">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="text-muted">{{ $voucher->nredeem ?? 0 }} / -</small>
                            @else
                            <div class="progress" style="height: 6px; width: 80px;" title="{{ $voucher->nredeem ?? 0 }} / {{ $voucher->nqty }} Terpakai">
                                @php $pct = $voucher->nqty > 0 ? min(100, (($voucher->nredeem ?? 0) / $voucher->nqty) * 100) : 0; @endphp
                                <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : 'bg-success' }}" role="progressbar" @style(['width: ' . $pct . '%'])></div>
                            </div>
                            <small class="text-muted">{{ $voucher->nredeem ?? 0 }} / {{ $voucher->nqty }}</small>
                            @endif
                        </td>
                        <td>
                            @if($voucher->ndisc_percent > 0)
                            <span class="badge bg-warning text-dark">{{ floatval($voucher->ndisc_percent) }}% OFF</span>
                            @elseif($voucher->ndisc_amount > 0)
                            <span class="badge bg-warning text-dark">Rp {{ number_format($voucher->ndisc_amount, 0, ',', '.') }} OFF</span>
                            @else
                            -
                            @endif
                        </td>
                        <td>
                            @if($voucher->cstatus == 'Active' || $voucher->cstatus == '1' || strtolower($voucher->cstatus) == 'aktif')
                            <span class="badge bg-success">{{ $voucher->cstatus }}</span>
                            @else
                            <span class="badge bg-secondary">{{ $voucher->cstatus }}</span>
                            @endif
                        </td>
                        @if($canModifyData)
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $voucher->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $voucher->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>

                    @if($canModifyData)
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $voucher->nid }}" tabindex="-1" aria-labelledby="editModalLabel{{ $voucher->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel{{ $voucher->nid }}">Edit Voucher</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('vouchers.update', $voucher->nid) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="modal_id" value="{{ $voucher->nid }}">
                                    <div class="modal-body text-start row">
                                        <div class="col-md-6 mb-3">
                                            <label for="ckode_{{ $voucher->nid }}" class="form-label">Kode Voucher</label>
                                            <input type="text" class="form-control @if(old('modal_id') == $voucher->nid) @error('ckode') is-invalid @enderror @endif" id="ckode_{{ $voucher->nid }}" name="ckode" value="{{ old('modal_id') == $voucher->nid ? old('ckode') : $voucher->ckode }}" required>
                                            @if(old('modal_id') == $voucher->nid) @error('ckode') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cstatus_{{ $voucher->nid }}" class="form-label">Status</label>
                                            <select class="form-select @if(old('modal_id') == $voucher->nid) @error('cstatus') is-invalid @enderror @endif" id="cstatus_{{ $voucher->nid }}" name="cstatus" required>
                                                <option value="Active" {{ (old('modal_id') == $voucher->nid ? old('cstatus') : $voucher->cstatus) == 'Active' ? 'selected' : '' }}>Aktif</option>
                                                <option value="Inactive" {{ (old('modal_id') == $voucher->nid ? old('cstatus') : $voucher->cstatus) == 'Inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                            </select>
                                            @if(old('modal_id') == $voucher->nid) @error('cstatus') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="cdesc_{{ $voucher->nid }}" class="form-label">Deskripsi</label>
                                            <textarea class="form-control @if(old('modal_id') == $voucher->nid) @error('cdesc') is-invalid @enderror @endif" id="cdesc_{{ $voucher->nid }}" name="cdesc" rows="2">{{ old('modal_id') == $voucher->nid ? old('cdesc') : $voucher->cdesc }}</textarea>
                                            @if(old('modal_id') == $voucher->nid) @error('cdesc') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="dstart_{{ $voucher->nid }}" class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control @if(old('modal_id') == $voucher->nid) @error('dstart') is-invalid @enderror @endif" id="dstart_{{ $voucher->nid }}" name="dstart" value="{{ old('modal_id') == $voucher->nid ? old('dstart') : (isset($voucher->dstart) ? \Carbon\Carbon::parse($voucher->dstart)->format('Y-m-d') : '') }}" required>
                                            @if(old('modal_id') == $voucher->nid) @error('dstart') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        @php
                                            $isEditOld = old('modal_id') == $voucher->nid;
                                            $isEditUnlimitedEnd = $isEditOld ? (bool) old('unlimited_dend') : is_null($voucher->dend);
                                            $isEditUnlimitedQty = $isEditOld ? (bool) old('unlimited_nqty') : is_null($voucher->nqty);
                                        @endphp
                                        <div class="col-md-6 mb-3">
                                            <label for="dend_{{ $voucher->nid }}" class="form-label">Tanggal Berakhir</label>
                                            <input type="date" class="form-control @if($isEditOld) @error('dend') is-invalid @enderror @endif" id="dend_{{ $voucher->nid }}" name="dend" value="{{ $isEditUnlimitedEnd ? '' : ($isEditOld ? old('dend') : (isset($voucher->dend) ? \Carbon\Carbon::parse($voucher->dend)->format('Y-m-d') : '')) }}" {{ $isEditUnlimitedEnd ? 'disabled' : 'required' }}>
                                            <div class="form-check form-switch mt-1">
                                                <input class="form-check-input js-unlimited-toggle" type="checkbox" role="switch" id="unlimited_dend_{{ $voucher->nid }}" name="unlimited_dend" value="1" data-target="#dend_{{ $voucher->nid }}" {{ $isEditUnlimitedEnd ? 'checked' : '' }}>
                                                <label class="form-check-label small text-muted" for="unlimited_dend_{{ $voucher->nid }}">Tidak ada kadaluarsa (Unlimited)</label>
                                            </div>
                                            @if($isEditOld) @error('dend') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nqty_{{ $voucher->nid }}" class="form-label">Total Kuota (Qty)</label>
                                            <input type="number" min="1" class="form-control @if($isEditOld) @error('nqty') is-invalid @enderror @endif" id="nqty_{{ $voucher->nid }}" name="nqty" value="{{ $isEditUnlimitedQty ? '' : ($isEditOld ? old('nqty') : $voucher->nqty) }}" {{ $isEditUnlimitedQty ? 'disabled' : 'required' }}>
                                            <div class="form-check form-switch mt-1">
                                                <input class="form-check-input js-unlimited-toggle" type="checkbox" role="switch" id="unlimited_nqty_{{ $voucher->nid }}" name="unlimited_nqty" value="1" data-target="#nqty_{{ $voucher->nid }}" {{ $isEditUnlimitedQty ? 'checked' : '' }}>
                                                <label class="form-check-label small text-muted" for="unlimited_nqty_{{ $voucher->nid }}">Kuota tidak terbatas (-)</label>
                                            </div>
                                            @if($isEditOld) @error('nqty') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="nredeem_{{ $voucher->nid }}" class="form-label">Total Terpakai (Redeem)</label>
                                            <input type="number" min="0" class="form-control @if(old('modal_id') == $voucher->nid) @error('nredeem') is-invalid @enderror @endif" id="nredeem_{{ $voucher->nid }}" name="nredeem" value="{{ old('modal_id') == $voucher->nid ? old('nredeem') : $voucher->nredeem }}">
                                            @if(old('modal_id') == $voucher->nid) @error('nredeem') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="nmin_spend_{{ $voucher->nid }}" class="form-label">Minimal Belanja (Rp)</label>
                                            <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == $voucher->nid) @error('nmin_spend') is-invalid @enderror @endif" id="nmin_spend_{{ $voucher->nid }}" name="nmin_spend" value="{{ old('modal_id') == $voucher->nid ? old('nmin_spend') : $voucher->nmin_spend }}">
                                            @if(old('modal_id') == $voucher->nid) @error('nmin_spend') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="ndisc_percent_{{ $voucher->nid }}" class="form-label">Diskon Persen (%)</label>
                                            <input type="number" step="any" min="0" max="100" class="form-control @if(old('modal_id') == $voucher->nid) @error('ndisc_percent') is-invalid @enderror @endif" id="ndisc_percent_{{ $voucher->nid }}" name="ndisc_percent" value="{{ old('modal_id') == $voucher->nid ? old('ndisc_percent') : floatval($voucher->ndisc_percent) }}">
                                            @if(old('modal_id') == $voucher->nid) @error('ndisc_percent') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="ndisc_amount_{{ $voucher->nid }}" class="form-label">Diskon Nominal (Rp)</label>
                                            <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == $voucher->nid) @error('ndisc_amount') is-invalid @enderror @endif" id="ndisc_amount_{{ $voucher->nid }}" name="ndisc_amount" value="{{ old('modal_id') == $voucher->nid ? old('ndisc_amount') : $voucher->ndisc_amount }}">
                                            @if(old('modal_id') == $voucher->nid) @error('ndisc_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
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
                    <div class="modal fade" id="deleteModal{{ $voucher->nid }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $voucher->nid }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="deleteModalLabel{{ $voucher->nid }}">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-3">Apakah anda ingin menghapus voucher <strong>{{ $voucher->ckode }}</strong>?</h5>
                                    <p class="text-muted mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('vouchers.destroy', $voucher->nid) }}" method="POST">
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
                    <tr>
                        <td colspan="{{ $canModifyData ? 7 : 6 }}" class="text-center text-muted py-4">Tidak ada data voucher.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $vouchers->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

@if($canModifyData)
<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Voucher Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vouchers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body row">
                    <div class="col-md-6 mb-3">
                        <label for="ckode" class="form-label">Kode Voucher</label>
                        <input type="text" class="form-control @if(old('modal_id') == 'create') @error('ckode') is-invalid @enderror @endif" id="ckode" name="ckode" value="{{ old('modal_id') == 'create' ? old('ckode') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('ckode') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
                        <label for="cdesc" class="form-label">Deskripsi</label>
                        <textarea class="form-control @if(old('modal_id') == 'create') @error('cdesc') is-invalid @enderror @endif" id="cdesc" name="cdesc" rows="2">{{ old('modal_id') == 'create' ? old('cdesc') : '' }}</textarea>
                        @if(old('modal_id') == 'create') @error('cdesc') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="dstart" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control @if(old('modal_id') == 'create') @error('dstart') is-invalid @enderror @endif" id="dstart" name="dstart" value="{{ old('modal_id') == 'create' ? old('dstart') : '' }}" required>
                        @if(old('modal_id') == 'create') @error('dstart') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    @php
                        $isCreateOld = old('modal_id') == 'create';
                        $isCreateUnlimitedEnd = $isCreateOld ? (bool) old('unlimited_dend') : false;
                        $isCreateUnlimitedQty = $isCreateOld ? (bool) old('unlimited_nqty') : false;
                    @endphp
                    <div class="col-md-6 mb-3">
                        <label for="dend" class="form-label">Tanggal Berakhir</label>
                        <input type="date" class="form-control @if($isCreateOld) @error('dend') is-invalid @enderror @endif" id="dend" name="dend" value="{{ $isCreateUnlimitedEnd ? '' : ($isCreateOld ? old('dend') : '') }}" {{ $isCreateUnlimitedEnd ? 'disabled' : 'required' }}>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input js-unlimited-toggle" type="checkbox" role="switch" id="unlimited_dend_create" name="unlimited_dend" value="1" data-target="#dend" {{ $isCreateUnlimitedEnd ? 'checked' : '' }}>
                            <label class="form-check-label small text-muted" for="unlimited_dend_create">Tidak ada kadaluarsa (Unlimited)</label>
                        </div>
                        @if($isCreateOld) @error('dend') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nqty" class="form-label">Total Kuota (Qty)</label>
                        <input type="number" min="1" class="form-control @if($isCreateOld) @error('nqty') is-invalid @enderror @endif" id="nqty" name="nqty" value="{{ $isCreateUnlimitedQty ? '' : ($isCreateOld ? old('nqty') : '') }}" {{ $isCreateUnlimitedQty ? 'disabled' : 'required' }}>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input js-unlimited-toggle" type="checkbox" role="switch" id="unlimited_nqty_create" name="unlimited_nqty" value="1" data-target="#nqty" {{ $isCreateUnlimitedQty ? 'checked' : '' }}>
                            <label class="form-check-label small text-muted" for="unlimited_nqty_create">Kuota tidak terbatas (-)</label>
                        </div>
                        @if($isCreateOld) @error('nqty') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nmin_spend" class="form-label">Minimal Belanja (Rp)</label>
                        <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == 'create') @error('nmin_spend') is-invalid @enderror @endif" id="nmin_spend" name="nmin_spend" value="{{ old('modal_id') == 'create' ? old('nmin_spend') : '' }}">
                        @if(old('modal_id') == 'create') @error('nmin_spend') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ndisc_percent" class="form-label">Diskon Persen (%)</label>
                        <input type="number" step="any" min="0" max="100" class="form-control @if(old('modal_id') == 'create') @error('ndisc_percent') is-invalid @enderror @endif" id="ndisc_percent" name="ndisc_percent" value="{{ old('modal_id') == 'create' ? old('ndisc_percent') : '' }}" placeholder="Contoh: 10">
                        @if(old('modal_id') == 'create') @error('ndisc_percent') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ndisc_amount" class="form-label">Diskon Nominal (Rp)</label>
                        <input type="number" step="any" min="0" class="form-control @if(old('modal_id') == 'create') @error('ndisc_amount') is-invalid @enderror @endif" id="ndisc_amount" name="ndisc_amount" value="{{ old('modal_id') == 'create' ? old('ndisc_amount') : '' }}" placeholder="Contoh: 50000">
                        @if(old('modal_id') == 'create') @error('ndisc_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                        <small class="text-muted d-block mt-1">Isi salah satu: Persen ATAU Nominal.</small>
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
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        function syncUnlimitedToggle(toggleEl) {
            var targetSelector = toggleEl.getAttribute('data-target');
            var targetInput = targetSelector ? document.querySelector(targetSelector) : null;
            if (!targetInput) {
                return;
            }

            if (toggleEl.checked) {
                targetInput.value = '';
                targetInput.disabled = true;
                targetInput.required = false;
            } else {
                targetInput.disabled = false;
                targetInput.required = true;
            }
        }

        document.querySelectorAll('.js-unlimited-toggle').forEach(function(toggleEl) {
            syncUnlimitedToggle(toggleEl);
            toggleEl.addEventListener('change', function() {
                syncUnlimitedToggle(toggleEl);
            });
        });

        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";

        if (hasErrors && modalId) {
            if (modalId === 'create') {
                var createEl = document.getElementById('createModal');
                if (createEl) {
                    new bootstrap.Modal(createEl).show();
                }
            } else {
                var editEl = document.getElementById('editModal' + modalId);
                if (editEl) {
                    new bootstrap.Modal(editEl).show();
                }
            }
        }
    });
</script>
@endpush