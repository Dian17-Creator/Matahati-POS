@php
    $activeReport = $activeReport ?? 'sales_detail';
    $activeReportLabel = 'Rincian Penjualan';
    if ($activeReport === 'sales_item') {
        $activeReportLabel = 'Item Penjualan';
    } elseif ($activeReport === 'customer_product') {
        $activeReportLabel = 'Laporan Pelanggan';
    }
@endphp

<div class="card border-0 shadow-sm report-sidebar-card mb-3" style="border-radius: 12px; background: #fff; border: 1px solid #e2e8f0 !important;">
    <div class="card-body p-3">
        <!-- Header Daftar Laporan -->
        <h6 class="fw-bold text-dark mb-3" style="font-size: 0.95rem; letter-spacing: 0.2px;">Daftar Laporan</h6>

        <!-- Searchable Dropdown Selector -->
        <div class="report-search-box position-relative mb-3" id="reportSearchContainer">
            <div class="input-group report-input-group">
                <input type="text" 
                       class="form-control report-search-input" 
                       id="reportSearchInput" 
                       placeholder="{{ $activeReportLabel }}" 
                       data-default="{{ $activeReportLabel }}"
                       autocomplete="off">
                <span class="input-group-text bg-white report-chevron-btn" id="reportChevronBtn">
                    <i class="bi bi-chevron-down text-muted" id="reportChevronIcon" style="font-size: 0.78rem;"></i>
                </span>
            </div>

            <!-- Dropdown Options Menu -->
            <div class="report-search-menu shadow border d-none" id="reportSearchMenu">
                <a class="report-search-option {{ $activeReport === 'sales_detail' ? 'active' : '' }}" 
                   href="{{ route('reports.sales-detail') }}" 
                   data-keyword="rincian penjualan">
                    Rincian Penjualan
                </a>
                <a class="report-search-option {{ $activeReport === 'sales_item' ? 'active' : '' }}" 
                   href="{{ route('reports.sales-item') }}" 
                   data-keyword="item penjualan">
                    Item Penjualan
                </a>
                <a class="report-search-option {{ $activeReport === 'customer_product' ? 'active' : '' }}" 
                   href="{{ route('reports.customer-product') }}" 
                   data-keyword="laporan pelanggan">
                    Laporan Pelanggan
                </a>
                <div class="report-search-empty d-none text-muted small text-center py-3" id="reportSearchEmpty">
                    Laporan tidak ditemukan
                </div>
            </div>
        </div>

        <!-- Vertical Report List (3 Laporan) -->
        <div class="report-nav-list d-flex flex-column gap-1" id="reportNavList">
            <!-- 1. Rincian Penjualan -->
            <a href="{{ route('reports.sales-detail') }}" 
               class="report-nav-item {{ $activeReport === 'sales_detail' ? 'active' : '' }}"
               data-keyword="rincian penjualan">
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-text me-2 report-nav-icon"></i>
                    <span>Rincian Penjualan</span>
                </div>
            </a>

            <!-- 2. Item Penjualan -->
            <a href="{{ route('reports.sales-item') }}" 
               class="report-nav-item {{ $activeReport === 'sales_item' ? 'active' : '' }}"
               data-keyword="item penjualan">
                <div class="d-flex align-items-center">
                    <i class="bi bi-box me-2 report-nav-icon"></i>
                    <span>Item Penjualan</span>
                </div>
            </a>

            <!-- 3. Laporan Pelanggan -->
            <a href="{{ route('reports.customer-product') }}" 
               class="report-nav-item {{ $activeReport === 'customer_product' ? 'active' : '' }}"
               data-keyword="laporan pelanggan">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-lines-fill me-2 report-nav-icon"></i>
                    <span>Laporan Pelanggan</span>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    /* Searchable Dropdown Styling */
    .report-input-group {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .report-search-box.open .report-input-group,
    .report-input-group:focus-within {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }
    .report-search-input {
        border: none !important;
        box-shadow: none !important;
        font-size: 0.88rem;
        padding: 8px 12px;
        color: #334155;
    }
    .report-search-input::placeholder {
        color: #475569;
        font-weight: 500;
    }
    .report-search-box.open .report-search-input::placeholder {
        color: #94a3b8;
        font-weight: normal;
    }
    .report-chevron-btn {
        border: none !important;
        cursor: pointer;
        padding-right: 12px;
    }

    /* Dropdown Menu */
    .report-search-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border-color: #e2e8f0 !important;
        border-radius: 8px;
        margin-top: 4px;
        z-index: 1060;
        max-height: 240px;
        overflow-y: auto;
        padding: 6px 0;
    }
    .report-search-option {
        display: block;
        padding: 9px 16px;
        font-size: 0.88rem;
        color: #334155;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .report-search-option:hover {
        background-color: #f1f5f9;
        color: #0284c7;
    }
    .report-search-option.active {
        background-color: #f0f9ff;
        color: #0284c7;
        font-weight: 600;
    }

    /* List Navigation */
    .report-nav-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        color: #475569;
        text-decoration: none;
        font-size: 0.86rem;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.15s ease-in-out;
    }
    .report-nav-item:hover {
        background-color: #f1f5f9;
        color: #0ea5e9;
    }
    .report-nav-item.active {
        background-color: #e0f2fe;
        color: #0284c7;
        font-weight: 600;
    }
    .report-nav-item.active .report-nav-icon {
        color: #0284c7;
    }
    .report-nav-icon {
        color: #94a3b8;
        font-size: 1rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('reportSearchContainer');
    const input = document.getElementById('reportSearchInput');
    const menu = document.getElementById('reportSearchMenu');
    const chevronIcon = document.getElementById('reportChevronIcon');
    const chevronBtn = document.getElementById('reportChevronBtn');
    const emptyMsg = document.getElementById('reportSearchEmpty');
    const options = menu ? menu.querySelectorAll('.report-search-option') : [];
    const listItems = document.querySelectorAll('#reportNavList .report-nav-item');

    if (!container || !input || !menu) return;

    function openDropdown() {
        container.classList.add('open');
        menu.classList.remove('d-none');
        if (chevronIcon) {
            chevronIcon.classList.remove('bi-chevron-down');
            chevronIcon.classList.add('bi-chevron-up');
        }
        input.placeholder = 'Ketikan keyword';
    }

    function closeDropdown() {
        container.classList.remove('open');
        menu.classList.add('d-none');
        if (chevronIcon) {
            chevronIcon.classList.remove('bi-chevron-up');
            chevronIcon.classList.add('bi-chevron-down');
        }
        input.value = '';
        input.placeholder = input.dataset.default || 'Pilih Laporan';
        filterOptions('');
    }

    function filterOptions(query) {
        const q = query.trim().toLowerCase();
        let matchCount = 0;

        options.forEach(function(opt) {
            const keyword = (opt.dataset.keyword || opt.textContent).toLowerCase();
            if (!q || keyword.includes(q)) {
                opt.classList.remove('d-none');
                matchCount++;
            } else {
                opt.classList.add('d-none');
            }
        });

        if (emptyMsg) {
            if (matchCount === 0) {
                emptyMsg.classList.remove('d-none');
            } else {
                emptyMsg.classList.add('d-none');
            }
        }

        // Also filter vertical list items below in real time
        listItems.forEach(function(item) {
            const itemKw = (item.dataset.keyword || item.textContent).toLowerCase();
            if (!q || itemKw.includes(q)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Input focus / click to open
    input.addEventListener('focus', function() {
        openDropdown();
    });

    input.addEventListener('click', function() {
        openDropdown();
    });

    // Toggle on chevron click
    if (chevronBtn) {
        chevronBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (container.classList.contains('open')) {
                closeDropdown();
                input.blur();
            } else {
                openDropdown();
                input.focus();
            }
        });
    }

    // Input search typing
    input.addEventListener('input', function() {
        if (!container.classList.contains('open')) {
            openDropdown();
        }
        filterOptions(this.value);
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            closeDropdown();
        }
    });

    // Close on Escape key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDropdown();
            input.blur();
        }
    });
});
</script>
