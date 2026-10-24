@extends('layouts.app')

@section('title', 'Rincian Supplier - ' . $supplier->cname)

@push('styles')
<style>
    .supplier-logo-box {
        width: 140px;
        height: 140px;
        border-radius: 12px;
        background-color: #f8fafc;
        border: 2px dashed #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .supplier-logo-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 8px;
    }
    .supplier-card-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .supplier-field-title {
        font-size: 0.78rem;
        color: #64748b;
        margin-bottom: 2px;
    }
    .supplier-field-value {
        font-size: 0.92rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 12px;
    }
</style>
@endpush

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}" class="text-decoration-none">Supplier</a></li>
                <li class="breadcrumb-item active" aria-current="page">Rincian Supplier</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">{{ $supplier->cname }}</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editSupplierModal">
            <i class="bi bi-pencil me-1"></i> Ubah
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white p-0" style="border-bottom: 2px solid #f1f5f9;">
        <ul class="nav nav-tabs border-0" id="supplierTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-4 py-3 fw-semibold text-dark" id="profil-tab" data-bs-toggle="tab" data-bs-target="#profil-pane" type="button" role="tab">
                    <i class="bi bi-person-lines-fill me-1"></i> Profil
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-3 fw-semibold text-dark" id="pembelian-tab" data-bs-toggle="tab" data-bs-target="#pembelian-pane" type="button" role="tab">
                    <i class="bi bi-cart-check me-1"></i> Riwayat Pembelian ({{ $supplier->purchases->count() }})
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="supplierTabsContent">
            <!-- TAB PROFIL -->
            <div class="tab-pane fade show active" id="profil-pane" role="tabpanel">
                <div class="row g-4">
                    <!-- Rincian Supplier -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border border-light-subtle h-100">
                            <div class="supplier-card-label"><i class="bi bi-info-circle me-1"></i> Rincian Supplier</div>
                            
                            <div class="supplier-field-title">Personal Yg Dihubungi (PIC)</div>
                            <div class="supplier-field-value">{{ $supplier->ccontact_person ?: '-' }}</div>

                            <div class="supplier-field-title">Nomor Telepon</div>
                            <div class="supplier-field-value">{{ $supplier->cphone ?: '-' }}</div>

                            <div class="supplier-field-title">Email</div>
                            <div class="supplier-field-value">{{ $supplier->cemail ?: '-' }}</div>

                            <div class="supplier-field-title">Status</div>
                            <div class="supplier-field-value">
                                @if($supplier->factive)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Nonaktif</span>
                                @endif
                            </div>

                            <div class="supplier-field-title">Outlet Terdaftar</div>
                            <div class="supplier-field-value">
                                @forelse($assignedOutlets as $assignedOutlet)
                                    <span class="badge bg-light text-dark border mb-1 me-1"><i class="bi bi-shop me-1 text-primary"></i>{{ $assignedOutlet->cname }}</span>
                                @empty
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Semua / Belum Ditentukan</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border border-light-subtle h-100">
                            <div class="supplier-card-label"><i class="bi bi-geo-alt me-1"></i> Alamat Lengkap</div>
                            <div class="supplier-field-value" style="white-space: pre-line;">{{ $supplier->caddress ?: 'Tidak ada alamat tercatat' }}</div>
                        </div>
                    </div>

                    <!-- Catatan & Logo -->
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="supplier-card-label"><i class="bi bi-sticky me-1"></i> Catatan</div>
                                <div class="supplier-field-value" style="white-space: pre-line;">{{ $supplier->cnote ?: 'Tidak ada catatan' }}</div>
                            </div>

                            <div class="mt-3">
                                <div class="supplier-card-label"><i class="bi bi-image me-1"></i> Logo Supplier</div>
                                <div class="supplier-logo-box">
                                    @if($supplier->logo_url)
                                        <img src="{{ $supplier->logo_url }}" alt="{{ $supplier->cname }}">
                                    @else
                                        <div class="text-center text-muted small">
                                            <i class="bi bi-image fs-3 d-block"></i>
                                            Belum ada logo
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        Didaftarkan pada: {{ \Carbon\Carbon::parse($supplier->dcreated)->format('d F Y, H:i') }}
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal">
                        <i class="bi bi-trash me-1"></i> Hapus Supplier
                    </button>
                </div>
            </div>

            <!-- TAB RIWAYAT PEMBELIAN -->
            <div class="tab-pane fade" id="pembelian-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold">Daftar Transaksi Pembelian Dari {{ $supplier->cname }}</h6>
                    <a href="{{ route('purchases.index', ['supplier_id' => $supplier->nid]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Menu Pembelian
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th>NO. PEMBELIAN</th>
                                <th>TANGGAL BELI</th>
                                <th>OUTLET</th>
                                <th>TOTAL AMOUNT</th>
                                <th>STATUS BAYAR</th>
                                <th>STATUS BARANG</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse($supplier->purchases as $purchase)
                                <tr>
                                    <td class="fw-bold">{{ $purchase->cno_purchase }}</td>
                                    <td>{{ \Carbon\Carbon::parse($purchase->dpurchase)->format('d M Y') }}</td>
                                    <td>{{ $purchase->outlet?->cname ?? '-' }}</td>
                                    <td class="fw-semibold">Rp {{ number_format($purchase->ntotal_amount, 0, ',', '.') }}</td>
                                    <td>
                                        @if($purchase->cpayment_status === 'PAID')
                                            <span class="badge bg-success-subtle text-success">Lunas</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($purchase->cstatus === 'RECEIVED')
                                            <span class="badge bg-success">Diterima</span>
                                        @elseif($purchase->cstatus === 'CANCELLED')
                                            <span class="badge bg-danger">Dibatalkan</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchases.show', $purchase->nid) }}" class="btn btn-outline-info btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Belum ada riwayat transaksi pembelian dari supplier ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Supplier -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-hidden="true">
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
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none select-all-btn" data-target="#editSupplierModal">Pilih Semua</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none deselect-all-btn text-secondary" data-target="#editSupplierModal">Kosongkan</button>
                                    </li>
                                    @php
                                        $currentAssignedIds = $assignedOutlets->pluck('nid')->toArray();
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
                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_detail_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }}>
                                            <label class="form-check-label w-100 ms-2" for="edit_outlet_detail_{{ $outlet->nid }}" style="cursor:pointer;">
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
                        <label class="form-label fw-semibold small">Ganti Logo Supplier</label>
                        <input type="file" name="clogo" class="form-control" accept="image/*">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="factive" value="1" id="factiveEditDetail" {{ $supplier->factive ? 'checked' : '' }}>
                        <label class="form-check-label small" for="factiveEditDetail">Supplier Aktif</label>
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
<div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-hidden="true">
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
                    @if($supplier->purchases->count() > 0)
                        <div class="alert alert-warning mt-2 mb-0 small">
                            <i class="bi bi-info-circle me-1"></i> Supplier ini memiliki <strong>{{ $supplier->purchases->count() }}</strong> transaksi pembelian dan tidak dapat dihapus.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm" {{ $supplier->purchases->count() > 0 ? 'disabled' : '' }}>Hapus</button>
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

        // Reopen modal if validation error occurred
        @if(session('error_modal') == 'edit_' . $supplier->nid)
            var editModalEl = document.getElementById('editSupplierModal');
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
