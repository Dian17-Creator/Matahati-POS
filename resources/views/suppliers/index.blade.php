@extends('layouts.app')

@section('title', 'Supplier')

@push('styles')
<style>
    .supplier-logo-thumb {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: contain;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 2px;
    }

    .supplier-logo-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        background-color: #e0f2fe;
        color: #0369a1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
    }
</style>
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 me-3">Daftar Supplier</h5>
            <form action="{{ route('suppliers.index') }}" method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="max-width: 140px;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, PIC, telepon..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                @if(request('search') || request('status'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="bi bi-x-circle"></i>
                </a>
                @endif
            </form>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                <i class="bi bi-plus-lg"></i> Tambah Supplier
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th class="py-3 ps-4" style="width: 50px;">LOGO</th>
                        <th class="py-3">NAMA SUPPLIER</th>
                        <th class="py-3">OUTLET</th>
                        <th class="py-3">PERSONAL YG DIHUBUNGI (PIC)</th>
                        <th class="py-3 text-center">KONTAK</th>
                        <th class="py-3">ALAMAT</th>
                        <th class="py-3 text-center">TOTAL PO</th>
                        <th class="py-3 text-center">STATUS</th>
                        <th class="py-3 text-center" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($suppliers as $supplier)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4">
                            @if($supplier->logo_url)
                            <img src="{{ $supplier->logo_url }}" alt="{{ $supplier->cname }}" class="supplier-logo-thumb">
                            @else
                            <div class="supplier-logo-placeholder">
                                {{ strtoupper(substr($supplier->cname, 0, 1)) }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('suppliers.show', $supplier->nid) }}" class="fw-bold text-decoration-none text-dark">
                                {{ $supplier->cname }}
                            </a>
                            @if(!empty($supplier->cnote))
                            <div class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $supplier->cnote }}">
                                {{ $supplier->cnote }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @php
                            $assignedRecords = $allSupplierOutlets->get($supplier->cname, collect());
                            $uniqueOutlets = $assignedRecords->pluck('outlet')->filter()->unique('nid');
                            @endphp
                            @forelse($uniqueOutlets as $outletItem)
                                <span class="badge bg-light text-dark border mb-1">{{ $outletItem->cname }}</span><br>
                            @empty
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Semua / Belum Ditentukan</span>
                            @endforelse
                        </td>
                        <td>{{ $supplier->ccontact_person ?? '-' }}</td>
                        <td class="text-center">
                            <div>{{ $supplier->cphone ?? '-' }}</div>
                            @if($supplier->cemail)
                            <div class="text-muted"><i class="bi bi-envelope text-muted me-1"></i>{{ $supplier->cemail }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="{{ $supplier->caddress }}">
                                {{ $supplier->caddress ?? '-' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $supplier->purchases_count }} PO</span>
                        </td>
                        <td class="text-center">
                            @if($supplier->factive)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('suppliers.show', $supplier->nid) }}" class="btn btn-outline-info" title="Detail Supplier">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSupplierModal{{ $supplier->nid }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal{{ $supplier->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Edit Supplier -->
                    <div class="modal fade" id="editSupplierModal{{ $supplier->nid }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('suppliers.update', $supplier->nid) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Supplier</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Nama Supplier <span class="text-danger">*</span></label>
                                            <input type="text" name="cname" class="form-control" value="{{ old('cname', $supplier->cname) }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                                            @if($isOutletLocked)
                                                @php
                                                    $lockedOutlet = $outlets->firstWhere('nid', $allowedOutletIds[0] ?? null);
                                                @endphp
                                                <input type="text" class="form-control" value="{{ $lockedOutlet?->cname ?? 'Outlet' }}" readonly disabled>
                                                <input type="hidden" name="outlet_ids[]" value="{{ $allowedOutletIds[0] ?? '' }}">
                                            @else
                                                <div class="dropdown @if(session('error_modal') == 'edit_' . $supplier->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                                    <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_{{ $supplier->nid }}">
                                                        <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                    </button>
                                                    <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                                        <li class="px-2 pb-1 border-bottom mb-1 d-flex justify-content-between">
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none select-all-btn" data-target="#editSupplierModal{{ $supplier->nid }}">Pilih Semua</button>
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none deselect-all-btn text-secondary" data-target="#editSupplierModal{{ $supplier->nid }}">Kosongkan</button>
                                                        </li>
                                                        @php
                                                            $currentAssignedIds = $allSupplierOutlets->get($supplier->cname, collect())->pluck('nid_outlet')->filter()->unique()->toArray();
                                                        @endphp
                                                        @foreach($outlets as $outlet)
                                                        @php
                                                            $isChecked = false;
                                                            if (session('error_modal') == 'edit_' . $supplier->nid && is_array(old('outlet_ids'))) {
                                                                $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                                            } else {
                                                                $isChecked = in_array($outlet->nid, $currentAssignedIds);
                                                            }
                                                        @endphp
                                                        <li>
                                                            <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                                <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $supplier->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }}>
                                                                <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $supplier->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                    {{ $outlet->cname }}
                                                                    @if($supplier->nid_outlet == $outlet->nid)
                                                                    <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                @if(session('error_modal') == 'edit_' . $supplier->nid)
                                                    @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                @endif
                                            @endif
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Personal Yg Dihubungi (PIC)</label>
                                            <input type="text" name="ccontact_person" class="form-control" value="{{ old('ccontact_person', $supplier->ccontact_person) }}">
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Nomor Telepon</label>
                                                <input type="text" name="cphone" class="form-control" value="{{ old('cphone', $supplier->cphone) }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small">Email</label>
                                                <input type="email" name="cemail" class="form-control" value="{{ old('cemail', $supplier->cemail) }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Alamat</label>
                                            <textarea name="caddress" class="form-control" rows="2">{{ old('caddress', $supplier->caddress) }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Catatan</label>
                                            <textarea name="cnote" class="form-control" rows="2">{{ old('cnote', $supplier->cnote) }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Logo Supplier</label>
                                            @if($supplier->logo_url)
                                            <div class="mb-2">
                                                <img src="{{ $supplier->logo_url }}" alt="Logo Saat Ini" style="max-height: 60px;" class="rounded border p-1">
                                            </div>
                                            @endif
                                            <input type="file" name="clogo" class="form-control" accept="image/*">
                                            <div class="form-text small text-muted">Format: JPG, PNG, WebP (Maks: 2MB). Biarkan kosong jika tidak diubah.</div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="factive" value="1" id="factiveEdit{{ $supplier->nid }}" {{ $supplier->factive ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="factiveEdit{{ $supplier->nid }}">Supplier Aktif</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Hapus Supplier -->
                    <div class="modal fade" id="deleteSupplierModal{{ $supplier->nid }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('suppliers.destroy', $supplier->nid) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-header">
                                        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Hapus Supplier</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Apakah Anda yakin ingin menghapus supplier <strong>{{ $supplier->cname }}</strong>?
                                        @if($supplier->purchases_count > 0)
                                        <div class="alert alert-warning mt-2 mb-0 small">
                                            <i class="bi bi-info-circle me-1"></i> Supplier ini memiliki <strong>{{ $supplier->purchases_count }}</strong> transaksi pembelian dan tidak dapat dihapus.
                                        </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger btn-sm" {{ $supplier->purchases_count > 0 ? 'disabled' : '' }}>Hapus</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            Tidak ada data supplier ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
        <div class="d-flex justify-content-center mt-3 p-3">
            {{ $suppliers->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Supplier -->
<div class="modal fade" id="createSupplierModal" tabindex="-1" aria-labelledby="createSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('suppliers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createSupplierModalLabel">Tambah Supplier Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="cname" class="form-control" value="{{ old('cname') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase">Pilih Outlet <span class="text-danger">*</span></label>
                        @if($isOutletLocked)
                            @php
                                $lockedOutlet = $outlets->firstWhere('nid', $allowedOutletIds[0] ?? null);
                            @endphp
                            <input type="text" class="form-control" value="{{ $lockedOutlet?->cname ?? 'Outlet' }}" readonly disabled>
                            <input type="hidden" name="outlet_ids[]" value="{{ $allowedOutletIds[0] ?? '' }}">
                        @else
                            <div class="dropdown @if(session('error_modal') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                                <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_create">
                                    <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                </button>
                                <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                    <li class="px-2 pb-1 border-bottom mb-1 d-flex justify-content-between">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none select-all-btn" data-target="#createSupplierModal">Pilih Semua</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none deselect-all-btn text-secondary" data-target="#createSupplierModal">Kosongkan</button>
                                    </li>
                                    @foreach($outlets as $outlet)
                                    @php
                                        $isChecked = false;
                                        if (session('error_modal') == 'create' && is_array(old('outlet_ids'))) {
                                            $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                        } elseif ($selectedOutletId && $selectedOutletId == $outlet->nid) {
                                            $isChecked = true;
                                        }
                                    @endphp
                                    <li>
                                        <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }}>
                                            <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                                {{ $outlet->cname }}
                                            </label>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @if(session('error_modal') == 'create')
                                @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Personal Yg Dihubungi (PIC)</label>
                        <input type="text" name="ccontact_person" class="form-control" value="{{ old('ccontact_person') }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor Telepon</label>
                            <input type="text" name="cphone" class="form-control" value="{{ old('cphone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" name="cemail" class="form-control" value="{{ old('cemail') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alamat</label>
                        <textarea name="caddress" class="form-control" rows="2">{{ old('caddress') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan</label>
                        <textarea name="cnote" class="form-control" rows="2">{{ old('cnote') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Logo Supplier</label>
                        <input type="file" name="clogo" class="form-control" accept="image/*">
                        <div class="form-text small text-muted">Format: JPG, PNG, WebP (Maks: 2MB).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle Dropdown Outlet Text Update
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

        // Select All / Deselect All
        document.querySelectorAll('.select-all-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const target = document.querySelector(this.getAttribute('data-target'));
                if (target) {
                    target.querySelectorAll('.outlet-checkbox').forEach(cb => cb.checked = true);
                    updateDropdownText();
                }
            });
        });

        document.querySelectorAll('.deselect-all-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const target = document.querySelector(this.getAttribute('data-target'));
                if (target) {
                    target.querySelectorAll('.outlet-checkbox').forEach(cb => cb.checked = false);
                    updateDropdownText();
                }
            });
        });

        // Reopen modal if there was an error
        @if(session('error_modal') == 'create')
            var createModalEl = document.getElementById('createSupplierModal');
            if (createModalEl) {
                var createModal = new bootstrap.Modal(createModalEl);
                createModal.show();
            }
        @elseif(session('error_modal') && str_starts_with(session('error_modal'), 'edit_'))
            var editModalId = "{{ session('error_modal') }}";
            var editModalEl = document.getElementById(editModalId.replace('edit_', 'editSupplierModal'));
            if (editModalEl) {
                var editModal = new bootstrap.Modal(editModalEl);
                editModal.show();
            }
        @endif

        // Initial update
        updateDropdownText();
    });
</script>
@endpush