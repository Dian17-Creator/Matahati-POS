        <!-- Baris Atas: Judul di kiri, Export/Import/Tambah di kanan -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
            <div>
                <h5 class="fw-bold mb-1 text-dark">Daftar Stok Masuk</h5>
                <div class="text-muted small">{{ $totalStockIn }} Stok Masuk</div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
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
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $today, 'end_date' => $today])) }}">
                            Hari Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                            Kemarin
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                            7 Hari Lalu
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                            Bulan Ini
                        </a>
                        <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'stok_masuk', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                            Bulan Lalu
                        </a>

                        <hr class="dropdown-divider my-2">
                        <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                        <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                            <input type="hidden" name="tab" value="stok_masuk">
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

                <!-- <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="alert('Fitur export data stok masuk segera aktif.')">
                    <i class="bi bi-download me-1"></i> Export
                </button>

                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="alert('Fitur import stok masuk segera aktif.')">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Import
                </button> -->

                <!-- Tombol Import PDF (Persis Pembelian) -->
                <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#importStockInPdfModal">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Import PDF
                </button>

                <!-- Tombol + Tambah langsung membuka Modal Tambah Stok Masuk -->
                <button type="button" class="btn btn-success btn-sm px-3 shadow-sm fw-semibold" onclick="openStockInModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>

                {{-- Terima dari Pembelian di-comment karena pembelian otomatis masuk ke stok masuk
                <div class="dropdown">
                    <button class="btn btn-success btn-sm px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg me-1"></i> Tambah
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="#" data-bs-toggle="modal" data-bs-target="#modalTerimaPembelian">
                                <i class="bi bi-receipt-cutoff me-2 text-primary"></i> Terima dari Pembelian
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <a class="dropdown-item py-2 px-3 fw-semibold text-secondary" href="javascript:void(0)" onclick="openStockInModal()">
                                <i class="bi bi-box-arrow-in-down me-2 text-success"></i> Tambah Stok Masuk
                            </a>
                        </li>
                    </ul>
                </div>
                --}}
            </div>
        </div>

        <!-- Baris Filter: Per Page & Search -->
        <!-- <div class="row align-items-center mb-3 g-2">
            <div class="col-auto">
                <form method="GET" action="{{ route('inventory.index') }}" class="d-inline">
                    <input type="hidden" name="tab" value="stok_masuk">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @if($selectedOutletId)
                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    @endif
                    @if($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <select name="per_page" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Baris</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 Baris</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Baris</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Baris</option>
                    </select>
                </form>
            </div>
            <div class="col-12 col-md-4 ms-auto">
                <form method="GET" action="{{ route('inventory.index') }}">
                    <input type="hidden" name="tab" value="stok_masuk">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    @if($selectedOutletId)
                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                    @endif
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari No. Stok Masuk / Catatan..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div> -->

        <!-- Tabel Daftar Stok Masuk Sesuai Layout Referensi Olsera -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary small fw-semibold">
                        <th class="px-3 py-3" style="width: 18%;">No. <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3" style="width: 14%;">Diterima dari <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3" style="width: 14%;">Supplier <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3" style="width: 12%;">Tanggal <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3" style="width: 18%;">Catatan <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3 text-center" style="width: 10%;">Status <i class="bi bi-arrow-down-up small text-muted"></i></th>
                        <th class="px-3 py-3" style="width: 14%;">Diterima Oleh <i class="bi bi-arrow-down-up small text-muted"></i></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockIns as $item)
                    <tr>
                        <td class="px-3 py-3">
                            <a href="{{ route('inventory.stock-in.show', $item->cno_stock_in) }}" class="fw-semibold text-primary text-decoration-none">
                                {{ $item->cno_stock_in }}
                            </a>
                        </td>
                        <td class="px-3 py-3 text-muted small">
                            @if(!empty($item->targetOutlet))
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                <i class="bi bi-shop me-1"></i>{{ $item->targetOutlet->cname }}
                            </span>
                            @else
                            {{ $item->outlet?->cname ?? '-' }}
                            @endif
                        </td>
                        <td class="px-3 py-3 fw-medium text-dark small">
                            {{ $item->supplier?->cname ?? (str_starts_with($item->creason ?? '', 'Penerimaan Supplier: ') ? str_replace('Penerimaan Supplier: ', '', $item->creason) : ($item->creason ?: '-')) }}
                        </td>
                        <td class="px-3 py-3 text-secondary small">
                            {{ $item->dstock_in ? \Carbon\Carbon::parse($item->dstock_in)->format('d-M-Y') : '-' }}
                        </td>
                        <td class="px-3 py-3 text-muted small">
                            {{ $item->cnote ?? '-' }}
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if(strtoupper($item->cstatus ?? '') === 'POSTED')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small rounded-pill">Posted</span>
                            @elseif(strtoupper($item->cstatus ?? '') === 'DRAFT')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 small rounded-pill">Draft</span>
                            @elseif(in_array(strtoupper($item->cstatus ?? ''), ['CANCELLED', 'VOID']))
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small rounded-pill">Cancelled</span>
                            @else
                            <span class="badge bg-secondary px-2 py-1 small rounded-pill">{{ $item->cstatus ?? 'Posted' }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-secondary small text-truncate" style="max-width: 180px;" title="{{ $item->creceived_by ?? ($item->user?->email ?? '-') }}">
                            {{ $item->creceived_by ?? ($item->user?->email ?? '-') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada riwayat stok masuk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Menampilkan {{ $stockIns->firstItem() ?? 0 }} - {{ $stockIns->lastItem() ?? 0 }} dari {{ $stockIns->total() }} stok masuk
            </div>
            <div>
                {{ $stockIns->links() }}
            </div>
        </div>

        <!-- ==================== MODAL IMPORT STOK MASUK DARI PDF ==================== -->
        <div class="modal fade" id="importStockInPdfModal" tabindex="-1" aria-labelledby="importStockInPdfModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light-subtle py-3 border-bottom">
                        <h5 class="modal-title fw-bold" id="importStockInPdfModalLabel">
                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Import Stok Masuk dari PDF
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('inventory.stock-in.import-pdf') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="alert alert-light border small text-muted mb-3 d-flex align-items-start">
                                <i class="bi bi-info-circle text-primary me-2 mt-1 fs-6"></i>
                                <div>
                                    Unggah dokumen invoice / stok masuk dalam format PDF (misal: Olsera/IQOS). Sistem otomatis membaca nomor dokumen, tanggal, mencocokkan produk, stok, dan harga beli satuan.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="import_stock_in_supplier_id" class="form-label fw-semibold small">Pilih Supplier <span class="text-danger">*</span></label>
                                <select class="form-select @error('nid_supplier') is-invalid @enderror" id="import_stock_in_supplier_id" name="nid_supplier" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    @if(isset($suppliers))
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->nid }}" data-outlet-id="{{ $s->nid_outlet }}" {{ (string)old('nid_supplier') === (string)$s->nid ? 'selected' : '' }}>
                                        {{ $s->cname }}@if(empty($selectedOutletId) && $s->outlet) ({{ $s->outlet->cname }})@endif
                                    </option>
                                    @endforeach
                                    @endif
                                </select>
                                @error('nid_supplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="import_stock_in_outlet_id" class="form-label fw-semibold small">Outlet Tujuan Stok <span class="text-danger">*</span></label>
                                @if($isOutletLocked || !empty($selectedOutletId))
                                @php
                                $lockedOutlet = $outlets->firstWhere('nid', $selectedOutletId);
                                @endphp
                                <input type="text" class="form-control bg-light" value="{{ $lockedOutlet ? $lockedOutlet->cname : 'Outlet Terkunci' }}" readonly>
                                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                                @else
                                <select class="form-select @error('nid_outlet') is-invalid @enderror" id="import_stock_in_outlet_id" name="nid_outlet" required>
                                    <option value="">-- Pilih Outlet --</option>
                                    @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->nid }}" {{ (string)(old('nid_outlet') ?? $selectedOutletId) === (string)$outlet->nid ? 'selected' : '' }}>
                                        {{ $outlet->cname }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                                @error('nid_outlet')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="import_stock_in_pdf_file" class="form-label fw-semibold small">File PDF (.pdf) <span class="text-danger">*</span></label>
                                <input class="form-control @error('pdf_file') is-invalid @enderror" type="file" id="import_stock_in_pdf_file" name="pdf_file" accept=".pdf,application/pdf" required>
                                @error('pdf_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light-subtle py-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger btn-sm px-3">
                                <i class="bi bi-upload me-1"></i> Mulai Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL: Terima dari Pembelian di-comment sementara karena pembelian otomatis masuk ke stok masuk
        <div class="modal fade" id="modalTerimaPembelian" tabindex="-1" aria-labelledby="modalTerimaPembelianLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalTerimaPembelianLabel">Terima dari Pembelian</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Input Pencarian PO / Supplier -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchPurchaseInput" class="form-control border-start-0" placeholder="Cari No. Pembelian / Nama Supplier..." onkeyup="filterPendingPurchases()">
                            </div>
                        </div>

                        <!-- Tabel List PO Pending -->
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle border mb-0" id="tablePendingPurchases">
                                <thead class="table-light sticky-top">
                                    <tr class="text-secondary small">
                                        <th>No. Pembelian</th>
                                        <th>Nama Supplier</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center">Tgl. Beli</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingPurchases as $po)
                                    <tr class="purchase-row">
                                        <td class="fw-semibold text-dark po-number">{{ $po->cno_purchase }}</td>
        <td class="text-dark po-supplier">{{ $po->supplier?->cname ?? '-' }}</td>
        <td class="text-end fw-semibold text-secondary">
            IDR {{ number_format($po->ntotal_amount, 2, ',', '.') }}
        </td>
        <td class="text-center text-secondary small">
            {{ $po->dpurchase ? \Carbon\Carbon::parse($po->dpurchase)->format('d-M-Y') : '-' }}
        </td>
        <td class="text-center">
            <form method="POST" action="{{ route('inventory.stock-in.receive-purchase', $po->nid) }}" onsubmit="return confirm('Apakah Anda yakin ingin menerima stok dari pesanan pembelian {{ $po->cno_purchase }}?');">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                    Terima
                </button>
            </form>
        </td>
        </tr>
        @empty
        <tr id="emptyPurchaseRow">
            <td colspan="5" class="text-center py-4 text-muted">
                <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                Tidak ada pesanan pembelian pending yang perlu diterima.
            </td>
        </tr>
        @endforelse
        </tbody>
        </table>
        </div>
        </div>
        </div>
        </div>
        </div>

        <script>
            function filterPendingPurchases() {
                const input = document.getElementById('searchPurchaseInput');
                const filter = input.value.toLowerCase().trim();
                const table = document.getElementById('tablePendingPurchases');
                const rows = table.getElementsByClassName('purchase-row');

                for (let i = 0; i < rows.length; i++) {
                    const poNum = rows[i].querySelector('.po-number')?.textContent.toLowerCase() || '';
                    const supplier = rows[i].querySelector('.po-supplier')?.textContent.toLowerCase() || '';

                    if (poNum.includes(filter) || supplier.includes(filter)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        </script>
        --}}

        <!-- ==================== MODAL TAMBAH STOK MASUK MANUAL ==================== -->
        <div class="modal fade" id="createStockInModal" tabindex="-1" aria-labelledby="createStockInModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1040px;">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('inventory.stock-in.store') }}" method="POST" id="createStockInForm">
                        @csrf
                        <div class="modal-header bg-light-subtle py-3 border-bottom">
                            <h5 class="modal-title fw-bold" id="createStockInModalLabel">
                                <i class="bi bi-box-arrow-in-down me-2 text-success"></i><span>Tambah Stok Masuk</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- Baris 1: Outlet Penerima, Tanggal, Alasan -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-secondary">Outlet Penerima <span class="text-danger">*</span></label>
                                    <select name="nid_outlet" id="modalStockInOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
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
                                    <label class="form-label fw-semibold small text-secondary">Tanggal Masuk <span class="text-danger">*</span></label>
                                    <input type="date" name="dstock_in" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-secondary">Alasan Penerimaan <span class="text-danger">*</span></label>
                                    <select name="creason" id="modalStockInReason" class="form-select">
                                        <option value="Koreksi Selisih Stok (Adjustment)">Koreksi Selisih Stok (Adjustment)</option>
                                        <option value="Saldo Awal">Saldo Awal</option>
                                        <option value="Temuan Barang Fisik">Temuan Barang Fisik</option>
                                        <option value="Bonus / Hadiah Supplier">Bonus / Hadiah Supplier</option>
                                        <option value="Pengembalian Manual">Pengembalian Manual</option>
                                        <option value="Lain-lain">Lain-lain</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Baris 2: Catatan Penerimaan -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-secondary">Catatan Penerimaan</label>
                                    <textarea name="cnote" id="modalStockInNote" class="form-control" rows="2" placeholder="Catatan stok masuk (misal: TAMBAHAN STOK TEMUAN OPNAME, SALDO AWAL TOKO, dll)..."></textarea>
                                </div>
                            </div>

                            <!-- Header Section Item Stok Masuk -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <div>
                                    <span class="fw-bold text-dark fs-6">
                                        Item Barang Masuk
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm px-3 fw-semibold shadow-sm" id="btnAddNewStockInRow">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                                </button>
                            </div>

                            <!-- Table Item Dinamis -->
                            <div class="table-responsive mt-2" style="overflow: visible;">
                                <table class="table align-middle mb-0" id="stockInItemsTable">
                                    <thead>
                                        <tr class="bg-light">
                                            <th style="width: 32px;" class="text-center py-2 text-muted text-nowrap">NO</th>
                                            <th style="width: 140px;" class="py-2 text-center text-nowrap">TIPE ITEM</th>
                                            <th class="py-2 text-center text-nowrap" style="min-width: 260px;">PRODUK / BAHAN BAKU</th>
                                            <th style="width: 105px;" class="text-center py-2 text-nowrap">STOK SEKARANG</th>
                                            <th style="width: 115px;" class="text-center py-2 text-nowrap">QTY MASUK</th>
                                            <th style="width: 130px;" class="text-center py-2 text-nowrap">HARGA BELI</th>
                                            <th style="width: 120px;" class="text-center py-2 text-nowrap">SUBTOTAL</th>
                                            <th style="width: 32px;" class="text-center py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="stockInItemsTableBody" class="small">
                                        <!-- Baris produk / bahan baku di-generate via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Baris Ringkasan Total Kuantitas & Nilai Masuk -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">

                                <div class="d-flex align-items-center gap-4 text-end ms-auto">
                                    <div>
                                        <span class="text-secondary small d-block">Total Kuantitas:</span>
                                        <span class="fw-bold fs-6 text-dark" id="stockInTotalQty">0</span> <span class="small text-muted">unit</span>
                                    </div>
                                    <div>
                                        <span class="text-secondary small d-block">Total Nilai Masuk:</span>
                                        <span class="fw-bold fs-5 text-success" id="stockInTotalAmount">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script type="application/json" id="stockInProductsData">
            @json($modalProducts)
        </script>
        <script>
            // Data Master Produk, Setengah Jadi & Bahan Baku dari mpos_product untuk Modal Stok Masuk
            const stockInProducts = JSON.parse(document.getElementById('stockInProductsData')?.textContent || '[]');

            let stockInRowCounter = 0;
            const stockInTsInstances = {};

            function formatStockInNumber(val) {
                const num = parseFloat(val || 0);
                if (Number.isNaN(num)) return '0';
                return Number.isInteger(num) ? String(num) : String(parseFloat(num.toFixed(2)));
            }

            function getSelectedStockInOutletId() {
                const sel = document.getElementById('modalStockInOutlet');
                if (sel) return sel.value;
                return "{{ $selectedOutletId ?: ($outlets->first()?->nid ?? '') }}";
            }

            function normalizeStockInItemType(val) {
                const raw = String(val || 'PRODUK').trim().toUpperCase();
                if (raw === 'SETENGAH_JADI') return 'SETENGAH_JADI';
                if (raw === 'BAHAN_BAKU' || raw === 'INGREDIENT') return 'BAHAN_BAKU';
                return 'PRODUK';
            }

            function getStockInPlaceholderForType(itemType) {
                const norm = normalizeStockInItemType(itemType);
                if (norm === 'BAHAN_BAKU') {
                    return '-- Cari atau pilih bahan baku --';
                }
                if (norm === 'SETENGAH_JADI') {
                    return '-- Cari atau pilih produk setengah jadi --';
                }
                return '-- Cari atau pilih produk --';
            }

            function getStockInItemsForType(type, outletId) {
                const targetType = normalizeStockInItemType(type);
                const byName = new Map();

                stockInProducts.forEach(p => {
                    const pType = normalizeStockInItemType(p.cproduct_type || 'PRODUK');
                    if (pType !== targetType) return;

                    const key = String(p.cname || '').trim().toUpperCase();
                    if (!key) return;
                    if (!byName.has(key)) {
                        byName.set(key, []);
                    }
                    byName.get(key).push(p);
                });

                const list = [];
                byName.forEach((prods) => {
                    const match = outletId ? prods.find(p => String(p.nid_outlet) === String(outletId)) : prods[0];
                    if (match) {
                        list.push({
                            nid: match.nid,
                            cname: match.cname,
                            nstock: parseFloat(match.nqty !== undefined && match.nqty !== null ? match.nqty : 0),
                            csatuan: match.csatuan || 'PCS',
                            ncost: parseFloat(match.nprice_buy !== undefined && match.nprice_buy !== null ? match.nprice_buy : 0),
                            cproduct_type: targetType
                        });
                    } else {
                        const tmpl = prods[0];
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

            function openStockInModal() {
                const modalEl = document.getElementById('createStockInModal');
                const modal = new bootstrap.Modal(modalEl);

                // Reset baris tabel jika kosong
                const tbody = document.getElementById('stockInItemsTableBody');
                if (tbody.children.length === 0) {
                    addStockInRow();
                }

                modal.show();
            }

            function addStockInRow(itemId = '', itemQty = 1, itemType = 'PRODUK') {
                stockInRowCounter++;
                const curIdx = stockInRowCounter;
                const tbody = document.getElementById('stockInItemsTableBody');
                const tr = document.createElement('tr');
                tr.id = 'stock-in-row-' + curIdx;
                tr.setAttribute('data-row-id', curIdx);

                const normalizedType = normalizeStockInItemType(itemType);
                const placeholderText = getStockInPlaceholderForType(normalizedType);

                tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle row-number py-2">${tbody.children.length + 1}</td>
            <td class="py-2 align-middle">
                <select name="items[${curIdx}][citem_type]" class="form-select form-select-sm fw-semibold in-item-type-select" data-row="${curIdx}">
                    <option value="PRODUK" ${normalizedType === 'PRODUK' ? 'selected' : ''}>Produk</option>
                    <option value="SETENGAH_JADI" ${normalizedType === 'SETENGAH_JADI' ? 'selected' : ''}>Setengah Jadi</option>
                    <option value="BAHAN_BAKU" ${normalizedType === 'BAHAN_BAKU' ? 'selected' : ''}>Bahan Baku</option>
                </select>
            </td>
            <td class="py-2 align-middle">
                <select name="items[${curIdx}][nid_item]" class="form-select form-select-sm in-item-select" id="stock_in_item_${curIdx}" data-row="${curIdx}">
                    <option value="">${placeholderText}</option>
                </select>
            </td>
            <td class="text-center py-2 align-middle">
                <span class="badge bg-light text-secondary border in-current-stock-badge" id="stock_in_badge_${curIdx}">-</span>
            </td>
            <td class="py-2 text-center align-middle">
                <div class="qty-stepper mx-auto">
                    <button type="button" class="qty-stepper-btn qty-stepper-minus in-btn-minus" title="Kurangi 1">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" step="any" min="0.01" name="items[${curIdx}][nqty]" class="qty-stepper-input in-item-qty-input no-spinners" value="${itemQty}" placeholder="1" required>
                    <button type="button" class="qty-stepper-btn qty-stepper-plus in-btn-plus" title="Tambah 1">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </td>
            <td class="py-2 align-middle">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted">Rp</span>
                    <input type="number" step="1" min="0" name="items[${curIdx}][ncost]" class="form-control form-control-sm text-end in-item-cost-input" value="0">
                </div>
            </td>
            <td class="text-end fw-semibold text-success align-middle in-item-subtotal-display">
                Rp 0
            </td>
            <td class="text-center py-2 align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle in-btn-delete" onclick="removeStockInRow(${curIdx})" title="Hapus Baris" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

                tbody.appendChild(tr);

                const selectEl = tr.querySelector('.in-item-select');
                const stockBadge = tr.querySelector('.in-current-stock-badge');
                const costInput = tr.querySelector('.in-item-cost-input');
                const qtyInput = tr.querySelector('.in-item-qty-input');
                const typeSelect = tr.querySelector('.in-item-type-select');
                const btnMinus = tr.querySelector('.in-btn-minus');
                const btnPlus = tr.querySelector('.in-btn-plus');

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
                            const stockLabel = stockVal === '-' ? '-' : `${formatStockInNumber(stockVal)} ${unitVal}`;
                            const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                            return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                                '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                                '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stockLabel) + '</span>' +
                                '</div>';
                        }
                    }
                });
                stockInTsInstances[curIdx] = ts;

                // Listener saat produk/bahan dipilih di TomSelect
                ts.on('change', function(val) {
                    const optData = ts.options[val];
                    if (optData && val) {
                        const stock = parseFloat((optData.nstock !== undefined && optData.nstock !== null) ? optData.nstock : 0);
                        const unit = optData.csatuan || 'PCS';
                        stockBadge.textContent = `${formatStockInNumber(stock)} ${unit}`;
                        if (stock <= 0) {
                            stockBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle in-current-stock-badge';
                        } else if (stock <= 5) {
                            stockBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle in-current-stock-badge';
                        } else {
                            stockBadge.className = 'badge bg-success-subtle text-success border border-success-subtle in-current-stock-badge';
                        }

                        const cost = parseFloat(optData.ncost || 0);
                        costInput.value = Math.round(cost);
                    } else {
                        stockBadge.textContent = '-';
                        stockBadge.className = 'badge bg-light text-secondary border in-current-stock-badge';
                        costInput.value = 0;
                    }
                    calculateStockInTotals();
                });

                // Event listener minus dan plus pada Qty Stepper
                if (btnMinus) {
                    btnMinus.addEventListener('click', function() {
                        let val = parseFloat(qtyInput.value) || 0;
                        val = Math.max(0, val - 1);
                        if (val === 0) val = 1;
                        qtyInput.value = val;
                        calculateStockInTotals();
                    });
                }

                if (btnPlus) {
                    btnPlus.addEventListener('click', function() {
                        let val = parseFloat(qtyInput.value) || 0;
                        qtyInput.value = val + 1;
                        calculateStockInTotals();
                    });
                }

                qtyInput.addEventListener('input', function() {
                    calculateStockInTotals();
                });

                costInput.addEventListener('input', function() {
                    calculateStockInTotals();
                });

                typeSelect.addEventListener('change', function() {
                    populateStockInTsOptions(curIdx, this.value);
                });

                populateStockInTsOptions(curIdx, normalizedType, itemId);
                renumberStockInRows();
                calculateStockInTotals();
            }

            function populateStockInTsOptions(rowId, rowType, keepSelectedId = '', keepSelectedName = '') {
                const ts = stockInTsInstances[rowId];
                if (!ts) return;

                const row = document.getElementById('stock-in-row-' + rowId);
                const stockBadge = row ? row.querySelector('.in-current-stock-badge') : null;
                const costInput = row ? row.querySelector('.in-item-cost-input') : null;

                const outletId = getSelectedStockInOutletId();
                const normType = normalizeStockInItemType(rowType);
                const list = getStockInItemsForType(normType, outletId);
                const ph = getStockInPlaceholderForType(normType);

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
                    stockBadge.className = 'badge bg-light text-secondary border in-current-stock-badge';
                }
                if (costInput) {
                    costInput.value = 0;
                }
                calculateStockInTotals();
            }

            function removeStockInRow(rowId) {
                if (stockInTsInstances[rowId]) {
                    try {
                        stockInTsInstances[rowId].destroy();
                    } catch (e) {}
                    delete stockInTsInstances[rowId];
                }
                const row = document.getElementById('stock-in-row-' + rowId);
                if (row) {
                    row.remove();
                    renumberStockInRows();
                    calculateStockInTotals();
                }
            }

            function renumberStockInRows() {
                const rows = document.querySelectorAll('#stockInItemsTableBody tr');
                rows.forEach((r, idx) => {
                    const numCell = r.querySelector('.row-number');
                    if (numCell) numCell.textContent = idx + 1;
                });
            }

            function calculateStockInTotals() {
                const rows = document.querySelectorAll('#stockInItemsTableBody tr');
                let grandQty = 0;
                let grandAmount = 0;

                rows.forEach(r => {
                    const qtyInput = r.querySelector('.in-item-qty-input');
                    const costInput = r.querySelector('.in-item-cost-input');
                    const subtotalDisplay = r.querySelector('.in-item-subtotal-display');

                    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
                    const cost = parseFloat(costInput ? costInput.value : 0) || 0;
                    const subtotal = qty * cost;

                    grandQty += qty;
                    grandAmount += subtotal;

                    if (subtotalDisplay) {
                        subtotalDisplay.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
                    }
                });

                const totalQtyEl = document.getElementById('stockInTotalQty');
                const totalAmountEl = document.getElementById('stockInTotalAmount');
                if (totalQtyEl) totalQtyEl.textContent = formatStockInNumber(grandQty);
                if (totalAmountEl) totalAmountEl.textContent = 'Rp ' + Math.round(grandAmount).toLocaleString('id-ID');
            }

            document.addEventListener('DOMContentLoaded', function() {
                const addInBtn = document.getElementById('btnAddNewStockInRow');
                if (addInBtn) {
                    addInBtn.addEventListener('click', function() {
                        addStockInRow();
                    });
                }

                // Form submit validation
                const formEl = document.getElementById('createStockInForm');
                if (formEl) {
                    formEl.addEventListener('submit', function(e) {
                        const rows = document.querySelectorAll('#stockInItemsTableBody tr');
                        if (rows.length === 0) {
                            e.preventDefault();
                            alert('Silakan tambahkan minimal 1 item barang masuk.');
                            return;
                        }

                        let hasEmptyItem = false;
                        rows.forEach(r => {
                            const rowId = r.getAttribute('data-row-id');
                            const ts = stockInTsInstances[rowId];
                            const val = ts ? ts.getValue() : r.querySelector('.in-item-select')?.value;
                            if (!val) {
                                hasEmptyItem = true;
                            }
                        });

                        if (hasEmptyItem) {
                            e.preventDefault();
                            alert('Mohon pilih produk atau bahan baku untuk setiap baris.');
                            return;
                        }
                    });
                }

                // Jika outlet diubah, perbarui opsi di semua baris yang sudah ada
                const outletSelect = document.getElementById('modalStockInOutlet');
                if (outletSelect) {
                    outletSelect.addEventListener('change', function() {
                        const rows = document.querySelectorAll('#stockInItemsTableBody tr');
                        rows.forEach(r => {
                            const rowId = r.getAttribute('data-row-id');
                            const typeSelect = r.querySelector('.in-item-type-select');
                            if (rowId && typeSelect && stockInTsInstances[rowId]) {
                                const ts = stockInTsInstances[rowId];
                                const currentVal = ts.getValue();
                                const curOption = currentVal ? ts.options[currentVal] : null;
                                const curName = curOption ? (curOption.cname || curOption.text || '') : '';
                                populateStockInTsOptions(rowId, typeSelect.value, currentVal, curName);
                            }
                        });
                    });
                }

                // Filter supplier pada modal Import PDF sesuai outlet yang dipilih (jika mode Semua Outlet)
                const importOutletSelect = document.getElementById('import_stock_in_outlet_id');
                const importSupplierSelect = document.getElementById('import_stock_in_supplier_id');
                if (importOutletSelect && importSupplierSelect) {
                    const allSupplierOptions = Array.from(importSupplierSelect.querySelectorAll('option'));
                    const filterImportSuppliers = () => {
                        const selectedOutlet = importOutletSelect.value;
                        const currentSupplier = importSupplierSelect.value;
                        importSupplierSelect.innerHTML = '';
                        allSupplierOptions.forEach(opt => {
                            const optOutlet = opt.getAttribute('data-outlet-id');
                            if (!opt.value || !selectedOutlet || !optOutlet || optOutlet === selectedOutlet) {
                                importSupplierSelect.appendChild(opt.cloneNode(true));
                            }
                        });
                        if (currentSupplier && importSupplierSelect.querySelector(`option[value="${currentSupplier}"]`)) {
                            importSupplierSelect.value = currentSupplier;
                        } else {
                            importSupplierSelect.value = '';
                        }
                    };
                    importOutletSelect.addEventListener('change', filterImportSuppliers);
                }
            });
        </script>