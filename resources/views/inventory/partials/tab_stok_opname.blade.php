<!-- Baris Atas: Judul di kiri, Filter Tanggal Chip & Tombol Tambah di kanan -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
    <div>
        <h5 class="fw-bold mb-1 text-dark">Opname Stok</h5>
        <div class="text-muted small">{{ $totalStockOpname ?? ($stockOpnames ? $stockOpnames->total() : 0) }} Opname Stok</div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Filter Tanggal Chip (Persis Tab Stok Masuk & Stok Keluar, di sebelah kiri tombol Tambah) -->
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
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_opname', 'start_date' => $today, 'end_date' => $today])) }}">
                    Hari Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_opname', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                    Kemarin
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_opname', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                    7 Hari Lalu
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_opname', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                    Bulan Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_opname', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                    Bulan Lalu
                </a>

                <hr class="dropdown-divider my-2">
                <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                    <input type="hidden" name="tab" value="stok_opname">
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

        <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahOpname">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah</span>
        </button>
    </div>
</div>

<!-- Tabel Daftar Stok Opname Sesuai Layout Referensi Stok Masuk/Keluar -->
<div class="table-responsive border rounded-2 mb-0">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light border-bottom">
            <tr class="text-secondary small fw-semibold">
                <th style="width: 22%;" class="ps-3 py-3">No. <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 14%;" class="py-3 text-center">Tanggal <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 24%;" class="py-3 text-center">Catatan <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 12%;" class="text-center py-3">Status <i class="bi bi-arrow-down-up small text-muted"></i></th>
                <th style="width: 18%;" class="py-3 text-center">Diterima Oleh <i class="bi bi-arrow-down-up small text-muted"></i></th>
            </tr>
        </thead>
        <tbody class="small">
            @forelse($stockOpnames as $op)
            <tr class="border-bottom">
                <td class="ps-3 py-3">
                    <a href="{{ route('inventory.stock-opname.show', $op->nid) }}" class="fw-semibold text-primary text-decoration-none">
                        {{ $op->cno_opname }}
                    </a>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">
                        {{ $op->outlet?->cname ?: 'Outlet #' . $op->nid_outlet }} &bull; {{ $op->details_count }} Item
                    </div>
                </td>
                <td class="py-3 text-center">
                    <div class="text-dark fw-medium">{{ \Carbon\Carbon::parse($op->dopname)->format('d-M-Y') }}</div>
                </td>
                <td class="py-3 text-center">
                    <div class="text-secondary text-truncate" style="max-width: 320px;">{{ $op->cnote ?: '-' }}</div>
                </td>
                <td class="py-3 text-center">
                    @if($op->isPosted())
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-normal">Posted</span>
                    @elseif($op->isDraft())
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-normal">Draft</span>
                    @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-normal">Cancelled</span>
                    @endif
                </td>
                <td class="py-3 text-center">
                    <div class="text-secondary">
                        {{ $op->user?->email ?: ($op->user?->cname ?: 'Admin') }}
                    </div>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
                    <p class="mb-0">Belum ada dokumen Stok Opname yang ditemukan.</p>
                    <button type="button" class="btn btn-primary btn-sm mt-3 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahOpname">
                        <i class="bi bi-plus-lg me-1"></i> Mulai Stok Opname
                    </button>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($stockOpnames && $stockOpnames->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3 pt-2">
    <div class="text-muted small">
        Menampilkan {{ $stockOpnames->firstItem() }} sampai {{ $stockOpnames->lastItem() }} dari {{ $stockOpnames->total() }} data
    </div>
    <div>
        {{ $stockOpnames->links() }}
    </div>
</div>
@endif

<!-- Modal Stok Opname Baru (Fit & Clean Layout seperti Pembelian Baru) -->
<div class="modal fade" id="modalTambahOpname" tabindex="-1" aria-labelledby="modalTambahOpnameLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 980px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-opname.store') }}" method="POST" id="formCreateStockOpname">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTambahOpnameLabel">
                        <i class="bi bi-clipboard-check me-2 text-primary"></i>Stok Opname Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Form Header Info (Baris 1: Outlet, Tanggal Opname) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Outlet Tujuan Stok <span class="text-danger">*</span></label>
                            <select name="nid_outlet" id="opnameModalOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
                                @foreach($outlets as $ot)
                                <option value="{{ $ot->nid }}" {{ (string)$selectedOutletId === (string)$ot->nid ? 'selected' : '' }}>
                                    {{ $ot->cname }}
                                </option>
                                @endforeach
                            </select>
                            @if($isOutletLocked)
                            <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Opname <span class="text-danger">*</span></label>
                            <input type="date" name="dopname" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <!-- Baris 2: Catatan Opname -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Opname</label>
                            <textarea name="cnote" class="form-control" rows="2" style="min-height: 60px;" placeholder="Contoh: Stock opname bulanan Tulungagung..."></textarea>
                        </div>
                    </div>

                    <!-- Header Section Item Stok Opname -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-box-seam me-1 text-primary"></i> Item Stok Opname
                            </span>
                            <span class="text-muted small ms-2 d-none d-sm-inline">Sesuaikan kuantitas fisik aktual dengan stok yang ada pada sistem</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" id="btnOpnameAddRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    <!-- Table Item Dinamis -->
                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="opnameItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 35px;" class="text-center py-2 text-muted text-nowrap">No</th>
                                    <th style="width: 140px;" class="py-2 text-center text-nowrap">TIPE ITEM</th>
                                    <th class="py-2 text-center text-nowrap">PRODUK / BAHAN BAKU</th>
                                    <th style="width: 120px;" class="text-center py-2 text-nowrap">STOK SISTEM</th>
                                    <th style="width: 145px;" class="text-center py-2 text-nowrap">QTY AKTUAL</th>
                                    <th style="width: 110px;" class="text-center py-2 text-nowrap">SELISIH</th>
                                    <th style="width: 120px;" class="text-center py-2 text-nowrap">RAK / LOKASI</th>
                                    <th style="width: 35px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="opnameItemsTableBody" class="small">
                                <!-- Baris produk dinamis via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Baris Ringkasan Total (Flat & Rapi) -->
                    <!-- <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            <span>Setelah disimpan, Anda akan diarahkan ke halaman <strong>Detail Stok Opname</strong> untuk meninjau data sebelum diposting.</span>
                        </div>
                        <div class="d-flex align-items-center gap-4 text-end">
                            <div>
                                <span class="text-secondary small d-block">Total Item:</span>
                                <span class="fw-bold fs-6 text-dark" id="opnameTotalItemsCount">0</span> <span class="small text-muted">baris</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Total Selisih Fisik:</span>
                                <span class="fw-bold fs-6 text-primary" id="opnameTotalDiffQty">0 unit</span>
                            </div>
                        </div>
                    </div> -->
                </div>

                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Stok Opname
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const allOpnameProducts = @json($modalProducts ?? []);
        const opnameTsInstances = {};
        let opnameRowIdx = 0;

        function formatStockNumber(val) {
            const num = parseFloat(val || 0);
            if (Number.isNaN(num)) return '0';
            return Number.isInteger(num) ? String(num) : String(parseFloat(num.toFixed(2)));
        }

        function getSelectedOpnameOutletId() {
            const sel = document.getElementById('opnameModalOutlet');
            if (sel) return sel.value;
            return "{{ $selectedOutletId ?: ($outlets->first()?->nid ?? '') }}";
        }

        function normalizeOpnameType(val) {
            const raw = String(val || 'PRODUK').trim().toUpperCase();
            if (raw === 'SETENGAH_JADI') return 'SETENGAH_JADI';
            if (raw === 'BAHAN_BAKU' || raw === 'INGREDIENT') return 'BAHAN_BAKU';
            return 'PRODUK';
        }

        function getPlaceholderForOpnameType(type) {
            const norm = normalizeOpnameType(type);
            if (norm === 'BAHAN_BAKU') return '-- Cari atau pilih bahan baku --';
            if (norm === 'SETENGAH_JADI') return '-- Cari atau pilih setengah jadi --';
            return '-- Cari atau pilih produk --';
        }

        function getOpnameItemsForType(type, outletId) {
            const normType = normalizeOpnameType(type);

            const groupedByName = {};
            allOpnameProducts.forEach(p => {
                const pType = normalizeOpnameType(p.cproduct_type || 'PRODUK');
                if (pType !== normType) return;

                const key = String(p.cname || '').trim().toLowerCase();
                if (!key) return;
                if (!groupedByName[key]) groupedByName[key] = [];
                groupedByName[key].push(p);
            });

            const list = [];
            Object.keys(groupedByName).forEach(key => {
                const variants = groupedByName[key];
                const exactOutletMatch = outletId
                    ? variants.find(v => String(v.nid_outlet || '') === String(outletId))
                    : null;
                const globalMatch = variants.find(v => !v.nid_outlet);

                if (exactOutletMatch) {
                    list.push({
                        nid: exactOutletMatch.nid,
                        cname: exactOutletMatch.cname,
                        nstock: parseFloat(exactOutletMatch.nqty !== undefined && exactOutletMatch.nqty !== null ? exactOutletMatch.nqty : 0),
                        csatuan: exactOutletMatch.csatuan || 'PCS',
                        citem_type: normType
                    });
                } else if (globalMatch) {
                    list.push({
                        nid: globalMatch.nid,
                        cname: globalMatch.cname,
                        nstock: parseFloat(globalMatch.nqty !== undefined && globalMatch.nqty !== null ? globalMatch.nqty : 0),
                        csatuan: globalMatch.csatuan || 'PCS',
                        citem_type: normType
                    });
                } else {
                    const tmpl = variants[0];
                    list.push({
                        nid: tmpl.nid,
                        cname: tmpl.cname,
                        nstock: 0,
                        csatuan: tmpl.csatuan || 'PCS',
                        citem_type: normType
                    });
                }
            });

            list.sort((a, b) => a.cname.localeCompare(b.cname));
            return list;
        }

        function addOpnameRow(itemId = '', actualQty = null, rack = '', itemType = 'PRODUK') {
            const tbody = document.getElementById('opnameItemsTableBody');
            if (!tbody) return;

            const normType = normalizeOpnameType(itemType);
            const placeholderText = getPlaceholderForOpnameType(normType);
            const curIdx = opnameRowIdx++;

            const tr = document.createElement('tr');
            tr.id = `opname-row-${curIdx}`;
            tr.setAttribute('data-row-id', curIdx);
            tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle opname-row-number py-2"></td>
            <td class="py-2">
                <select name="items[${curIdx}][citem_type]" class="form-select form-select-sm fw-semibold opname-type-select" data-row="${curIdx}">
                    <option value="PRODUK" ${normType === 'PRODUK' ? 'selected' : ''}>Produk</option>
                    <option value="SETENGAH_JADI" ${normType === 'SETENGAH_JADI' ? 'selected' : ''}>Setengah Jadi</option>
                    <option value="BAHAN_BAKU" ${normType === 'BAHAN_BAKU' ? 'selected' : ''}>Bahan Baku</option>
                </select>
            </td>
            <td class="py-2">
                <select name="items[${curIdx}][item_id]" class="form-select form-select-sm opname-item-select" id="opname_item_${curIdx}" data-row="${curIdx}" required>
                    <option value="">${placeholderText}</option>
                </select>
            </td>
            <td class="text-center py-2 align-middle">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 opname-stock-badge fw-medium" id="opname_stock_badge_${curIdx}">-</span>
            </td>
            <td class="py-2 text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus opname-btn-minus" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" step="any" min="0" name="items[${curIdx}][nqty_actual]" id="opname_qty_actual_${curIdx}" class="qty-stepper-input opname-actual-input" value="${actualQty !== null ? actualQty : ''}" placeholder="0" required>
                    <button type="button" class="qty-stepper-btn qty-stepper-plus opname-btn-plus" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="text-center py-2 align-middle">
                <span class="badge bg-light text-secondary border px-2 py-1 opname-diff-badge" id="opname_diff_badge_${curIdx}">0</span>
            </td>
            <td class="py-2">
                <input type="text" name="items[${curIdx}][crack]" class="form-control form-control-sm" value="${rack}" placeholder="Rak...">
            </td>
            <td class="text-center py-2 align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle opname-btn-delete" title="Hapus Baris" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

            tbody.appendChild(tr);
            recalcOpnameRowNumbers();

            const typeSelect = tr.querySelector('.opname-type-select');
            const selectEl = tr.querySelector('.opname-item-select');
            const actualInput = tr.querySelector('.opname-actual-input');
            const deleteBtn = tr.querySelector('.opname-btn-delete');
            const stockBadge = tr.querySelector('.opname-stock-badge');

            // Inisialisasi TomSelect untuk live search dan badge stok
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
                        const stockLabel = stockVal === '-' ? '-' : `${formatStockNumber(stockVal)} ${unitVal}`;
                        const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                        return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                            '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                            '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                            '</div>';
                    }
                }
            });
            opnameTsInstances[curIdx] = ts;

            function populateTsOptions(rowType, keepSelectedId = '') {
                const list = getOpnameItemsForType(rowType, getSelectedOpnameOutletId());
                const ph = getPlaceholderForOpnameType(rowType);

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
                        csatuan: item.csatuan || 'PCS'
                    });
                });
                ts.refreshOptions(false);

                if (keepSelectedId && ts.options[String(keepSelectedId)]) {
                    ts.setValue(String(keepSelectedId));
                } else {
                    stockBadge.innerText = '-';
                    if (!itemId) {
                        actualInput.value = '';
                    }
                    updateOpnameRowDiff(curIdx);
                }
            }

            // Event listener TomSelect saat produk dipilih
            ts.on('change', function(val) {
                const optData = ts.options[val];
                if (optData && val) {
                    const stock = parseFloat((optData.nstock !== undefined && optData.nstock !== null) ? optData.nstock : 0);
                    const unit = optData.csatuan || 'PCS';
                    stockBadge.innerText = `${formatStockNumber(stock)} ${unit}`;
                    if (actualInput.value === '' || actualInput.dataset.userEdited !== 'true') {
                        actualInput.value = formatStockNumber(stock);
                    }
                } else {
                    stockBadge.innerText = '-';
                }
                updateOpnameRowDiff(curIdx);
            });

            // Tandai jika user mengedit input Qty Aktual secara manual
            actualInput.addEventListener('input', function() {
                actualInput.dataset.userEdited = 'true';
                updateOpnameRowDiff(curIdx);
            });

            // Tombol minus dan plus untuk Qty Aktual
            const btnMinus = tr.querySelector('.opname-btn-minus');
            const btnPlus = tr.querySelector('.opname-btn-plus');

            if (btnMinus) {
                btnMinus.addEventListener('click', function() {
                    let val = parseFloat(actualInput.value) || 0;
                    val = Math.max(0, val - 1);
                    actualInput.value = val;
                    actualInput.dataset.userEdited = 'true';
                    updateOpnameRowDiff(curIdx);
                });
            }

            if (btnPlus) {
                btnPlus.addEventListener('click', function() {
                    let val = parseFloat(actualInput.value) || 0;
                    val += 1;
                    actualInput.value = val;
                    actualInput.dataset.userEdited = 'true';
                    updateOpnameRowDiff(curIdx);
                });
            }

            // Event listener saat ganti tipe item
            typeSelect.addEventListener('change', function() {
                populateTsOptions(this.value);
            });

            // Event listener hapus baris
            deleteBtn.addEventListener('click', function() {
                if (opnameTsInstances[curIdx]) {
                    opnameTsInstances[curIdx].destroy();
                    delete opnameTsInstances[curIdx];
                }
                tr.remove();
                recalcOpnameRowNumbers();
                recalcOpnameSummary();
            });

            // PENTING: Langsung isi options saat baris dibuat!
            populateTsOptions(normType, itemId);
        }

        function updateOpnameRowDiff(rowIdx) {
            const tr = document.getElementById(`opname-row-${rowIdx}`);
            if (!tr) return;

            const ts = opnameTsInstances[rowIdx];
            const val = ts ? ts.getValue() : null;
            const optData = (ts && val) ? ts.options[val] : null;
            const systemStock = optData ? parseFloat(optData.nstock || 0) : 0;

            const actualInput = tr.querySelector('.opname-actual-input');
            const actualQty = actualInput && actualInput.value !== '' ? parseFloat(actualInput.value) : systemStock;

            const diff = actualQty - systemStock;
            const badge = tr.querySelector('.opname-diff-badge');

            if (badge) {
                if (!val || !optData) {
                    badge.className = 'badge bg-light text-secondary border px-2 py-1 opname-diff-badge';
                    badge.innerText = '0';
                } else if (diff > 0) {
                    badge.className = 'badge bg-success-subtle text-success fw-bold px-2 py-1 opname-diff-badge';
                    badge.innerText = `+${formatStockNumber(diff)}`;
                } else if (diff < 0) {
                    badge.className = 'badge bg-danger-subtle text-danger fw-bold px-2 py-1 opname-diff-badge';
                    badge.innerText = `${formatStockNumber(diff)}`;
                } else {
                    badge.className = 'badge bg-light text-secondary border px-2 py-1 opname-diff-badge';
                    badge.innerText = '0';
                }
            }

            recalcOpnameSummary();
        }

        function recalcOpnameRowNumbers() {
            const rows = document.querySelectorAll('#opnameItemsTableBody tr');
            rows.forEach((row, idx) => {
                const numTd = row.querySelector('.opname-row-number');
                if (numTd) numTd.innerText = idx + 1;
            });
            const totalCountEl = document.getElementById('opnameTotalItemsCount');
            if (totalCountEl) totalCountEl.innerText = rows.length;
        }

        function recalcOpnameSummary() {
            const rows = document.querySelectorAll('#opnameItemsTableBody tr');
            let totalDiff = 0;

            rows.forEach(tr => {
                const rowIdx = tr.getAttribute('data-row-id');
                const ts = opnameTsInstances[rowIdx];
                const val = ts ? ts.getValue() : null;
                const optData = (ts && val) ? ts.options[val] : null;

                if (optData && val) {
                    const systemStock = parseFloat(optData.nstock || 0);
                    const actualInput = tr.querySelector('.opname-actual-input');
                    const actualQty = actualInput && actualInput.value !== '' ? parseFloat(actualInput.value) : systemStock;
                    totalDiff += (actualQty - systemStock);
                }
            });

            const diffEl = document.getElementById('opnameTotalDiffQty');
            if (diffEl) {
                if (totalDiff > 0) {
                    diffEl.className = 'fw-bold fs-6 text-success';
                    diffEl.innerText = `+${formatStockNumber(totalDiff)} unit`;
                } else if (totalDiff < 0) {
                    diffEl.className = 'fw-bold fs-6 text-danger';
                    diffEl.innerText = `${formatStockNumber(totalDiff)} unit`;
                } else {
                    diffEl.className = 'fw-bold fs-6 text-secondary';
                    diffEl.innerText = `0 unit`;
                }
            }
        }

        // Tombol Tambah Baris
        const btnAdd = document.getElementById('btnOpnameAddRow');
        if (btnAdd) {
            btnAdd.addEventListener('click', function() {
                addOpnameRow();
            });
        }

        // Buka modal: jika baris kosong, auto-create 1 baris
        const opnameModalEl = document.getElementById('modalTambahOpname');
        if (opnameModalEl) {
            opnameModalEl.addEventListener('show.bs.modal', function() {
                const tbody = document.getElementById('opnameItemsTableBody');
                if (tbody && tbody.children.length === 0) {
                    addOpnameRow();
                }
            });
        }

        // Ganti outlet di modal: refresh seluruh baris
        const outletSelect = document.getElementById('opnameModalOutlet');
        if (outletSelect) {
            outletSelect.addEventListener('change', function() {
                const tbody = document.getElementById('opnameItemsTableBody');
                if (tbody) {
                    Object.values(opnameTsInstances).forEach(ts => ts.destroy());
                    tbody.innerHTML = '';
                    addOpnameRow();
                }
            });
        }
    });
</script>

