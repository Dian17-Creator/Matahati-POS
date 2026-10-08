@extends('layouts.app')

@section('title', 'Laporan Pelanggan berdasarkan Produk')

@push('styles')
<style>
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.05);
        margin-bottom: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.06);
    }
    .summary-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #b63352;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .summary-label {
        font-size: 0.75rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .summary-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #2c3e50;
    }
    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 8px;
        margin-bottom: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
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
        border: 1px solid #e2e8f0;
    }
    .filter-chip-date i {
        color: #b63352;
    }
    
    /* Modernizing the Card / Table */
    .table-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important;
    }
    .table-light th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-dark {
        background-color: #1e293b;
    }
    .table-dark td {
        background-color: #1e293b;
        color: #fff;
        border-color: #334155;
    }
    
    /* Modern Pagination (from Customers) */
    .pagination {
        gap: 4px;
        margin-bottom: 0;
    }
    .page-item .page-link {
        border-radius: 6px !important;
        border: 1px solid transparent;
        color: #64748b;
        font-weight: 500;
        padding: 6px 12px;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        box-shadow: none;
    }
    .page-item.active .page-link {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
    }
    .page-item .page-link:hover:not(.active):not(.disabled) {
        background-color: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .page-item.disabled .page-link {
        background-color: transparent;
        color: #cbd5e1;
        cursor: not-allowed;
    }
    nav.d-flex.justify-items-center.justify-content-between {
        align-items: center;
        width: 100%;
        padding: 10px 20px;
    }

    /* Modal Modernization */
    .modern-modal .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .modern-modal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 20px 24px;
    }
    .modern-modal .modal-body {
        padding: 24px;
    }
    .modern-modal .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 16px 24px;
        background-color: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }
    .modern-modal .form-control, .modern-modal .form-select, .ts-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 10px 14px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .modern-modal .form-control:focus, .ts-control.focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
    }
    .modern-modal .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    /* Tom Select Customization */
    .ts-dropdown {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        margin-top: 4px;
    }
    .ts-dropdown .option {
        padding: 10px 14px;
        font-size: 0.95rem;
    }
    .ts-dropdown .option.active {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    
    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
    }
    .btn-primary {
        background-color: #0ea5e9;
        border-color: #0ea5e9;
    }
    .btn-primary:hover, .btn-primary:focus {
        background-color: #0284c7;
        border-color: #0284c7;
        box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25);
    }
</style>
<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<!-- Print Header (Hidden on Screen) -->
<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="mb-1" style="color: #000; font-weight: 600; font-size: 1rem;">Laporan Pelanggan berdasarkan Produk</h6>
            <div style="font-size: 0.7rem; color: #000;">
                <div>Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</div>
                <div>Dibuat pada {{ \Carbon\Carbon::now()->format('d F Y') }}</div>
            </div>
        </div>
        <div class="text-end">
            @php
                $mposUser = auth()->check() ? \App\Models\MposUser::with('outlet')->where('nid_user', auth()->id())->first() : null;
                $outletName = $mposUser?->outlet?->cname ?? 'Matahati POS';
            @endphp
            <div style="color: #000; font-weight: 700; font-size: 0.8rem;">{{ $outletName }}</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Laporan Pelanggan berdasarkan Produk</h5>
        </div>
        <div class="d-flex gap-2">
            @php
                $isAnyFilterActive = $customerId || $categoryId || $productId;
            @endphp
            <button type="button" class="btn {{ $isAnyFilterActive ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <a href="{{ route('reports.customer-product.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
            <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <div class="card-body p-0 d-flex flex-column">
        <!-- Active Filters -->
        <div class="px-4 py-3 bg-light d-print-none order-1" style="border-bottom: 2px solid #f1f5f9;">
            <span class="fw-bold me-2 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Filter Aktif:</span>
            <span class="filter-chip filter-chip-date"><i class="bi bi-calendar"></i> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
        
        @if($customerId)
            <span class="filter-chip filter-chip-active"><i class="bi bi-person"></i> {{ $customerId }}</span>
        @else
            <span class="filter-chip filter-chip-inactive"><i class="bi bi-person"></i> Semua Pelanggan</span>
        @endif

        @if($categoryId)
            <span class="filter-chip filter-chip-active"><i class="bi bi-tags"></i> {{ $categoryId }}</span>
        @else
            <span class="filter-chip filter-chip-inactive"><i class="bi bi-tags"></i> Semua Kategori</span>
        @endif

        @if($productId)
            <span class="filter-chip filter-chip-active"><i class="bi bi-cup-hot"></i> {{ $productId }}</span>
        @else
            <span class="filter-chip filter-chip-inactive"><i class="bi bi-cup-hot"></i> Semua Produk</span>
        @endif
        </div>

        <!-- Table -->
        <div class="table-responsive order-3 order-print-2">
            @php
            $sortUrl = function($col, $defaultDir) use ($sortCol, $sortDir) {
                $nextDir = $defaultDir;
                if ($sortCol === $col) {
                    $nextDir = $sortDir === 'asc' ? 'desc' : 'asc';
                }
                return request()->fullUrlWithQuery(['sort_col' => $col, 'sort_dir' => $nextDir]);
            };
            $sortIcon = function($col) use ($sortCol, $sortDir) {
                if ($sortCol === $col) {
                    return $sortDir === 'asc' ? '<i class="bi bi-sort-alpha-down ms-1 text-dark"></i>' : '<i class="bi bi-sort-alpha-down-alt ms-1 text-dark"></i>';
                }
                return '<i class="bi bi-arrow-down-up ms-1 text-muted opacity-25" style="font-size: 0.7rem;"></i>';
            };
            $sortIconNum = function($col) use ($sortCol, $sortDir) {
                if ($sortCol === $col) {
                    return $sortDir === 'asc' ? '<i class="bi bi-sort-numeric-down ms-1 text-dark"></i>' : '<i class="bi bi-sort-numeric-down-alt ms-1 text-dark"></i>';
                }
                return '<i class="bi bi-arrow-down-up ms-1 text-muted opacity-25" style="font-size: 0.7rem;"></i>';
            };
            @endphp
            <table class="table table-hover table-borderless align-middle mb-0 text-nowrap">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('customer_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Pelanggan {!! $sortIcon('customer_name') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('category_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Kategori {!! $sortIcon('category_name') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('product_name', 'asc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Produk {!! $sortIcon('product_name') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('qty', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Qty {!! $sortIconNum('qty') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('total_penjualan', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Total Penjualan {!! $sortIconNum('total_penjualan') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('diskon', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Diskon {!! $sortIconNum('diskon') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('modal', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Modal Produk {!! $sortIconNum('modal') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('laba', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Laba {!! $sortIconNum('laba') !!}</a>
                        </th>
                        <th class="py-3 text-center">
                            <a href="{{ $sortUrl('jml_transaksi', 'desc') }}" class="text-decoration-none text-secondary d-flex align-items-center justify-content-center">Jumlah Transaksi {!! $sortIconNum('jml_transaksi') !!}</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $row)
                        @php
                            $modal = 0; // Default as we don't have modal field
                            $laba = $row->total_penjualan - $row->diskon - $modal;
                        @endphp
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td class="text-center">{{ $row->customer_name ?? 'Unknown' }}</td>
                            <td class="text-center">{{ $row->category_name ?? '-' }}</td>
                            <td class="text-center">{{ $row->product_name }}</td>
                            <td class="text-center">{{ number_format($row->qty, 0, ',', '.') }}</td>
                            <td class="text-center">IDR {{ number_format($row->total_penjualan, 2, ',', '.') }}</td>
                            <td class="text-center">IDR {{ number_format($row->diskon, 2, ',', '.') }}</td>
                            <td class="text-center">IDR {{ number_format($modal, 2, ',', '.') }}</td>
                            <td class="text-center">IDR {{ number_format($laba, 2, ',', '.') }}</td>
                            <td class="text-center">{{ number_format($row->jml_transaksi, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Tidak ada data transaksi yang ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Grand Total -->
        <div class="px-4 py-3 print-grand-total order-2 order-print-3" style="background-color: #1e293b; color: #fff;">
            <div class="fw-bold mb-2" style="font-size: 0.85rem; text-transform: uppercase; color: #94a3b8;">Grand Total</div>
            <div class="row w-100 m-0">
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Qty</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem;">{{ number_format($grandTotals->total_qty ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Total Penjualan</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #34d399;">IDR {{ number_format($grandTotals->total_penjualan ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Diskon</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #f87171;">IDR {{ number_format($grandTotals->total_diskon ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Modal Produk</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #fbbf24;">IDR {{ number_format($grandTotals->total_modal ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Laba</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem; color: #60a5fa;">IDR {{ number_format($grandTotals->total_laba ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="col px-0 d-flex flex-column">
                    <span style="font-size: 0.75rem; color: #cbd5e1;">Jml Transaksi</span>
                    <span class="fw-bold print-text-black" style="font-size: 0.9rem;">{{ number_format($grandTotals->total_transaksi ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        
        @if(!request()->has('print') && method_exists($reports, 'hasPages'))
        <div class="d-flex justify-content-center mt-3 order-4">
            {{ $reports->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade modern-modal" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('reports.customer-product') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label">Periode</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                            </div>
                            <div class="col-auto d-flex align-items-center">
                                -
                            </div>
                            <div class="col">
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pelanggan</label>
                        <select name="customer_id" id="customer_id" class="form-select tom-select-filter" placeholder="Semua Pelanggan">
                            <option value="">Semua Pelanggan</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->cname }}" {{ $customerId == $c->cname ? 'selected' : '' }}>{{ $c->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" id="category_id" class="form-select tom-select-filter" placeholder="Semua Kategori">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->cname }}" {{ $categoryId == $cat->cname ? 'selected' : '' }}>{{ $cat->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="product_id" id="product_id" class="form-select tom-select-filter" placeholder="Semua Produk">
                            <option value="">Semua Produk</option>
                            @foreach($products as $p)
                                <option value="{{ $p->cname }}" {{ $productId == $p->cname ? 'selected' : '' }}>{{ $p->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="{{ route('reports.customer-product') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // Initialize Tom Select
        const tomSelects = {};
        
        document.querySelectorAll('.tom-select-filter').forEach((el) => {
            tomSelects[el.id] = new TomSelect(el, {
                create: false,
                maxItems: 1,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                placeholder: el.getAttribute('placeholder'),
                allowEmptyOption: true,
                onFocus: function() {
                    this.storedValue = this.getValue();
                    this.clear(true);
                },
                onBlur: function() {
                    if (this.getValue() === '') {
                        this.setValue(this.storedValue, true);
                    }
                }
            });
        });

        const categorySelect = document.getElementById('category_id');
        const productTs = tomSelects['product_id'];

        categorySelect.addEventListener('change', function () {
            const categoryId = this.value;
            
            // Clear current product options in Tom Select
            productTs.clear();
            productTs.clearOptions();
            productTs.addOption({value: '', text: 'Semua Produk'});

            fetch(`{{ route('reports.customer-product.products') }}?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(product => {
                        productTs.addOption({value: product.cname, text: product.cname});
                    });
                    
                    // Retain selection if applicable
                    if ("{{ $productId }}") {
                        productTs.setValue("{{ $productId }}");
                    } else {
                        productTs.setValue('');
                    }
                })
                .catch(error => console.error('Error fetching products:', error));
        });

        // Add print styling
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                .sidebar, nav.navbar, .btn, .filter-chip, .card-header, .pagination, .page-item {
                    display: none !important;
                }
                .main-content {
                    padding: 0 !important;
                }
                .container-fluid {
                    padding: 0 !important;
                }
                .row {
                    margin: 0 !important;
                }
                .col-md-10 {
                    width: 100% !important;
                    padding: 0 !important;
                }
                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
                .card-body {
                    padding: 0 !important;
                }
                .table-responsive {
                    overflow-x: visible !important;
                }
                table th {
                    color: #000 !important;
                    border-bottom: 1px solid #e5e7eb !important;
                    border: 1px solid #e5e7eb !important;
                    background-color: transparent !important;
                    white-space: nowrap !important;
                }
                table td {
                    color: #000 !important;
                    white-space: normal !important;
                    padding: 4px !important;
                    border: 1px solid #e5e7eb !important;
                }
                table {
                    width: 100% !important;
                    font-size: 0.65rem !important; /* Extremely small font to fit 9 columns in portrait */
                    border-collapse: collapse !important;
                }
                .print-grand-total {
                    border: 1px solid #e5e7eb !important;
                    background-color: transparent !important;
                    color: #000 !important;
                    margin-top: 15px !important;
                }
                .print-grand-total * {
                    color: #000 !important;
                    font-size: 0.65rem !important;
                }
                .print-text-black {
                    color: #000 !important;
                    white-space: nowrap !important;
                    font-size: 0.75rem !important;
                }
                .d-print-block {
                    display: block !important;
                }
                a {
                    text-decoration: none !important;
                    color: #000 !important;
                }
                .bi-arrow-down-up, .bi-sort-alpha-down, .bi-sort-alpha-down-alt, .bi-sort-numeric-down, .bi-sort-numeric-down-alt {
                    display: none !important;
                }
                .order-print-2 {
                    order: 2 !important;
                }
                .order-print-3 {
                    order: 3 !important;
                }
            }
            @page {
                size: portrait; /* Force portrait (vertical) orientation */
                margin: 1cm;
            }
        `;
        document.head.appendChild(style);

        // Auto print if ?print=1 is present
        @if(request()->has('print'))
            window.print();
        @endif
    });
</script>
@endpush
