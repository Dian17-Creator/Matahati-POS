<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light text-secondary text-nowrap" style="font-size: 0.72rem; letter-spacing: 0.2px;">
            <tr>
                <th class="py-3 px-2 text-center">NO. RETUR</th>
                <th class="py-3 px-2 text-center">NO. PEMBELIAN</th>
                <th class="py-3 px-2 text-center">TANGGAL RETUR</th>
                <th class="py-3 px-2 text-center">SUPPLIER</th>
                <th class="py-3 px-2 text-center">OUTLET</th>
                <th class="py-3 px-2 text-center">TOTAL RETUR</th>
                <th class="py-3 px-2 text-center">TANGGAL PEMBAYARAN</th>
                <th class="py-3 px-2 text-center">STATUS</th>
                <th class="py-3 px-2 text-center">PEMBAYARAN / PENGEMBALIAN</th>
                <th class="py-3 px-2 text-center" style="width: 90px;">AKSI</th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($purchases as $item)
            <tr>
                <td class="ps-3">
                    <a href="{{ route('purchases.returns.show', $item->nid) }}" class="fw-bold text-decoration-none text-primary">
                        {{ $item->cno_purchase }}
                    </a>
                </td>
                <td>
                    @if($item->nid_purchase_ref)
                    <a href="{{ route('purchases.show', $item->nid_purchase_ref) }}" class="fw-semibold text-decoration-none text-dark">
                        {{ $item->cno_purchase_ref ?: ($item->purchaseRef?->cno_purchase ?? '-') }}
                    </a>
                    @else
                    <span class="text-muted">{{ $item->cno_purchase_ref ?: '-' }}</span>
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
                <td class="text-center text-muted">
                    {{ $item->dpayment ? \Carbon\Carbon::parse($item->dpayment)->format('d M Y') : '-' }}
                </td>
                <td class="text-center">
                    @if($item->cstatus === 'POSTED')
                    <span class="badge bg-success">Diposting</span>
                    @elseif($item->cstatus === 'CANCELLED')
                    <span class="badge bg-danger">Batal</span>
                    @else
                    <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($item->cpayment_status === 'PAID')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Selesai</span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Belum</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('purchases.returns.show', $item->nid) }}" class="btn btn-outline-info btn-sm px-2 py-1" title="Buka Rincian Retur">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Buka
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-return-left fs-2 d-block text-secondary opacity-50 mb-2"></i>
                    Belum ada data retur pembelian yang ditemukan.
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