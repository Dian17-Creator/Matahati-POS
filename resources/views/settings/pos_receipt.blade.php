@extends('layouts.app')

@section('title', 'Point Of Sales / Settings')

@section('sub_header')
@include('settings.partials.pos_tabs', ['activeTab' => 'settings'])
@endsection

@section('content')
<div class="container-fluid py-2">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Info Outlet Aktif (mengikuti switcher header kanan atas) -->
    @if($selectedOutletId <= 0)
        <div class="alert alert-warning py-2 small mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Saat ini Anda berada pada mode <strong>Semua Outlet</strong>. Silakan pilih salah satu outlet pada dropdown di pojok kanan atas untuk mengatur catatan resi outlet tersebut.
</div>
@else
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
        <div>
            <h5 class="mb-0 fw-bold">Point of Sale / Catatan Resi</h5>
            <small class="text-muted">Atur judul resi, catatan bawah resi, dan lihat contoh tampilan struk untuk <strong>{{ $selectedOutlet?->cname ?? 'Outlet' }}</strong>.</small>
        </div>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-shop me-1"></i>{{ $selectedOutlet?->cname ?? 'Outlet' }}
        </span>
    </div>
</div>

<div class="row g-3">
    <!-- Menu Kiri (seperti Gambar 2) -->
    <div class="col-lg-3 col-xl-2">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small text-muted py-2 d-flex align-items-center justify-content-between">
                <span>Menu</span>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('settings.pos.receipt') }}" class="list-group-item list-group-item-action border-0 py-2 px-3 fw-semibold" style="background-color: #fff1f2; color: #b63352;">
                    Catatan Resi
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-9 col-xl-10">
        <!-- Tabs Offline vs Online -->
        <ul class="nav nav-tabs mb-3" id="receiptTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'offline' ? 'active fw-semibold' : '' }}" id="receipt-offline-tab" data-bs-toggle="tab" data-bs-target="#receipt-offline-pane" type="button" role="tab">
                    <i class="bi bi-receipt me-1"></i> Resi Transaksi Offline
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'online' ? 'active fw-semibold' : '' }}" id="receipt-online-tab" data-bs-toggle="tab" data-bs-target="#receipt-online-pane" type="button" role="tab">
                    <i class="bi bi-globe me-1"></i> Resi Transaksi Online
                </button>
            </li>
        </ul>

        <div class="tab-content">
            @foreach([
            'offline' => ['setting' => $offlineSetting, 'category' => 'OFFLINE'],
            'online' => ['setting' => $onlineSetting, 'category' => 'ONLINE'],
            ] as $key => $cfg)
            @php
            $setting = $cfg['setting'];
            $isOffline = $key === 'offline';
            @endphp
            <div class="tab-pane fade {{ $activeTab === $key ? 'show active' : '' }}" id="receipt-{{ $key }}-pane" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Form Pengaturan Resi -->
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('settings.pos.receipt.update') }}" id="form-receipt-{{ $key }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                                    <input type="hidden" name="ccategory" value="{{ $cfg['category'] }}">

                                    <div class="alert alert-light border small text-muted mb-3">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Hasil cetak resi akan disesuaikan dengan pengaturan struk thermal 80mm untuk transaksi <strong>{{ $cfg['category'] }}</strong>.
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Judul resi</label>
                                        <textarea
                                            name="creceipt_title"
                                            rows="3"
                                            class="form-control receipt-title-input"
                                            data-target="preview-title-{{ $key }}">{{ old('creceipt_title', $setting?->creceipt_title) }}</textarea>
                                        <small class="text-muted">Ditampilkan di bagian paling atas resi.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Catatan Resi</label>
                                        <textarea
                                            name="creceipt_note"
                                            rows="3"
                                            class="form-control receipt-note-input"
                                            data-target="preview-note-{{ $key }}">{{ old('creceipt_note', $setting?->creceipt_note) }}</textarea>
                                        <small class="text-muted">Dicetak di bagian bawah resi.</small>
                                    </div>

                                    <div class="form-check form-switch mb-4">
                                        <input
                                            class="form-check-input receipt-hide-order-input"
                                            type="checkbox"
                                            role="switch"
                                            id="hideOrderNo-{{ $key }}"
                                            name="bhide_order_no"
                                            value="1"
                                            data-target="preview-order-no-{{ $key }}"
                                            {{ old('bhide_order_no', $setting?->bhide_order_no ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hideOrderNo-{{ $key }}">
                                            Sembunyikan no. pesanan
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i> Simpan Catatan Resi {{ ucfirst($key) }}
                                    </button>
                                </form>
                            </div>

                            <!-- Preview Struk -->
                            <div class="col-lg-6">
                                <div class="bg-light rounded p-3 border">
                                    <div class="small text-muted fw-semibold mb-2">Preview</div>
                                    <div class="bg-white mx-auto p-4 shadow-sm" style="max-width: 340px; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #111; line-height: 1.4;">
                                        <div class="text-center fw-bold mb-2" id="preview-title-{{ $key }}" style="white-space: pre-line; font-size: 13px;">{{ $setting?->creceipt_title ?? '' }}</div>

                                        <div class="mt-2">
                                            <div>{{ now()->format('d M Y H:i') }}</div>
                                            <div><span style="display:inline-block; width:82px;">Kasir</span>: Budiman</div>
                                            <div><span style="display:inline-block; width:82px;">Pelanggan</span>: Umum</div>
                                            <div id="preview-order-no-{{ $key }}" style="{{ ($setting?->bhide_order_no ?? false) ? 'display:none;' : '' }}">
                                                {{ $isOffline ? '#1OFF26112400000001' : '#1C6226112400000001' }}
                                            </div>
                                        </div>

                                        <div class="border-top border-dark my-2" style="border-top-style: dashed !important;"></div>
                                        <div class="text-center fw-bold mb-2">
                                            {{ $isOffline ? 'Antrian B01 (DINE-IN)' : 'Order (ONLINE)' }}
                                        </div>

                                        <div class="mb-1">
                                            <div>Kopi Susu Gula Aren</div>
                                            <div class="d-flex justify-content-between">
                                                <span>Rp 20.000 x1</span>
                                                <span>Rp 20.000</span>
                                            </div>
                                        </div>

                                        <div class="border-top border-dark my-2" style="border-top-style: dashed !important;"></div>

                                        <div class="d-flex justify-content-between">
                                            <span>Subtotal</span>
                                            <span>Rp 20.000</span>
                                        </div>
                                        @if($isOffline)
                                        @php
                                        $taxName = $offlineSetting?->ctax_name ?: 'PB1';
                                        $taxPct = (float) ($offlineSetting?->ntax_percent ?? 10);
                                        $isInclude = (bool) ($offlineSetting?->bis_tax_included ?? true);
                                        @endphp
                                        @if($taxPct > 0)
                                        <div class="d-flex justify-content-between">
                                            <span>{{ $taxName }} {{ rtrim(rtrim(number_format($taxPct, 2, '.', ''), '0'), '.') }}%{{ $isInclude ? ' (Include)' : '' }}</span>
                                            <span>{{ $isInclude ? 'Rp 1.818' : 'Rp 2.000' }}</span>
                                        </div>
                                        @endif
                                        @endif

                                        <div class="border-top border-dark my-2" style="border-top-style: dashed !important;"></div>

                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total</span>
                                            <span>{{ ($isOffline && !($offlineSetting?->bis_tax_included ?? true) && ($offlineSetting?->ntax_percent ?? 10) > 0) ? 'Rp 22.000' : 'Rp 20.000' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Tunai</span>
                                            <span>{{ ($isOffline && !($offlineSetting?->bis_tax_included ?? true) && ($offlineSetting?->ntax_percent ?? 10) > 0) ? 'Rp 22.000' : 'Rp 20.000' }}</span>
                                        </div>
                                        <div class="mt-2">
                                            <div>Jumlah item : 1</div>
                                            <div>Dilayani oleh : Budiman</div>
                                        </div>

                                        <div class="text-center mt-3" id="preview-note-{{ $key }}" style="white-space: pre-line;">{{ $setting?->creceipt_note ?? '' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.receipt-title-input, .receipt-note-input').forEach(function(el) {
            el.addEventListener('input', function() {
                const target = document.getElementById(el.dataset.target);
                if (target) {
                    target.textContent = el.value;
                }
            });
        });

        document.querySelectorAll('.receipt-hide-order-input').forEach(function(el) {
            el.addEventListener('change', function() {
                const target = document.getElementById(el.dataset.target);
                if (target) {
                    target.style.display = el.checked ? 'none' : '';
                }
            });
        });
    });
</script>
@endpush
@endsection