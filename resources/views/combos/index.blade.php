@extends('layouts.app')

@section('title', 'Katalog Produk / Paket Produk')

@section('sub_header')
@include('products.partials.catalog_tabs', ['activeTab' => 'combos'])
@endsection

@section('content')
<div class="card olsera-panel-card">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <h5 class="mb-0 fw-bold text-nowrap">Paket Produk</h5>
            <form action="{{ route('combos.index') }}" method="GET" class="d-flex flex-grow-1" style="max-width: 340px;">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari paket kombo..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($outlets->count() > 1)
            <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#copyComboModal">
                <i class="bi bi-copy me-1"></i> Salin Paket Produk
            </button>
            @endif
            <a href="{{ route('combos.create') }}" class="btn btn-success btn-sm px-3">
                <i class="bi bi-plus-lg me-1"></i> Tambah
            </a>
        </div>
    </div>

    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="py-3 ps-3">Nama Paket</th>
                        <th class="py-3 text-center">Harga Jual Online</th>
                        <th class="py-3 text-center">Harga Jual Offline</th>
                        <th class="py-3 text-center">Publikasi</th>
                        <th class="py-3 text-center pe-3" width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comboProducts as $combo)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('combos.show', $combo->nid) }}" class="d-flex align-items-center gap-3 text-decoration-none text-dark">
                                @if(!empty($combo->cphotos))
                                <img src="{{ asset($combo->cphotos) }}" alt="{{ $combo->cname }}" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                                @else
                                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $combo->cname }}</div>
                                    @if(empty($selectedOutletId) && $combo->outlet)
                                    <small class="text-muted"><i class="bi bi-shop me-1"></i>{{ $combo->outlet->cname }}</small>
                                    @endif
                                </div>
                            </a>
                        </td>
                        <td class="text-center fw-medium">IDR {{ number_format((float) $combo->nprice_online, 0, ',', '.') }}</td>
                        <td class="text-center fw-medium">IDR {{ number_format((float) $combo->nprice, 0, ',', '.') }}</td>
                        <td class="text-center text-muted small">
                            {{ $combo->dpublish_date ? $combo->dpublish_date->format('Y-m-d') : ($combo->dcreated ? $combo->dcreated->format('Y-m-d') : '-') }}
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('combos.show', $combo->nid) }}" class="btn btn-outline-info" title="Detail & Edit Paket">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canModifyData ?? true)
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $combo->nid }}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Hapus -->
                    <div class="modal fade" id="deleteModal{{ $combo->nid }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="mb-2">Hapus paket kombo <strong>{{ $combo->cname }}</strong>?</h5>
                                    <p class="text-muted mb-0">Data paket kombo dan daftar item di dalamnya akan dihapus.</p>
                                </div>
                                <div class="modal-footer bg-light justify-content-center">
                                    <form action="{{ route('combos.destroy', $combo->nid) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            Belum ada paket kombo. Klik <strong>+ Tambah</strong> untuk membuat paket produk baru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($comboProducts->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted">Total {{ $comboProducts->total() }} Paket</div>
            {{ $comboProducts->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

@if($outlets->count() > 1)
<!-- Modal Salin Paket Produk (Gaya Tambah Produk Merchant) -->
<div class="modal fade" id="copyComboModal" tabindex="-1" aria-labelledby="copyComboModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-2">
                <h5 class="modal-title fw-bold" id="copyComboModalLabel">Salin Paket Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('combos.copy') }}" method="POST" id="copyComboForm">
                @csrf
                <div class="modal-body pt-2">
                    @php
                        $defaultSourceOutletId = !empty($selectedOutletId)
                            ? (int) $selectedOutletId
                            : (int) ($allSourceCombos->first()?->nid_outlet ?? $outlets->first()?->nid ?? 0);
                    @endphp

                    <!-- Bagian Atas: Pilih Outlet Asal & Outlet Tujuan -->
                    <div class="bg-light border rounded-3 p-3 mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-secondary mb-1">Outlet Asal</label>
                                @if(!empty($selectedOutletId))
                                <input type="hidden" name="source_outlet_id" id="copySourceOutletInput" value="{{ $defaultSourceOutletId }}">
                                <div class="form-control form-control-sm bg-white d-flex align-items-center text-dark fw-medium">
                                    <span class="text-truncate">{{ $outlets->firstWhere('nid', $defaultSourceOutletId)?->cname ?? 'Outlet Aktif' }}</span>
                                </div>
                                @else
                                <select name="source_outlet_id" id="copySourceOutletSelect" class="form-select form-select-sm bg-white" required>
                                    @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->nid }}" {{ (int) $outlet->nid === $defaultSourceOutletId ? 'selected' : '' }}>
                                        {{ $outlet->cname }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>

                            <div class="col-md-1 text-center d-none d-md-flex justify-content-center pb-1">
                                <i class="bi bi-arrow-right text-secondary fs-5"></i>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Outlet Tujuan <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2 w-100" id="copyTargetOutletsWrapper">
                                    @foreach($outlets as $outlet)
                                    <label class="btn btn-sm btn-outline-secondary bg-white text-dark d-inline-flex align-items-center gap-2 flex-grow-1 copy-target-outlet-pill" data-outlet-id="{{ $outlet->nid }}" style="cursor: pointer;">
                                        <input type="checkbox" class="form-check-input m-0 copy-target-outlet-cb" name="target_outlet_ids[]" value="{{ $outlet->nid }}">
                                        <span>{{ $outlet->cname }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr class="my-2 opacity-25">

                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="overwrite_existing" value="1" id="copyOverwriteCheck">
                            <label class="form-check-label small text-muted" for="copyOverwriteCheck">
                                Timpa / perbarui paket jika nama paket sudah ada di outlet tujuan
                            </label>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="copyComboSearch" class="form-control border-start-0 ps-0" placeholder="Cari paket kombo...">
                    </div>

                    <!-- Header Pilih Paket + Select All -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small fw-semibold text-secondary">Pilih Paket Produk</span>
                        <input type="checkbox" class="form-check-input" id="selectAllCopyCombos" title="Pilih Semua">
                    </div>

                    <!-- List Paket Produk -->
                    <div class="list-group list-group-flush" id="copyComboList" style="max-height: 280px; overflow-y: auto;">
                        @forelse($allSourceCombos as $srcCombo)
                        <label class="list-group-item d-flex align-items-center justify-content-between gap-3 py-2 copy-combo-item"
                            data-name="{{ strtolower($srcCombo->cname) }}"
                            data-outlet-id="{{ (int) $srcCombo->nid_outlet }}"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                @if(!empty($srcCombo->cphotos))
                                <img src="{{ asset($srcCombo->cphotos) }}" alt="{{ $srcCombo->cname }}" class="rounded border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                @else
                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="fw-medium text-dark text-truncate">{{ $srcCombo->cname }}</div>
                                    <div class="small text-muted">
                                        Offline: IDR {{ number_format((float) $srcCombo->nprice, 0, ',', '.') }}
                                        &bull; Online: IDR {{ number_format((float) $srcCombo->nprice_online, 0, ',', '.') }}
                                        &bull; <span class="text-primary fw-semibold">{{ $srcCombo->comboItems->count() }} item</span>
                                    </div>
                                </div>
                            </div>
                            <input type="checkbox" class="form-check-input copy-combo-cb flex-shrink-0" name="combo_ids[]" value="{{ $srcCombo->nid }}">
                        </label>
                        @empty
                        <div class="text-center text-muted py-4 small">Belum ada paket produk yang dapat disalin.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer border-top px-3 py-3 mt-1">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="copyComboSubmitBtn">
                        <i class="bi bi-check2 me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if($outlets->count() > 1)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sourceSelect = document.getElementById('copySourceOutletSelect');
        var sourceInput = document.getElementById('copySourceOutletInput');
        var searchInput = document.getElementById('copyComboSearch');
        var selectAllCb = document.getElementById('selectAllCopyCombos');
        var comboItems = document.querySelectorAll('.copy-combo-item');
        var targetPills = document.querySelectorAll('.copy-target-outlet-pill');

        function getActiveSourceOutletId() {
            if (sourceSelect) {
                return sourceSelect.value;
            }
            if (sourceInput) {
                return sourceInput.value;
            }
            return '';
        }

        function syncTargetOutletsVisibility() {
            var activeSourceId = String(getActiveSourceOutletId());
            targetPills.forEach(function(pill) {
                var oid = String(pill.getAttribute('data-outlet-id'));
                var cb = pill.querySelector('.copy-target-outlet-cb');
                if (oid === activeSourceId) {
                    pill.classList.add('d-none');
                    if (cb) {
                        cb.checked = false;
                        cb.disabled = true;
                    }
                } else {
                    pill.classList.remove('d-none');
                    if (cb) {
                        cb.disabled = false;
                    }
                }
            });
        }

        function filterCopyCombos() {
            var activeSourceId = String(getActiveSourceOutletId());
            var keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';

            comboItems.forEach(function(item) {
                var itemOutlet = String(item.getAttribute('data-outlet-id'));
                var itemName = item.getAttribute('data-name') || '';
                var matchesOutlet = !activeSourceId || itemOutlet === activeSourceId;
                var matchesSearch = !keyword || itemName.indexOf(keyword) !== -1;

                if (matchesOutlet && matchesSearch) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                    var cb = item.querySelector('.copy-combo-cb');
                    if (cb && !matchesOutlet) {
                        cb.checked = false;
                    }
                }
            });

            syncSelectAllState();
        }

        function syncSelectAllState() {
            if (!selectAllCb) {
                return;
            }
            var visibleCbs = [];
            comboItems.forEach(function(item) {
                if (!item.classList.contains('d-none')) {
                    var cb = item.querySelector('.copy-combo-cb');
                    if (cb) {
                        visibleCbs.push(cb);
                    }
                }
            });
            selectAllCb.checked = visibleCbs.length > 0 && visibleCbs.every(function(cb) {
                return cb.checked;
            });
        }

        if (sourceSelect) {
            sourceSelect.addEventListener('change', function() {
                syncTargetOutletsVisibility();
                filterCopyCombos();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterCopyCombos);
        }

        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                comboItems.forEach(function(item) {
                    if (!item.classList.contains('d-none')) {
                        var cb = item.querySelector('.copy-combo-cb');
                        if (cb) {
                            cb.checked = selectAllCb.checked;
                        }
                    }
                });
            });
        }

        comboItems.forEach(function(item) {
            var cb = item.querySelector('.copy-combo-cb');
            if (cb) {
                cb.addEventListener('change', syncSelectAllState);
            }
        });

        syncTargetOutletsVisibility();
        filterCopyCombos();
    });
</script>
@endpush
@endif