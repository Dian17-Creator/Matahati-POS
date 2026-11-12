@extends('layouts.app')

@section('title', 'Rincian Retur Pembelian - ' . $return->cno_purchase)
@section('custom_alerts', '1')

@section('sub_header')
@include('purchases.partials.purchase_tabs', ['activeTab' => 'return'])
@endsection

@push('styles')
<style>
    .purchase-status-bar {
        border-radius: 8px 8px 0 0;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .purchase-status-bar.unpaid {
        background-color: #ef4444;
        color: white;
    }

    .purchase-status-bar.paid {
        background-color: #10b981;
        color: white;
    }

    .detail-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .detail-card-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .field-label {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 2px;
    }

    .field-value {
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')
@php
    $isPending = $return->cstatus === 'PENDING';
    $isPosted = $return->cstatus === 'POSTED';
    $isCancelled = $return->cstatus === 'CANCELLED';
    $sourcePo = $return->purchaseRef;
    $poTotalQty = $sourcePo ? (int) $sourcePo->details->sum('nqty') : 0;
    $hasReturnableLeft = collect($availablePoItems)->contains(fn ($it) => (int) ($it['qty_remaining'] ?? 0) > 0);
@endphp

<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('purchases.index', ['tab' => 'return']) }}" class="text-decoration-none">Daftar Pembelian / Retur</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $return->cno_purchase }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $return->cno_purchase }}</h4>
            @if($isPosted)
            <span class="badge bg-success px-2 py-1"><i class="bi bi-check2-circle me-1"></i> Diposting (Stok Keluar: {{ $return->cno_reference ?: '-' }})</span>
            @elseif($isCancelled)
            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i> Batal</span>
            @else
            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock me-1"></i> Pending (Draft)</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('purchases.index', ['tab' => 'return']) }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('purchases.returns.print', $return->nid) }}" target="_blank" class="btn btn-outline-dark btn-sm px-3">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        @if($isPending)
        <form action="{{ route('purchases.returns.destroy', $return->nid) }}" method="POST" onsubmit="return confirm('Hapus permanen draft retur {{ $return->cno_purchase }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm px-3" title="Hapus Draft">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </form>
        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#cancelReturnModal">
            <i class="bi bi-x-lg me-1"></i> Batalkan
        </button>
        <form action="{{ route('purchases.returns.post', $return->nid) }}" method="POST" onsubmit="return confirm('Post retur sekarang? Stok di outlet {{ $return->outlet?->cname }} akan otomatis berkurang dan tercatat sebagai Stok Keluar.')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold" {{ $return->details->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle me-1"></i> Post Sekarang
            </button>
        </form>
        @endif
    </div>
</div>

@if($isPending)
<div class="alert alert-light border d-flex align-items-center gap-2 text-secondary small mb-3">
    <i class="bi bi-info-circle text-primary fs-6"></i>
    <span>Pastikan data sudah benar sebelum diposting. Setelah terposting, stok otomatis berkurang sebagai <strong>Stok Keluar</strong> dan data tidak diperbolehkan diubah.</span>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Top Status Bar Pembayaran/Pengembalian -->
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="purchase-status-bar {{ $return->cpayment_status === 'PAID' ? 'paid' : 'unpaid' }} d-flex justify-content-between align-items-center">
        <div>
            @if($return->cpayment_status === 'PAID')
            <i class="bi bi-check-circle-fill me-1"></i> Pengembalian / Refund Selesai
            @if($return->dpayment)
            <span class="fw-normal ms-1">({{ \Carbon\Carbon::parse($return->dpayment)->format('d M Y') }})</span>
            @endif
            @else
            <i class="bi bi-exclamation-circle-fill me-1"></i> Pengembalian / Refund Belum Selesai
            @endif
        </div>
        @if(!$isCancelled)
        <div>
            <button type="button" class="btn btn-sm {{ $return->cpayment_status === 'PAID' ? 'btn-light text-success' : 'btn-light text-danger' }} py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#returnPaymentModal" style="font-size: 0.78rem;">
                <i class="bi bi-cash-stack me-1"></i> Ubah Status Pembayaran
            </button>
        </div>
        @endif
    </div>
    <div class="card-body bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-arrow-return-left fs-4 text-warning-emphasis"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark">{{ $return->cno_purchase }}</div>
                <div class="text-muted small">Tanggal Retur: {{ \Carbon\Carbon::parse($return->dpurchase)->format('d M Y') }} • Oleh: {{ $return->user?->cname ?? 'Admin' }}</div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small">Outlet Asal Stok</div>
            <div class="fw-bold text-dark fs-6">{{ $return->outlet?->cname ?? '-' }}</div>
        </div>
    </div>
</div>

<!-- 3 Cards Grid (Supplier, Pembelian Asal, Catatan) -->
<div class="row g-3 mb-4">
    <!-- Card Supplier -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-truck me-1 text-primary"></i> Supplier</span>
                @if($return->supplier)
                <a href="{{ route('suppliers.show', $return->supplier->nid) }}" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.75rem;">
                    Rincian <i class="bi bi-arrow-right"></i>
                </a>
                @endif
            </div>
            @if($return->supplier)
            <div class="field-label">Nama Supplier</div>
            <div class="field-value">{{ $return->supplier->cname }}</div>

            <div class="field-label">Personal Yg Dihubungi</div>
            <div class="field-value">{{ $return->supplier->ccontact_person ?: '-' }}</div>

            <div class="field-label">Telepon</div>
            <div class="field-value">{{ $return->supplier->cphone ?: '-' }}</div>

            <div class="field-label">Alamat</div>
            <div class="field-value text-muted small mb-0">{{ $return->supplier->caddress ?: '-' }}</div>
            @else
            <div class="text-muted small">Supplier tidak ditemukan.</div>
            @endif
        </div>
    </div>

    <!-- Card Pembelian -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-box-seam me-1 text-info"></i> Pembelian</span>
                @if($sourcePo)
                <a href="{{ route('purchases.show', $sourcePo->nid) }}" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.75rem;">
                    Lihat PO <i class="bi bi-arrow-right"></i>
                </a>
                @endif
            </div>

            <div class="field-label">No. Pembelian</div>
            <div class="field-value fw-bold">{{ $return->cno_purchase_ref ?: ($sourcePo?->cno_purchase ?? '-') }}</div>

            <div class="field-label">Tanggal Beli</div>
            <div class="field-value">{{ $sourcePo?->dpurchase ? \Carbon\Carbon::parse($sourcePo->dpurchase)->format('d M Y') : '-' }}</div>

            <div class="field-label">Status Bayar PO</div>
            <div class="field-value">
                @if($sourcePo?->cpayment_status === 'PAID')
                <span class="badge bg-success-subtle text-success border border-success-subtle">Lunas</span>
                @elseif($sourcePo)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Belum</span>
                @else
                -
                @endif
            </div>

            <div class="row">
                <div class="col-5">
                    <div class="field-label">QTY Produk PO</div>
                    <div class="field-value mb-0">{{ $poTotalQty }}</div>
                </div>
                <div class="col-7">
                    <div class="field-label">Jumlah PO</div>
                    <div class="field-value mb-0">Rp {{ rtrim(rtrim(number_format((float) ($sourcePo?->ntotal_amount ?? 0), 2, ',', '.'), '0'), ',') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Catatan -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-chat-left-text me-1 text-secondary"></i> Catatan</span>
                @if($isPending)
                <button type="button" class="btn btn-link btn-sm p-0 text-primary" data-bs-toggle="modal" data-bs-target="#editNoteModal" title="Edit Catatan">
                    <i class="bi bi-pencil"></i>
                </button>
                @endif
            </div>
            <div class="field-value text-muted small" style="white-space: pre-line;">{{ $return->cnote ?: 'Tidak ada catatan khusus.' }}</div>
            @if($isPosted && $return->cno_reference)
            <div class="alert alert-success p-2 small mb-0 mt-3">
                <i class="bi bi-check-circle me-1"></i> Mutasi Stok Keluar: <strong>{{ $return->cno_reference }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Section Produk yang Dikembalikan -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold">Produk yang dikembalikan</h6>
            <span class="badge bg-white text-primary">{{ $return->details->count() }} Item</span>
        </div>
        @if($isPending && $hasReturnableLeft)
        <button type="button" class="btn btn-light btn-sm text-primary fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#addReturnItemModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
        </button>
        @endif
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <th class="py-3 ps-3" style="width: 50px;">NO.</th>
                        <th class="py-3">BARANG (PRODUK / BAHAN BAKU)</th>
                        <th class="py-3 text-center">STOK SAAT INI</th>
                        <th class="py-3 text-center">QTY RETUR</th>
                        <th class="py-3 text-center">SATUAN</th>
                        <th class="py-3 text-end">HARGA BELI</th>
                        <th class="py-3">ALASAN & CATATAN</th>
                        <th class="py-3 text-end">SUBTOTAL</th>
                        @if($isPending)
                        <th class="py-3 text-center" style="width: 110px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($return->details as $index => $item)
                    @php
                        $prodModel = $item->product;
                        $ingModel = $item->ingredient;
                        $itemUnit = $item->getItemUnit();
                        $normType = $item->getItemTypeNormalized();
                        $currentStock = $prodModel ? (float) ($prodModel->nqty ?? 0) : ($ingModel ? (float) ($ingModel->nstock ?? 0) : 0);
                        $currentStockFormatted = rtrim(rtrim(number_format($currentStock, 2, ',', '.'), '0'), ',');
                        $poItemMeta = collect($availablePoItems)->firstWhere('nid_purchase_detail_ref', (int) $item->nid_purchase_detail_ref);
                        $maxEditableQty = $poItemMeta ? (int) $poItemMeta['qty_remaining'] : (int) $item->nqty;
                    @endphp
                    <tr>
                        <td class="ps-3 text-center text-muted">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $prodModel?->cname ?? ($ingModel?->cname ?? 'Item dihapus') }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                @if($normType === 'BAHAN_BAKU')
                                <span class="badge bg-warning-subtle text-warning-emphasis">Bahan Baku</span>
                                @elseif($normType === 'SETENGAH_JADI')
                                <span class="badge bg-info-subtle text-info-emphasis">Setengah Jadi</span>
                                @else
                                <span class="badge bg-primary-subtle text-primary">Produk</span>
                                @endif
                                @if($item->purchaseDetailRef)
                                <span class="ms-1">Beli di PO: {{ $item->purchaseDetailRef->nqty }} {{ $itemUnit }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $currentStockFormatted }} {{ $itemUnit }}</span>
                        </td>
                        <td class="text-center fw-bold fs-6 text-danger">
                            -{{ $item->nqty }}
                        </td>
                        <td class="text-center">{{ $itemUnit }}</td>
                        <td class="text-end">Rp {{ rtrim(rtrim(number_format((float) $item->ncost, 2, ',', '.'), '0'), ',') }}</td>
                        <td>
                            @if($item->creason)
                            <div class="fw-semibold text-dark">{{ $item->creason }}</div>
                            @endif
                            @if($item->cnote)
                            <div class="text-muted" style="font-size: 0.78rem;">{{ $item->cnote }}</div>
                            @endif
                            @if(!$item->creason && !$item->cnote)
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark">Rp {{ rtrim(rtrim(number_format((float) $item->nsubtotal, 2, ',', '.'), '0'), ',') }}</td>
                        @if($isPending)
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm p-1 btn-edit-return-item"
                                    title="Edit Item"
                                    data-action="{{ route('purchases.returns.items.update', [$return->nid, $item->nid]) }}"
                                    data-name="{{ $prodModel?->cname ?? ($ingModel?->cname ?? 'Item') }}"
                                    data-qty="{{ $item->nqty }}"
                                    data-max="{{ $maxEditableQty }}"
                                    data-unit="{{ $itemUnit }}"
                                    data-cost="{{ (float) $item->ncost }}"
                                    data-reason="{{ $item->creason }}"
                                    data-note="{{ $item->cnote }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('purchases.returns.items.destroy', [$return->nid, $item->nid]) }}" method="POST" onsubmit="return confirm('Hapus item ini dari retur?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Hapus Item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isPending ? 9 : 8 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block text-secondary opacity-50 mb-2"></i>
                            Tidak ada produk yang dikembalikan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold py-3">Total Retur:</td>
                        <td class="text-center fw-bold text-danger py-3">{{ $return->details->sum('nqty') }}</td>
                        <td colspan="3"></td>
                        <td class="text-end fw-bold fs-6 text-primary py-3">
                            Rp {{ rtrim(rtrim(number_format((float) $return->ntotal_amount, 2, ',', '.'), '0'), ',') }}
                        </td>
                        @if($isPending)
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Catatan -->
@if($isPending)
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.returns.update-note', $return->nid) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Catatan Retur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea name="cnote" class="form-control" rows="4" placeholder="Tulis catatan retur...">{{ $return->cnote }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk dari PO -->
<div class="modal fade" id="addReturnItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.returns.items.store', $return->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Produk Retur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Barang dari PO {{ $return->cno_purchase_ref }}</label>
                        <select name="nid_purchase_detail_ref" id="addReturnPoItemSelect" class="form-select" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach($availablePoItems as $poIt)
                                @if((int) $poIt['qty_remaining'] > 0)
                                <option value="{{ $poIt['nid_purchase_detail_ref'] }}"
                                    data-unit="{{ $poIt['unit'] }}"
                                    data-cost="{{ $poIt['ncost'] }}"
                                    data-max="{{ $poIt['qty_remaining'] }}"
                                    data-stock="{{ $poIt['current_stock'] }}">
                                    {{ $poIt['item_name'] }} (Stok: {{ $poIt['current_stock'] }} {{ $poIt['unit'] }} | Sisa retur: {{ $poIt['qty_remaining'] }})
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">QTY Retur</label>
                            <input type="number" name="nqty" id="addReturnItemQty" class="form-control" min="1" value="1" required>
                            <div class="form-text" id="addReturnItemLimitHint"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Satuan</label>
                            <input type="text" name="csatuan" id="addReturnItemUnit" class="form-control" maxlength="50" value="PCS">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Pengembalian</label>
                        <select name="creason" class="form-select">
                            <option value="">-- Pilih Alasan (Opsional) --</option>
                            <option value="Barang Rusak / Cacat">Barang Rusak / Cacat</option>
                            <option value="Salah Kirim Barang">Salah Kirim Barang</option>
                            <option value="Kadaluarsa">Kadaluarsa</option>
                            <option value="Kelebihan Kirim">Kelebihan Kirim</option>
                            <option value="Tidak Sesuai Pesanan">Tidak Sesuai Pesanan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Catatan Item</label>
                        <textarea name="cnote" class="form-control" rows="2" placeholder="Catatan tambahan item..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Produk Retur -->
<div class="modal fade" id="editReturnItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editReturnItemForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Item Retur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-2 bg-light rounded border">
                        <div class="fw-bold text-dark" id="editReturnItemName"></div>
                        <div class="small text-muted" id="editReturnItemMaxInfo"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">QTY Retur</label>
                            <input type="number" name="nqty" id="editReturnItemQty" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Satuan</label>
                            <input type="text" name="csatuan" id="editReturnItemUnit" class="form-control" maxlength="50">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Pengembalian</label>
                        <select name="creason" id="editReturnItemReason" class="form-select">
                            <option value="">-- Pilih Alasan (Opsional) --</option>
                            <option value="Barang Rusak / Cacat">Barang Rusak / Cacat</option>
                            <option value="Salah Kirim Barang">Salah Kirim Barang</option>
                            <option value="Kadaluarsa">Kadaluarsa</option>
                            <option value="Kelebihan Kirim">Kelebihan Kirim</option>
                            <option value="Tidak Sesuai Pesanan">Tidak Sesuai Pesanan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Catatan Item</label>
                        <textarea name="cnote" id="editReturnItemNote" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Batalkan Retur -->
<div class="modal fade" id="cancelReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.returns.cancel', $return->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Batalkan Retur?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small text-muted">
                    Dokumen retur <strong>{{ $return->cno_purchase }}</strong> akan berstatus <strong>Batal</strong> dan tidak memotong stok.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Update Status Pembayaran / Pengembalian -->
@if(!$isCancelled)
<div class="modal fade" id="returnPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.returns.update-payment', $return->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Status Pembayaran / Pengembalian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Pengembalian Dana Supplier</label>
                        <select name="cpayment_status" id="returnPaymentStatusSelect" class="form-select" required>
                            <option value="UNPAID" {{ $return->cpayment_status === 'UNPAID' ? 'selected' : '' }}>Belum Selesai</option>
                            <option value="PAID" {{ $return->cpayment_status === 'PAID' ? 'selected' : '' }}>Selesai (Refund Diterima / Potong Nota)</option>
                        </select>
                    </div>
                    <div class="mb-0" id="returnPaymentDateGroup" @style(['display: none' => $return->cpayment_status !== 'PAID'])>
                        <label class="form-label small fw-semibold">Tanggal Pembayaran</label>
                        <input type="date" name="dpayment" class="form-control" value="{{ $return->dpayment ? \Carbon\Carbon::parse($return->dpayment)->format('Y-m-d') : date('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const payStatusSelect = document.getElementById('returnPaymentStatusSelect');
        const payDateGroup = document.getElementById('returnPaymentDateGroup');
        if (payStatusSelect && payDateGroup) {
            payStatusSelect.addEventListener('change', function () {
                payDateGroup.style.display = this.value === 'PAID' ? 'block' : 'none';
            });
        }

        const addPoItemSelect = document.getElementById('addReturnPoItemSelect');
        const addQtyInput = document.getElementById('addReturnItemQty');
        const addUnitInput = document.getElementById('addReturnItemUnit');
        const addLimitHint = document.getElementById('addReturnItemLimitHint');
        if (addPoItemSelect) {
            addPoItemSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) {
                    if (addLimitHint) addLimitHint.textContent = '';
                    return;
                }
                const unit = opt.getAttribute('data-unit') || 'PCS';
                const maxQty = parseInt(opt.getAttribute('data-max') || '1', 10);
                const stock = opt.getAttribute('data-stock') || '0';
                if (addUnitInput) addUnitInput.value = unit;
                if (addQtyInput) {
                    addQtyInput.max = maxQty;
                    if (parseInt(addQtyInput.value || '1', 10) > maxQty) {
                        addQtyInput.value = maxQty;
                    }
                }
                if (addLimitHint) {
                    addLimitHint.textContent = 'Maks. retur: ' + maxQty + ' ' + unit + ' (Stok outlet: ' + stock + ' ' + unit + ')';
                }
            });
        }

        const editModalEl = document.getElementById('editReturnItemModal');
        const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
        document.querySelectorAll('.btn-edit-return-item').forEach(btn => {
            btn.addEventListener('click', function () {
                const form = document.getElementById('editReturnItemForm');
                if (form) form.action = this.getAttribute('data-action');

                const nameEl = document.getElementById('editReturnItemName');
                const maxInfoEl = document.getElementById('editReturnItemMaxInfo');
                const qtyEl = document.getElementById('editReturnItemQty');
                const unitEl = document.getElementById('editReturnItemUnit');
                const reasonEl = document.getElementById('editReturnItemReason');
                const noteEl = document.getElementById('editReturnItemNote');

                const maxQty = parseInt(this.getAttribute('data-max') || '1', 10);
                const unit = this.getAttribute('data-unit') || 'PCS';

                if (nameEl) nameEl.textContent = this.getAttribute('data-name') || '';
                if (maxInfoEl) maxInfoEl.textContent = 'Maksimal dapat diretur: ' + maxQty + ' ' + unit;
                if (qtyEl) {
                    qtyEl.value = this.getAttribute('data-qty') || '1';
                    qtyEl.max = maxQty;
                }
                if (unitEl) unitEl.value = unit;
                if (reasonEl) reasonEl.value = this.getAttribute('data-reason') || '';
                if (noteEl) noteEl.value = this.getAttribute('data-note') || '';

                if (editModal) editModal.show();
            });
        });
    });
</script>
@endpush

