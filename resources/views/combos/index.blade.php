@extends('layouts.app')

@section('title', 'Katalog Produk / Paket Produk')

@section('sub_header')
@include('products.partials.catalog_tabs', ['activeTab' => 'combos'])
@endsection

@section('content')
<div class="card olsera-panel-card">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <h5 class="mb-0 fw-bold text-nowrap">Paket Produk</h5>
            <form action="{{ route('combos.index') }}" method="GET" class="d-flex flex-grow-1" style="max-width: 340px;">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari paket kombo..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <a href="{{ route('combos.create') }}" class="btn btn-success btn-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </a>
    </div>

    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="py-3 ps-3">Nama Paket</th>
                        <th class="py-3 text-center">Harga Jual Online</th>
                        <th class="py-3 text-center">Harga Jual Offline</th>
                        <th class="py-3 text-center">Publikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comboProducts as $combo)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('combos.show', $combo->nid) }}" class="d-flex align-items-center gap-3 text-decoration-none text-dark">
                                @if(!empty($combo->cphotos))
                                <img src="{{ asset($combo->cphotos) }}" alt="{{ $combo->cname }}" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                @else
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $combo->cname }}</div>
                                    @if(empty($selectedOutletId) && $combo->outlet)
                                    <small class="text-muted"><i class="bi bi-shop me-1"></i>{{ $combo->outlet->cname }}</small>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="text-center fw-medium">IDR {{ number_format((float) $combo->nprice_online, 0, ',', '.') }}</td>
                        <td class="text-center fw-medium">IDR {{ number_format((float) $combo->nprice, 0, ',', '.') }}</td>
                        <td class="text-center text-muted small">
                            {{ $combo->dpublish_date ? $combo->dpublish_date->format('Y-m-d') : ($combo->dcreated ? $combo->dcreated->format('Y-m-d') : '-') }}
                        </td>

                    </tr>

                    <!-- Modal Hapus -->
                    <div class="modal fade" id="deleteModal{{ $combo->nid }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-2">Hapus paket kombo <strong>{{ $combo->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Data paket kombo dan daftar item di dalamnya akan dihapus.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('combos.destroy', $combo->nid) }}" method="POST">
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
                        <td colspan="7" class="text-center text-muted py-5">
                            Belum ada paket kombo. Klik <strong>+ Tambah</strong> untuk membuat paket produk baru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($comboProducts->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted">Total {{ $comboProducts->total() }} Paket</div>
            {{ $comboProducts->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection