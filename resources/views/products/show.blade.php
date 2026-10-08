@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
<div class="mb-3">
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Produk
    </a>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informasi Produk</h5>
            </div>
            <div class="card-body">
                @if($product->cphotos)
                    <div class="text-center mb-3">
                        <img src="{{ asset($product->cphotos) }}" alt="{{ $product->cname }}" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                    </div>
                @endif
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" width="40%">Nama</td>
                        <td class="fw-bold">{{ $product->cname }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kategori</td>
                        <td>{{ $product->category->cname ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Outlet</td>
                        <td>{{ $product->outlet->cname ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            @if(strtolower($product->cstatus) == 'active' || strtolower($product->cstatus) == 'aktif' || $product->cstatus == '1')
                                <span class="badge bg-success">{{ $product->cstatus }}</span>
                            @else
                                <span class="badge bg-secondary">{{ $product->cstatus }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga POS</td>
                        <td class="fw-bold text-primary">Rp {{ number_format($product->nprice, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Online</td>
                        <td>Rp {{ number_format($product->nprice_online, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="price-tab" data-bs-toggle="tab" data-bs-target="#price" type="button" role="tab" aria-controls="price" aria-selected="true">
                            <i class="bi bi-tags me-1"></i> Tingkatan Harga
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="productTabsContent">
                    
                    <!-- Tab Tingkatan Harga -->
                    <div class="tab-pane fade show active" id="price" role="tabpanel" aria-labelledby="price-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Daftar Tingkat Harga</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPriceModal">
                                <i class="bi bi-plus-lg"></i> Tambah
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipe Pelanggan</th>
                                        <th>Variant</th>
                                        <th>Qty Mulai</th>
                                        <th>Harga Jual</th>
                                        <th class="text-center" width="20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($product->productPrices->sortBy(['customerType.cname', 'nqty_start']) as $price)
                                        <tr>
                                            <td>{{ $price->customerType->cname ?? '-' }}</td>
                                            <td><span class="badge bg-secondary">Semua</span></td>
                                            <td>{{ $price->nqty_start }}</td>
                                            <td class="fw-bold">IDR {{ number_format($price->nprice, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#editPriceModal{{ $price->nid }}">
                                                    <i class="bi bi-pencil"></i> Ubah
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deletePriceModal{{ $price->nid }}">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Price Modal -->
                                        <div class="modal fade" id="editPriceModal{{ $price->nid }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Ubah Tingkat Harga</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('products.prices.update', ['product' => $product->nid, 'price' => $price->nid]) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="modal_id" value="{{ $price->nid }}">
                                                        <div class="modal-body text-start">
                                                            <div class="mb-3">
                                                                <label class="form-label">Tipe Pelanggan</label>
                                                                <select class="form-select @if(old('modal_id') == $price->nid) @error('nid_cust_type') is-invalid @enderror @endif" name="nid_cust_type" required>
                                                                    <option value="">Pilih Tipe Pelanggan</option>
                                                                    @foreach($customerTypes as $cat)
                                                                        <option value="{{ $cat->nid }}" {{ (old('modal_id') == $price->nid ? old('nid_cust_type') : $price->nid_cust_type) == $cat->nid ? 'selected' : '' }}>
                                                                            {{ $cat->cname }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @if(old('modal_id') == $price->nid) @error('nid_cust_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Qty Mulai</label>
                                                                <input type="number" min="1" class="form-control @if(old('modal_id') == $price->nid) @error('nqty_start') is-invalid @enderror @endif" name="nqty_start" value="{{ old('modal_id') == $price->nid ? old('nqty_start') : $price->nqty_start }}" required>
                                                                @if(old('modal_id') == $price->nid) @error('nqty_start') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Harga Jual</label>
                                                                <input type="number" min="0" step="any" class="form-control @if(old('modal_id') == $price->nid) @error('nprice') is-invalid @enderror @endif" name="nprice" value="{{ old('modal_id') == $price->nid ? old('nprice') : $price->nprice }}" required>
                                                                @if(old('modal_id') == $price->nid) @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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

                                        <!-- Delete Price Modal -->
                                        <div class="modal fade" id="deletePriceModal{{ $price->nid }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center py-4">
                                                        <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                                        <h5 class="mb-3">Apakah Anda yakin?</h5>
                                                        <p class="text-muted">Menghapus tingkat harga untuk tipe pelanggan <strong>{{ $price->customerType->cname ?? '-' }}</strong> mulai Qty <strong>{{ $price->nqty_start }}</strong>.</p>
                                                    </div>
                                                    <div class="modal-footer bg-light justify-content-center">
                                                        <form action="{{ route('products.prices.destroy', ['product' => $product->nid, 'price' => $price->nid]) }}" method="POST">
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
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada tingkatan harga.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Price Modal -->
<div class="modal fade" id="createPriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tingkat Harga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.prices.store', $product->nid) }}" method="POST">
                @csrf
                <input type="hidden" name="modal_id" value="create_price">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipe Pelanggan</label>
                        <select class="form-select @if(old('modal_id') == 'create_price') @error('nid_cust_type') is-invalid @enderror @endif" name="nid_cust_type" required>
                            <option value="">Pilih Tipe Pelanggan</option>
                            @foreach($customerTypes as $cat)
                                <option value="{{ $cat->nid }}" {{ old('modal_id') == 'create_price' && old('nid_cust_type') == $cat->nid ? 'selected' : '' }}>
                                    {{ $cat->cname }}
                                </option>
                            @endforeach
                        </select>
                        @if(old('modal_id') == 'create_price') @error('nid_cust_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qty Mulai</label>
                        <input type="number" min="1" class="form-control @if(old('modal_id') == 'create_price') @error('nqty_start') is-invalid @enderror @endif" name="nqty_start" value="{{ old('modal_id') == 'create_price' ? old('nqty_start') : '1' }}" required>
                        @if(old('modal_id') == 'create_price') @error('nqty_start') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" min="0" step="any" class="form-control @if(old('modal_id') == 'create_price') @error('nprice') is-invalid @enderror @endif" name="nprice" value="{{ old('modal_id') == 'create_price' ? old('nprice') : '' }}" required>
                        @if(old('modal_id') == 'create_price') @error('nprice') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
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
            if (modalId === 'create_price') {
                var myModal = new bootstrap.Modal(document.getElementById('createPriceModal'));
                myModal.show();
            } else if (modalId) {
                var myModal = new bootstrap.Modal(document.getElementById('editPriceModal' + modalId));
                myModal.show();
            }
        }
    });
</script>
@endpush
