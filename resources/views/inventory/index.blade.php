@extends('layouts.app')
@section('custom_alerts', '1')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
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

    /* Qty Stepper Widget Gaya Modern Olsera (Button Putih dengan Overlay Biru saat Hover) */
    .qty-stepper {
        display: inline-flex;
        align-items: stretch;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: all 0.2s ease;
        position: relative;
        max-width: 135px;
        height: 33px;
    }

    .qty-stepper:hover,
    .qty-stepper:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .qty-stepper-btn {
        border: none;
        outline: none;
        width: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ffffff;
        color: #64748b;
        cursor: pointer;
        position: relative;
        transition: all 0.18s ease;
        padding: 0;
        font-size: 0.85rem;
    }

    .qty-stepper-minus {
        border-right: 1px solid #e2e8f0;
    }

    .qty-stepper-plus {
        border-left: 1px solid #e2e8f0;
    }

    /* Efek Hover: Overlay Warna Biru Olsera */
    .qty-stepper-btn:hover {
        background-color: #0ea5e9;
        color: #ffffff;
    }

    .qty-stepper-btn:active {
        background-color: #0284c7;
        color: #ffffff;
        transform: scale(0.92);
    }

    /* Input Angka di Tengah */
    .qty-stepper-input {
        border: none !important;
        outline: none !important;
        width: 100%;
        min-width: 50px;
        text-align: center;
        font-weight: 700;
        font-size: 0.92rem;
        color: #0f172a;
        background-color: #ffffff;
        padding: 0 4px;
        margin: 0;
        box-shadow: none !important;
    }

    .qty-stepper-input:focus {
        background-color: #ffffff;
        box-shadow: none !important;
        outline: none !important;
    }

    /* Hilangkan panah spinner atas bawah pada input number */
    .qty-stepper-input::-webkit-outer-spin-button,
    .qty-stepper-input::-webkit-inner-spin-button,
    .no-spinners::-webkit-outer-spin-button,
    .no-spinners::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }

    .qty-stepper-input,
    .no-spinners {
        -moz-appearance: textfield !important;
        appearance: textfield !important;
    }

    /* Table Compact Styling untuk Modal Tambah Stok Masuk & Stok Keluar */
    #stockInItemsTable th,
    #stockOutItemsTable th {
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.4px !important;
        color: #475569 !important;
        padding: 7px 5px !important;
        vertical-align: middle !important;
    }

    #stockInItemsTable td,
    #stockOutItemsTable td {
        padding: 5px 5px !important;
        vertical-align: middle !important;
        font-size: 0.79rem !important;
    }

    #stockInItemsTable .in-item-type-select,
    #stockOutItemsTable .out-item-type-select {
        font-size: 0.78rem !important;
        height: 31px !important;
        padding: 2px 20px 2px 7px !important;
        background-position: right 6px center !important;
        background-size: 10px 10px !important;
    }

    #stockInItemsTable .ts-control,
    #stockOutItemsTable .ts-control {
        font-size: 0.79rem !important;
        min-height: 31px !important;
        height: 31px !important;
        padding: 2px 8px !important;
    }

    #stockInItemsTable .ts-dropdown,
    #stockOutItemsTable .ts-dropdown {
        font-size: 0.79rem !important;
    }

    #stockInItemsTable .in-item-cost-input,
    #stockOutItemsTable .out-item-cost-input {
        font-size: 0.79rem !important;
        height: 31px !important;
        padding: 2px 6px !important;
    }

    #stockInItemsTable .input-group-text,
    #stockOutItemsTable .input-group-text {
        font-size: 0.74rem !important;
        padding: 2px 6px !important;
        height: 31px !important;
    }

    #stockInItemsTable .qty-stepper,
    #stockOutItemsTable .qty-stepper,
    #tableProductionProducts .qty-stepper,
    #tableProductionMaterials .qty-stepper {
        height: 31px !important;
        max-width: 105px !important;
    }

    #stockInItemsTable .qty-stepper-btn,
    #stockOutItemsTable .qty-stepper-btn,
    #tableProductionProducts .qty-stepper-btn,
    #tableProductionMaterials .qty-stepper-btn {
        width: 27px !important;
        min-width: 27px !important;
        font-size: 0.75rem !important;
    }

    #stockInItemsTable .qty-stepper-input,
    #stockOutItemsTable .qty-stepper-input,
    #tableProductionProducts .qty-stepper-input,
    #tableProductionMaterials .qty-stepper-input {
        font-size: 0.82rem !important;
        min-width: 36px !important;
    }

    #stockInItemsTable .in-current-stock-badge,
    #stockOutItemsTable .out-current-stock-badge {
        font-size: 0.74rem !important;
        padding: 3px 6px !important;
    }

    #stockInItemsTable .in-item-subtotal-display,
    #stockOutItemsTable .out-item-subtotal-display {
        font-size: 0.79rem !important;
    }
</style>
@endpush

@php
$tabTitles = [
'stok_masuk' => 'Stok Masuk',
'stok_keluar' => 'Stok Keluar',
'produksi_stok' => 'Produksi Stok',
'stok_opname' => 'Stok Opname',
'pergerakan_stok' => 'Pergerakan Stok',
];
$currentTabName = $tabTitles[$activeTab] ?? 'Stok Masuk';
@endphp

@section('title', 'Inventori / ' . $currentTabName)

@section('sub_header')
<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('inventory.index', ['tab' => 'stok_masuk']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_masuk' ? 'active' : '' }}">
            <span>Stok Masuk</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_keluar']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_keluar' ? 'active' : '' }}">
            <span>Stok Keluar</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'produksi_stok']) }}"
            class="catalog-nav-tab {{ $activeTab === 'produksi_stok' ? 'active' : '' }}">
            <span>Produksi Stok</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'stok_opname']) }}"
            class="catalog-nav-tab {{ $activeTab === 'stok_opname' ? 'active' : '' }}">
            <span>Stok Opname</span>
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'pergerakan_stok']) }}"
            class="catalog-nav-tab {{ $activeTab === 'pergerakan_stok' ? 'active' : '' }}">
            <span>Pergerakan Stok</span>
        </a>
    </div>
</div>

<style>
    .catalog-headbar-tabs {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .catalog-horizontal-nav {
        width: 100%;
        padding: 0;
        margin: 0;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .catalog-horizontal-nav::-webkit-scrollbar {
        display: none;
    }

    .catalog-nav-tab {
        flex: 1 1 0;
        min-width: 120px;
        padding: 12px 16px;
        font-size: 0.86rem;
        font-weight: 500;
        color: #334155;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-bottom: 2.5px solid transparent;
        transition: all 0.2s ease;
    }

    .catalog-nav-tab:hover {
        color: #b63352;
        background-color: #fff8f9;
    }

    .catalog-nav-tab.active {
        color: #b63352;
        font-weight: 600;
        background-color: #fff1f2;
        border-bottom-color: #b63352;
    }

    @media (max-width: 575.98px) {
        .catalog-nav-tab {
            min-width: 0;
            padding: 11px 16px;
        }
    }

    /* Style Filter Chip Persis Halaman Laporan Penjualan */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 0.84rem;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
        text-decoration: none;
    }

    .filter-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        filter: brightness(0.96);
    }

    .filter-chip i {
        margin-right: 6px;
    }

    .filter-chip-date {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .filter-chip-date:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .filter-chip-date i.bi-calendar {
        color: #b63352;
    }

    .filter-chip.dropdown-toggle::after {
        display: none !important;
    }

    .filter-dropdown-menu {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        font-size: 0.85rem;
        z-index: 1060 !important;
    }

    .filter-dropdown-menu .dropdown-item {
        padding: 7px 14px;
        font-weight: 500;
        color: #334155;
        display: flex;
        align-items: center;
        transition: background 0.15s ease;
    }

    .filter-dropdown-menu .dropdown-item:hover,
    .filter-dropdown-menu .dropdown-item:focus {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }

    .filter-dropdown-menu .dropdown-item.active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 600;
    }

    .dropdown-sync-date-btn {
        color: #64748b;
        text-decoration: none;
    }

    .dropdown-sync-date-btn:hover {
        color: #0ea5e9;
    }

    .olsera-panel-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03) !important;
    }

    /* Border Pemisah Kolom Tabel Olsera Style */
    .olsera-panel-card .table-bordered,
    .olsera-panel-card .table-bordered th,
    .olsera-panel-card .table-bordered td {
        border-color: #e2e8f0 !important;
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card olsera-panel-card mb-4">
    <div class="card-body p-4">
        {{-- ==================== TAB 1: STOK MASUK ==================== --}}
        @if($activeTab === 'stok_masuk')
            @include('inventory.partials.tab_stok_masuk')
        @elseif($activeTab === 'stok_keluar')
            @include('inventory.partials.tab_stok_keluar')
        @elseif($activeTab === 'produksi_stok')
            @include('inventory.partials.tab_produksi_stok')
        @elseif($activeTab === 'stok_opname')
            @include('inventory.partials.tab_stok_opname')
        @elseif($activeTab === 'pergerakan_stok')
            @include('inventory.partials.tab_pergerakan_stok')
        @endif
    </div>
</div>
@endsection
