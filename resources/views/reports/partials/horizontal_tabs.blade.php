@php
$currentCategory = $currentCategory ?? 'penjualan';
@endphp


<style>
    .report-headbar-tabs {
        background-color: #fff;
        overflow: hidden;
    }

    .report-horizontal-nav {
        padding: 0;
        margin: 0;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE / Edge */
    }

    .report-horizontal-nav::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
    }

    .report-category-tab {
        padding: 12px 18px;
        font-size: 0.88rem;
        font-weight: 500;
        color: #475569;
        text-decoration: none;
        white-space: nowrap;
        border-bottom: 3px solid transparent;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }

    .report-category-tab:hover:not(.opacity-60) {
        color: #0284c7;
        background-color: #f8fafc;
    }

    .report-category-tab.active {
        color: #0284c7;
        font-weight: 700;
        background-color: transparent;
        border-bottom: 3px solid #0284c7;
    }
</style>