@extends('layouts.app')

@section('title', 'Laporan Rincian Penjualan')

@push('styles')
<style>
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
    
    .sync-date-btn {
        color: #94a3b8 !important;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    .sync-date-btn:hover {
        color: #0ea5e9 !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="mb-1" style="color: #000; font-weight: 600; font-size: 1rem;">Laporan Rincian Penjualan</h6>
            <div style="font-size: 0.7rem; color: #000;">
                <div>Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</div>
                <div>Dibuat pada {{ \Carbon\Carbon::now()->format('d F Y') }}</div>
            </div>
        </div>
        <div class="text-end">
            @php
                $mposUser = auth()->check() ? \App\Models\MposUser::with('outlet')->where('nid_user', auth()->id())->first() : null;
                $currentOutletName = $mposUser?->outlet?->cname ?? 'Matahati POS';
            @endphp
            <div style="color: #000; font-weight: 700; font-size: 0.8rem;">{{ $currentOutletName }}</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Laporan Rincian Penjualan</h5>
        </div>
        <div class="d-flex gap-2">
            @php
                $isAnyFilterActive = !empty($outletId) || !empty($status);
            @endphp
            <button type="button" class="btn {{ $isAnyFilterActive ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <a href="#" class="btn btn-outline-success btn-sm disabled" title="Belum Tersedia">
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
        
            @if($outletId)
                @php
                    $outletName = collect($outlets)->firstWhere('nid', $outletId)?->cname ?? $outletId;
                @endphp
                <span class="filter-chip filter-chip-active"><i class="bi bi-shop"></i> {{ $outletName }}</span>
            @else
                <span class="filter-chip filter-chip-inactive"><i class="bi bi-shop"></i> Semua Outlet</span>
            @endif

            @if($status)
                <span class="filter-chip filter-chip-active"><i class="bi bi-check-circle"></i> {{ $status }}</span>
            @else
                <span class="filter-chip filter-chip-inactive"><i class="bi bi-check-circle"></i> Semua Status</span>
            @endif
        </div>

        <div class="table-responsive order-3 order-print-2">
            <table class="table table-hover table-borderless align-middle mb-0 text-nowrap" style="min-width: 1500px;">
                <thead class="bg-light text-secondary" style="border-bottom: 2px solid #f1f5f9;">
                    <tr>
                        <th class="py-3 text-center">TANGGAL</th>
                        <th class="py-3 text-center">NO. PESANAN</th>
                        <th class="py-3 text-center">STATUS</th>
                        <th class="py-3 text-center">KASIR</th>
                        <th class="py-3 text-center">PELANGGAN</th>
                        <th class="py-3 text-end">TOTAL PENJUALAN</th>
                        <th class="py-3 text-end">VOID/REFUND</th>
                        <th class="py-3 text-end">DISKON</th>
                        <th class="py-3 text-end">PAJAK</th>
                        <th class="py-3 text-end">SERVICE</th>
                        <th class="py-3 text-end">PEMBULATAN</th>
                        <th class="py-3 text-end fw-bold text-primary">PENJUALAN NETT</th>
                        <th class="py-3 text-end">MODAL</th>
                        <th class="py-3 text-end fw-bold text-success">LABA</th>
                        <th class="py-3 text-center">DEPOSIT</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($transactions as $row)
                        @php
                            $isVoid = in_array($row->cstatus, ['VOID', 'CANCELLED', 'REFUND']);
                            $netSalesRow = $isVoid ? 0 : max(0, $row->ngrandtotal - $row->total_void_refund);
                            
                            if($row->min_cost < 0) {
                                $modalLabel = '-';
                                $labaLabel = '-';
                            } else {
                                $modalLabel = 'Rp ' . number_format($row->total_cost, 0, ',', '.');
                                $laba = $netSalesRow - $row->total_cost;
                                $labaLabel = 'Rp ' . number_format($laba, 0, ',', '.');
                            }
                        @endphp
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td class="text-center">{{ \Carbon\Carbon::parse($row->dtransaction)->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <strong>{{ $row->cnotransaction }}</strong>
                                @if($row->csource === 'OLSERA')
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;">OLSERA</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->cstatus === 'PAID')
                                    <span class="badge bg-success">PAID</span>
                                @elseif($isVoid)
                                    <span class="badge bg-danger">{{ $row->cstatus }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $row->cstatus }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $row->cashier_name ?? '-' }}</td>
                            <td class="text-center">{{ $row->final_customer_name }}</td>
                            
                            <td class="text-end text-muted">Rp {{ number_format($row->ngrandtotal, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ $row->total_void_refund > 0 ? '-Rp '.number_format($row->total_void_refund, 0, ',', '.') : 'Rp 0' }}</td>
                            <td class="text-end text-warning">Rp {{ number_format($row->ndiscount, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row->ntax, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row->nservice_charge, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row->nrounding, 0, ',', '.') }}</td>
                            
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($netSalesRow, 0, ',', '.') }}</td>
                            
                            <td class="text-end {{ $row->min_cost < 0 ? 'text-danger' : '' }}" title="{{ $row->min_cost < 0 ? 'Modal tidak lengkap' : '' }}">{{ $modalLabel }}</td>
                            <td class="text-end fw-bold text-success">{{ $labaLabel }}</td>
                            <td class="text-center text-muted">-</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-4 text-muted">Tidak ada data transaksi yang ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!request()->has('print') && method_exists($transactions, 'hasPages'))
        <div class="d-flex justify-content-center mt-3 order-4">
            {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade modern-modal" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('reports.sales-detail') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Laporan Penjualan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label text-muted small mb-1">Dari</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                            </div>
                            <div class="col-auto d-flex align-items-end" style="padding-bottom: 2px;">
                                <button type="button" class="btn btn-link p-1 border-0 sync-date-btn" id="syncDateBtn" title="Samakan Tanggal">
                                    <i class="bi bi-arrow-left-right fs-5"></i>
                                </button>
                            </div>
                            <div class="col">
                                <label class="form-label text-muted small mb-1">Sampai</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="form-select tom-select-filter" placeholder="Semua Outlet" {{ $isCashierOnly ? 'disabled' : '' }}>
                            <option value="">Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ $outletId == $outlet->nid ? 'selected' : '' }}>{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @if($isCashierOnly)
                            <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status Transaksi</label>
                        <select name="status" id="status" class="form-select tom-select-filter" placeholder="Semua Status">
                            <option value="">Semua Status</option>
                            <option value="PAID" {{ $status == 'PAID' ? 'selected' : '' }}>PAID</option>
                            <option value="VOID" {{ $status == 'VOID' ? 'selected' : '' }}>VOID</option>
                            <option value="REFUND" {{ $status == 'REFUND' ? 'selected' : '' }}>REFUND</option>
                            <option value="CANCELLED" {{ $status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="{{ route('reports.sales-detail') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tom-select-filter').forEach((el) => {
            if (!el.disabled) {
                new TomSelect(el, {
                    create: false,
                    maxItems: 1,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    placeholder: el.getAttribute('placeholder'),
                    allowEmptyOption: true
                });
            }
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
                    font-size: 0.65rem !important;
                    border-collapse: collapse !important;
                }
                .d-print-block {
                    display: block !important;
                }
                a {
                    text-decoration: none !important;
                    color: #000 !important;
                }
                body {
                    margin-top: 1.5cm;
                    margin-bottom: 1.5cm;
                    margin-left: 1cm;
                    margin-right: 1cm;
                }
            }
            @page {
                size: landscape;
                margin: 0;
            }
        `;
        document.head.appendChild(style);

        // Sync Date Button
        const syncBtn = document.getElementById('syncDateBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function() {
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                if (startInput && endInput && startInput.value) {
                    endInput.value = startInput.value;
                }
            });
        }

        @if(request()->has('print'))
            window.print();
        @endif
    });
</script>
@endpush