<!-- Baris Atas: Judul di kiri, Filter Tanggal, Export, Import, dan Dropdown + Tambah di kanan (Persis Olsera) -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
    <div>
        <h5 class="fw-bold mb-1 text-dark">Daftar Stok Keluar</h5>
        <div class="text-muted small">{{ $totalStockOut }} Stok Keluar</div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Dropdown Periode Tanggal -->
        <div class="dropdown d-inline-block">
            <button type="button" class="filter-chip filter-chip-date dropdown-toggle border" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                <i class="bi bi-calendar"></i>
                <span>{{ $startDate === $endDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
                <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
            </button>
            <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 350px;">
                <div class="px-2 py-1 text-muted text-center small fw-bold">Pilih Cepat</div>
                @php
                $today = \Carbon\Carbon::today()->format('Y-m-d');
                $yesterday = \Carbon\Carbon::yesterday()->format('Y-m-d');
                $last7Days = \Carbon\Carbon::today()->subDays(6)->format('Y-m-d');
                $thisMonthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                $thisMonthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
                $lastMonthStart = \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $lastMonthEnd = \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                @endphp
                <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $today, 'end_date' => $today])) }}">
                    Hari Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                    Kemarin
                </a>
                <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                    7 Hari Lalu
                </a>
                <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                    Bulan Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_keluar', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                    Bulan Lalu
                </a>

                <div class="dropdown-divider my-2"></div>
                <div class="px-2 py-1 text-muted text-center small fw-bold">Custom Tanggal</div>
                <form action="{{ route('inventory.index') }}" method="GET" class="px-2 py-1">
                    <input type="hidden" name="tab" value="stok_keluar">
                    @if(request('nid_outlet'))
                    <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                    @endif
                    @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    <div class="mb-2">
                        <label class="form-label text-muted small mb-1">Dari</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small mb-1">Sampai</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">Terapkan Tanggal</button>
                </form>
            </div>
        </div>

        <!-- Tombol Export -->
        <!-- <button type="button" class="btn btn-primary btn-sm px-3" onclick="alert('Fitur export data stok keluar siap diintegrasikan.')">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Export
                </button> -->

        <!-- Tombol Import -->
        <!-- <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="alert('Fitur import data stok keluar siap diintegrasikan.')">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Import
                </button> -->

        <!-- Tombol Import PDF -->
        <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#importStockOutPdfModal">
            <i class="bi bi-file-earmark-pdf me-1"></i> Import PDF
        </button>

        <!-- Tombol + Tambah dengan Dropdown (Persis Olsera Image 2) -->
        <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-success btn-sm px-3 dropdown-toggle shadow-sm fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-lg me-1"></i> Tambah
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="border-radius: 10px;">
                <li>
                    <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="javascript:void(0)" onclick="openStockOutModal('TRANSFER')">
                        <i class="bi bi-arrow-left-right me-2 text-primary"></i> Transfer ke toko
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="javascript:void(0)" onclick="openStockOutModal('OUT')">
                        <i class="bi bi-box-arrow-up me-2 text-danger"></i> Tambah Stok Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Baris Filter Baris & Pencarian No Stok Keluar (Persis Olsera) -->
<!-- <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <form action="{{ route('inventory.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="stok_keluar">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if(request('nid_outlet'))
                    <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                    @endif
                    @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <select name="per_page" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Baris</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 Baris</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Baris</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Baris</option>
                    </select>
                </form>
            </div>

            <div>
                <form action="{{ route('inventory.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="stok_keluar">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if(request('nid_outlet'))
                    <input type="hidden" name="nid_outlet" value="{{ request('nid_outlet') }}">
                    @endif
                    @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari No. Stok Keluar..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div> -->

<!-- Tabel Daftar Stok Keluar (Persis Kolom Olsera Image 1 & Image 5) -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
            <tr class="text-secondary small fw-semibold">
                <th class="px-3 py-3">No. <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3">Transfer ke <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3">Tanggal <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3">Catatan <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3">Alasan <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3 text-center">Status <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.75rem;"></i></th>
                <th class="px-3 py-3">Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($stockOuts as $so)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('inventory.stock-out.show', $so->cno_stock_out) }}" class="fw-bold text-primary text-decoration-none">
                        {{ $so->cno_stock_out }}
                    </a>
                </td>
                <td class="px-3 py-3 text-secondary">
                    @if($so->targetOutlet)
                    <span class="fw-semibold text-primary"><i class="bi bi-shop me-1"></i>{{ $so->targetOutlet->cname }}</span>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-dark fw-semibold">
                    {{ $so->dstock_out ? \Carbon\Carbon::parse($so->dstock_out)->format('d-M-Y') : '-' }}
                </td>
                <td class="px-3 py-3 text-secondary">
                    {{ $so->cnote ?: '-' }}
                </td>
                <td class="px-3 py-3 text-secondary">
                    {{ $so->creason ?: '-' }}
                </td>
                <td class="px-3 py-3 text-center">
                    @if(strtoupper($so->cstatus) === 'POSTED')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Posted</span>
                    @elseif(strtoupper($so->cstatus) === 'DRAFT')
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill">Draft</span>
                    @elseif(in_array(strtoupper($so->cstatus), ['CANCELLED', 'VOID']))
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">Cancelled</span>
                    @else
                    <span class="badge bg-secondary px-2 py-1 rounded-pill">{{ $so->cstatus }}</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-secondary">
                    {{ $so->user?->cemail ?: ($so->user?->email ?: ($so->user?->cname ?: 'admin')) }}
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    Tidak ada riwayat transaksi stok keluar pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Paginasi -->
@if($stockOuts && $stockOuts->hasPages())
<div class="mt-3">
    {{ $stockOuts->links() }}
</div>
@endif

<!-- ==================== MODAL IMPORT STOK KELUAR DARI PDF ==================== -->
<div class="modal fade" id="importStockOutPdfModal" tabindex="-1" aria-labelledby="importStockOutPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-out.import-pdf') }}" method="POST" enctype="multipart/form-data" id="importStockOutPdfForm">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="importStockOutPdfModalLabel">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Import Stok Keluar dari PDF
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Unggah dokumen stok keluar / transfer dalam format PDF (misal: Olsera/IQOS). Sistem otomatis membaca nomor dokumen, tanggal, mencocokkan produk, qty, dan harga satuan.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Outlet Asal Stok <span class="text-danger">*</span></label>
                        @if($isOutletLocked || !empty($selectedOutletId))
                        @php
                            $lockedOutlet = $outlets->firstWhere('nid', $selectedOutletId);
                        @endphp
                        <input type="text" class="form-control bg-light" value="{{ $lockedOutlet?->cname ?? 'Outlet Terpilih' }}" readonly>
                        <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                        <div class="form-text text-muted small">Mengikuti outlet yang sedang aktif di pojok kanan atas.</div>
                        @else
                        <select name="nid_outlet" class="form-select" required>
                            <option value="">-- Pilih Outlet Asal --</option>
                            @foreach($outlets as $outlet)
                            <option value="{{ $outlet->nid }}">{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Outlet Tujuan Transfer (Opsional)</label>
                        <select name="nid_target_outlet" class="form-select">
                            <option value="">-- Bukan Transfer (Stok Keluar Biasa) --</option>
                            @foreach($outlets as $outlet)
                            @if(empty($selectedOutletId) || (int) $outlet->nid !== (int) $selectedOutletId)
                            <option value="{{ $outlet->nid }}">{{ $outlet->cname }}</option>
                            @endif
                            @endforeach
                        </select>
                        <div class="form-text text-muted small">Isi hanya jika dokumen ini adalah transfer stok ke outlet lain.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan / Keterangan (Opsional)</label>
                        <input type="text" name="creason" class="form-control" maxlength="100" placeholder="Misal: Transfer cabang, retur, rusak, dll.">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">File PDF Dokumen <span class="text-danger">*</span></label>
                        <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf" required>
                        <div class="form-text text-muted small">Format `.pdf`, maksimal 10 MB. Dokumen akan disimpan sebagai <strong>Draft</strong> agar bisa diperiksa terlebih dahulu.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold">
                        <i class="bi bi-upload me-1"></i> Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL TAMBAH STOK KELUAR (MIRIP MODAL PEMBELIAN BARU) ==================== -->
<div class="modal fade" id="createStockOutModal" tabindex="-1" aria-labelledby="createStockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1040px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-out.store') }}" method="POST" id="createStockOutForm">
                @csrf
                <input type="hidden" name="ctype" id="stockOutTypeInput" value="OUT">

                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="createStockOutModalLabel">
                        <i class="bi bi-box-arrow-up me-2 text-danger"></i><span id="stockOutModalTitle">Tambah Stok Keluar</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Baris 1: Outlet Asal, Outlet Tujuan (jika transfer), Tanggal, Alasan -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Outlet Asal <span class="text-danger">*</span></label>
                            <select name="nid_outlet" id="modalStockOutOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
                                @foreach($outlets as $ot)
                                <option value="{{ $ot->nid }}" {{ (string)($selectedOutletId ?: $outlets->first()?->nid) === (string)$ot->nid ? 'selected' : '' }}>
                                    {{ $ot->cname }}
                                </option>
                                @endforeach
                            </select>
                            @if($isOutletLocked)
                            <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                            @endif
                        </div>

                        <div class="col-md-3" id="wrapperTargetOutlet" style="display: none;">
                            <label class="form-label fw-semibold small text-secondary">Transfer ke Outlet <span class="text-danger">*</span></label>
                            <select name="nid_target_outlet" id="modalStockOutTargetOutlet" class="form-select">
                                <option value="">-- Pilih Outlet Tujuan --</option>
                                @foreach($outlets as $ot)
                                <option value="{{ $ot->nid }}">{{ $ot->cname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Keluar <span class="text-danger">*</span></label>
                            <input type="date" name="dstock_out" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-3" id="wrapperStockOutReason">
                            <label class="form-label fw-semibold small text-secondary">Alasan Pengeluaran <span class="text-danger">*</span></label>
                            <select name="creason" id="modalStockOutReason" class="form-select">
                                <option value="Transfer Antar Toko">Transfer Antar Toko</option>
                                <option value="Rusak / Cacat">Rusak / Cacat</option>
                                <option value="Kedaluwarsa">Kedaluwarsa</option>
                                <option value="Pemakaian Bar / Kitchen">Pemakaian Bar / Kitchen</option>
                                <option value="Pemakaian Internal / Promosi">Pemakaian Internal / Promosi</option>
                                <option value="Koreksi Selisih Stok">Koreksi Selisih Stok</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>
                    </div>

                    <!-- Baris 2: Catatan Pengeluaran -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Pengeluaran</label>
                            <textarea name="cnote" id="modalStockOutNote" class="form-control" rows="2" placeholder="Catatan stok keluar (misal: SALAH INPUT BARANG DI INVOICE, BARANG GUDANG DIAMBIL SERVER, dll)..."></textarea>
                        </div>
                    </div>

                    <!-- Header Section Item Stok Keluar (Persis Modal Pembelian) -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                Item Barang Keluar
                            </span>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold shadow-sm" id="btnAddNewStockOutRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <!-- Table Item Dinamis (Persis Modal Pembelian) -->
                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="stockOutItemsTable">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 32px;" class="text-center py-2 text-muted text-nowrap">NO</th>
                                    <th style="width: 140px;" class="py-2 text-center text-nowrap">TIPE ITEM</th>
                                    <th class="py-2 text-center text-nowrap" style="min-width: 260px;">PRODUK / BAHAN BAKU</th>
                                    <th style="width: 105px;" class="text-center py-2 text-nowrap">STOK SAAT INI</th>
                                    <th style="width: 115px;" class="text-center py-2 text-nowrap">QTY KELUAR</th>
                                    <th style="width: 130px;" class="text-center py-2 text-nowrap">HARGA MODAL</th>
                                    <th style="width: 120px;" class="text-end py-2 text-nowrap">SUBTOTAL</th>
                                    <th style="width: 32px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="stockOutItemsTableBody" class="small">
                                <!-- Baris produk / bahan baku di-generate via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Baris Ringkasan Total Kuantitas & Nilai Modal -->
                    <div class="d-flex flex-wrap justify-content-end align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="d-flex align-items-center gap-4 text-end ms-auto">
                                <div>
                                <span class="text-secondary small d-block">Total Kuantitas:</span>
                                <span class="fw-bold fs-6 text-dark" id="stockOutTotalQty">0</span> <span class="small text-muted">unit</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Total Nilai Modal:</span>
                                <span class="fw-bold fs-5 text-danger" id="stockOutTotalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitStockOut">
                        <i class="bi bi-check2-circle me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="stockOutProductsData">
    @json($modalProducts)
</script>
<script>
    // Data Master Produk (Produk, Setengah Jadi, Bahan Baku) dari mpos_product untuk Modal Stok Keluar
    const stockOutProducts = JSON.parse(document.getElementById('stockOutProductsData')?.textContent || '[]');

    let stockOutRowCounter = 0;
    const stockOutTsInstances = {};

    function formatStockOutNumber(val) {
        const num = parseFloat(val || 0);
        if (Number.isNaN(num)) return '0';
        return Number.isInteger(num) ? String(num) : String(parseFloat(num.toFixed(2)));
    }

    function getSelectedStockOutOutletId() {
        const sel = document.getElementById('modalStockOutOutlet');
        if (sel) return sel.value;
        return "{{ $selectedOutletId ?: ($outlets->first()?->nid ?? '') }}";
    }

    function normalizeStockOutItemType(val) {
        const raw = String(val || 'PRODUK').trim().toUpperCase();
        if (raw === 'SETENGAH_JADI') return 'SETENGAH_JADI';
        if (raw === 'BAHAN_BAKU' || raw === 'INGREDIENT') return 'BAHAN_BAKU';
        return 'PRODUK';
    }

    function getStockOutPlaceholderForType(type) {
        const norm = normalizeStockOutItemType(type);
        if (norm === 'BAHAN_BAKU') return '-- Cari atau pilih bahan baku --';
        if (norm === 'SETENGAH_JADI') return '-- Cari atau pilih setengah jadi --';
        return '-- Cari atau pilih produk --';
    }

    function getStockOutItemsForType(type, outletId) {
        const targetType = normalizeStockOutItemType(type);

        // Kelompokkan berdasarkan nama produk dengan memprioritaskan record pada outlet asal yang dipilih
        const groupedByName = {};
        stockOutProducts.forEach(p => {
            const pType = normalizeStockOutItemType(p.cproduct_type || 'PRODUK');
            if (pType !== targetType) return;

            const key = String(p.cname || '').trim().toLowerCase();
            if (!key) return;
            if (!groupedByName[key]) groupedByName[key] = [];
            groupedByName[key].push(p);
        });

        const list = [];
        Object.keys(groupedByName).forEach(key => {
            const variants = groupedByName[key];
            const exactOutletMatch = outletId ?
                variants.find(v => String(v.nid_outlet || '') === String(outletId)) :
                null;
            const globalMatch = variants.find(v => !v.nid_outlet);

            if (exactOutletMatch) {
                list.push({
                    nid: exactOutletMatch.nid,
                    cname: exactOutletMatch.cname,
                    nstock: parseFloat(exactOutletMatch.nqty !== undefined && exactOutletMatch.nqty !== null ? exactOutletMatch.nqty : 0),
                    csatuan: exactOutletMatch.csatuan || 'PCS',
                    ncost: parseFloat(exactOutletMatch.nprice_buy !== undefined && exactOutletMatch.nprice_buy !== null ? exactOutletMatch.nprice_buy : 0),
                    cproduct_type: targetType
                });
            } else if (globalMatch) {
                list.push({
                    nid: globalMatch.nid,
                    cname: globalMatch.cname,
                    nstock: parseFloat(globalMatch.nqty !== undefined && globalMatch.nqty !== null ? globalMatch.nqty : 0),
                    csatuan: globalMatch.csatuan || 'PCS',
                    ncost: parseFloat(globalMatch.nprice_buy !== undefined && globalMatch.nprice_buy !== null ? globalMatch.nprice_buy : 0),
                    cproduct_type: targetType
                });
            } else {
                const tmpl = variants[0];
                list.push({
                    nid: tmpl.nid,
                    cname: tmpl.cname,
                    nstock: 0,
                    csatuan: tmpl.csatuan || 'PCS',
                    ncost: parseFloat(tmpl.nprice_buy !== undefined && tmpl.nprice_buy !== null ? tmpl.nprice_buy : 0),
                    cproduct_type: targetType
                });
            }
        });

        list.sort((a, b) => a.cname.localeCompare(b.cname));
        return list;
    }

    function openStockOutModal(type) {
        const modalEl = document.getElementById('createStockOutModal');
        const modal = new bootstrap.Modal(modalEl);
        const titleSpan = document.getElementById('stockOutModalTitle');
        const typeInput = document.getElementById('stockOutTypeInput');
        const wrapperTarget = document.getElementById('wrapperTargetOutlet');
        const selectTarget = document.getElementById('modalStockOutTargetOutlet');
        const selectReason = document.getElementById('modalStockOutReason');

        if (type === 'TRANSFER') {
            titleSpan.textContent = 'Transfer Stok ke Toko';
            typeInput.value = 'TRANSFER';
            wrapperTarget.style.display = 'block';
            selectTarget.required = true;
            selectReason.value = 'Transfer Antar Toko';
        } else {
            titleSpan.textContent = 'Tambah Stok Keluar';
            typeInput.value = 'OUT';
            wrapperTarget.style.display = 'none';
            selectTarget.required = false;
            selectTarget.value = '';
            selectReason.value = 'Rusak / Cacat';
        }

        // Reset baris tabel jika kosong
        const tbody = document.getElementById('stockOutItemsTableBody');
        if (tbody.children.length === 0) {
            addStockOutRow();
        }

        modal.show();
    }

    function addStockOutRow(itemId = '', itemQty = 1, itemType = 'PRODUK') {
        stockOutRowCounter++;
        const curIdx = stockOutRowCounter;
        const tbody = document.getElementById('stockOutItemsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'stock-out-row-' + curIdx;
        tr.setAttribute('data-row-id', curIdx);

        const normalizedType = normalizeStockOutItemType(itemType);
        const placeholderText = getStockOutPlaceholderForType(normalizedType);

        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle row-number py-2">${tbody.children.length + 1}</td>
            <td class="py-2 align-middle">
                <select name="items[${curIdx}][citem_type]" class="form-select form-select-sm fw-semibold out-item-type-select" data-row="${curIdx}">
                    <option value="PRODUK" ${normalizedType === 'PRODUK' ? 'selected' : ''}>Produk</option>
                    <option value="SETENGAH_JADI" ${normalizedType === 'SETENGAH_JADI' ? 'selected' : ''}>Setengah Jadi</option>
                    <option value="BAHAN_BAKU" ${normalizedType === 'BAHAN_BAKU' ? 'selected' : ''}>Bahan Baku</option>
                </select>
            </td>
            <td class="py-2 align-middle">
                <select name="items[${curIdx}][nid_item]" class="form-select form-select-sm out-item-select" id="stock_out_item_${curIdx}" data-row="${curIdx}">
                    <option value="">${placeholderText}</option>
                </select>
            </td>
            <td class="text-center py-2 align-middle">
                <span class="badge bg-light text-secondary border out-current-stock-badge" id="stock_out_badge_${curIdx}">-</span>
            </td>
            <td class="py-2 text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus out-btn-minus" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" step="any" min="0.01" name="items[${curIdx}][nqty]" class="qty-stepper-input out-item-qty-input no-spinners" value="${itemQty}" placeholder="1" required>
                    <button type="button" class="qty-stepper-btn qty-stepper-plus out-btn-plus" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="py-2 align-middle">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted">Rp</span>
                    <input type="number" step="1" min="0" name="items[${curIdx}][ncost]" class="form-control form-control-sm text-end out-item-cost-input" value="0">
                </div>
            </td>
            <td class="text-end fw-semibold text-danger align-middle out-item-subtotal-display">
                Rp 0
            </td>
            <td class="text-center py-2 align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle out-btn-delete" onclick="removeStockOutRow(${curIdx})" title="Hapus Baris" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const selectEl = tr.querySelector('.out-item-select');
        const stockBadge = tr.querySelector('.out-current-stock-badge');
        const costInput = tr.querySelector('.out-item-cost-input');
        const qtyInput = tr.querySelector('.out-item-qty-input');
        const typeSelect = tr.querySelector('.out-item-type-select');
        const btnMinus = tr.querySelector('.out-btn-minus');
        const btnPlus = tr.querySelector('.out-btn-plus');

        // Inisialisasi TomSelect untuk live search & dropdown modern
        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: placeholderText,
            allowEmptyOption: true,
            sortField: {
                field: "text",
                direction: "asc"
            },
            render: {
                item: function(data, escape) {
                    const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                    return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                },
                option: function(data, escape) {
                    if (!data.value) {
                        return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                    }
                    const stockVal = (data.nstock !== undefined && data.nstock !== null) ? data.nstock : '-';
                    const unitVal = data.csatuan || 'PCS';
                    const stockLabel = stockVal === '-' ? '-' : `${formatStockOutNumber(stockVal)} ${unitVal}`;
                    const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                    return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                        '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                        '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                        '</div>';
                }
            }
        });
        stockOutTsInstances[curIdx] = ts;

        // Listener saat produk/bahan dipilih di TomSelect
        ts.on('change', function(val) {
            const optData = ts.options[val];
            if (optData && val) {
                const stock = parseFloat((optData.nstock !== undefined && optData.nstock !== null) ? optData.nstock : 0);
                const unit = optData.csatuan || 'PCS';
                stockBadge.textContent = `${formatStockOutNumber(stock)} ${unit}`;
                if (stock <= 0) {
                    stockBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle out-current-stock-badge';
                } else if (stock <= 5) {
                    stockBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle out-current-stock-badge';
                } else {
                    stockBadge.className = 'badge bg-success-subtle text-success border border-success-subtle out-current-stock-badge';
                }

                const cost = parseFloat(optData.ncost || 0);
                costInput.value = Math.round(cost);
            } else {
                stockBadge.textContent = '-';
                stockBadge.className = 'badge bg-light text-secondary border out-current-stock-badge';
                costInput.value = 0;
            }
            calculateStockOutTotals();
        });

        // Event listener minus dan plus pada Qty Stepper
        if (btnMinus) {
            btnMinus.addEventListener('click', function() {
                let val = parseFloat(qtyInput.value) || 0;
                val = Math.max(0, val - 1);
                if (val === 0) val = 1;
                qtyInput.value = val;
                calculateStockOutTotals();
            });
        }

        if (btnPlus) {
            btnPlus.addEventListener('click', function() {
                let val = parseFloat(qtyInput.value) || 0;
                qtyInput.value = val + 1;
                calculateStockOutTotals();
            });
        }

        qtyInput.addEventListener('input', function() {
            calculateStockOutTotals();
        });

        costInput.addEventListener('input', function() {
            calculateStockOutTotals();
        });

        typeSelect.addEventListener('change', function() {
            populateStockOutTsOptions(curIdx, this.value);
        });

        populateStockOutTsOptions(curIdx, normalizedType, itemId);
        renumberStockOutRows();
        calculateStockOutTotals();
    }

    function populateStockOutTsOptions(rowId, rowType, keepSelectedId = '', keepSelectedName = '') {
        const ts = stockOutTsInstances[rowId];
        if (!ts) return;

        const row = document.getElementById('stock-out-row-' + rowId);
        const stockBadge = row ? row.querySelector('.out-current-stock-badge') : null;
        const costInput = row ? row.querySelector('.out-item-cost-input') : null;

        const outletId = getSelectedStockOutOutletId();
        const normType = normalizeStockOutItemType(rowType);
        const list = getStockOutItemsForType(normType, outletId);
        const ph = getStockOutPlaceholderForType(normType);

        ts.settings.placeholder = ph;
        ts.inputState();
        ts.clear(true);
        ts.clearOptions();

        list.forEach(item => {
            ts.addOption({
                value: String(item.nid),
                text: item.cname,
                cname: item.cname,
                nstock: item.nstock,
                csatuan: item.csatuan || 'PCS',
                ncost: item.ncost
            });
        });
        ts.refreshOptions(false);

        if (keepSelectedId && ts.options[String(keepSelectedId)]) {
            ts.setValue(String(keepSelectedId));
            return;
        }

        if (keepSelectedName) {
            const matched = list.find(it => String(it.cname).trim().toLowerCase() === String(keepSelectedName).trim().toLowerCase());
            if (matched) {
                ts.setValue(String(matched.nid));
                return;
            }
        }

        if (stockBadge) {
            stockBadge.textContent = '-';
            stockBadge.className = 'badge bg-light text-secondary border out-current-stock-badge';
        }
        if (costInput) {
            costInput.value = 0;
        }
        calculateStockOutTotals();
    }

    function removeStockOutRow(rowId) {
        if (stockOutTsInstances[rowId]) {
            try {
                stockOutTsInstances[rowId].destroy();
            } catch (e) {}
            delete stockOutTsInstances[rowId];
        }
        const row = document.getElementById('stock-out-row-' + rowId);
        if (row) {
            row.remove();
            renumberStockOutRows();
            calculateStockOutTotals();
        }
    }

    function renumberStockOutRows() {
        const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
        rows.forEach((r, idx) => {
            const numCell = r.querySelector('.row-number');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    function calculateStockOutTotals() {
        const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
        let grandQty = 0;
        let grandAmount = 0;

        rows.forEach(r => {
            const qtyInput = r.querySelector('.out-item-qty-input');
            const costInput = r.querySelector('.out-item-cost-input');
            const subtotalDisplay = r.querySelector('.out-item-subtotal-display');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const cost = parseFloat(costInput ? costInput.value : 0) || 0;
            const subtotal = qty * cost;

            grandQty += qty;
            grandAmount += subtotal;

            if (subtotalDisplay) {
                subtotalDisplay.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
            }
        });

        const totalQtyEl = document.getElementById('stockOutTotalQty');
        const totalAmountEl = document.getElementById('stockOutTotalAmount');
        if (totalQtyEl) totalQtyEl.textContent = formatStockOutNumber(grandQty);
        if (totalAmountEl) totalAmountEl.textContent = 'Rp ' + Math.round(grandAmount).toLocaleString('id-ID');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnAdd = document.getElementById('btnAddNewStockOutRow');
        if (btnAdd) {
            btnAdd.addEventListener('click', function() {
                addStockOutRow();
            });
        }

        // Form submit validation
        const formEl = document.getElementById('createStockOutForm');
        if (formEl) {
            formEl.addEventListener('submit', function(e) {
                const typeInput = document.getElementById('stockOutTypeInput');
                const targetOutlet = document.getElementById('modalStockOutTargetOutlet');

                if (typeInput && typeInput.value === 'TRANSFER') {
                    if (!targetOutlet || !targetOutlet.value) {
                        e.preventDefault();
                        alert('Silakan pilih Transfer ke Outlet tujuan terlebih dahulu.');
                        if (targetOutlet) targetOutlet.focus();
                        return;
                    }
                }

                const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Silakan tambahkan minimal 1 item barang keluar.');
                    return;
                }

                let hasEmptyItem = false;
                rows.forEach(r => {
                    const rowId = r.getAttribute('data-row-id');
                    const ts = stockOutTsInstances[rowId];
                    const val = ts ? ts.getValue() : r.querySelector('.out-item-select')?.value;
                    if (!val) {
                        hasEmptyItem = true;
                    }
                });

                if (hasEmptyItem) {
                    e.preventDefault();
                    alert('Mohon pilih produk atau bahan baku pada baris item barang keluar sebelum menyimpan.');
                    return;
                }
            });
        }

        // Jika outlet asal diubah, perbarui opsi di semua baris yang sudah ada
        const outletSelect = document.getElementById('modalStockOutOutlet');
        if (outletSelect) {
            outletSelect.addEventListener('change', function() {
                const rows = document.querySelectorAll('#stockOutItemsTableBody tr');
                rows.forEach(r => {
                    const rowId = r.getAttribute('data-row-id');
                    const typeSelect = r.querySelector('.out-item-type-select');
                    if (rowId && typeSelect && stockOutTsInstances[rowId]) {
                        const ts = stockOutTsInstances[rowId];
                        const currentVal = ts.getValue();
                        const curOption = currentVal ? ts.options[currentVal] : null;
                        const curName = curOption ? (curOption.cname || curOption.text || '') : '';
                        populateStockOutTsOptions(rowId, typeSelect.value, currentVal, curName);
                    }
                });
            });
        }
    });
</script>