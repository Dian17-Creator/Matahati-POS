@extends('layouts.app')

@section('title', 'Detail Produk')

@push('styles')
<style>
    .modern-nav-container {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .modern-tabs {
        background-color: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 0.3rem;
        display: inline-flex;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .modern-tabs .back-link {
        text-decoration: none !important;
    }

    .modern-tabs .nav-item {
        margin: 0;
    }

    .modern-tabs .nav-link {
        color: #6c757d;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        transition: all 0.2s ease;
        border: none;
        background: transparent;
    }

    .modern-tabs .nav-link:hover {
        color: #495057;
        background-color: #f8f9fa;
    }

    .modern-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #e7f1ff;
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.2);
    }

    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.2rem;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: #212529;
        margin-bottom: 0.8rem;
    }

    .harga-box {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .harga-box .title {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .harga-box .price {
        font-size: 1.25rem;
        font-weight: 600;
        color: #0d6efd;
    }
</style>
@endpush

@section('content')

<div class="modern-nav-container">
    <ul class="nav nav-pills modern-tabs" id="productDetailTabs" role="tablist">
        <li class="nav-item">
            <a href="{{ route('products.index') }}" class="nav-link back-link">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profil-tab" data-bs-toggle="tab" data-bs-target="#profil" type="button" role="tab" aria-controls="profil" aria-selected="true">
                <i class="bi bi-person-lines-fill me-1"></i> Profil
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="harga-tab" data-bs-toggle="tab" data-bs-target="#harga" type="button" role="tab" aria-controls="harga" aria-selected="false">
                <i class="bi bi-tags-fill me-1"></i> Tingkatan Harga
            </button>
        </li>
    </ul>
</div>

<div class="tab-content" id="productDetailTabsContent">

    <!-- Profil Tab -->
    <div class="tab-pane fade show active" id="profil" role="tabpanel" aria-labelledby="profil-tab">

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                <h5 class="mb-0">Info Umum</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditInfo">
                        <i class="bi bi-pencil"></i> Ubah
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary d-none" id="btnCancelInfo">
                        Batal
                    </button>
                    <button type="submit" form="formUpdateInfo" class="btn btn-sm btn-success d-none" id="btnSaveInfo">
                        Simpan
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('products.update', $product->nid) }}" method="POST" enctype="multipart/form-data" id="formUpdateInfo">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal_id" value="{{ $product->nid }}_info">
                    <input type="hidden" name="nprice" value="{{ $product->nprice }}">
                    <input type="hidden" name="nprice_online" value="{{ $product->nprice_online }}">
                    @if($product->recipes->count() > 0)
                    <input type="hidden" name="has_recipe" value="1">
                    @foreach($product->recipes as $r)
                    <input type="hidden" name="nid_ingredient[]" value="{{ $r->nid_ingredient }}">
                    <input type="hidden" name="nqty[]" value="{{ $r->nqty }}">
                    @endforeach
                    @endif

                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            @if($product->cphotos)
                            <img src="{{ asset($product->cphotos) }}" alt="{{ $product->cname }}" class="img-fluid rounded border p-2 mb-3" style="max-height: 250px; width: 100%; object-fit: contain; background-color: #fff;">
                            @else
                            <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded border mb-3" style="height: 250px; width: 100%;">
                                <i class="bi bi-image" style="font-size: 4rem;"></i>
                            </div>
                            @endif
                            <div class="info-input d-none text-start">
                                <label class="form-label small text-muted">Ganti Foto Produk</label>
                                <input type="file" class="form-control form-control-sm" name="photo" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-12">
                                    <div class="info-label">Nama Produk</div>
                                    <div class="info-value info-display">{{ $product->cname }}</div>
                                    <input type="text" class="form-control d-none info-input mb-3" name="cname" value="{{ $product->cname }}" required>
                                    <hr class="mt-0 mb-3 info-display">
                                </div>
                                <div class="col-12">
                                    <div class="info-label">Kategori</div>
                                    <div class="info-value info-display">{{ $product->category->cname ?? '-' }}</div>
                                    <select class="form-select d-none info-input mb-3" name="nid_category" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->nid }}" {{ (optional($product->category)->cname == $category->cname) ? 'selected' : '' }}>
                                            {{ $category->cname }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <hr class="mt-0 mb-3 info-display">
                                </div>
                                <div class="col-12">
                                    <div class="info-label">Outlet</div>
                                    <div class="info-value info-display">
                                        @php
                                        $productOutlets = \App\Models\MposProduct::where('cname', $product->cname)->with('outlet')->get();
                                        @endphp
                                        @foreach($productOutlets as $po)
                                        @if($po->outlet)
                                        <span class="badge bg-light text-dark border">{{ $po->outlet->cname }}</span>
                                        @endif
                                        @endforeach
                                    </div>
                                    <div class="dropdown d-none info-input mb-3">
                                        <button class="btn border dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center bg-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                            <span class="selected-text-info">Pilih Outlet...</span>
                                        </button>
                                        <ul class="dropdown-menu w-100 p-2 shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                            @php
                                            $selectedOutletIds = $productOutlets->pluck('nid_outlet')->toArray();
                                            @endphp
                                            @foreach($outlets as $outlet)
                                            <li>
                                                <div class="form-check dropdown-item rounded py-1 px-3 mb-1 d-flex align-items-center" style="cursor: pointer;">
                                                    <input class="form-check-input outlet-checkbox-info" style="margin-left: 0; margin-top: 0;" type="checkbox" name="outlet_ids[]" value="{{ $outlet->nid }}" id="info_outlet_{{ $outlet->nid }}" data-name="{{ $outlet->cname }}" {{ in_array($outlet->nid, $selectedOutletIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100 ms-2" for="info_outlet_{{ $outlet->nid }}" style="cursor: pointer;">
                                                        {{ $outlet->cname }}
                                                        @if($product->nid_outlet == $outlet->nid)
                                                            <span class="badge bg-light text-primary border border-primary ms-1" style="font-size: 0.65rem;">Asal</span>
                                                        @endif
                                                    </label>
                                                </div>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <hr class="mt-0 mb-3 info-display">
                                </div>
                                <div class="col-12">
                                    <div class="info-label">Status</div>
                                    <div class="info-value info-display">
                                        @if(strtolower($product->cstatus) == 'active' || strtolower($product->cstatus) == 'aktif' || $product->cstatus == '1')
                                        <span class="badge bg-success">{{ $product->cstatus }}</span>
                                        @else
                                        <span class="badge bg-secondary">{{ $product->cstatus }}</span>
                                        @endif
                                    </div>
                                    <select class="form-select d-none info-input mb-3" name="cstatus" required>
                                        <option value="ACTIVE" {{ strtoupper($product->cstatus) == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                        <option value="INACTIVE" {{ strtoupper($product->cstatus) == 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                                    </select>
                                    <hr class="mt-0 mb-3 info-display">
                                </div>
                                <div class="col-12">
                                    <div class="info-label">Kombo</div>
                                    <div class="info-value info-display">
                                        @if($product->fcombo)
                                        <span class="badge bg-info text-white">Ya</span>
                                        @else
                                        <span class="badge bg-light text-dark border">Tidak</span>
                                        @endif
                                    </div>
                                    <div class="form-check form-switch mt-2 d-none info-input mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch" id="info_fcombo" name="fcombo" value="1" {{ $product->fcombo ? 'checked' : '' }}>
                                        <label class="form-check-label" for="info_fcombo">Jadikan Produk Kombo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                <h5 class="mb-0">Harga</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditHarga">
                        <i class="bi bi-pencil"></i> Ubah
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary d-none" id="btnCancelHarga">
                        Batal
                    </button>
                    <button type="submit" form="formUpdateHarga" class="btn btn-sm btn-success d-none" id="btnSaveHarga">
                        Simpan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('products.update', $product->nid) }}" method="POST" id="formUpdateHarga">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal_id" value="{{ $product->nid }}">
                    <input type="hidden" name="cname" value="{{ $product->cname }}">
                    <input type="hidden" name="nid_category" value="{{ $product->nid_category }}">
                    <input type="hidden" name="cstatus" value="{{ $product->cstatus }}">

                    @php
                    $productOutlets = \App\Models\MposProduct::where('cname', $product->cname)->pluck('nid_outlet');
                    @endphp
                    @foreach($productOutlets as $oid)
                    <input type="hidden" name="outlet_ids[]" value="{{ $oid }}">
                    @endforeach

                    @if($product->fcombo)
                    <input type="hidden" name="fcombo" value="1">
                    @endif
                    @if($product->recipes->count() > 0)
                    <input type="hidden" name="has_recipe" value="1">
                    @foreach($product->recipes as $r)
                    <input type="hidden" name="nid_ingredient[]" value="{{ $r->nid_ingredient }}">
                    <input type="hidden" name="nqty[]" value="{{ $r->nqty }}">
                    @endforeach
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="harga-box mb-3">
                                <div class="title mb-1">Harga POS</div>
                                <div class="price price-display">Rp {{ number_format($product->nprice, 0, ',', '.') }}</div>
                                <input type="number" step="any" min="0" class="form-control d-none price-input" name="nprice" value="{{ $product->nprice }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="harga-box mb-3">
                                <div class="title mb-1">Harga Online</div>
                                <div class="price price-display">Rp {{ number_format($product->nprice_online, 0, ',', '.') }}</div>
                                <input type="number" step="any" min="0" class="form-control d-none price-input" name="nprice_online" value="{{ $product->nprice_online }}">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="stock_qty" value="{{ $product->nqty }}">
                </form>
            </div>
        </div>

        <!-- Stok Card -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2 border-bottom">
                <h5 class="mb-0">Stok</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditStok">
                        <i class="bi bi-pencil"></i> Ubah
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary d-none" id="btnCancelStok">
                        Batal
                    </button>
                    <button type="submit" form="formUpdateStok" class="btn btn-sm btn-success d-none" id="btnSaveStok">
                        Simpan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('products.update', $product->nid) }}" method="POST" id="formUpdateStok">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal_id" value="{{ $product->nid }}_stok">
                    <input type="hidden" name="cname" value="{{ $product->cname }}">
                    <input type="hidden" name="nid_category" value="{{ $product->nid_category }}">
                    <input type="hidden" name="cstatus" value="{{ $product->cstatus }}">
                    <input type="hidden" name="nprice" value="{{ $product->nprice }}">
                    <input type="hidden" name="nprice_online" value="{{ $product->nprice_online }}">
                    
                    @foreach($productOutlets as $oid)
                    <input type="hidden" name="outlet_ids[]" value="{{ $oid }}">
                    @endforeach

                    @if($product->fcombo)
                    <input type="hidden" name="fcombo" value="1">
                    @endif
                    @if($product->recipes->count() > 0)
                    <input type="hidden" name="has_recipe" value="1">
                    @foreach($product->recipes as $r)
                    <input type="hidden" name="nid_ingredient[]" value="{{ $r->nid_ingredient }}">
                    <input type="hidden" name="nqty[]" value="{{ $r->nqty }}">
                    @endforeach
                    @endif

                    <div class="row">
                        <div class="col-md-12">
                            <div class="harga-box mb-0">
                                <div class="title mb-1">Stok / Qty</div>
                                <div class="price stok-display">{{ $product->nqty }}</div>
                                <input type="number" min="0" class="form-control d-none stok-input" name="stock_qty" value="{{ $product->nqty }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Tingkatan Harga Tab -->
    <div class="tab-pane fade" id="harga" role="tabpanel" aria-labelledby="harga-tab">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Daftar Tingkat Harga</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPriceModal">
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
                                <td class="fw-bold text-primary">IDR {{ number_format($price->nprice, 0, ',', '.') }}</td>
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
                // Ignore if the error is from the inline Harga form (which has the product nid as modal_id)
                // because it doesn't use a Bootstrap Modal
                var editPriceModal = document.getElementById('editPriceModal' + modalId);
                if (editPriceModal) {
                    var myModal = new bootstrap.Modal(editPriceModal);
                    myModal.show();
                }
            }
        }

        // Inline Harga Edit Logic
        const btnEditHarga = document.getElementById('btnEditHarga');
        const btnCancelHarga = document.getElementById('btnCancelHarga');
        const btnSaveHarga = document.getElementById('btnSaveHarga');
        const priceDisplays = document.querySelectorAll('.price-display');
        const priceInputs = document.querySelectorAll('.price-input');

        if (btnEditHarga) {
            btnEditHarga.addEventListener('click', function() {
                priceDisplays.forEach(d => d.classList.add('d-none'));
                priceInputs.forEach(i => i.classList.remove('d-none'));
                btnEditHarga.classList.add('d-none');
                btnCancelHarga.classList.remove('d-none');
                btnSaveHarga.classList.remove('d-none');
            });

            btnCancelHarga.addEventListener('click', function() {
                priceDisplays.forEach(d => d.classList.remove('d-none'));
                priceInputs.forEach(i => {
                    i.classList.add('d-none');
                    if (i.tagName !== 'SELECT' && i.type !== 'checkbox' && i.type !== 'radio' && i.type !== 'file') {
                        i.value = i.defaultValue;
                    }
                });
                btnEditHarga.classList.remove('d-none');
                btnCancelHarga.classList.add('d-none');
                btnSaveHarga.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}') {
                btnEditHarga.click();
            }
        }

        // Inline Stok Edit Logic
        const btnEditStok = document.getElementById('btnEditStok');
        const btnCancelStok = document.getElementById('btnCancelStok');
        const btnSaveStok = document.getElementById('btnSaveStok');
        const stokDisplays = document.querySelectorAll('.stok-display');
        const stokInputs = document.querySelectorAll('.stok-input');

        if (btnEditStok) {
            btnEditStok.addEventListener('click', function() {
                stokDisplays.forEach(d => d.classList.add('d-none'));
                stokInputs.forEach(i => i.classList.remove('d-none'));
                btnEditStok.classList.add('d-none');
                btnCancelStok.classList.remove('d-none');
                btnSaveStok.classList.remove('d-none');
            });

            btnCancelStok.addEventListener('click', function() {
                stokDisplays.forEach(d => d.classList.remove('d-none'));
                stokInputs.forEach(i => {
                    i.classList.add('d-none');
                    if (i.tagName !== 'SELECT' && i.type !== 'checkbox' && i.type !== 'radio' && i.type !== 'file') {
                        i.value = i.defaultValue;
                    }
                });
                btnEditStok.classList.remove('d-none');
                btnCancelStok.classList.add('d-none');
                btnSaveStok.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}_stok') {
                btnEditStok.click();
            }
        }

        // Inline Info Umum Edit Logic
        const btnEditInfo = document.getElementById('btnEditInfo');
        const btnCancelInfo = document.getElementById('btnCancelInfo');
        const btnSaveInfo = document.getElementById('btnSaveInfo');
        const infoDisplays = document.querySelectorAll('.info-display');
        const infoInputs = document.querySelectorAll('.info-input');

        if (btnEditInfo) {
            btnEditInfo.addEventListener('click', function() {
                infoDisplays.forEach(d => d.classList.add('d-none'));
                infoInputs.forEach(i => i.classList.remove('d-none'));
                btnEditInfo.classList.add('d-none');
                btnCancelInfo.classList.remove('d-none');
                btnSaveInfo.classList.remove('d-none');
            });

            btnCancelInfo.addEventListener('click', function() {
                infoDisplays.forEach(d => d.classList.remove('d-none'));
                infoInputs.forEach(i => {
                    i.classList.add('d-none');
                });
                btnEditInfo.classList.remove('d-none');
                btnCancelInfo.classList.add('d-none');
                btnSaveInfo.classList.add('d-none');
            });

            if (hasErrors && modalId === '{{ $product->nid }}_info') {
                btnEditInfo.click();
            }
        }

        // Update Dropdown Outlet Text for Info Umum
        function updateInfoDropdownText() {
            const dropdown = document.querySelector('.info-input.dropdown');
            if (!dropdown) return;
            const button = dropdown.querySelector('.dropdown-toggle');
            if (!button) return;
            const checkboxes = dropdown.querySelectorAll('.outlet-checkbox-info:checked');
            const selectedText = button.querySelector('.selected-text-info');
            if (!selectedText) return;

            if (checkboxes.length === 0) {
                selectedText.textContent = 'Pilih Outlet...';
            } else if (checkboxes.length === 1) {
                selectedText.textContent = checkboxes[0].getAttribute('data-name');
            } else {
                selectedText.textContent = checkboxes.length + ' Outlet Terpilih';
            }
        }

        document.querySelectorAll('.outlet-checkbox-info').forEach(checkbox => {
            checkbox.addEventListener('change', updateInfoDropdownText);
        });

        // Initial update
        updateInfoDropdownText();
    });
</script>
@endpush