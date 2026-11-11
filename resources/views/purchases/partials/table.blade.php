<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light text-secondary small">
            <tr>
                <th class="py-3 text-center">NO. PEMBELIAN</th>
                <th class="py-3 text-center">TANGGAL BELI</th>
                <th class="py-3 text-center">SUPPLIER</th>
                <th class="py-3 text-center">OUTLET</th>
                <th class="py-3 text-center">JUMLAH</th>
                <th class="py-3 text-center">TELAH BAYAR</th>
                <th class="py-3 text-center">TANGGAL PEMBAYARAN</th>
                <th class="py-3 text-center">STATUS</th>
                <th class="py-3 text-center" style="width: 100px;">AKSI</th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($purchases as $item)
            <tr>
                <td class="ps-3">
                    <a href="{{ route('purchases.show', $item->nid) }}" class="fw-bold text-decoration-none text-primary">
                        {{ $item->cno_purchase }}
                    </a>
                    @if($item->cno_reference)
                    <div class="text-muted small">Ref: {{ $item->cno_reference }}</div>
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($item->dpurchase)->format('d M Y') }}</td>
                <td>
                    <span class="fw-semibold text-dark">{{ $item->supplier?->cname ?? '-' }}</span>
                </td>
                <td>{{ $item->outlet?->cname ?? '-' }}</td>
                <td class="text-end fw-bold">
                    Rp {{ rtrim(rtrim(number_format((float) $item->ntotal_amount, 2, ',', '.'), '0'), ',') }}
                    <div class="text-muted fw-normal small" style="font-size: 0.75rem;">{{ $item->details_count }} Item</div>
                </td>
                <td class="text-center">
                    @if($item->cpayment_status === 'PAID')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Lunas</span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Belum</span>
                    @endif
                </td>
                <td class="text-center text-muted">
                    {{ $item->dpayment ? \Carbon\Carbon::parse($item->dpayment)->format('d M Y') : '-' }}
                </td>
                <td class="text-center">
                    @if($item->cstatus === 'RECEIVED')
                    <span class="badge bg-success">Diterima</span>
                    @elseif($item->cstatus === 'CANCELLED')
                    <span class="badge bg-danger">Dibatalkan</span>
                    @else
                    <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('purchases.show', $item->nid) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Buka Detail">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Buka
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block text-secondary opacity-50 mb-2"></i>
                    Tidak ada transaksi pembelian yang ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($purchases->hasPages())
<div class="d-flex justify-content-center mt-3">
    {{ $purchases->links('pagination::bootstrap-5') }}
</div>
@endif