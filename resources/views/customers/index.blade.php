@extends('layouts.app')

@section('title', 'Pelanggan')

@push('styles')
<style>
    .sticky-end {
        position: sticky;
        right: 0;
        z-index: 1;
        box-shadow: -2px 0 5px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Daftar Pelanggan</h5>
            <form action="{{ route('customers.index') }}" method="GET" class="d-flex gap-2" id="filterForm">
                <select name="nid_outlet" class="form-select form-select-sm" style="min-width: 150px;" id="outletFilter">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                    <option value="{{ $outlet->nid }}" {{ request('nid_outlet') == $outlet->nid ? 'selected' : '' }}>
                        {{ $outlet->cname }}
                    </option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm" style="width: 350px;">
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Cari pelanggan..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div>
            <button type="button" class="btn btn-outline-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-excel"></i> Import Data
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-lg"></i> Tambah Baru
            </button>
        </div>
    </div>
    <div class="card-body p-0" id="table-container">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0" style="white-space: nowrap;">
                <thead class="bg-light text-secondary text-center" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <!-- <th class="py-3 ps-4">ID</th> -->
                        <th class="py-3">Nama Pelanggan</th>
                        <th class="py-3">Outlet</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Tipe</th>
                        <th class="py-3">No Member</th>
                        <th class="py-3">Gender</th>
                        <th class="py-3">No HP</th>
                        <th class="py-3">Email</th>
                        <th class="py-3">Tanggal Lahir</th>
                        <th class="py-3">Alamat</th>
                        <th class="py-3">Kode Pos</th>
                        <th class="py-3">Kota</th>
                        <th class="py-3">Kecamatan</th>
                        <th class="py-3">Provinsi</th>
                        <th class="py-3">Negara</th>
                        <th class="py-3">Catatan</th>
                        <th class="py-3">Transaksi Terakhir</th>
                        <th class="text-center py-3 pe-4 sticky-end bg-light">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <!-- <td class="ps-4 fw-bold text-muted">#{{ $customer->nid }}</td> -->
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill fs-5"></i>
                                </div>
                                <span class="fw-semibold text-dark">{{ $customer->cname }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                            $customerOutlets = \App\Models\MposCust::where('cphone', $customer->cphone)
                                ->where('cname', $customer->cname)
                                ->with('outlet')->get();
                            $hasOutlets = false;
                            @endphp
                            @foreach($customerOutlets as $co)
                            @if($co->outlet)
                            <span class="badge bg-light text-dark border mb-1">{{ $co->outlet->cname }}</span><br>
                            @php $hasOutlets = true; @endphp
                            @endif
                            @endforeach
                            @if(!$hasOutlets)
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($customer->factive)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded">Aktif</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded">Non-Aktif</span>
                            @endif
                        </td>
                        <td>{{ $customer->type ? $customer->type->cname : '-' }}</td>
                        <td>{{ $customer->cmembership_no ?? '-' }}</td>
                        <td>{{ $customer->cgender ?? '-' }}</td>
                        <td>{{ $customer->cphone ?? '-' }}</td>
                        <td>{{ $customer->cemail ?? '-' }}</td>
                        <td>{{ $customer->dbirth ? \Carbon\Carbon::parse($customer->dbirth)->format('d M Y') : '-' }}</td>
                        <td>{{ $customer->caddress ? \Illuminate\Support\Str::limit($customer->caddress, 30) : '-' }}</td>
                        <td>{{ $customer->cpostal_code ?? '-' }}</td>
                        <td>{{ $customer->ccity ?? '-' }}</td>
                        <td>{{ $customer->cdistrict ?? '-' }}</td>
                        <td>{{ $customer->cprovince ?? '-' }}</td>
                        <td>{{ $customer->ccountry ?? '-' }}</td>
                        <td>{{ $customer->cnotes ? \Illuminate\Support\Str::limit($customer->cnotes, 20) : '-' }}</td>
                        <td>
                            @if($customer->dlast_transaction)
                                {{ \Carbon\Carbon::parse($customer->dlast_transaction)->format('d M Y H:i') }}
                            @elseif($customer->sales_max_dtransaction)
                                {{ \Carbon\Carbon::parse($customer->sales_max_dtransaction)->format('d M Y H:i') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center pe-4 sticky-end bg-white">
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
                        <div class="modal-dialog modal-dialog-centered modal-lg">
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
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="cname_{{ $customer->nid }}" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cname') is-invalid @enderror @endif" id="cname_{{ $customer->nid }}" name="cname" value="{{ old('modal_id') == $customer->nid ? old('cname') : $customer->cname }}" required>
                                                @if(old('modal_id') == $customer->nid) @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Pilih Outlet <span class="text-danger">*</span></label>
                                                <div class="dropdown @if(old('modal_id') == $customer->nid) @error('outlet_ids') is-invalid @enderror @endif">
                                                    <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_{{ $customer->nid }}">
                                                        <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                                    </button>
                                                    <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                                        @php
                                                        $custOutletIds = \App\Models\MposCust::where('cphone', $customer->cphone)
                                                            ->where('cname', $customer->cname)
                                                            ->pluck('nid_outlet')->toArray();
                                                        @endphp
                                                        @foreach($outlets as $outlet)
                                                        @php
                                                        $isChecked = false;
                                                        if (old('modal_id') == $customer->nid && old('outlet_ids')) {
                                                        $isChecked = in_array($outlet->nid, old('outlet_ids'));
                                                        } else {
                                                        $isChecked = in_array($outlet->nid, $custOutletIds);
                                                        }
                                                        @endphp
                                                        <li>
                                                            <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                                                <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="edit_outlet_{{ $customer->nid }}_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ $isChecked ? 'checked' : '' }}>
                                                                <label class="form-check-label w-100 ms-2" for="edit_outlet_{{ $customer->nid }}_{{ $outlet->nid }}" style="cursor:pointer;">
                                                                    {{ $outlet->cname }}
                                                                    @if($customer->nid_outlet == $outlet->nid)
                                                                    <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                @if(old('modal_id') == $customer->nid) @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="nid_type_{{ $customer->nid }}" class="form-label">Tipe Pelanggan</label>
                                                <select class="form-select @if(old('modal_id') == $customer->nid) @error('nid_type') is-invalid @enderror @endif" id="nid_type_{{ $customer->nid }}" name="nid_type">
                                                    <option value="">Pilih Tipe</option>
                                                    @foreach($customerTypes as $type)
                                                    <option value="{{ $type->nid }}" {{ (old('modal_id') == $customer->nid ? old('nid_type') : $customer->nid_type) == $type->nid ? 'selected' : '' }}>{{ $type->cname }}</option>
                                                    @endforeach
                                                </select>
                                                @if(old('modal_id') == $customer->nid) @error('nid_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cphone_{{ $customer->nid }}" class="form-label">No HP</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cphone') is-invalid @enderror @endif" id="cphone_{{ $customer->nid }}" name="cphone" value="{{ old('modal_id') == $customer->nid ? old('cphone') : $customer->cphone }}">
                                                @if(old('modal_id') == $customer->nid) @error('cphone') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cemail_{{ $customer->nid }}" class="form-label">Email</label>
                                                <input type="email" class="form-control @if(old('modal_id') == $customer->nid) @error('cemail') is-invalid @enderror @endif" id="cemail_{{ $customer->nid }}" name="cemail" value="{{ old('modal_id') == $customer->nid ? old('cemail') : $customer->cemail }}">
                                                @if(old('modal_id') == $customer->nid) @error('cemail') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cgender_{{ $customer->nid }}" class="form-label">Jenis Kelamin</label>
                                                <select class="form-select @if(old('modal_id') == $customer->nid) @error('cgender') is-invalid @enderror @endif" id="cgender_{{ $customer->nid }}" name="cgender">
                                                    <option value="">Pilih Gender</option>
                                                    <option value="MALE" {{ (old('modal_id') == $customer->nid ? old('cgender') : $customer->cgender) == 'MALE' ? 'selected' : '' }}>Laki-laki</option>
                                                    <option value="FEMALE" {{ (old('modal_id') == $customer->nid ? old('cgender') : $customer->cgender) == 'FEMALE' ? 'selected' : '' }}>Perempuan</option>
                                                </select>
                                                @if(old('modal_id') == $customer->nid) @error('cgender') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cmembership_no_{{ $customer->nid }}" class="form-label">No Member</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cmembership_no') is-invalid @enderror @endif" id="cmembership_no_{{ $customer->nid }}" name="cmembership_no" value="{{ old('modal_id') == $customer->nid ? old('cmembership_no') : $customer->cmembership_no }}">
                                                @if(old('modal_id') == $customer->nid) @error('cmembership_no') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="dbirth_{{ $customer->nid }}" class="form-label">Tanggal Lahir</label>
                                                <input type="date" class="form-control @if(old('modal_id') == $customer->nid) @error('dbirth') is-invalid @enderror @endif" id="dbirth_{{ $customer->nid }}" name="dbirth" value="{{ old('modal_id') == $customer->nid ? old('dbirth') : ($customer->dbirth ? \Carbon\Carbon::parse($customer->dbirth)->format('Y-m-d') : '') }}">
                                                @if(old('modal_id') == $customer->nid) @error('dbirth') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cpostal_code_{{ $customer->nid }}" class="form-label">Kode Pos</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cpostal_code') is-invalid @enderror @endif" id="cpostal_code_{{ $customer->nid }}" name="cpostal_code" value="{{ old('modal_id') == $customer->nid ? old('cpostal_code') : $customer->cpostal_code }}">
                                                @if(old('modal_id') == $customer->nid) @error('cpostal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="ccountry_{{ $customer->nid }}" class="form-label">Negara</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('ccountry') is-invalid @enderror @endif" id="ccountry_{{ $customer->nid }}" name="ccountry" value="{{ old('modal_id') == $customer->nid ? old('ccountry') : $customer->ccountry }}">
                                                @if(old('modal_id') == $customer->nid) @error('ccountry') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cprovince_{{ $customer->nid }}" class="form-label">Provinsi</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cprovince') is-invalid @enderror @endif" id="cprovince_{{ $customer->nid }}" name="cprovince" value="{{ old('modal_id') == $customer->nid ? old('cprovince') : $customer->cprovince }}">
                                                @if(old('modal_id') == $customer->nid) @error('cprovince') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="ccity_{{ $customer->nid }}" class="form-label">Kota/Kabupaten</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('ccity') is-invalid @enderror @endif" id="ccity_{{ $customer->nid }}" name="ccity" value="{{ old('modal_id') == $customer->nid ? old('ccity') : $customer->ccity }}">
                                                @if(old('modal_id') == $customer->nid) @error('ccity') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cdistrict_{{ $customer->nid }}" class="form-label">Kecamatan</label>
                                                <input type="text" class="form-control @if(old('modal_id') == $customer->nid) @error('cdistrict') is-invalid @enderror @endif" id="cdistrict_{{ $customer->nid }}" name="cdistrict" value="{{ old('modal_id') == $customer->nid ? old('cdistrict') : $customer->cdistrict }}">
                                                @if(old('modal_id') == $customer->nid) @error('cdistrict') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label for="factive_{{ $customer->nid }}" class="form-label">Status Pelanggan</label>
                                                <select class="form-select @if(old('modal_id') == $customer->nid) @error('factive') is-invalid @enderror @endif" id="factive_{{ $customer->nid }}" name="factive">
                                                    <option value="1" {{ (old('modal_id') == $customer->nid ? old('factive') : $customer->factive) == 1 ? 'selected' : (old('modal_id') != $customer->nid && !isset($customer->factive) ? 'selected' : '') }}>Aktif</option>
                                                    <option value="0" {{ (old('modal_id') == $customer->nid ? old('factive') : $customer->factive) == 0 && isset($customer->factive) ? 'selected' : '' }}>Non-Aktif</option>
                                                </select>
                                                @if(old('modal_id') == $customer->nid) @error('factive') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label for="caddress_{{ $customer->nid }}" class="form-label">Alamat</label>
                                                <textarea class="form-control @if(old('modal_id') == $customer->nid) @error('caddress') is-invalid @enderror @endif" id="caddress_{{ $customer->nid }}" name="caddress" rows="2">{{ old('modal_id') == $customer->nid ? old('caddress') : $customer->caddress }}</textarea>
                                                @if(old('modal_id') == $customer->nid) @error('caddress') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label for="cnotes_{{ $customer->nid }}" class="form-label">Catatan</label>
                                                <textarea class="form-control @if(old('modal_id') == $customer->nid) @error('cnotes') is-invalid @enderror @endif" id="cnotes_{{ $customer->nid }}" name="cnotes" rows="2">{{ old('modal_id') == $customer->nid ? old('cnotes') : $customer->cnotes }}</textarea>
                                                @if(old('modal_id') == $customer->nid) @error('cnotes') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                            </div>
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
                        <td colspan="17" class="text-center text-muted py-4">Tidak ada data pelanggan.</td>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Pelanggan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body text-start">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cname" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cname') is-invalid @enderror @endif" id="cname" name="cname" value="{{ old('modal_id') == 'create' ? old('cname') : '' }}" required>
                            @if(old('modal_id') == 'create') @error('cname') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pilih Outlet <span class="text-danger">*</span></label>
                            <div class="dropdown @if(old('modal_id') == 'create') @error('outlet_ids') is-invalid @enderror @endif">
                                <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="dropdownBtn_create">
                                    <span><i class="bi bi-shop me-2"></i> <span class="selected-text">Pilih Outlet...</span></span>
                                </button>
                                <ul class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                                    @foreach($outlets as $outlet)
                                    <li>
                                        <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center">
                                            <input class="form-check-input me-2 outlet-checkbox" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="create_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ (is_array(old('outlet_ids')) && in_array($outlet->nid, old('outlet_ids')) && old('modal_id') == 'create') ? 'checked' : '' }}>
                                            <label class="form-check-label w-100 ms-2" for="create_outlet_{{ $outlet->nid }}" style="cursor:pointer;">
                                                {{ $outlet->cname }}
                                            </label>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @if(old('modal_id') == 'create')
                            @error('outlet_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nid_type" class="form-label">Tipe Pelanggan</label>
                            <select class="form-select @if(old('modal_id') == 'create') @error('nid_type') is-invalid @enderror @endif" id="nid_type" name="nid_type">
                                <option value="">Pilih Tipe</option>
                                @foreach($customerTypes as $type)
                                <option value="{{ $type->nid }}" {{ old('modal_id') == 'create' && old('nid_type') == $type->nid ? 'selected' : '' }}>{{ $type->cname }}</option>
                                @endforeach
                            </select>
                            @if(old('modal_id') == 'create') @error('nid_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cphone" class="form-label">No HP</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cphone') is-invalid @enderror @endif" id="cphone" name="cphone" value="{{ old('modal_id') == 'create' ? old('cphone') : '' }}">
                            @if(old('modal_id') == 'create') @error('cphone') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cemail" class="form-label">Email</label>
                            <input type="email" class="form-control @if(old('modal_id') == 'create') @error('cemail') is-invalid @enderror @endif" id="cemail" name="cemail" value="{{ old('modal_id') == 'create' ? old('cemail') : '' }}">
                            @if(old('modal_id') == 'create') @error('cemail') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cgender" class="form-label">Jenis Kelamin</label>
                            <select class="form-select @if(old('modal_id') == 'create') @error('cgender') is-invalid @enderror @endif" id="cgender" name="cgender">
                                <option value="">Pilih Gender</option>
                                <option value="MALE" {{ old('modal_id') == 'create' && old('cgender') == 'MALE' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="FEMALE" {{ old('modal_id') == 'create' && old('cgender') == 'FEMALE' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @if(old('modal_id') == 'create') @error('cgender') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cmembership_no" class="form-label">No Member</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cmembership_no') is-invalid @enderror @endif" id="cmembership_no" name="cmembership_no" value="{{ old('modal_id') == 'create' ? old('cmembership_no') : '' }}">
                            @if(old('modal_id') == 'create') @error('cmembership_no') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dbirth" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control @if(old('modal_id') == 'create') @error('dbirth') is-invalid @enderror @endif" id="dbirth" name="dbirth" value="{{ old('modal_id') == 'create' ? old('dbirth') : '' }}">
                            @if(old('modal_id') == 'create') @error('dbirth') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cpostal_code" class="form-label">Kode Pos</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cpostal_code') is-invalid @enderror @endif" id="cpostal_code" name="cpostal_code" value="{{ old('modal_id') == 'create' ? old('cpostal_code') : '' }}">
                            @if(old('modal_id') == 'create') @error('cpostal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ccountry" class="form-label">Negara</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('ccountry') is-invalid @enderror @endif" id="ccountry" name="ccountry" value="{{ old('modal_id') == 'create' ? old('ccountry') : '' }}">
                            @if(old('modal_id') == 'create') @error('ccountry') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cprovince" class="form-label">Provinsi</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cprovince') is-invalid @enderror @endif" id="cprovince" name="cprovince" value="{{ old('modal_id') == 'create' ? old('cprovince') : '' }}">
                            @if(old('modal_id') == 'create') @error('cprovince') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ccity" class="form-label">Kota/Kabupaten</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('ccity') is-invalid @enderror @endif" id="ccity" name="ccity" value="{{ old('modal_id') == 'create' ? old('ccity') : '' }}">
                            @if(old('modal_id') == 'create') @error('ccity') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cdistrict" class="form-label">Kecamatan</label>
                            <input type="text" class="form-control @if(old('modal_id') == 'create') @error('cdistrict') is-invalid @enderror @endif" id="cdistrict" name="cdistrict" value="{{ old('modal_id') == 'create' ? old('cdistrict') : '' }}">
                            @if(old('modal_id') == 'create') @error('cdistrict') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="factive" class="form-label">Status Pelanggan</label>
                            <select class="form-select @if(old('modal_id') == 'create') @error('factive') is-invalid @enderror @endif" id="factive" name="factive">
                                <option value="1" {{ old('modal_id') == 'create' && old('factive') == '1' ? 'selected' : (old('modal_id') != 'create' ? 'selected' : '') }}>Aktif</option>
                                <option value="0" {{ old('modal_id') == 'create' && old('factive') == '0' ? 'selected' : '' }}>Non-Aktif</option>
                            </select>
                            @if(old('modal_id') == 'create') @error('factive') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="caddress" class="form-label">Alamat</label>
                            <textarea class="form-control @if(old('modal_id') == 'create') @error('caddress') is-invalid @enderror @endif" id="caddress" name="caddress" rows="2">{{ old('modal_id') == 'create' ? old('caddress') : '' }}</textarea>
                            @if(old('modal_id') == 'create') @error('caddress') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="cnotes" class="form-label">Catatan</label>
                            <textarea class="form-control @if(old('modal_id') == 'create') @error('cnotes') is-invalid @enderror @endif" id="cnotes" name="cnotes" rows="2">{{ old('modal_id') == 'create' ? old('cnotes') : '' }}</textarea>
                            @if(old('modal_id') == 'create') @error('cnotes') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_outlet_id" class="form-label">Pilih Outlet</label>
                        <select class="form-select @error('outlet_id') is-invalid @enderror" id="import_outlet_id" name="outlet_id" required>
                            <option value="">-- Pilih Outlet --</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}">{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">File Excel (.xlsx)</label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import</button>
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

        // Initial update
        updateDropdownText();

        // AJAX Search
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const outletFilter = document.getElementById('outletFilter');
        const tableContainer = document.getElementById('table-container');
        let searchTimeout;

        function fetchResults() {
            const url = new URL(filterForm.action);
            const params = new URLSearchParams(new FormData(filterForm));
            url.search = params.toString();

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('table-container');
                if (newContent) {
                    tableContainer.innerHTML = newContent.innerHTML;
                    window.history.pushState({}, '', url);
                }
            })
            .catch(err => console.error("Error fetching data:", err));
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                // Trigger fetch 500ms after user stops typing
                searchTimeout = setTimeout(fetchResults, 500);
            });
        }

        if (outletFilter) {
            outletFilter.addEventListener('change', fetchResults);
        }
        
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchResults();
            });
        }
    });
</script>
@endpush