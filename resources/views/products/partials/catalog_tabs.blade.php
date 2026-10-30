@php
    $activeTab = $activeTab ?? (request()->routeIs('categories.*') ? 'categories' : 'products');
@endphp

<div class="catalog-headbar-tabs bg-white border-bottom">
    <div class="d-flex align-items-stretch flex-nowrap catalog-horizontal-nav">
        <a href="{{ route('products.index') }}"
            class="catalog-nav-tab {{ $activeTab === 'products' ? 'active' : '' }}">
            <span>Produk</span>
        </a>
        <a href="{{ route('categories.index') }}"
            class="catalog-nav-tab {{ $activeTab === 'categories' ? 'active' : '' }}">
            <span>Kategori</span>
        </a>
    </div>
</div>

<style>
    .catalog-headbar-tabs {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .catalog-horizontal-nav {
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
        min-width: 190px;
        padding: 12px 28px;
        font-size: 0.84rem;
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
            flex: 1 1 50%;
            min-width: 0;
            padding: 11px 16px;
        }
    }
</style>

