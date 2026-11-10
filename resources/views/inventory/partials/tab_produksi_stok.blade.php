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

<!-- Modal Tambah Produksi Stok Baru -->
<div class="modal fade" id="modalTambahProduksi" tabindex="-1" aria-labelledby="modalTambahProduksiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.production.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark" id="modalTambahProduksiLabel">
                        <i class="bi bi-gear-wide-connected text-primary me-2"></i>Buat Produksi Stok Baru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Outlet Produksi <span class="text-danger">*</span></label>
                        <select name="nid_outlet" class="form-select form-select-sm" required>
                            @foreach($outlets as $outlet)
                            <option value="{{ $outlet->nid }}" {{ (string)$selectedOutletId === (string)$outlet->nid ? 'selected' : '' }}>
                                {{ $outlet->cname }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tanggal Produksi <span class="text-danger">*</span></label>
                        <input type="date" name="dproduction" class="form-control form-control-sm" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan (Opsional)</label>
                        <textarea name="cnote" class="form-control form-control-sm" rows="3" placeholder="Contoh: Produksi batch pagi, target 50 pcs mie kering..."></textarea>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0 border-0 bg-info-subtle text-info-emphasis">
                        <i class="bi bi-info-circle me-1"></i> Setelah dokumen dibuat, Anda dapat menambahkan produk jadi dan bahan baku pada halaman detail.
                    </div>
                </div>
                <div class="modal-footer border-top py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Buat Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
