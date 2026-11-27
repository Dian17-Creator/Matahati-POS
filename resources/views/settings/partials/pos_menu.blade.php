<div class="card olsera-panel-card">
    <div class="card-header bg-white fw-semibold small text-muted py-2 d-flex align-items-center justify-content-between">
        <span>Menu</span>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('payments.index') }}"
            class="list-group-item list-group-item-action border-0 py-2 px-3 {{ request()->routeIs('payments.*') ? 'fw-semibold' : '' }}"
            @if(request()->routeIs('payments.*')) style="background-color: #fff1f2; color: #b63352;" @endif>
            Pembayaran
        </a>
        <a href="{{ route('settings.pos.receipt') }}"
            class="list-group-item list-group-item-action border-0 py-2 px-3 {{ request()->routeIs('settings.pos.receipt*') ? 'fw-semibold' : '' }}"
            @if(request()->routeIs('settings.pos.receipt*')) style="background-color: #fff1f2; color: #b63352;" @endif>
            Catatan Resi
        </a>
    </div>
</div>
