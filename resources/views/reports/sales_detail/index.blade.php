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
    
    .table td {
        padding: 1rem 0.75rem !important;
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

@if(session('import_success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3 shadow-sm border-0 d-print-none" role="alert">
        <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
        <div class="flex-grow-1">
            <strong>Import Olsera Berhasil!</strong><br>
            Tersimpan <strong>{{ session('import_success.saved_count') }}</strong> transaksi valid (Penjualan Nett: <strong>Rp {{ session('import_success.total_net_amount') }}</strong>) untuk outlet <strong>{{ session('import_success.outlet_name') }}</strong>.<br>
            <span class="small text-muted">
                Dilewati secara aman: {{ session('import_success.duplicate_skipped') }} duplikat, {{ session('import_success.conflict_skipped') }} konflik data, {{ session('import_success.invalid_rows') }} baris tidak valid.
            </span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">Laporan Rincian Penjualan</h5>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importOlseraModal">
                <i class="bi bi-cloud-arrow-up"></i> Import Olsera
            </button>
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
                        <th class="py-3 text-center">TOTAL PENJUALAN</th>
                        <th class="py-3 text-center">VOID/REFUND</th>
                        <th class="py-3 text-center">DISKON</th>
                        <th class="py-3 text-center">PAJAK</th>
                        <th class="py-3 text-center">SERVICE</th>
                        <th class="py-3 text-center">PEMBULATAN</th>
                        <th class="py-3 text-center fw-bold text-primary">PENJUALAN NETT</th>
                        <th class="py-3 text-center">MODAL</th>
                        <th class="py-3 text-center fw-bold text-success">LABA</th>
                        <th class="py-3 text-center">DEPOSIT</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($transactions as $row)
                        @php
                            $isVoid = in_array($row->cstatus, ['VOID', 'CANCELLED', 'REFUND']);
                            
                            if ($row->csource === 'OLSERA') {
                                $grossSalesRow = $row->nsubtotal;
                                $netSalesRow = $row->ngrandtotal;
                                
                                if ($row->ntotal_cost !== null) {
                                    $modalLabel = 'Rp ' . number_format($row->ntotal_cost, 0, ',', '.');
                                    $isModalMissing = false;
                                } else {
                                    $modalLabel = '-';
                                    $isModalMissing = true;
                                }
                                
                                if ($row->ntotal_profit !== null) {
                                    $labaLabel = 'Rp ' . number_format($row->ntotal_profit, 0, ',', '.');
                                    $isLabaMissing = false;
                                } else {
                                    $labaLabel = '-';
                                    $isLabaMissing = true;
                                }
                            } else {
                                $grossSalesRow = $row->ngrandtotal;
                                $netSalesRow = $isVoid ? 0 : max(0, $row->ngrandtotal - $row->total_void_refund);
                                
                                if ($row->min_cost < 0) {
                                    $modalLabel = '-';
                                    $labaLabel = '-';
                                    $isModalMissing = true;
                                    $isLabaMissing = true;
                                } else {
                                    $modalLabel = 'Rp ' . number_format($row->total_cost, 0, ',', '.');
                                    $laba = $netSalesRow - $row->total_cost;
                                    $labaLabel = 'Rp ' . number_format($laba, 0, ',', '.');
                                    $isModalMissing = false;
                                    $isLabaMissing = false;
                                }
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
                            
                            <td class="text-center text-muted">Rp {{ number_format($grossSalesRow, 0, ',', '.') }}</td>
                            <td class="text-center text-danger">{{ $row->total_void_refund > 0 ? '-Rp '.number_format($row->total_void_refund, 0, ',', '.') : 'Rp 0' }}</td>
                            <td class="text-center text-warning">Rp {{ number_format($row->ndiscount, 0, ',', '.') }}</td>
                            <td class="text-center">Rp {{ number_format($row->ntax, 0, ',', '.') }}</td>
                            <td class="text-center">Rp {{ number_format($row->nservice_charge, 0, ',', '.') }}</td>
                            <td class="text-center">Rp {{ number_format($row->nrounding, 0, ',', '.') }}</td>
                            
                            <td class="text-center fw-bold text-primary">Rp {{ number_format($netSalesRow, 0, ',', '.') }}</td>
                            
                            <td class="text-center {{ $isModalMissing ? 'text-danger' : '' }}" title="{{ $isModalMissing ? 'Modal tidak lengkap / kosong (-)' : '' }}">{{ $modalLabel }}</td>
                            <td class="text-center fw-bold {{ $isLabaMissing ? 'text-danger' : 'text-success' }}" title="{{ $isLabaMissing ? 'Laba tidak lengkap / kosong (-)' : '' }}">{{ $labaLabel }}</td>
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

<!-- Modal 1: Upload File Olsera (Langkah 1: Preview) -->
<div class="modal fade modern-modal" id="importOlseraModal" tabindex="-1" aria-labelledby="importOlseraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('reports.sales-detail.import-preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="importOlseraModalLabel"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Import Transaksi Olsera</h5>
                        <p class="text-muted small mb-0">Upload file export Excel / CSV dari sistem Olsera POS</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3" style="background-color: #eff6ff; color: #1e40af;">
                        <i class="bi bi-shield-check me-1"></i> <strong>Mode Aman (Dry-Run):</strong> File akan diaudit dan divalidasi terlebih dahulu. Database Anda <strong>tidak akan diubah</strong> sebelum Anda memeriksa dan menyetujui ringkasan di layar konfirmasi.
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="import_outlet_id">Pilih Outlet Tujuan <span class="text-danger">*</span></label>
                        <select name="outlet_id" id="import_outlet_id" class="form-select" required {{ $isCashierOnly ? 'disabled' : '' }}>
                            @if(!$isCashierOnly)
                                <option value="">-- Pilih Outlet --</option>
                            @endif
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->nid }}" {{ ($outletId == $outlet->nid || (count($outlets) == 1)) ? 'selected' : '' }}>{{ $outlet->cname }}</option>
                            @endforeach
                        </select>
                        @if($isCashierOnly)
                            <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        @endif
                        <div class="form-text small">Transaksi akan dicatat sebagai riwayat transaksi outlet yang dipilih.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="import_file">File Excel Olsera (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text small">Maksimal ukuran file 20 MB. Mendukung format standar export transaksi Olsera POS.</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 small text-muted">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1"></i> Ketentuan Mapping & Validasi:</div>
                        <ul class="mb-0 ps-3">
                            <li>Transaksi disimpan sebagai <em>Header-Only</em> di <code>mpos_sales_h</code>.</li>
                            <li>Waktu diambil dari <code>order time</code> (waktu <code>00:00:00</code> bawaan tanggal diabaikan).</li>
                            <li>Status transaksi dipetakan ke <code>PAID</code> untuk baris dengan <code>paid = 1</code>.</li>
                            <li>Modal & laba yang kosong akan disimpan kosong (<code>-</code>) tanpa rumus pengganti.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Lanjutkan ke Preview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Preview & Konfirmasi Audit Olsera (Langkah 2: Konfirmasi) -->
@if(session('preview_data'))
@php
    $preview = session('preview_data');
    $results = $preview['results'] ?? [];
    $hasIssues = !empty($results['issues']);
    $validCount = $results['valid_rows'] ?? 0;
@endphp
<div class="modal fade modern-modal" id="previewAuditModal" tabindex="-1" aria-labelledby="previewAuditModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="previewAuditModalLabel"><i class="bi bi-clipboard-check text-success me-2"></i>Hasil Audit Preview File Olsera</h5>
                    <div class="small text-muted">
                        File: <strong class="text-dark">{{ $preview['original_filename'] ?? '-' }}</strong> &bull;
                        Outlet: <strong class="text-primary">{{ $preview['outlet_name'] ?? '-' }}</strong> &bull;
                        Total Baris: <strong>{{ $results['total_rows'] ?? 0 }}</strong>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Metrik Ringkasan Audit -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border border-success-subtle bg-success bg-opacity-10 h-100">
                            <div class="small fw-bold text-success text-uppercase">Siap Diimpor (Valid)</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($validCount, 0, ',', '.') }} Baris</div>
                            <div class="small text-muted mt-1">Nett: <strong>Rp {{ number_format($results['valid_net_amount'] ?? 0, 0, ',', '.') }}</strong></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border border-warning-subtle bg-warning bg-opacity-10 h-100">
                            <div class="small fw-bold text-warning text-uppercase">Duplikat (Dilewati)</div>
                            <div class="fs-4 fw-bold text-warning">{{ number_format($results['duplicate_skipped'] ?? 0, 0, ',', '.') }} Baris</div>
                            <div class="small text-muted mt-1">Nett: Rp {{ number_format($results['duplicate_net_amount'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border border-danger-subtle bg-danger bg-opacity-10 h-100">
                            <div class="small fw-bold text-danger text-uppercase">Konflik Data (Dilewati)</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($results['conflict_skipped'] ?? 0, 0, ',', '.') }} Baris</div>
                            <div class="small text-muted mt-1">Nett: Rp {{ number_format($results['conflict_net_amount'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border border-secondary-subtle bg-light h-100">
                            <div class="small fw-bold text-secondary text-uppercase">Format Tidak Valid</div>
                            <div class="fs-4 fw-bold text-secondary">{{ number_format($results['invalid_rows'] ?? 0, 0, ',', '.') }} Baris</div>
                            <div class="small text-muted mt-1">Qty rusak / status belum dikenali</div>
                        </div>
                    </div>
                </div>

                @if($validCount === 0)
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                        <div>
                            <strong>Tidak ada baris valid untuk diimpor.</strong> Seluruh baris transaksi dalam file ini telah ada di database atau memiliki format yang tidak valid.
                        </div>
                    </div>
                @else
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3" style="background-color: #eff6ff; color: #1e40af;">
                        <i class="bi bi-info-circle me-1"></i> Hanya <strong>{{ $validCount }} baris transaksi valid</strong> yang akan disimpan ke database. Transaksi duplikat dan konflik akan dilewati tanpa mengubah data yang sudah ada. Sesi konfirmasi berlaku selama 15 menit.
                    </div>
                @endif

                <!-- Daftar Isu / Baris Bermasalah -->
                @if($hasIssues)
                    <div class="card border-0 shadow-none bg-light mb-3">
                        <div class="card-header bg-transparent py-2 fw-bold small text-muted text-uppercase">
                            <i class="bi bi-list-check me-1"></i> Rincian Baris yang Memerlukan Perhatian (Total: {{ count($results['issues']) }})
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 250px;">
                                <table class="table table-sm table-hover mb-0 small align-middle">
                                    <thead class="table-light text-secondary sticky-top">
                                        <tr>
                                            <th class="py-2 px-3" style="width: 80px;">Baris</th>
                                            <th class="py-2" style="width: 150px;">No. Pesanan</th>
                                            <th class="py-2" style="width: 140px;">Status Audit</th>
                                            <th class="py-2">Keterangan / Tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($results['issues'] as $issue)
                                            <tr>
                                                <td class="px-3 fw-bold">{{ $issue['row'] ?? '-' }}</td>
                                                <td><code>{{ $issue['order_no'] ?? '-' }}</code></td>
                                                <td>
                                                    @if(($issue['type'] ?? '') === 'DUPLICATE')
                                                        <span class="badge bg-warning text-dark">DUPLIKAT (DILEWATI)</span>
                                                    @elseif(($issue['type'] ?? '') === 'CONFLICT')
                                                        <span class="badge bg-danger">KONFLIK DATA (DILEWATI)</span>
                                                    @else
                                                        <span class="badge bg-secondary">TIDAK VALID (DILEWATI)</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $issue['message'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal (Tutup)</button>
                <form action="{{ route('reports.sales-detail.import-confirm') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ session('preview_token') }}">
                    <button type="submit" class="btn btn-success" {{ $validCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-check-circle me-1"></i> Konfirmasi & Simpan {{ $validCount }} Transaksi Valid
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('preview_data'))
            const previewModalEl = document.getElementById('previewAuditModal');
            if (previewModalEl) {
                const previewModal = new bootstrap.Modal(previewModalEl);
                previewModal.show();
            }
        @endif
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