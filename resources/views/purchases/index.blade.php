@extends('layouts.app')

@section('title', 'Daftar Pembelian')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .nav-tabs-custom .nav-link {
        font-weight: 600;
        color: #64748b;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 12px 20px;
        transition: all 0.2s;
    }

    .nav-tabs-custom .nav-link:hover {
        color: #0284c7;
    }

    .nav-tabs-custom .nav-link.active {
        color: #0284c7;
        background: transparent;
        border-bottom: 2px solid #0284c7;
    }

    .ts-control {
        border-radius: 6px !important;
        font-size: 0.85rem !important;
        min-height: 33px !important;
        padding: 4px 10px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
    }

    .ts-control:focus,
    .ts-control.focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.15) !important;
    }

    .ts-dropdown {
        z-index: 1060 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        font-size: 0.85rem !important;
        border: 1px solid #e2e8f0 !important;
    }

    .ts-dropdown .option {
        padding: 6px 12px !important;
    }

    .ts-dropdown .active {
        background-color: #f0f9ff !important;
        color: #0284c7 !important;
    }

    #createItemsTable {
        border-collapse: separate;
        border-spacing: 0;
    }

    #createItemsTable th {
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #64748b;
        background: transparent;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        white-space: nowrap;
    }

    #createItemsTable td {
        border-bottom: 1px solid #f1f5f9;
        border-top: none;
        vertical-align: middle;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    #createItemsTable tr:hover td {
        background-color: #f8fafc;
    }

    /* Modern Filter Chips (Report Style) */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }

    .filter-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        filter: brightness(0.96);
    }

    .filter-chip i {
        margin-right: 6px;
    }

    .filter-chip-active {
        background-color: #0ea5e9;
        color: white;
        border: 1px solid #0ea5e9;
    }

    .filter-chip-active i {
        color: white;
    }

    .filter-chip-inactive {
        background-color: white;
        color: #0ea5e9;
        border: 1px solid #0ea5e9;
    }

    .filter-chip-inactive i {
        color: #0ea5e9;
    }

    .filter-chip-date {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .filter-chip-date:hover {
        background: #e2e8f0;
    }

    .filter-chip-date i {
        color: #0ea5e9;
    }

    .filter-chip.dropdown-toggle::after {
        display: none !important;
    }

    .filter-dropdown-menu {
        border-radius: 12px;
    }

    .sync-date-btn {
        color: #94a3b8;
        transition: color 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .sync-date-btn:hover {
        color: #0ea5e9;
    }

    .table-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(1px);
        z-index: 10;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
@endpush

@section('content')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Daftar Pembelian</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPurchaseModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Pembelian
            </button>
        </div>
    </div>

    @php
    if (!empty($startDate) && !empty($endDate)) {
    $dateLabel = \Carbon\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
    } elseif (!empty($startDate)) {
    $dateLabel = 'Sejak ' . \Carbon\Carbon::parse($startDate)->format('d M Y');
    } elseif (!empty($endDate)) {
    $dateLabel = 'Hingga ' . \Carbon\Carbon::parse($endDate)->format('d M Y');
    } else {
    $dateLabel = 'Semua Tanggal';
    }

    $statusLabels = [
    'pending' => 'Butuh Diproses',
    'received' => 'Telah Diproses',
    'cancelled' => 'Dibatalkan',
    'all' => 'Semua Status'
    ];
    $statusLabel = $statusLabels[$statusTab] ?? 'Semua Status';
    @endphp

    <!-- Status Tabs -->
    <div class="bg-white border-bottom px-3">

    </div>

    <!-- Active Filters Bar (Report Style: Date, Status, Search) -->
    <div class="px-4 py-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap align-items-center">
            <span class="fw-bold me-2 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">FILTER :</span>

            <!-- Dropdown Periode Tanggal -->
            <div class="dropdown d-inline-block">
                <button type="button" class="filter-chip filter-chip-date dropdown-toggle border-0" id="dateFilterChip" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Ubah Periode Tanggal">
                    <i class="bi bi-calendar"></i> <span id="dateFilterLabel">{{ $dateLabel }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 p-2" style="min-width: 350px;">
                    <div class="px-2 py-1 text-muted text-center small fw-bold">Pilih Cepat</div>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="today">
                        Hari Ini
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="yesterday">
                        Kemarin
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="last7days">
                        7 Hari Lalu
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="thismonth">
                        Bulan Ini
                    </button>
                    <button type="button" class="dropdown-item rounded py-1 px-2 quick-date-btn" data-preset="lastmonth">
                        Bulan Lalu
                    </button>

                    <hr class="dropdown-divider my-2">
                    <div class="px-2 py-1 text-muted small fw-semibold">Custom Tanggal</div>
                    <div class="px-1">
                        <div class="d-flex align-items-center gap-2 flex-nowrap mb-2">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Dari</label>
                                <input type="date" id="customStartDate" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $startDate }}">
                            </div>
                            <div class="d-flex align-items-center flex-shrink-0" style="padding-top: 25px;">
                                <button type="button" class="btn btn-link p-1 border-0 sync-date-btn" id="syncCustomDateBtn" title="Samakan Tanggal">
                                    <i class="bi bi-arrow-left-right fs-6"></i>
                                </button>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <label class="form-label small text-muted mb-1 fw-semibold" style="font-size: 0.75rem;">Sampai</label>
                                <input type="date" id="customEndDate" class="form-control form-control-sm px-2 text-center" style="font-size: 0.82rem;" value="{{ $endDate }}">
                            </div>
                        </div>
                        <button type="button" id="applyCustomDateBtn" class="btn btn-sm w-100 py-1 mt-1 mb-2 text-white fw-semibold" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                            Terapkan Tanggal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dropdown Status -->
            <div class="dropdown d-inline-block">
                <button type="button" id="statusFilterChip" class="filter-chip {{ ($statusTab && $statusTab !== 'all') ? 'filter-chip-active' : 'filter-chip-inactive' }} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih Status">
                    <i class="bi bi-check-circle"></i> <span id="statusFilterLabel">{{ $statusLabel }}</span>
                    <i class="bi bi-chevron-down ms-1" style="font-size: 0.65rem; opacity: 0.75;"></i>
                </button>
                <div class="dropdown-menu filter-dropdown-menu shadow border-0 py-1" style="min-width: 200px;">
                    <button type="button" class="dropdown-item status-filter-btn d-flex align-items-center {{ $statusTab === 'all' ? 'active' : '' }}" data-status="all">
                        <span>Semua Status</span>
                        <i class="bi bi-check-lg text-primary ms-auto check-icon {{ $statusTab === 'all' ? '' : 'd-none' }}"></i>
                    </button>
                    <button type="button" class="dropdown-item status-filter-btn d-flex align-items-center {{ $statusTab === 'pending' ? 'active' : '' }}" data-status="pending">
                        <span>Butuh Diproses</span>
                        <i class="bi bi-check-lg text-primary ms-auto check-icon {{ $statusTab === 'pending' ? '' : 'd-none' }}"></i>
                    </button>
                    <button type="button" class="dropdown-item status-filter-btn d-flex align-items-center {{ $statusTab === 'received' ? 'active' : '' }}" data-status="received">
                        <span>Telah Diproses</span>
                        <i class="bi bi-check-lg text-primary ms-auto check-icon {{ $statusTab === 'received' ? '' : 'd-none' }}"></i>
                    </button>
                    <button type="button" class="dropdown-item status-filter-btn d-flex align-items-center {{ $statusTab === 'cancelled' ? 'active' : '' }}" data-status="cancelled">
                        <span>Dibatalkan</span>
                        <i class="bi bi-check-lg text-primary ms-auto check-icon {{ $statusTab === 'cancelled' ? '' : 'd-none' }}"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Searching Box -->
        <div class="d-flex align-items-center">
            <div class="input-group input-group-sm" style="width: 700px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="filterSearchInput" class="form-control border-start-0 ps-0" placeholder="Cari No. PO, Supplier, No. SJ..." value="{{ $search }}">
                <button class="btn btn-white border border-start-0 text-muted" type="button" id="clearSearchBtn" style="{{ $search ? '' : 'display: none;' }}" title="Hapus Pencarian">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Purchases Table Container with Realtime Loading -->
    <div class="card-body p-0 position-relative" id="purchasesTableContainer" style="min-height: 220px;">
        <div id="tableLoadingOverlay" class="table-loading-overlay d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Memuat data...</span>
            </div>
        </div>

        <div id="tableContent">
            @include('purchases.partials.table', ['purchases' => $purchases])
        </div>
    </div>
</div>

<!-- Modal Pembelian Baru (Fit & Clean Layout) -->
<div class="modal fade" id="createPurchaseModal" tabindex="-1" aria-labelledby="createPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 880px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchases.store') }}" method="POST" id="createPurchaseForm">
                @csrf
                <div class="modal-header bg-light-subtle py-3 border-bottom">
                    <h5 class="modal-title fw-bold" id="createPurchaseModalLabel">
                        <i class="bi bi-cart-plus me-2 text-primary"></i>Pembelian Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Form Header Info (Baris 1: Supplier, Outlet, Tanggal Beli, Jatuh Tempo) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Supplier <span class="text-danger">*</span></label>
                            <select name="nid_supplier" class="form-select" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $sp)
                                <option value="{{ $sp->nid }}" {{ old('nid_supplier') == $sp->nid ? 'selected' : '' }}>
                                    {{ $sp->cname }} {{ $sp->cphone ? "({$sp->cphone})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Outlet Tujuan Stok <span class="text-danger">*</span></label>
                            <select name="nid_outlet" id="modalCreateOutlet" class="form-select" required {{ $isOutletLocked ? 'disabled' : '' }}>
                                @foreach($outlets as $ot)
                                <option value="{{ $ot->nid }}" {{ (string)($outletId ?: $outlets->first()?->nid) === (string)$ot->nid ? 'selected' : '' }}>
                                    {{ $ot->cname }}
                                </option>
                                @endforeach
                            </select>
                            @if($isOutletLocked)
                            <input type="hidden" name="nid_outlet" value="{{ $outletId }}">
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Beli <span class="text-danger">*</span></label>
                            <input type="date" name="dpurchase" class="form-control" value="{{ old('dpurchase', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Jatuh Tempo</label>
                            <input type="date" name="ddue_date" class="form-control" value="{{ old('ddue_date') }}">
                        </div>
                    </div>

                    <!-- Baris 2: Catatan Pembelian -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Catatan Pembelian</label>
                            <textarea name="cnote" class="form-control" rows="3" style="min-height: 80px;" placeholder="Catatan PO atau keterangan tambahan...">{{ old('cnote') }}</textarea>
                        </div>
                    </div>

                    <!-- Header Section Produk Pesanan -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="fw-bold text-dark fs-6">
                                <i class="bi bi-box-seam me-1 text-primary"></i> Produk Pesanan
                            </span>
                            <span class="text-muted small ms-2 d-none d-sm-inline">Tambahkan produk yang dibeli</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm" id="btnAddNewItemRow">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- Table Produk Tanpa Box/Card Wrapper -->
                    <div class="table-responsive mt-2" style="overflow: visible;">
                        <table class="table align-middle mb-0" id="createItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 35px;" class="text-center py-2 text-muted text-nowrap">No</th>
                                    <th class="py-2 text-center text-nowrap">PRODUK</th>
                                    <th style="width: 115px;" class="text-center py-2 text-nowrap">STOK SAAT INI</th>
                                    <th style="width: 80px;" class="text-center py-2 text-nowrap">QTY</th>
                                    <th style="width: 155px;" class="text-center py-1 text-nowrap">HARGA BELI</th>
                                    <th style="width: 125px;" class="text-end py-2 text-nowrap">SUBTOTAL</th>
                                    <th style="width: 35px;" class="text-center py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="createItemsTableBody" class="small">
                                <!-- Baris produk via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Baris Ringkasan Total (Flat & Rapi) -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top bg-light-subtle rounded-3 p-3">
                        <div class="text-muted small">
                            <i></i>
                        </div>
                        <div class="d-flex align-items-center gap-4 text-end">
                            <div>
                                <span class="text-secondary small d-block">Total Kuantitas:</span>
                                <span class="fw-bold fs-6 text-dark" id="createTotalQty">0</span> <span class="small text-muted">unit</span>
                            </div>
                            <div>
                                <span class="text-secondary small d-block">Total Nilai Pesanan:</span>
                                <span class="fw-bold fs-5 text-primary" id="createTotalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle py-2 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    const allProducts = @json($products ?? []);
    let rowIndex = 0;
    const tsInstances = {};

    function getSelectedOutletId() {
        const outletSelect = document.getElementById('modalCreateOutlet');
        if (outletSelect) {
            return outletSelect.value;
        }
        return "{{ $outletId }}";
    }

    function getProductsForOutlet(selectedOutletId) {
        // Group all products by name
        const byName = new Map();
        allProducts.forEach(p => {
            if (!byName.has(p.cname)) {
                byName.set(p.cname, []);
            }
            byName.get(p.cname).push(p);
        });

        const list = [];
        byName.forEach((prods, name) => {
            const match = prods.find(p => String(p.nid_outlet) === String(selectedOutletId));
            if (match) {
                list.push({
                    nid: match.nid,
                    cname: match.cname,
                    nstock: match.nqty || 0,
                    nprice: match.nprice || 0
                });
            } else {
                const tmpl = prods[0];
                list.push({
                    nid: tmpl.nid,
                    cname: tmpl.cname,
                    nstock: 0,
                    nprice: tmpl.nprice || 0
                });
            }
        });

        list.sort((a, b) => a.cname.localeCompare(b.cname));
        return list;
    }

    function addRow(productId = '', qty = 1, cost = 0) {
        const tbody = document.getElementById('createItemsTableBody');
        const outletId = getSelectedOutletId();
        const productList = getProductsForOutlet(outletId);
        const currentIdx = rowIndex++;

        const tr = document.createElement('tr');
        tr.id = `item-row-${currentIdx}`;
        tr.setAttribute('data-row-id', currentIdx);
        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold align-middle row-number py-2"></td>
            <td class="py-2">
                <select name="items[${currentIdx}][nid_product]" class="form-select form-select-sm item-product-select" id="product_select_${currentIdx}">
                    <option value="">-- Cari atau pilih produk --</option>
                    ${productList.map(p => `<option value="${p.nid}" ${String(productId) === String(p.nid) ? 'selected' : ''} data-stock="${p.nstock}">${p.cname}</option>`).join('')}
                </select>
            </td>
            <td class="text-center align-middle py-2">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 item-stock-badge fw-medium" id="stock-badge-${currentIdx}">-</span>
            </td>
            <td class="py-2">
                <input type="number" name="items[${currentIdx}][nqty]" class="form-control form-control-sm text-center fw-semibold item-qty-input" value="${qty}" min="1" step="1" data-row="${currentIdx}">
            </td>
            <td class="py-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted px-2 border-end-0">Rp</span>
                    <input type="number" name="items[${currentIdx}][ncost]" class="form-control form-control-sm text-end fw-semibold item-cost-input border-start-0 ps-1" value="${cost}" min="0" step="100" data-row="${currentIdx}">
                </div>
            </td>
            <td class="text-end align-middle fw-bold text-dark py-2 item-subtotal-text" id="subtotal-${currentIdx}">
                Rp 0
            </td>
            <td class="text-center align-middle py-2">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 border-0 rounded-circle btn-remove-row" data-row="${currentIdx}" title="Hapus Baris" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        const selectEl = tr.querySelector('.item-product-select');
        const qtyInput = tr.querySelector('.item-qty-input');
        const costInput = tr.querySelector('.item-cost-input');
        const removeBtn = tr.querySelector('.btn-remove-row');

        // Inisialisasi TomSelect untuk pencarian live search yang rapi
        const ts = new TomSelect(selectEl, {
            create: false,
            maxItems: 1,
            placeholder: '-- Cari atau pilih produk --',
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
                    const stockMatch = data.text ? data.text.match(/\(Stok:\s*(\d+)\)/) : null;
                    const stock = stockMatch ? stockMatch[1] : (data.nstock !== undefined ? data.nstock : (data.stock !== undefined ? data.stock : '-'));
                    const cleanName = data.cname || (data.text ? data.text.replace(/\s*\(Stok:.*?\)$/, '') : '');
                    return '<div class="d-flex justify-content-between align-items-center py-1 px-1">' +
                        '<span class="fw-medium text-dark text-truncate me-2">' + escape(cleanName) + '</span>' +
                        '<span class="badge bg-light text-secondary border px-2 py-0.5 small flex-shrink-0">Stok: ' + escape(stock) + '</span>' +
                        '</div>';
                }
            }
        });
        tsInstances[currentIdx] = ts;

        ts.on('change', function(val) {
            const currentOutletId = getSelectedOutletId();
            const currentList = getProductsForOutlet(currentOutletId);
            const selectedProduct = currentList.find(p => String(p.nid) === String(val));
            const stockBadge = document.getElementById(`stock-badge-${currentIdx}`);

            if (selectedProduct) {
                stockBadge.textContent = `${selectedProduct.nstock} unit`;
                if (parseFloat(costInput.value || 0) === 0) {
                    costInput.value = parseFloat(selectedProduct.nprice || 0);
                }
            } else {
                stockBadge.textContent = '-';
            }
            recalculateSubtotal(currentIdx);
        });

        qtyInput.addEventListener('input', () => recalculateSubtotal(currentIdx));
        costInput.addEventListener('input', () => recalculateSubtotal(currentIdx));

        removeBtn.addEventListener('click', function() {
            if (tsInstances[currentIdx]) {
                tsInstances[currentIdx].destroy();
                delete tsInstances[currentIdx];
            }
            tr.remove();
            updateRowNumbers();
            recalculateTotals();

            // Bila semua baris dihapus, buat 1 baris kosong otomatis agar selalu ada baris input
            const curTbody = document.getElementById('createItemsTableBody');
            if (curTbody && curTbody.children.length === 0) {
                addRow();
            }
        });

        updateRowNumbers();
        recalculateSubtotal(currentIdx);
    }

    function recalculateSubtotal(idx) {
        const row = document.getElementById(`item-row-${idx}`);
        if (!row) return;

        const qty = parseFloat(row.querySelector('.item-qty-input')?.value || 0);
        const cost = parseFloat(row.querySelector('.item-cost-input')?.value || 0);
        const subtotal = Math.max(0, qty * cost);

        const subtotalText = document.getElementById(`subtotal-${idx}`);
        if (subtotalText) {
            subtotalText.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
        }

        recalculateTotals();
    }

    function recalculateTotals() {
        let totalQty = 0;
        let totalAmount = 0;

        document.querySelectorAll('#createItemsTableBody tr').forEach(tr => {
            const rowIdx = tr.getAttribute('data-row-id');
            const ts = tsInstances[rowIdx];
            if (ts && ts.getValue()) {
                const qty = parseFloat(tr.querySelector('.item-qty-input')?.value || 0);
                const cost = parseFloat(tr.querySelector('.item-cost-input')?.value || 0);
                totalQty += qty;
                totalAmount += (qty * cost);
            }
        });

        const totalQtyEl = document.getElementById('createTotalQty');
        const totalAmountEl = document.getElementById('createTotalAmount');

        if (totalQtyEl) totalQtyEl.textContent = totalQty.toLocaleString('id-ID');
        if (totalAmountEl) totalAmountEl.textContent = 'Rp ' + totalAmount.toLocaleString('id-ID');
    }

    function updateRowNumbers() {
        document.querySelectorAll('#createItemsTableBody tr').forEach((tr, index) => {
            const numEl = tr.querySelector('.row-number');
            if (numEl) numEl.textContent = index + 1;
        });
    }

    function refreshAllProductSelects() {
        const outletId = getSelectedOutletId();
        const productList = getProductsForOutlet(outletId);

        document.querySelectorAll('#createItemsTableBody tr').forEach(tr => {
            const rowIdx = tr.getAttribute('data-row-id');
            const ts = tsInstances[rowIdx];
            if (ts) {
                const currentVal = ts.getValue();
                ts.clearOptions();
                productList.forEach(p => {
                    ts.addOption({
                        value: p.nid,
                        text: p.cname,
                        cname: p.cname,
                        nstock: p.nstock
                    });
                });
                if (currentVal) {
                    ts.setValue(currentVal, true);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addBtn = document.getElementById('btnAddNewItemRow');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                addRow();
            });
        }

        const outletSelect = document.getElementById('modalCreateOutlet');
        if (outletSelect) {
            outletSelect.addEventListener('change', function() {
                refreshAllProductSelects();
            });
        }

        // Siapkan 1 baris kosong otomatis ketika modal dibuka pertama kali
        const createModalEl = document.getElementById('createPurchaseModal');
        if (createModalEl) {
            createModalEl.addEventListener('show.bs.modal', function() {
                const tbody = document.getElementById('createItemsTableBody');
                if (tbody && tbody.children.length === 0) {
                    addRow();
                }
            });
        }

        // ==========================================
        // Real-Time AJAX Fetching & Filter Handler
        // ==========================================
        let currentStatus = '{{ $statusTab }}';
        let currentStartDate = '{{ $startDate }}';
        let currentEndDate = '{{ $endDate }}';
        let currentSearch = '{{ $search }}';
        let abortController = null;
        let searchDebounceTimeout = null;

        const dateFilterChip = document.getElementById('dateFilterChip');
        const dateFilterLabel = document.getElementById('dateFilterLabel');
        const statusFilterChip = document.getElementById('statusFilterChip');
        const statusFilterLabel = document.getElementById('statusFilterLabel');
        const customStartDate = document.getElementById('customStartDate');
        const customEndDate = document.getElementById('customEndDate');
        const filterSearchInput = document.getElementById('filterSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const overlay = document.getElementById('tableLoadingOverlay');
        const tableContent = document.getElementById('tableContent');

        const statusNames = {
            'all': 'Semua Status',
            'pending': 'Butuh Diproses',
            'received': 'Telah Diproses',
            'cancelled': 'Dibatalkan'
        };

        function formatYmd(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function formatDisplayDate(d) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const day = d.getDate();
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            return `${day} ${month} ${year}`;
        }

        function fetchPurchases(page = 1) {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            if (overlay) overlay.classList.remove('d-none');

            const params = new URLSearchParams();
            if (currentStatus) params.append('tab', currentStatus);
            if (currentStartDate) params.append('start_date', currentStartDate);
            if (currentEndDate) params.append('end_date', currentEndDate);
            if (currentSearch) params.append('search', currentSearch);
            if (page && page > 1) params.append('page', page);
            params.append('ajax', '1');

            const fetchUrl = '{{ route("purchases.index") }}?' + params.toString();

            // Update URL in browser address bar without reloading
            const displayParams = new URLSearchParams(params);
            displayParams.delete('ajax');
            const displayUrl = '{{ route("purchases.index") }}' + (displayParams.toString() ? '?' + displayParams.toString() : '');
            window.history.replaceState({}, '', displayUrl);

            fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: abortController.signal
                })
                .then(res => res.json())
                .then(data => {
                    if (data.html && tableContent) {
                        tableContent.innerHTML = data.html;
                    }
                    if (data.counts) {
                        const badgePending = document.getElementById('badgePending');
                        const badgeReceived = document.getElementById('badgeReceived');
                        const badgeCancelled = document.getElementById('badgeCancelled');
                        if (badgePending && data.counts.pending !== undefined) badgePending.textContent = data.counts.pending;
                        if (badgeReceived && data.counts.received !== undefined) badgeReceived.textContent = data.counts.received;
                        if (badgeCancelled && data.counts.cancelled !== undefined) badgeCancelled.textContent = data.counts.cancelled;
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Gagal memuat data pembelian:', err);
                    }
                })
                .finally(() => {
                    if (overlay) overlay.classList.add('d-none');
                });
        }

        // Set Status Handler
        function setStatus(status) {
            currentStatus = status;

            // Update Status Filter Chip
            if (statusFilterLabel) {
                statusFilterLabel.textContent = statusNames[status] || 'Semua Status';
            }
            if (statusFilterChip) {
                if (status && status !== 'all') {
                    statusFilterChip.classList.remove('filter-chip-inactive');
                    statusFilterChip.classList.add('filter-chip-active');
                } else {
                    statusFilterChip.classList.remove('filter-chip-active');
                    statusFilterChip.classList.add('filter-chip-inactive');
                }
            }

            // Update Status Dropdown Menu Checkmarks
            document.querySelectorAll('.status-filter-btn').forEach(btn => {
                const btnStatus = btn.getAttribute('data-status');
                const check = btn.querySelector('.check-icon');
                if (btnStatus === status) {
                    btn.classList.add('active');
                    if (check) check.classList.remove('d-none');
                } else {
                    btn.classList.remove('active');
                    if (check) check.classList.add('d-none');
                }
            });

            // Update Status Tabs
            document.querySelectorAll('.status-tab-link').forEach(link => {
                const tabStatus = link.getAttribute('data-status');
                if (tabStatus === status) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            fetchPurchases(1);
        }

        // Status Tabs click
        document.querySelectorAll('.status-tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const status = this.getAttribute('data-status');
                setStatus(status);
            });
        });

        // Status dropdown items click
        document.querySelectorAll('.status-filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const status = this.getAttribute('data-status');
                setStatus(status);
            });
        });

        // Date Presets click
        document.querySelectorAll('.quick-date-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const preset = this.getAttribute('data-preset');
                const now = new Date();

                if (preset === 'today') {
                    currentStartDate = formatYmd(now);
                    currentEndDate = formatYmd(now);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Hari Ini (' + formatDisplayDate(now) + ')';
                } else if (preset === 'yesterday') {
                    const y = new Date();
                    y.setDate(now.getDate() - 1);
                    currentStartDate = formatYmd(y);
                    currentEndDate = formatYmd(y);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Kemarin (' + formatDisplayDate(y) + ')';
                } else if (preset === 'last7days') {
                    const past = new Date();
                    past.setDate(now.getDate() - 6);
                    currentStartDate = formatYmd(past);
                    currentEndDate = formatYmd(now);
                    if (dateFilterLabel) dateFilterLabel.textContent = formatDisplayDate(past) + ' - ' + formatDisplayDate(now);
                } else if (preset === 'thismonth') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    currentStartDate = formatYmd(firstDay);
                    currentEndDate = formatYmd(lastDay);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Bulan Ini (' + formatDisplayDate(firstDay) + ' - ' + formatDisplayDate(lastDay) + ')';
                } else if (preset === 'lastmonth') {
                    const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
                    currentStartDate = formatYmd(firstDay);
                    currentEndDate = formatYmd(lastDay);
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Bulan Lalu (' + formatDisplayDate(firstDay) + ' - ' + formatDisplayDate(lastDay) + ')';
                } else if (preset === 'all') {
                    currentStartDate = '';
                    currentEndDate = '';
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Semua Tanggal';
                }

                if (customStartDate) customStartDate.value = currentStartDate;
                if (customEndDate) customEndDate.value = currentEndDate;

                // Close date dropdown
                if (dateFilterChip) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dateFilterChip);
                    if (bsDropdown) bsDropdown.hide();
                }

                fetchPurchases(1);
            });
        });

        // Custom Date Apply
        const applyCustomDateBtn = document.getElementById('applyCustomDateBtn');
        if (applyCustomDateBtn) {
            applyCustomDateBtn.addEventListener('click', function() {
                const sVal = customStartDate ? customStartDate.value : '';
                const eVal = customEndDate ? customEndDate.value : '';

                if (!sVal && !eVal) {
                    currentStartDate = '';
                    currentEndDate = '';
                    if (dateFilterLabel) dateFilterLabel.textContent = 'Semua Tanggal';
                } else {
                    currentStartDate = sVal;
                    currentEndDate = eVal;

                    if (sVal && eVal) {
                        const sD = new Date(sVal + 'T00:00:00');
                        const eD = new Date(eVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = formatDisplayDate(sD) + ' - ' + formatDisplayDate(eD);
                    } else if (sVal) {
                        const sD = new Date(sVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = 'Sejak ' + formatDisplayDate(sD);
                    } else {
                        const eD = new Date(eVal + 'T00:00:00');
                        if (dateFilterLabel) dateFilterLabel.textContent = 'Hingga ' + formatDisplayDate(eD);
                    }
                }

                // Close date dropdown
                if (dateFilterChip) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dateFilterChip);
                    if (bsDropdown) bsDropdown.hide();
                }

                fetchPurchases(1);
            });
        }

        // Tombol sync / panah bolak-balik untuk menyamakan tanggal Dari ke Sampai
        const syncCustomDateBtn = document.getElementById('syncCustomDateBtn');
        if (syncCustomDateBtn && customStartDate && customEndDate) {
            syncCustomDateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (customStartDate.value) {
                    customEndDate.value = customStartDate.value;
                } else if (customEndDate.value) {
                    customStartDate.value = customEndDate.value;
                }
            });
        }

        // Search Input with Debounce
        if (filterSearchInput) {
            filterSearchInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = val ? 'block' : 'none';
                }
                clearTimeout(searchDebounceTimeout);
                searchDebounceTimeout = setTimeout(function() {
                    currentSearch = val;
                    fetchPurchases(1);
                }, 350);
            });

            filterSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimeout);
                    currentSearch = this.value.trim();
                    fetchPurchases(1);
                }
            });
        }

        // Clear Search Button
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                if (filterSearchInput) {
                    filterSearchInput.value = '';
                }
                this.style.display = 'none';
                currentSearch = '';
                fetchPurchases(1);
            });
        }

        // AJAX Pagination delegation
        if (tableContent) {
            tableContent.addEventListener('click', function(e) {
                const link = e.target.closest('.pagination a');
                if (link) {
                    e.preventDefault();
                    try {
                        const url = new URL(link.href);
                        const page = url.searchParams.get('page') || 1;
                        fetchPurchases(page);
                    } catch (err) {
                        // fallback
                    }
                }
            });
        }
    });
</script>
@endpush