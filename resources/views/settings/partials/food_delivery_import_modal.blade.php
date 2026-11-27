@php
$importAction = $importAction ?? route('settings.pos.food-delivery.import-all');
$platformLabel = $platformLabel ?? null;
@endphp

<div class="modal fade" id="importFoodDeliveryModal" tabindex="-1" aria-labelledby="importFoodDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="importFoodDeliveryModalLabel">
                    <i class="bi bi-file-earmark-excel text-success me-2"></i>Import Harga Food Delivery
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ $importAction }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="nid_outlet" value="{{ $selectedOutletId }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="foodDeliveryImportFile" class="form-label fw-semibold">Pilih File Excel / CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="foodDeliveryImportFile" name="file" accept=".xlsx,.xls,.csv" required>
                    </div>

                    @if($platformLabel)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="only_current_platform" value="1" id="onlyCurrentPlatformCheck">
                        <label class="form-check-label small" for="onlyCurrentPlatformCheck">
                            Hanya import baris dengan merchant <strong>{{ $platformLabel }}</strong> (jika tidak dicentang, semua merchant di file akan ikut terimport).
                        </label>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload me-1"></i> Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>