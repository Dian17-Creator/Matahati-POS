@extends('layouts.app')

@section('title', 'Produk Kombo')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Daftar Produk Kombo</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Tambah Baru
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th width="35%" class="py-3 ps-4">Produk Kombo Utama</th>
                        <th>Daftar Isi & Kuantitas</th>
                        <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($combos as $nid_combo_product => $comboGroup)
                    @php $mainProduct = $comboGroup->first()->comboProduct; @endphp
                    <tr style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-4 py-3">
                            <span class="fw-bold text-dark">{{ $mainProduct->cname ?? 'ID: ' . $nid_combo_product }}</span>
                        </td>
                        <td class="py-3">
                            <ul class="list-unstyled mb-0">
                                @foreach($comboGroup as $item)
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="bi bi-check2 text-success me-2 fw-bold"></i> 
                                    {{ $item->product->cname ?? 'ID: ' . $item->nid_product }}
                                    <span class="badge bg-light text-dark border ms-2">&times; {{ $item->nqty }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm text-primary rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#editModal{{ $nid_combo_product }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px; background: #fff;" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $nid_combo_product }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>


                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            <i class="bi bi-inboxes fs-1 d-block mb-3 opacity-50"></i>
                            Tidak ada data produk kombo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3 me-4">
            {{ $paginatedComboProducts->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@foreach ($combos as $nid_combo_product => $comboGroup)
@php $mainProduct = $comboGroup->first()->comboProduct; @endphp
<!-- Edit Modal -->
<div class="modal fade" id="editModal{{ $nid_combo_product }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Edit Isi Kombo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('combos.update', $nid_combo_product) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="modal_id" value="{{ $nid_combo_product }}">
                <input type="hidden" name="nid_combo_product" value="{{ $nid_combo_product }}">
                <div class="modal-body text-start">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Produk Kombo Utama</label>
                        <div class="form-control bg-light border-0 fw-bold">{{ $mainProduct->cname ?? 'ID: ' . $nid_combo_product }}</div>
                    </div>
                    
                    <label class="form-label text-muted small fw-bold text-uppercase">Daftar Isi Produk</label>
                    <div id="edit-items-container-{{ $nid_combo_product }}">
                        @foreach($comboGroup as $item)
                        <div class="row mb-2 combo-item-row align-items-center">
                            <div class="col-7">
                                <select class="form-select" name="nid_product[]" required>
                                    <option value="">Pilih Isi Produk</option>
                                    @foreach($regularProducts as $rp)
                                        <option value="{{ $rp->nid }}" {{ $item->nid_product == $rp->nid ? 'selected' : '' }}>
                                            {{ $rp->cname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" step="any" min="1" class="form-control" name="nqty[]" value="{{ $item->nqty }}" required placeholder="Qty">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.combo-item-row').remove()" title="Hapus baris">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="addComboItem('edit-items-container-{{ $nid_combo_product }}')">
                        <i class="bi bi-plus-lg"></i> Tambah Produk Isi
                    </button>
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
<div class="modal fade" id="deleteModal{{ $nid_combo_product }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                <h5 class="mb-3">Hapus kombo ini?</h5>
                <p class="text-muted mb-0">Seluruh detail isi kombo akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <form action="{{ route('combos.destroy', $nid_combo_product) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Ya, Hapus Semua</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach


<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk Kombo Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('combos.store') }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Produk Kombo Utama</label>
                        <select class="form-select" name="nid_combo_product" required>
                            <option value="">Pilih Produk Kombo</option>
                            @foreach($comboProducts as $cp)
                                <option value="{{ $cp->nid }}">{{ $cp->cname }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <label class="form-label text-muted small fw-bold text-uppercase">Daftar Isi Produk</label>
                    <div id="create-items-container">
                        <div class="row mb-2 combo-item-row align-items-center">
                            <div class="col-7">
                                <select class="form-select" name="nid_product[]" required>
                                    <option value="">Pilih Isi Produk</option>
                                    @foreach($regularProducts as $rp)
                                        <option value="{{ $rp->nid }}">{{ $rp->cname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" step="any" min="1" class="form-control" name="nqty[]" value="1" required placeholder="Qty">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.combo-item-row').remove()" title="Hapus baris">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="addComboItem('create-items-container')">
                        <i class="bi bi-plus-lg"></i> Tambah Produk Isi
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kombo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productOptions = `
        <option value="">Pilih Isi Produk</option>
        @foreach($regularProducts as $rp)
            <option value="{{ $rp->nid }}">{{ $rp->cname }}</option>
        @endforeach
    `;

    function addComboItem(containerId) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'row mb-2 combo-item-row align-items-center';
        row.innerHTML = `
            <div class="col-7">
                <select class="form-select" name="nid_product[]" required>
                    ${productOptions}
                </select>
            </div>
            <div class="col-3">
                <input type="number" step="any" min="1" class="form-control" name="nqty[]" value="1" required placeholder="Qty">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.combo-item-row').remove()" title="Hapus baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    document.addEventListener("DOMContentLoaded", function() {
        var hasErrors = "{{ $errors->any() ? 'true' : 'false' }}" === "true";
        var modalId = "{{ old('modal_id') }}";
        
        if (hasErrors && modalId) {
            if (modalId === 'create') {
                var myModal = new bootstrap.Modal(document.getElementById('createModal'));
                myModal.show();
            } else {
                var editModal = document.getElementById('editModal' + modalId);
                if(editModal) {
                    var myModal = new bootstrap.Modal(editModal);
                    myModal.show();
                }
            }
        }
    });
</script>
@endpush
