@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('categories.update', $category->nid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="cname" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control @error('cname') is-invalid @enderror" id="cname" name="cname" value="{{ old('cname', $category->cname) }}" required>
                        @error('cname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 p-2 bg-light rounded border">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="ftrack_stock" name="ftrack_stock" value="1" {{ old('ftrack_stock', $category->ftrack_stock) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="ftrack_stock">
                                Kelola Stok Produk
                            </label>
                        </div>
                        <div class="form-text small text-muted" style="font-size: 0.78rem;">
                            Aktifkan untuk barang retail fisik (IQOS/Device/Pod). Matikan untuk Makanan, Minuman, atau Snack agar kasir bisa bertransaksi tanpa batasan stok.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection