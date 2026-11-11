<!-- Baris Atas: Judul di kiri, Filter Tanggal Chip & Tombol Tambah di kanan -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
    <div>
        <h5 class="fw-bold mb-1 text-dark">Daftar Produksi Stok</h5>
        <div class="text-muted small">{{ $totalProduction ?? ($productions ? $productions->total() : 0) }} Dokumen Produksi</div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Filter Tanggal Chip -->
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
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'produksi_stok', 'start_date' => $today, 'end_date' => $today])) }}">
                    Hari Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'produksi_stok', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                    Kemarin
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'produksi_stok', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                    7 Hari Lalu
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'produksi_stok', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                    Bulan Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'produksi_stok', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                    Bulan Lalu
                </a>

                <hr class="dropdown-divider my-2">
                <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                    <input type="hidden" name="tab" value="produksi_stok">
                    @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                    @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif
                    @if(!empty($perPage)) <input type="hidden" name="per_page" value="{{ $perPage }}"> @endif

                    <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                            <input type="date" name="start_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}" required>
                        </div>
                        <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                            <button type="button" class="btn btn-link p-1 border-0 sync-date-btn dropdown-sync-date-btn" title="Samakan Tanggal">
                                <i class="bi bi-arrow-left-right fs-6"></i>
                            </button>
                        </div>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                        Terapkan Tanggal
                    </button>
                </form>
            </div>
        </div>

        @if(!$isOutletLocked && count($outlets) > 1)
        <form method="GET" action="{{ route('inventory.index') }}" class="d-inline">
            <input type="hidden" name="tab" value="produksi_stok">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif
            @if($perPage) <input type="hidden" name="per_page" value="{{ $perPage }}"> @endif

            <select name="nid_outlet" class="form-select form-select-sm" style="width: auto; border-radius: 20px; font-size: 0.84rem;" onchange="this.form.submit()">
                <option value="">Semua Outlet</option>
                @foreach($outlets as $o)
                <option value="{{ $o->nid }}" {{ (string)$selectedOutletId === (string)$o->nid ? 'selected' : '' }}>
                    {{ $o->cname }}
                </option>
                @endforeach
            </select>
        </form>
        @endif

        <button type="button" class="btn btn-success btn-sm px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahProduksi">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah</span>
        </button>
    </div>
</div>

<!-- Baris Filter Cepat & Search -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="{{ route('inventory.index') }}" class="d-inline">
            <input type="hidden" name="tab" value="produksi_stok">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            @if($selectedOutletId) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
            @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif

            <select name="per_page" class="form-select form-select-sm text-secondary" style="font-size: 0.82rem; border-color: #cbd5e1; width: auto;" onchange="this.form.submit()">
                <option value="10" {{ (string)$perPage === '10' ? 'selected' : '' }}>10 Baris</option>
                <option value="15" {{ (string)$perPage === '15' ? 'selected' : '' }}>15 Baris</option>
                <option value="25" {{ (string)$perPage === '25' ? 'selected' : '' }}>25 Baris</option>
                <option value="50" {{ (string)$perPage === '50' ? 'selected' : '' }}>50 Baris</option>
            </select>
        </form>
    </div>

    <div>
        <form method="GET" action="{{ route('inventory.index') }}" class="d-flex align-items-center">
            <input type="hidden" name="tab" value="produksi_stok">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            @if($selectedOutletId) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
            @if($perPage) <input type="hidden" name="per_page" value="{{ $perPage }}"> @endif

            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" name="search" class="form-control" placeholder="Cari..." value="{{ $search }}" autocomplete="off">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Produksi Stok Olsera Style -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
            <tr class="text-secondary small fw-semibold">
                <th style="width: 24%;" class="px-3 py-3">No. <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 14%;" class="px-3 py-3 text-center">Tanggal <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 28%;" class="px-3 py-3">Catatan <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 12%;" class="px-3 py-3 text-center">Status <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 22%;" class="px-3 py-3">Diterima / Dibuat Oleh <i class="bi bi-arrow-down-up small text-muted"></i></th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($productions as $prod)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('inventory.production.show', $prod->nid) }}" class="fw-semibold text-primary text-decoration-none">
                        {{ $prod->cno_production }}
                    </a>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">
                        {{ $prod->outlet?->cname ?: 'Outlet #' . $prod->nid_outlet }} &bull; {{ $prod->products_count }} Hasil Jadi &bull; {{ $prod->materials_count }} Bahan
                    </div>
                </td>
                <td class="px-3 py-3 text-center">
                    <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($prod->dproduction)->format('d-M-Y') }}</div>
                </td>
                <td class="px-3 py-3">
                    <div class="text-secondary text-truncate" style="max-width: 320px;">{{ $prod->cnote ?: '-' }}</div>
                </td>
                <td class="px-3 py-3 text-center">
                    @if($prod->isPosted())
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-normal">Posted</span>
                    @elseif($prod->isDraft())
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-normal">Draft</span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-normal">Batal</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <div class="text-secondary">
                        {{ $prod->user?->cemail ?: ($prod->user?->email ?: ($prod->user?->cname ?: 'Admin')) }}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-gear-wide-connected display-6 d-block mb-2 text-secondary opacity-50"></i>
                    <p class="mb-0">Belum ada dokumen Produksi Stok yang ditemukan.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($productions && $productions->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3 pt-2">
    <div class="text-muted small">
        Menampilkan {{ $productions->firstItem() }} sampai {{ $productions->lastItem() }} dari {{ $productions->total() }} data
    </div>
    <div>
        {{ $productions->links() }}
    </div>
</div>
@endif

<!-- ==================== MODAL TAMBAH PRODUKSI STOK BARU (LENGKAP DENGAN PRODUK & BAHAN) ==================== -->
<div class="modal fade" id="modalTambahProduksi" tabindex="-1" aria-labelledby="modalTambahProduksiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1060px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.store') }}" method="POST" id="formCreateProduction">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="modalTambahProduksiLabel">
                        <i class="bi bi-gear-wide-connected text-primary me-2"></i>Tambah Produksi Stok Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Baris 1: Outlet, Tanggal Produksi, Catatan -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Outlet Produksi <span class="text-danger">*</span></label>
                            <select name="nid_outlet" id="prodModalOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
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

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Produksi <span class="text-danger">*</span></label>
                            <input type="date" name="dproduction" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Catatan (Opsional)</label>
                            <input type="text" name="cnote" class="form-control" placeholder="Contoh: Produksi batch pagi, target 50 pcs...">
                        </div>
                    </div>

                    <!-- ==================== BAGIAN 1: PRODUK HASIL PRODUKSI (PLUS) ==================== -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-box-seam me-1 text-primary"></i> Produk Hasil Produksi (Stok Bertambah)
                            </span>
                            <span class="badge bg-primary-subtle text-primary border ms-2">Hasil Jadi</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" id="btnAddProdRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                        </button>
                    </div>

                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="tableProductionProducts">
                            <thead>
                                <tr class="bg-light text-secondary small">
                                    <th style="width: 35px;" class="text-center py-2 text-nowrap">NO</th>
                                    <th class="py-2 text-center text-nowrap" style="min-width: 280px;">PRODUK JADI</th>
                                    <th style="width: 125px;" class="text-center py-2 text-nowrap">STOK SEKARANG</th>
                                    <th style="width: 135px;" class="text-center py-2 text-nowrap">QTY PRODUKSI</th>
                                    <th style="width: 95px;" class="text-center py-2 text-nowrap">SATUAN</th>
                                    <th style="width: 40px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="productionProductsTableBody" class="small">
                                <!-- Baris produk di-generate via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- ==================== BAGIAN 2: BAHAN BAKU YANG DIGUNAKAN (MINUS) ==================== -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-layers me-1 text-danger"></i> Bahan Baku yang Digunakan (Stok Berkurang)
                            </span>
                            <span class="badge bg-danger-subtle text-danger border ms-2">Bahan / Resep</span>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold shadow-sm" id="btnAddMatRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Bahan Baku
                        </button>
                    </div>

                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="tableProductionMaterials">
                            <thead>
                                <tr class="bg-light text-secondary small">
                                    <th style="width: 35px;" class="text-center py-2 text-nowrap">NO</th>
                                    <th class="py-2 text-center text-nowrap" style="min-width: 240px;">NAMA BAHAN BAKU</th>
                                    <th style="width: 175px;" class="text-center py-2 text-nowrap">UNTUK PRODUK</th>
                                    <th style="width: 150px;" class="text-center py-2 text-nowrap">QTY DIPOTONG</th>
                                    <th style="width: 90px;" class="text-center py-2 text-nowrap">SATUAN</th>
                                    <th style="width: 40px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="productionMaterialsTableBody" class="small">
                                <!-- Baris bahan baku di-generate via JS -->
                            </tbody>
                        </table>
                        <div id="emptyMatNotice" class="text-center py-3 text-muted small bg-light-subtle border rounded mt-1">
                            <i class="bi bi-info-circle me-1"></i> Belum ada bahan baku. Bahan baku otomatis muncul jika produk memiliki resep, atau klik <strong>+ Tambah Bahan Baku</strong>.
                        </div>
                    </div>

                    <!-- Ringkasan Kuantitas di Bawah -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="text-muted small">
                            <i class="bi bi-shield-check me-1 text-success"></i> Dokumen akan disimpan sebagai <strong>Draf</strong> terlebih dahulu sebelum diposting.
                        </div>
                        <div class="d-flex align-items-center gap-4 text-end ms-auto">
                            <div>
                                <span class="text-secondary small d-block">Total Hasil Jadi:</span>
                                <span class="fw-bold fs-6 text-primary" id="prodModalTotalQty">0</span> <span class="small text-muted">unit</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Jumlah Bahan Baku:</span>
                                <span class="fw-bold fs-6 text-danger" id="prodModalTotalMatCount">0</span> <span class="small text-muted">item</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitProduction">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="productionProductsData">
    @json($modalProducts ?? [])
</script>

<script type="application/json" id="productionRecipesData">
    @json($modalRecipes ?? [])
</script>

<script>
    let prodRowCounter = 0;
    let matRowCounter = 0;
    const prodTsInstances = {};
    const matTsInstances = {};

    let rawProdProducts = [];
    let rawProdRecipes = {};

    try {
        const pEl = document.getElementById('productionProductsData');
        if (pEl) rawProdProducts = JSON.parse(pEl.textContent || '[]');
    } catch (e) {
        rawProdProducts = [];
    }

    try {
        const rEl = document.getElementById('productionRecipesData');
        if (rEl) rawProdRecipes = JSON.parse(rEl.textContent || '{}');
    } catch (e) {
        rawProdRecipes = {};
    }

    function getSelectedProdModalOutlet() {
        const sel = document.getElementById('prodModalOutlet');
        if (sel) return sel.value;
        return "{{ $selectedOutletId ?: ($outlets->first()?->nid ?? '') }}";
    }

    // Filter daftar produk berdasarkan outlet
    function getFilteredProductionProducts() {
        const curOutlet = getSelectedProdModalOutlet();
        return rawProdProducts.filter(p => {
            if (!curOutlet) return true;
            return !p.nid_outlet || String(p.nid_outlet) === String(curOutlet);
        });
    }

    // Tambah Baris Produk Hasil Produksi
    function addProductionProductRow(defaultProdId = '', defaultQty = 1) {
        prodRowCounter++;
        const curIdx = prodRowCounter;
        const tempId = 'prod_' + curIdx;
        const tbody = document.getElementById('productionProductsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'prod-row-' + curIdx;
        tr.setAttribute('data-temp-id', tempId);
        tr.setAttribute('data-row-id', curIdx);

        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle prod-row-number py-2">${tbody.children.length + 1}</td>
            <td class="py-2 align-middle">
                <input type="hidden" name="products[${curIdx}][temp_id]" value="${tempId}">
                <select name="products[${curIdx}][nid_product]" class="form-select form-select-sm prod-item-select" id="prod_select_${curIdx}" data-row="${curIdx}" required>
                    <option value="">-- Cari atau pilih produk jadi --</option>
                </select>
            </td>
            <td class="text-center py-2 align-middle">
                <span class="badge bg-light text-secondary border prod-stock-badge" id="prod_stock_badge_${curIdx}">-</span>
            </td>
            <td class="py-2 text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus prod-btn-minus" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" step="any" min="0.01" name="products[${curIdx}][nqty]" class="qty-stepper-input prod-qty-input no-spinners" value="${defaultQty}" placeholder="1" required>
                    <button type="button" class="qty-stepper-btn qty-stepper-plus prod-btn-plus" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="text-center py-2 align-middle text-muted prod-satuan-cell" id="prod_satuan_${curIdx}">
                PCS
            </td>
            <td class="text-center py-2 align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle" onclick="removeProductionProductRow(${curIdx})" title="Hapus Produk" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const selectEl = tr.querySelector('.prod-item-select');
        const qtyInput = tr.querySelector('.prod-qty-input');
        const btnMinus = tr.querySelector('.prod-btn-minus');
        const btnPlus = tr.querySelector('.prod-btn-plus');

        // Init TomSelect untuk produk jadi
        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: '-- Cari atau pilih produk jadi --',
            allowEmptyOption: true,
            sortField: { field: "text", direction: "asc" },
            render: {
                item: function(data, escape) {
                    const cleanName = data.cname || data.text || '';
                    return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                },
                option: function(data, escape) {
                    if (!data.value) return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                    const stockVal = data.nqty !== undefined ? data.nqty : '-';
                    const unitVal = data.csatuan || 'PCS';
                    return '<div class="d-flex justify-content-between align-items-center py-1">' +
                        '<span class="fw-medium text-dark">' + escape(data.cname || data.text) + '</span>' +
                        '<span class="badge bg-light text-secondary border ms-2" style="font-size: 0.72rem;">Stok: ' + escape(stockVal) + ' ' + escape(unitVal) + '</span>' +
                        '</div>';
                }
            }
        });

        prodTsInstances[curIdx] = ts;
        populateProdTsOptions(ts, defaultProdId);

        // Event saat produk dipilih
        ts.on('change', function(val) {
            const stockBadge = document.getElementById('prod_stock_badge_' + curIdx);
            const satuanCell = document.getElementById('prod_satuan_' + curIdx);
            const opt = val ? ts.options[val] : null;

            if (opt) {
                if (stockBadge) stockBadge.textContent = (opt.nqty !== undefined ? opt.nqty : '-') + ' ' + (opt.csatuan || 'PCS');
                if (satuanCell) satuanCell.textContent = opt.csatuan || 'PCS';

                // Update opsi target produk pada tabel bahan
                refreshMaterialParentDropdowns();

                // Otomatis muat bahan dari resep jika tersedia
                loadRecipeMaterialsForProduct(tempId, val, parseFloat(qtyInput.value) || 1);
            } else {
                if (stockBadge) stockBadge.textContent = '-';
                if (satuanCell) satuanCell.textContent = 'PCS';
                refreshMaterialParentDropdowns();
            }
            calculateProductionModalTotals();
        });

        // Event Stepper Qty Produk
        btnMinus.addEventListener('click', function() {
            let v = parseFloat(qtyInput.value) || 0;
            v = Math.max(1, v - 1);
            qtyInput.value = v;
            calculateProductionModalTotals();
        });

        btnPlus.addEventListener('click', function() {
            let v = parseFloat(qtyInput.value) || 0;
            v = v + 1;
            qtyInput.value = v;
            calculateProductionModalTotals();
        });

        qtyInput.addEventListener('input', function() {
            calculateProductionModalTotals();
        });

        renumberProductionProductRows();
        refreshMaterialParentDropdowns();
        calculateProductionModalTotals();
    }

    function populateProdTsOptions(ts, selectedVal = '') {
        ts.clearOptions();
        const items = getFilteredProductionProducts();
        items.forEach(p => {
            ts.addOption({
                value: String(p.nid),
                text: p.cname,
                cname: p.cname,
                nqty: p.nqty,
                csatuan: p.csatuan || 'PCS',
                nprice_buy: p.nprice_buy || 0
            });
        });
        if (selectedVal) {
            ts.setValue(String(selectedVal), true);
        }
    }

    function removeProductionProductRow(rowId) {
        const tr = document.getElementById('prod-row-' + rowId);
        if (!tr) return;
        const tempId = tr.getAttribute('data-temp-id');

        if (prodTsInstances[rowId]) {
            try { prodTsInstances[rowId].destroy(); } catch (e) {}
            delete prodTsInstances[rowId];
        }
        tr.remove();

        // Hapus juga bahan baku yang terhubung dengan produk ini
        const matRows = document.querySelectorAll('#productionMaterialsTableBody tr');
        matRows.forEach(mr => {
            const parentSelect = mr.querySelector('.mat-parent-select');
            if (parentSelect && parentSelect.value === tempId) {
                const matRowId = mr.getAttribute('data-mat-id');
                removeProductionMaterialRow(matRowId);
            }
        });

        renumberProductionProductRows();
        refreshMaterialParentDropdowns();
        calculateProductionModalTotals();
    }

    function renumberProductionProductRows() {
        const rows = document.querySelectorAll('#productionProductsTableBody tr');
        rows.forEach((r, idx) => {
            const numCell = r.querySelector('.prod-row-number');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    // ==================== BAGIAN BAHAN BAKU ====================

    function addProductionMaterialRow(parentTempId = '', defaultMatId = '', defaultFinalQty = 1, isFromRecipe = false) {
        matRowCounter++;
        const curIdx = matRowCounter;
        const tbody = document.getElementById('productionMaterialsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'mat-row-' + curIdx;
        tr.setAttribute('data-mat-id', curIdx);
        if (isFromRecipe) tr.setAttribute('data-is-recipe', '1');

        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle mat-row-number py-2">${tbody.children.length + 1}</td>
            <td class="py-2 align-middle">
                <select name="materials[${curIdx}][nid_product]" class="form-select form-select-sm mat-item-select" id="mat_select_${curIdx}" data-mat="${curIdx}" required>
                    <option value="">-- Cari atau pilih bahan baku --</option>
                </select>
            </td>
            <td class="py-2 align-middle">
                <select name="materials[${curIdx}][product_temp_id]" class="form-select form-select-sm mat-parent-select" id="mat_parent_${curIdx}">
                    <!-- Pilihan produk hasil produksi -->
                </select>
            </td>
            <td class="py-2 text-center align-middle">
                <input type="number" step="any" min="0.001" name="materials[${curIdx}][nqty]" class="form-control form-control-sm mat-total-qty-input text-center fw-bold text-danger mx-auto" style="max-width: 120px;" value="${defaultFinalQty}" placeholder="1" required>
            </td>
            <td class="text-center py-2 align-middle text-muted mat-satuan-cell" id="mat_satuan_${curIdx}">
                PCS
            </td>
            <td class="text-center py-2 align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle" onclick="removeProductionMaterialRow(${curIdx})" title="Hapus Bahan" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const selectEl = tr.querySelector('.mat-item-select');
        const parentSelect = tr.querySelector('.mat-parent-select');

        // Populate parent dropdown
        populateMaterialParentSelect(parentSelect, parentTempId);

        // Init TomSelect bahan
        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: '-- Cari atau pilih bahan baku --',
            allowEmptyOption: true,
            sortField: { field: "text", direction: "asc" },
            render: {
                item: function(data, escape) {
                    const cleanName = data.cname || data.text || '';
                    return '<div class="text-truncate fw-medium">' + escape(cleanName) + '</div>';
                },
                option: function(data, escape) {
                    if (!data.value) return '<div class="text-muted small">' + escape(data.text || '') + '</div>';
                    const stockVal = data.nqty !== undefined ? data.nqty : '-';
                    const unitVal = data.csatuan || 'PCS';
                    return '<div class="d-flex justify-content-between align-items-center py-1">' +
                        '<span class="fw-medium text-dark">' + escape(data.cname || data.text) + '</span>' +
                        '<span class="badge bg-light text-secondary border ms-2" style="font-size: 0.72rem;">Stok: ' + escape(stockVal) + ' ' + escape(unitVal) + '</span>' +
                        '</div>';
                }
            }
        });

        matTsInstances[curIdx] = ts;
        populateProdTsOptions(ts, defaultMatId);

        ts.on('change', function(val) {
            const satuanCell = document.getElementById('mat_satuan_' + curIdx);
            const opt = val ? ts.options[val] : null;
            if (satuanCell) {
                satuanCell.textContent = opt ? (opt.csatuan || 'PCS') : 'PCS';
            }
        });

        renumberProductionMaterialRows();
        checkEmptyMaterialsNotice();
        calculateProductionModalTotals();
    }

    function removeProductionMaterialRow(matRowId) {
        const tr = document.getElementById('mat-row-' + matRowId);
        if (!tr) return;

        if (matTsInstances[matRowId]) {
            try { matTsInstances[matRowId].destroy(); } catch (e) {}
            delete matTsInstances[matRowId];
        }
        tr.remove();

        renumberProductionMaterialRows();
        checkEmptyMaterialsNotice();
        calculateProductionModalTotals();
    }

    function renumberProductionMaterialRows() {
        const rows = document.querySelectorAll('#productionMaterialsTableBody tr');
        rows.forEach((r, idx) => {
            const numCell = r.querySelector('.mat-row-number');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    function checkEmptyMaterialsNotice() {
        const rows = document.querySelectorAll('#productionMaterialsTableBody tr');
        const notice = document.getElementById('emptyMatNotice');
        if (notice) {
            notice.style.display = rows.length === 0 ? 'block' : 'none';
        }
    }

    // Refresh opsi dropdown "UNTUK PRODUK" di seluruh baris bahan
    function refreshMaterialParentDropdowns() {
        const matRows = document.querySelectorAll('#productionMaterialsTableBody tr');
        matRows.forEach(mr => {
            const parentSelect = mr.querySelector('.mat-parent-select');
            if (parentSelect) {
                const curVal = parentSelect.value;
                populateMaterialParentSelect(parentSelect, curVal);
            }
        });
    }

    function populateMaterialParentSelect(selectEl, selectedTempId = '') {
        const prodRows = document.querySelectorAll('#productionProductsTableBody tr');
        selectEl.innerHTML = '';

        if (prodRows.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Belum ada produk --';
            selectEl.appendChild(opt);
            return;
        }

        prodRows.forEach(pr => {
            const tempId = pr.getAttribute('data-temp-id');
            const rowId = pr.getAttribute('data-row-id');
            const ts = prodTsInstances[rowId];
            let prodName = 'Produk #' + pr.querySelector('.prod-row-number')?.textContent;
            if (ts && ts.getValue()) {
                const optData = ts.options[ts.getValue()];
                if (optData) prodName = optData.cname || optData.text;
            }

            const opt = document.createElement('option');
            opt.value = tempId;
            opt.textContent = prodName;
            if (selectedTempId && selectedTempId === tempId) {
                opt.selected = true;
            }
            selectEl.appendChild(opt);
        });

        if (!selectEl.value && selectEl.children.length > 0) {
            selectEl.children[0].selected = true;
        }
    }

    // Otomatis muat bahan resep saat produk dipilih
    function loadRecipeMaterialsForProduct(tempId, productId, parentQty) {
        if (!productId || !rawProdRecipes[productId]) return;

        const recipes = rawProdRecipes[productId];
        if (Array.isArray(recipes) && recipes.length > 0) {
            // Hapus bahan resep lama untuk produk ini jika ada
            const existingMatRows = document.querySelectorAll('#productionMaterialsTableBody tr');
            existingMatRows.forEach(mr => {
                const pSelect = mr.querySelector('.mat-parent-select');
                if (pSelect && pSelect.value === tempId && mr.getAttribute('data-is-recipe') === '1') {
                    removeProductionMaterialRow(mr.getAttribute('data-mat-id'));
                }
            });

            recipes.forEach(rc => {
                const finalQty = parseFloat(((parseFloat(rc.nqty) || 1) * (parentQty || 1)).toFixed(4));
                addProductionMaterialRow(tempId, rc.nid_ingredient, finalQty, true);
            });
        }
    }

    function calculateProductionModalTotals() {
        const prodRows = document.querySelectorAll('#productionProductsTableBody tr');
        let totalProdQty = 0;
        prodRows.forEach(pr => {
            const inp = pr.querySelector('.prod-qty-input');
            if (inp) totalProdQty += parseFloat(inp.value) || 0;
        });

        const matRows = document.querySelectorAll('#productionMaterialsTableBody tr');

        const totalQtyEl = document.getElementById('prodModalTotalQty');
        const totalMatEl = document.getElementById('prodModalTotalMatCount');

        if (totalQtyEl) totalQtyEl.textContent = totalProdQty.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        if (totalMatEl) totalMatEl.textContent = matRows.length;
    }

    // Inisialisasi Event Listener Modal
    document.addEventListener('DOMContentLoaded', function() {
        const addProdBtn = document.getElementById('btnAddProdRow');
        if (addProdBtn) {
            addProdBtn.addEventListener('click', function() {
                addProductionProductRow();
            });
        }

        const addMatBtn = document.getElementById('btnAddMatRow');
        if (addMatBtn) {
            addMatBtn.addEventListener('click', function() {
                // Pilih produk pertama sebagai default parent jika ada
                const firstProdRow = document.querySelector('#productionProductsTableBody tr');
                const defaultParentTempId = firstProdRow ? firstProdRow.getAttribute('data-temp-id') : '';
                addProductionMaterialRow(defaultParentTempId);
            });
        }

        // Auto tambah 1 baris saat modal dibuka jika kosong
        const modalEl = document.getElementById('modalTambahProduksi');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function() {
                const tbody = document.getElementById('productionProductsTableBody');
                if (tbody && tbody.children.length === 0) {
                    addProductionProductRow();
                }
            });
        }

        // Outlet di modal berubah: refresh semua opsi TomSelect
        const outletSelect = document.getElementById('prodModalOutlet');
        if (outletSelect) {
            outletSelect.addEventListener('change', function() {
                Object.keys(prodTsInstances).forEach(rowId => {
                    const ts = prodTsInstances[rowId];
                    if (ts) populateProdTsOptions(ts, ts.getValue());
                });
                Object.keys(matTsInstances).forEach(rowId => {
                    const ts = matTsInstances[rowId];
                    if (ts) populateProdTsOptions(ts, ts.getValue());
                });
            });
        }

        // Validasi Submit Form
        const formEl = document.getElementById('formCreateProduction');
        if (formEl) {
            formEl.addEventListener('submit', function(e) {
                const prodRows = document.querySelectorAll('#productionProductsTableBody tr');
                if (prodRows.length === 0) {
                    e.preventDefault();
                    alert('Silakan tambahkan minimal 1 produk hasil produksi.');
                    return;
                }

                let hasEmptyProduct = false;
                prodRows.forEach(pr => {
                    const rowId = pr.getAttribute('data-row-id');
                    const ts = prodTsInstances[rowId];
                    if (!ts || !ts.getValue()) {
                        hasEmptyProduct = true;
                    }
                });

                if (hasEmptyProduct) {
                    e.preventDefault();
                    alert('Mohon pilih produk jadi untuk setiap baris produk hasil produksi.');
                    return;
                }

                const matRows = document.querySelectorAll('#productionMaterialsTableBody tr');
                let hasEmptyMaterial = false;
                matRows.forEach(mr => {
                    const matId = mr.getAttribute('data-mat-id');
                    const ts = matTsInstances[matId];
                    if (!ts || !ts.getValue()) {
                        hasEmptyMaterial = true;
                    }
                });

                if (hasEmptyMaterial) {
                    e.preventDefault();
                    alert('Mohon pilih item bahan baku untuk setiap baris bahan yang ditambahkan, atau hapus baris yang kosong.');
                    return;
                }
            });
        }
    });
</script>
