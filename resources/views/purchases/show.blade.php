@extends('layouts.app')

@section('title', 'Detail Pembelian - ' . $purchase->cno_purchase)

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
<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}" class="text-decoration-none">Daftar Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $purchase->cno_purchase }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $purchase->cno_purchase }}</h4>
            @if($purchase->cstatus === 'RECEIVED')
            <span class="badge bg-success px-2 py-1"><i class="bi bi-check2-circle me-1"></i> Telah Diterima</span>
            @elseif($purchase->cstatus === 'CANCELLED')
            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i> Dibatalkan</span>
            @else
            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock me-1"></i> Butuh Diproses (Pending)</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('purchases.print', $purchase->nid) }}" target="_blank" class="btn btn-outline-dark btn-sm px-3">
            <i class="bi bi-printer me-1"></i> Cetak PO
        </a>
        @if($purchase->cstatus === 'PENDING')
        <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#receiveModal">
            <i class="bi bi-box-seam me-1"></i> Konfirmasi Barang Diterima
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-lg me-1"></i> Batalkan PO
        </button>
        @endif
    </div>
</div>

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

<!-- Top Status Header (Mirip Olsera) -->
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="purchase-status-bar {{ $purchase->cpayment_status === 'PAID' ? 'paid' : 'unpaid' }} d-flex justify-content-between align-items-center">
        <div>
            @if($purchase->cpayment_status === 'PAID')
            <i class="bi bi-check-circle-fill me-1"></i> Telah Dibayar (Lunas)
            @else
            <i class="bi bi-exclamation-circle-fill me-1"></i> Belum Bayar
            @endif
        </div>
        <div>
            <button type="button" class="btn btn-sm {{ $purchase->cpayment_status === 'PAID' ? 'btn-light text-success' : 'btn-light text-danger' }} py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#paymentModal" style="font-size: 0.78rem;">
                <i class="bi bi-cash-stack me-1"></i> Ubah Status Bayar
            </button>
        </div>
    </div>
    <div class="card-body bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-receipt-cutoff fs-4 text-primary"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark">{{ $purchase->cno_purchase }}</div>
                <div class="text-muted small">Dibuat oleh: {{ $purchase->user?->cname ?? 'Admin' }} pada {{ \Carbon\Carbon::parse($purchase->dpurchase)->format('d M Y') }}</div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small">Outlet Tujuan</div>
            <div class="fw-bold text-dark fs-6">{{ $purchase->outlet?->cname ?? '-' }}</div>
        </div>
    </div>
</div>

<!-- 4 Cards Grid (Supplier, Info Penerimaan, Catatan, Jatuh Tempo) -->
<div class="row g-3 mb-4">
    <!-- Card Supplier -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-building me-1 text-primary"></i> Supplier</span>
                @if($purchase->supplier)
                <a href="{{ route('suppliers.show', $purchase->supplier->nid) }}" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.75rem;">
                    Rincian <i class="bi bi-arrow-right"></i>
                </a>
                @endif
            </div>
            @if($purchase->supplier)
            <div class="field-label">Nama Supplier</div>
            <div class="field-value">{{ $purchase->supplier->cname }}</div>

            <div class="field-label">PIC / Kontak</div>
            <div class="field-value">{{ $purchase->supplier->ccontact_person ?: '-' }}</div>

            <div class="field-label">Telepon & Email</div>
            <div class="field-value">
                {{ $purchase->supplier->cphone ?: '-' }}
                @if($purchase->supplier->cemail)
                <span class="text-muted">({{ $purchase->supplier->cemail }})</span>
                @endif
            </div>

            <div class="field-label">Alamat</div>
            <div class="field-value text-muted small">{{ $purchase->supplier->caddress ?: '-' }}</div>
            @else
            <div class="text-muted small">Supplier tidak ditemukan.</div>
            @endif
        </div>
    </div>

    <!-- Card Info Penerimaan -->
    <div class="col-md-4">
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="bi bi-box-seam me-1 text-success"></i> Info Penerimaan</span>
            </div>

            <div class="field-label">Tanggal Diterima</div>
            <div class="field-value">
                @if($purchase->dreceived)
                <span class="text-success fw-bold">{{ \Carbon\Carbon::parse($purchase->dreceived)->format('d F Y') }}</span>
                @else
                <span class="text-muted fst-italic">Belum diterima</span>
                @endif
            </div>

            <div class="field-label">No. Terima / Surat Jalan</div>
            <div class="field-value">{{ $purchase->cno_reference ?: '-' }}</div>

            <div class="field-label">Penerima Barang</div>
            <div class="field-value">{{ $purchase->creceived_by ?: '-' }}</div>

            @if($purchase->cstatus === 'RECEIVED')
            <div class="alert alert-success p-2 small mb-0 mt-2">
                <i class="bi bi-check-circle me-1"></i> Stok produk telah ditambahkan ke sistem.
            </div>
            @endif
        </div>
    </div>

    <!-- Card Catatan & Jatuh Tempo -->
    <div class="col-md-4">
        <div class="d-flex flex-column gap-3 h-100">
            <div class="detail-card flex-fill">
                <div class="detail-card-title">
                    <span><i class="bi bi-card-text me-1 text-secondary"></i> Catatan</span>
                </div>
                <div class="field-value text-muted small" style="white-space: pre-line;">
                    {{ $purchase->cnote ?: 'Tidak ada catatan khusus.' }}
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-title">
                    <span><i class="bi bi-calendar-event me-1 text-warning"></i> Jatuh Tempo</span>
                </div>
                <div class="field-value">
                    @if($purchase->ddue_date)
                    {{ \Carbon\Carbon::parse($purchase->ddue_date)->format('d F Y') }}
                    @else
                    <span class="text-muted">Tidak ditentukan</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Produk Pesanan -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0 fw-bold">Produk Pesanan</h6>
            <span class="badge bg-light text-dark border">{{ $purchase->details->count() }} Item</span>
        </div>
        @if($purchase->cstatus === 'PENDING')
        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
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
                        <th class="py-3">PRODUK</th>
                        <th class="py-3 text-center">STOK SAAT INI</th>
                        <th class="py-3 text-center">KUANTITAS BELI</th>
                        <th class="py-3 text-end">HARGA BELI SATUAN</th>
                        <th class="py-3 text-end">TOTAL HARGA</th>
                        @if($purchase->cstatus === 'PENDING')
                        <th class="py-3 text-center" style="width: 80px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($purchase->details as $index => $item)
                    <tr class="align-middle" style="border-bottom: 1px solid #f8f9fa;">
                        <td class="ps-3 text-muted text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if(!empty($item->product?->cphotos))
                                <img src="{{ asset($item->product->cphotos) }}"
                                     alt="{{ $item->product->cname }}"
                                     class="rounded border flex-shrink-0"
                                     style="width: 44px; height: 44px; object-fit: cover; background-color: #f8fafc;"
                                     onerror="this.onerror=null; this.src=''; this.classList.add('d-none'); if(this.nextElementSibling) this.nextElementSibling.classList.remove('d-none');">
                                <div class="rounded border bg-light text-muted d-none align-items-center justify-content-center flex-shrink-0"
                                     style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-image"></i>
                                </div>
                                @else
                                <div class="rounded border bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <i class="bi bi-image"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark">{{ $item->product?->cname ?? 'Produk dihapus' }}</div>
                                    <div class="text-muted small">{{ $item->product?->category?->cname ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                {{ $item->product?->nqty ?? 0 }} unit
                            </span>
                        </td>
                        <td class="text-center fw-bold fs-6 text-primary">
                            +{{ $item->nqty }}
                        </td>
                        <td class="text-end">
                            Rp {{ number_format($item->ncost, 0, ',', '.') }}
                        </td>
                        <td class="text-end fw-bold text-dark">
                            Rp {{ number_format($item->nsubtotal, 0, ',', '.') }}
                        </td>
                        @if($purchase->cstatus === 'PENDING')
                        <td class="text-center">
                            <form action="{{ route('purchases.remove-item', [$purchase->nid, $item->nid]) }}" method="POST" onsubmit="return confirm('Hapus produk ini dari pesanan?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Hapus Item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $purchase->cstatus === 'PENDING' ? 7 : 6 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-box-arrow-in-down fs-2 d-block text-secondary opacity-50 mb-2"></i>
                            Belum ada produk dalam pesanan pembelian ini.
                            @if($purchase->cstatus === 'PENDING')
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk Sekarang
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($purchase->details->isNotEmpty())
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="{{ $purchase->cstatus === 'PENDING' ? 5 : 4 }}" class="text-end fw-bold py-3">
                            GRAND TOTAL PEMBELIAN:
                        </td>
                        <td class="text-end fw-bold fs-5 text-primary py-3">
                            Rp {{ number_format($purchase->ntotal_amount, 0, ',', '.') }}
                        </td>
                        @if($purchase->cstatus === 'PENDING')
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk (Sesuai Screenshot Olsera) -->
@if($purchase->cstatus === 'PENDING')
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.add-item', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addProductModalLabel">Tambah Produk ke Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="nid_product" id="product_select" class="form-select" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $p)
                            <option value="{{ $p->nid }}" data-price="{{ $p->nprice }}">
                                {{ $p->cname }} (Stok saat ini: {{ $p->nqty }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Qty (Kuantitas) <span class="text-danger">*</span></label>
                            <input type="number" name="nqty" id="input_qty" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Harga Beli Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="ncost" id="input_cost" class="form-control" min="0" step="100" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Total Harga Beli</label>
                        <div class="form-control bg-light fw-bold text-primary" id="display_subtotal">
                            Rp 0
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambahkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Konfirmasi Terima Barang -->
@if($purchase->cstatus === 'PENDING')
<div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.receive', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="receiveModalLabel">
                        <i class="bi bi-box-seam me-1"></i> Konfirmasi Barang Diterima
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Perhatian:</strong> Mengonfirmasi penerimaan barang akan mengubah status PO menjadi <strong>Telah Diproses</strong> dan <strong>otomatis menambahkan stok</strong> produk yang dipesan ke outlet <strong>{{ $purchase->outlet?->cname }}</strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tanggal Diterima <span class="text-danger">*</span></label>
                        <input type="date" name="dreceived" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">No. Terima / Surat Jalan</label>
                        <input type="text" name="cno_reference" class="form-control" placeholder="Contoh: SJ-2026/10/001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Penerima</label>
                        <input type="text" name="creceived_by" class="form-control" value="{{ Auth::user()?->cname ?: 'Admin' }}" placeholder="Nama petugas penerima">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-check-lg me-1"></i> Ya, Konfirmasi Terima Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Batalkan PO -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.cancel', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Batalkan Pesanan Pembelian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin membatalkan pesanan pembelian <strong>{{ $purchase->cno_purchase }}</strong>?
                    Tindakan ini akan membatalkan seluruh pesanan ini dan stok tidak akan bertambah.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Batalkan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Update Status Bayar -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('purchases.update-payment', $purchase->nid) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentModalLabel">Status Pembayaran PO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Status Pembayaran</label>
                        <select name="cpayment_status" id="cpayment_status_select" class="form-select" required>
                            <option value="UNPAID" {{ $purchase->cpayment_status === 'UNPAID' ? 'selected' : '' }}>Belum Bayar</option>
                            <option value="PAID" {{ $purchase->cpayment_status === 'PAID' ? 'selected' : '' }}>Lunas (Telah Dibayar)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="payment_date_group">
                        <label class="form-label fw-semibold small">Tanggal Pembayaran</label>
                        <input type="date" name="dpayment" class="form-control" value="{{ $purchase->dpayment ? \Carbon\Carbon::parse($purchase->dpayment)->format('Y-m-d') : date('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Status Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputQty = document.getElementById('input_qty');
        const inputCost = document.getElementById('input_cost');
        const displaySubtotal = document.getElementById('display_subtotal');

        function calculateSubtotal() {
            if (inputQty && inputCost && displaySubtotal) {
                const qty = parseFloat(inputQty.value) || 0;
                const cost = parseFloat(inputCost.value) || 0;
                const subtotal = qty * cost;
                displaySubtotal.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
            }
        }

        if (inputQty && inputCost) {
            inputQty.addEventListener('input', calculateSubtotal);
            inputCost.addEventListener('input', calculateSubtotal);
        }

        const paymentSelect = document.getElementById('cpayment_status_select');
        const paymentDateGroup = document.getElementById('payment_date_group');
        if (paymentSelect && paymentDateGroup) {
            function togglePaymentDate() {
                if (paymentSelect.value === 'PAID') {
                    paymentDateGroup.style.display = 'block';
                } else {
                    paymentDateGroup.style.display = 'none';
                }
            }
            paymentSelect.addEventListener('change', togglePaymentDate);
            togglePaymentDate();
        }
    });
</script>
@endpush

@endsection