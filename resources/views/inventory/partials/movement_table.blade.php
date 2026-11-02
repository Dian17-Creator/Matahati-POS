<div class="table-responsive">
    <table class="table table-hover align-middle border mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 15%;">Grup / Kategori</th>
                <th style="width: 35%;">Produk</th>
                <th class="text-center" style="width: 8%;">Awal</th>
                <th class="text-center" style="width: 8%;">Masuk</th>
                <th class="text-center" style="width: 9%;">Pengembalian</th>
                <th class="text-center" style="width: 8%;">Penjualan</th>
                <th class="text-center" style="width: 8%;">Keluar</th>
                <th class="text-center" style="width: 9%;">Sisa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
            @php
                $fmtAwal = is_numeric($p->qty_awal) ? (fmod((float)$p->qty_awal, 1) === 0.0 ? number_format($p->qty_awal, 0, ',', '.') : number_format($p->qty_awal, 2, ',', '.')) : $p->qty_awal;
                $fmtMasuk = is_numeric($p->qty_masuk) ? (fmod((float)$p->qty_masuk, 1) === 0.0 ? number_format($p->qty_masuk, 0, ',', '.') : number_format($p->qty_masuk, 2, ',', '.')) : $p->qty_masuk;
                $fmtPengembalian = is_numeric($p->qty_pengembalian) ? (fmod((float)$p->qty_pengembalian, 1) === 0.0 ? number_format($p->qty_pengembalian, 0, ',', '.') : number_format($p->qty_pengembalian, 2, ',', '.')) : $p->qty_pengembalian;
                $fmtPenjualan = is_numeric($p->qty_penjualan) ? (fmod((float)$p->qty_penjualan, 1) === 0.0 ? number_format($p->qty_penjualan, 0, ',', '.') : number_format($p->qty_penjualan, 2, ',', '.')) : $p->qty_penjualan;
                $fmtKeluar = is_numeric($p->qty_keluar) ? (fmod((float)$p->qty_keluar, 1) === 0.0 ? number_format($p->qty_keluar, 0, ',', '.') : number_format($p->qty_keluar, 2, ',', '.')) : $p->qty_keluar;
                $fmtSisa = is_numeric($p->qty_sisa) ? (fmod((float)$p->qty_sisa, 1) === 0.0 ? number_format($p->qty_sisa, 0, ',', '.') : number_format($p->qty_sisa, 2, ',', '.')) : $p->qty_sisa;
            @endphp
            <tr>
                <td class="text-muted fw-semibold small">
                    {{ $p->category_name ?? (optional($p->category)->cname ?: '-') }}
                </td>
                <td>
                    <div class="fw-semibold text-dark">{{ $p->cname }}</div>
                </td>
                <td class="text-center text-secondary">
                    {{ $fmtAwal }}
                </td>
                <td class="text-center text-success fw-medium">
                    {{ $fmtMasuk }}
                </td>
                <td class="text-center text-info fw-medium">
                    {{ $fmtPengembalian }}
                </td>
                <td class="text-center text-primary fw-medium">
                    {{ $fmtPenjualan }}
                </td>
                <td class="text-center text-danger fw-medium">
                    {{ $fmtKeluar }}
                </td>
                <td class="text-center fw-bold text-dark">
                    {{ $fmtSisa }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    Tidak ada pergerakan stok pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small text-muted">
        Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} item
    </div>
    <div class="pagination-wrapper">
        {{ $products->links() }}
    </div>
</div>
