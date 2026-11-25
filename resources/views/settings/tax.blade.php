@extends('layouts.app')

@section('title', 'Pengaturan Pajak & Biaya')

@section('content')
<style>
    .tax-compact-wrap {
        max-width: 1300px;
        margin: 0 auto;
        font-size: 12.5px;
    }

    .tax-compact-wrap .card-header {
        padding: 0.55rem 0.9rem;
    }

    .tax-compact-wrap .card-body {
        padding: 0.9rem 1rem;
    }

    .tax-compact-wrap .section-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 0.65rem;
    }

    .tax-compact-wrap .form-label,
    .tax-compact-wrap .switch-label {
        font-size: 12px;
        margin-bottom: 0.2rem;
    }

    .tax-compact-wrap .helper-text {
        font-size: 11px;
        line-height: 1.35;
    }

    .tax-compact-wrap .form-control,
    .tax-compact-wrap .input-group-text {
        font-size: 12.5px;
        padding: 0.3rem 0.6rem;
    }

    .tax-compact-wrap .form-switch .form-check-input {
        width: 2.1em;
        height: 1.05em;
    }

    .tax-compact-wrap .nav-tabs .nav-link {
        font-size: 12.5px;
        padding: 0.45rem 0.85rem;
    }

    .tax-compact-wrap .ppn12-box {
        font-size: 11.5px;
        padding: 0.55rem 0.75rem;
    }
</style>

<div class="container-fluid py-2 tax-compact-wrap">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Info Outlet Aktif (mengikuti switcher header kanan atas) -->
    @if($selectedOutletId <= 0)
        <div class="alert alert-warning py-2 small mb-2">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Saat ini Anda berada pada mode <strong>Semua Outlet</strong>. Silakan pilih salah satu outlet pada dropdown di pojok kanan atas untuk mengatur pajak & biaya outlet tersebut.
</div>
@else
<div class="card border-0 shadow-sm mb-2">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3">
        <div>
            <div class="fw-bold" style="font-size: 14px;">Pajak & Biaya</div>
            <div class="helper-text text-muted">Atur skema pajak transaksi Offline dan Online untuk <strong>{{ $selectedOutlet?->cname ?? 'Outlet' }}</strong>.</div>
        </div>
        <span class="badge bg-light text-dark border" style="font-size: 11.5px;">
            <i class="bi bi-shop me-1"></i>{{ $selectedOutlet?->cname ?? 'Outlet' }}
        </span>
    </div>
</div>

<!-- Tabs Offline vs Online -->
<ul class="nav nav-tabs mb-2" id="taxSettingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'offline' ? 'active fw-semibold' : '' }}" id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline-pane" type="button" role="tab">
            <i class="bi bi-shop me-1"></i> Transaksi Offline (Kena Pajak)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'online' ? 'active fw-semibold' : '' }}" id="online-tab" data-bs-toggle="tab" data-bs-target="#online-pane" type="button" role="tab">
            <i class="bi bi-globe me-1"></i> Transaksi Online (Non-Pajak)
        </button>
    </li>
</ul>

<div class="tab-content">
    @foreach([
    'offline' => ['setting' => $offlineSetting, 'category' => 'OFFLINE', 'isOnline' => false],
    'online' => ['setting' => $onlineSetting, 'category' => 'ONLINE', 'isOnline' => true],
    ] as $key => $cfg)
    @php
    $setting = $cfg['setting'];
    $isOnline = $cfg['isOnline'];
    @endphp
    <div class="tab-pane fade {{ $activeTab === $key ? 'show active' : '' }}" id="{{ $key }}-pane" role="tabpanel">
        <form method="POST" action="{{ route('settings.tax.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
            <input type="hidden" name="ccategory" value="{{ $cfg['category'] }}">
            <input type="hidden" name="nservice_charge_percent" value="0">

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Kolom Kiri: Rincian Pajak -->
                        <div class="col-lg-6">
                            <div class="section-title text-success">Rincian Pajak</div>

                            @if($isOnline)
                            <div class="alert alert-info py-2 px-3 helper-text mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Transaksi <strong>Online</strong> tidak dikenakan pajak di POS (<strong>0%</strong>).
                            </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nama Pajak</label>
                                <input
                                    type="text"
                                    name="ctax_name"
                                    class="form-control form-control-sm {{ $isOnline ? 'bg-light' : '' }}"
                                    placeholder="misal: PPN, GST"
                                    value="{{ $isOnline ? '' : old('ctax_name', $setting?->ctax_name ?? 'PB1') }}"
                                    {{ $isOnline ? 'readonly disabled' : '' }}>
                                <div class="helper-text text-muted mt-1">misal: PPN, GST</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Persentase Pajak</label>
                                <div class="input-group input-group-sm">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="ntax_percent"
                                        class="form-control {{ $isOnline ? 'bg-light' : '' }}"
                                        value="{{ $isOnline ? 0 : old('ntax_percent', $setting?->ntax_percent ?? 10) }}"
                                        {{ $isOnline ? 'readonly disabled' : '' }}>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <!-- Toggle: Harga produk sudah termasuk pajak -->
                            <div class="mb-2">
                                <div class="switch-label">Harga produk sudah termasuk pajak</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}IncludeTax" name="bis_tax_included" value="1"
                                            {{ !$isOnline && old('bis_tax_included', $setting?->bis_tax_included ?? true) ? 'checked' : '' }}
                                            {{ $isOnline ? 'disabled' : '' }}>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                                @if(!$isOnline)

                                @endif
                            </div>

                            <!-- Toggle: Kebijakan 11/12 PPN 12% -->
                            <div class="mb-2">
                                <div class="switch-label">Terapkan perhitungan 11/12 kebijakan PPN 12%</div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}Ppn1112" name="bppn_11_12" value="1"
                                            {{ !$isOnline && old('bppn_11_12', $setting?->bppn_11_12 ?? false) ? 'checked' : '' }}
                                            {{ $isOnline ? 'disabled' : '' }}>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                                <div class="bg-light border rounded ppn12-box d-flex align-items-start gap-2 text-muted">
                                    <i class="bi bi-info-circle mt-1"></i>
                                    <div>
                                        <div class="fw-semibold text-dark mb-1">Contoh perhitungan 11/12 PPN 12%</div>
                                        <ul class="mb-0 ps-3">
                                            <li>Harga jual = Rp 111.000 / 1.11 = Rp 100.000</li>
                                            <li>DPP = Rp 100.000 x 11/12 = Rp 91.666,66</li>
                                            <li>PPN = Rp 91.666,66 x 12 % = Rp 11.000</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle: Bungkus tidak dikenakan pajak -->
                            <div class="mb-2">
                                <div class="switch-label">Bungkus (tidak makan di tempat) tidak dikenakan pajak</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}TakeawayExempt" name="btakeaway_tax_exempt" value="1"
                                            {{ !$isOnline && old('btakeaway_tax_exempt', $setting?->btakeaway_tax_exempt ?? false) ? 'checked' : '' }}
                                            {{ $isOnline ? 'disabled' : '' }}>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                            </div>

                            <!-- Toggle: Pajak bisa ditiadakan -->
                            <div class="mb-2">
                                <div class="switch-label">Pajak (Aplikasi Kasir) bisa ditiadakan untuk tujuan tertentu, misalnya bungkus (tidak makan di tempat)</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}ManualExempt" name="ballow_manual_tax_exempt" value="1"
                                            {{ !$isOnline && old('ballow_manual_tax_exempt', $setting?->ballow_manual_tax_exempt ?? false) ? 'checked' : '' }}
                                            {{ $isOnline ? 'disabled' : '' }}>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                            </div>

                            <!-- Toggle: Aktifkan pilihan pajak sebelum diskon -->
                            <div class="mb-0">
                                <div class="switch-label">Aktifkan pilihan pajak sebelum diskon</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}TaxBeforeDiscount" name="btax_before_discount" value="1"
                                            {{ !$isOnline && old('btax_before_discount', $setting?->btax_before_discount ?? false) ? 'checked' : '' }}
                                            {{ $isOnline ? 'disabled' : '' }}>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Rincian Biaya Layanan & Pengantaran Makanan (Read-only / Belum Aktif) -->
                        <div class="col-lg-6 opacity-75">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="section-title text-success mb-0">Rincian Biaya Layanan</span>
                                <span class="badge bg-secondary" style="font-size: 10px;">Fitur Belum Aktif</span>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Biaya Layanan (Excluded)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control bg-light" value="0" readonly disabled>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="helper-text text-muted mt-1">Diimplementasikan untuk POS di restoran, cafe, dll.</div>
                            </div>

                            @foreach([
                            'Bungkus (tidak makan di tempat) dikenakan biaya layanan',
                            'Biaya layanan (Aplikasi Kasir) bisa ditiadakan untuk tujuan tertentu, misalnya bungkus (tidak makan di tempat)',
                            'Biaya layanan tidak dikenakan pajak',
                            'Harga produk sudah termasuk biaya layanan',
                            ] as $label)
                            <div class="mb-2">
                                <div class="switch-label">{{ $label }}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" disabled>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                            </div>
                            @endforeach

                            <div class="section-title text-success mt-3">Pengantaran Makanan</div>

                            @foreach([
                            'Pengantaran Makanan dikenakan biaya layanan',
                            'Pengantaran Makanan dikenakan pajak',
                            'Pembulatan biaya Pengantaran Makanan',
                            ] as $label)
                            <div class="mb-2">
                                <div class="switch-label">{{ $label }}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="helper-text text-primary">Tidak</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" disabled>
                                    </div>
                                    <span class="helper-text">Ya</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @if(!$isOnline)
                    <hr class="my-3">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-save me-1"></i> Simpan Pengaturan Pajak Offline
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
    @endforeach
</div>
@endif
</div>
@endsection