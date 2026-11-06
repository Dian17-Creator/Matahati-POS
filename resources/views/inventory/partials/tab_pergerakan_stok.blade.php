<!-- Baris Header Tab: Judul & Subtitle -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
    <div>
        <h5 class="fw-bold mb-1">Pergerakan Stok</h5>
        <div class="text-muted small"><span id="movement-total-count">{{ $products->total() }}</span> Item Terdata</div>
    </div>

    <div>
        <button type="button" class="btn btn-success btn-sm px-3" onclick="alert('Fitur download excel pergerakan stok siap diintegrasikan.')">
            <i class="bi bi-download me-1"></i> Download Excel
        </button>
    </div>
</div>

<!-- Baris FILTER Persis Gaya Halaman Laporan Penjualan -->
<div class="px-3 py-2 bg-light rounded-3 border mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="position: relative; z-index: 20;">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold me-1 text-secondary" style="font-size: 0.82rem; text-transform: uppercase;">FILTER :</span>

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
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $today, 'end_date' => $today])) }}">
                    Hari Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $yesterday, 'end_date' => $yesterday])) }}">
                    Kemarin
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $last7Days, 'end_date' => $today])) }}">
                    7 Hari Lalu
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $thisMonthStart, 'end_date' => $thisMonthEnd])) }}">
                    Bulan Ini
                </a>
                <a class="dropdown-item rounded py-1 px-2 quick-date-btn" href="{{ route('inventory.index', array_merge(request()->except(['page']), ['tab' => 'pergerakan_stok', 'start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}">
                    Bulan Lalu
                </a>

                <hr class="dropdown-divider my-2">
                <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                <form action="{{ route('inventory.index') }}" method="GET" class="px-1">
                    <input type="hidden" name="tab" value="pergerakan_stok">
                    @if(!empty($selectedOutletId)) <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}"> @endif
                    @if(!empty($search)) <input type="hidden" name="search" value="{{ $search }}"> @endif

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
            <input type="hidden" name="tab" value="pergerakan_stok">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif

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
    </div>

    <div>
        <form id="movementSearchForm" method="GET" action="{{ route('inventory.index') }}" onsubmit="return false;">
            <input type="hidden" name="tab" value="pergerakan_stok">
            <input type="hidden" name="start_date" id="movementStartDate" value="{{ $startDate }}">
            <input type="hidden" name="end_date" id="movementEndDate" value="{{ $endDate }}">
            @if($selectedOutletId)
            <input type="hidden" name="nid_outlet" id="movementOutletId" value="{{ $selectedOutletId }}">
            @endif
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" id="movementSearchInput" name="search" class="form-control" placeholder="Cari produk..." value="{{ $search }}" autocomplete="off">
                <span class="input-group-text bg-white" id="movementSearchSpinner" style="display: none;">
                    <span class="spinner-border spinner-border-sm text-primary" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                </span>
                <button class="btn btn-outline-secondary" type="button" id="movementSearchBtn">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Container Tabel Pergerakan Stok untuk Auto-Fetching AJAX -->
<div id="movement-container" style="position: relative; transition: opacity 0.2s ease;">
    @include('inventory.partials.movement_table', ['products' => $products])
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Sync Date Button pada dropdown custom tanggal
        document.querySelectorAll('.dropdown-sync-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const form = this.closest('form');
                if (form) {
                    const startInp = form.querySelector('input[name="start_date"]');
                    const endInp = form.querySelector('input[name="end_date"]');
                    if (startInp && endInp) {
                        endInp.value = startInp.value;
                    }
                }
            });
        });

        // Auto-fetching Live Search untuk Pergerakan Stok
        const movementSearchInput = document.getElementById('movementSearchInput');
        const movementContainer = document.getElementById('movement-container');
        const movementTotalCount = document.getElementById('movement-total-count');
        const movementSpinner = document.getElementById('movementSearchSpinner');
        const movementSearchBtn = document.getElementById('movementSearchBtn');
        let movementDebounceTimer = null;
        let movementAbortController = null;

        function fetchMovementData(fetchUrl = null) {
            if (!movementContainer) return;

            if (movementAbortController) {
                movementAbortController.abort();
            }
            movementAbortController = new AbortController();

            const searchVal = movementSearchInput ? movementSearchInput.value.trim() : '';
            const startDate = document.getElementById('movementStartDate')?.value || '{{ $startDate }}';
            const endDate = document.getElementById('movementEndDate')?.value || '{{ $endDate }}';
            const outletId = document.getElementById('movementOutletId')?.value || '{{ $selectedOutletId }}';

            let url;
            if (fetchUrl) {
                url = new URL(fetchUrl, window.location.origin);
            } else {
                url = new URL('{{ route("inventory.index") }}', window.location.origin);
                url.searchParams.set('tab', 'pergerakan_stok');
                url.searchParams.set('start_date', startDate);
                url.searchParams.set('end_date', endDate);
                if (outletId) url.searchParams.set('nid_outlet', outletId);
                if (searchVal) {
                    url.searchParams.set('search', searchVal);
                } else {
                    url.searchParams.delete('search');
                }
            }

            if (movementSpinner) movementSpinner.style.display = 'inline-flex';
            if (movementSearchBtn) movementSearchBtn.style.display = 'none';
            movementContainer.style.opacity = '0.5';

            fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: movementAbortController.signal
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data && data.html) {
                        movementContainer.innerHTML = data.html;
                        if (movementTotalCount && data.total_count !== undefined) {
                            movementTotalCount.textContent = data.total_count;
                        }
                        window.history.replaceState({}, '', url.toString());
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Error fetching movement data:', err);
                    }
                })
                .finally(() => {
                    if (movementSpinner) movementSpinner.style.display = 'none';
                    if (movementSearchBtn) movementSearchBtn.style.display = 'inline-flex';
                    movementContainer.style.opacity = '1';
                });
        }

        if (movementSearchInput) {
            movementSearchInput.addEventListener('input', function() {
                clearTimeout(movementDebounceTimer);
                movementDebounceTimer = setTimeout(() => {
                    fetchMovementData();
                }, 350);
            });

            movementSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(movementDebounceTimer);
                    fetchMovementData();
                }
            });
        }

        if (movementSearchBtn) {
            movementSearchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearTimeout(movementDebounceTimer);
                fetchMovementData();
            });
        }

        // Intercept pagination clicks di dalam movementContainer
        if (movementContainer) {
            movementContainer.addEventListener('click', function(e) {
                const pageLink = e.target.closest('a.page-link');
                if (pageLink && pageLink.href) {
                    e.preventDefault();
                    fetchMovementData(pageLink.href);
                }
            });
        }
    });
</script>
