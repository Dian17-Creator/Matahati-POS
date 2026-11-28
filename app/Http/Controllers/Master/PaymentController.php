<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposPayment;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ResolvesOutletScope;

    public function apiIndex(Request $request): JsonResponse
    {
        $query = MposPayment::query();

        $outletId = $request->input('nid_outlet') ?? $request->input('outlet_id');
        if ($outletId !== null && $outletId !== '') {
            $query->where(function ($q) use ($outletId): void {
                $q->whereNull('nid_outlet')
                    ->orWhere('nid_outlet', (int) $outletId);
            });
        }

        $payments = $query->orderBy('nid', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar metode pembayaran berhasil diambil.',
            'data' => $payments,
        ], 200);
    }

    public function index(Request $request): View
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $selectedOutlet = $selectedOutletId ? \App\Models\MposOutlet::find($selectedOutletId) : null;
        $isSuperAdmin = $this->isSuperAdminUser();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposPayment::with('outlet')
            ->leftJoin('mpos_outlet', 'mpos_payment.nid_outlet', '=', 'mpos_outlet.nid')
            ->select('mpos_payment.*');

        if ($selectedOutletId !== null) {
            $query->where(function ($q) use ($selectedOutletId): void {
                $q->whereNull('mpos_payment.nid_outlet')
                    ->orWhere('mpos_payment.nid_outlet', $selectedOutletId);
            });
        } elseif (! $isSuperAdmin) {
            $query->where(function ($q) use ($allowedOutletIds): void {
                $q->whereNull('mpos_payment.nid_outlet');
                if (! empty($allowedOutletIds)) {
                    $q->orWhereIn('mpos_payment.nid_outlet', $allowedOutletIds);
                }
            });
        }

        $allRows = $query
            ->orderBy('mpos_payment.cname', 'asc')
            ->orderByRaw('CASE WHEN mpos_payment.nid_outlet IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('mpos_outlet.cname', 'asc')
            ->orderBy('mpos_payment.nid', 'asc')
            ->get();

        // Group identical payment method names so each method appears once in the list,
        // while keeping all outlet assignments for the Edit modal.
        $grouped = $allRows
            ->groupBy(fn (MposPayment $item): string => strtolower(trim((string) $item->cname)))
            ->map(function ($rows) {
                /** @var MposPayment $representative */
                $representative = $rows->first();
                $isAllOutlets = $rows->contains(fn (MposPayment $r): bool => is_null($r->nid_outlet));
                $outletIds = $rows
                    ->pluck('nid_outlet')
                    ->filter(fn ($id): bool => ! is_null($id))
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                return (object) [
                    'nid' => $representative->nid,
                    'cname' => $representative->cname,
                    'is_all_outlets' => $isAllOutlets,
                    'outlet_ids' => $outletIds,
                    'row_ids' => $rows->pluck('nid')->map(fn ($id): int => (int) $id)->values()->all(),
                ];
            })
            ->values();

        $perPage = max(1, $grouped->count());
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $payments = new LengthAwarePaginator(
            $grouped->forPage($currentPage, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('payments.index', compact('payments', 'outlets', 'selectedOutletId', 'selectedOutlet', 'isSuperAdmin'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $targetOutlets] = $this->validatePayment($request);

        DB::transaction(function () use ($data, $targetOutlets): void {
            foreach ($targetOutlets as $outletId) {
                MposPayment::create(array_merge($data, [
                    'nid_outlet' => $outletId,
                ]));
            }
        });

        $createdCount = count($targetOutlets);
        $message = $createdCount > 1
            ? "Metode pembayaran berhasil ditambahkan untuk {$createdCount} outlet."
            : 'Metode pembayaran berhasil ditambahkan.';

        return redirect()->route('payments.index')
            ->with('success', $message);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $payment = MposPayment::findOrFail($id);
        $oldNormalizedName = strtolower(trim((string) $payment->cname));

        $siblingQuery = MposPayment::whereRaw('LOWER(TRIM(cname)) = ?', [$oldNormalizedName]);
        if (! $this->isSuperAdminUser()) {
            $allowedOutletIds = $this->getUserAllowedOutletIds();
            $siblingQuery->where(function ($q) use ($allowedOutletIds): void {
                $q->whereNull('nid_outlet');
                if (! empty($allowedOutletIds)) {
                    $q->orWhereIn('nid_outlet', $allowedOutletIds);
                }
            });
        }
        $siblingRows = $siblingQuery->get();
        $excludeIds = $siblingRows->pluck('nid')->map(fn ($nid): int => (int) $nid)->all();

        [$data, $targetOutlets] = $this->validatePayment($request, $excludeIds);

        DB::transaction(function () use ($payment, $siblingRows, $data, $targetOutlets): void {
            if ($targetOutlets === [null]) {
                $payment->update([
                    'cname' => $data['cname'],
                    'nid_outlet' => null,
                ]);

                $siblingRows
                    ->filter(fn (MposPayment $row): bool => (int) $row->nid !== (int) $payment->nid)
                    ->each(fn (MposPayment $row) => $row->delete());

                return;
            }

            $targetOutletIds = array_map('intval', $targetOutlets);

            // Remove rows whose outlets were unchecked (keep representative if we can reuse it).
            $keptByOutlet = [];
            $reusableRows = collect();

            foreach ($siblingRows as $row) {
                if (! is_null($row->nid_outlet) && in_array((int) $row->nid_outlet, $targetOutletIds, true) && ! isset($keptByOutlet[(int) $row->nid_outlet])) {
                    $row->update(['cname' => $data['cname']]);
                    $keptByOutlet[(int) $row->nid_outlet] = $row;
                } else {
                    $reusableRows->push($row);
                }
            }

            foreach ($targetOutletIds as $outletId) {
                if (isset($keptByOutlet[$outletId])) {
                    continue;
                }

                $reuse = $reusableRows->shift();
                if ($reuse) {
                    $reuse->update([
                        'cname' => $data['cname'],
                        'nid_outlet' => $outletId,
                    ]);
                } else {
                    MposPayment::create([
                        'cname' => $data['cname'],
                        'nid_outlet' => $outletId,
                    ]);
                }
            }

            $reusableRows->each(fn (MposPayment $row) => $row->delete());
        });

        return redirect()->route('payments.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $payment = MposPayment::findOrFail($id);
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');

        // When viewing a specific outlet and deleting an outlet-specific row, delete only that row.
        // Otherwise (e.g. Semua Outlet mode), delete all sibling rows of that payment method in scope.
        if ($selectedOutletId !== null && ! is_null($payment->nid_outlet)) {
            $payment->delete();
        } else {
            $deleteQuery = MposPayment::whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim((string) $payment->cname))]);
            if (! $this->isSuperAdminUser()) {
                $allowedOutletIds = $this->getUserAllowedOutletIds();
                $deleteQuery->where(function ($q) use ($allowedOutletIds): void {
                    $q->whereNull('nid_outlet');
                    if (! empty($allowedOutletIds)) {
                        $q->orWhereIn('nid_outlet', $allowedOutletIds);
                    }
                });
            }
            $deleteQuery->delete();
        }

        return redirect()->route('payments.index')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    /**
     * Validate payment payload and resolve target outlet IDs (null represents all outlets).
     *
     * @param  array<int, int>  $excludeIds
     * @return array{0: array{cname: string}, 1: array<int, int|null>}
     */
    private function validatePayment(Request $request, array $excludeIds = []): array
    {
        $allOutlets = $request->boolean('all_outlets');

        $validated = $request->validate([
            'all_outlets' => 'nullable|boolean',
            'outlet_ids' => 'nullable|array',
            'outlet_ids.*' => 'integer|exists:mpos_outlet,nid',
            'cname' => 'required|string|max:255',
        ]);

        $outletIds = array_values(array_unique(array_map('intval', $validated['outlet_ids'] ?? [])));

        if (! $allOutlets && empty($outletIds)) {
            throw ValidationException::withMessages([
                'outlet_ids' => 'Pilih minimal 1 outlet atau centang Semua Outlet.',
            ]);
        }

        if (! $this->isSuperAdminUser()) {
            $allowedOutletIds = $this->getUserAllowedOutletIds();
            if ($allOutlets && count($allowedOutletIds) <= 1) {
                throw ValidationException::withMessages([
                    'outlet_ids' => 'Anda tidak memiliki akses ke Semua Outlet.',
                ]);
            }

            foreach ($outletIds as $oid) {
                if (! in_array($oid, $allowedOutletIds, true)) {
                    throw ValidationException::withMessages([
                        'outlet_ids' => 'Anda tidak memiliki akses ke salah satu outlet yang dipilih.',
                    ]);
                }
            }
        }

        $targetOutlets = $allOutlets ? [null] : $outletIds;

        foreach ($targetOutlets as $targetOutletId) {
            $duplicateQuery = MposPayment::whereRaw('LOWER(TRIM(cname)) = ?', [strtolower(trim($validated['cname']))]);
            if ($targetOutletId === null) {
                $duplicateQuery->whereNull('nid_outlet');
            } else {
                $duplicateQuery->where('nid_outlet', $targetOutletId);
            }

            if (! empty($excludeIds)) {
                $duplicateQuery->whereNotIn('nid', $excludeIds);
            }

            if ($duplicateQuery->exists()) {
                throw ValidationException::withMessages([
                    'cname' => 'Nama metode pembayaran sudah digunakan pada outlet yang dipilih.',
                ]);
            }
        }

        return [
            ['cname' => trim($validated['cname'])],
            $targetOutlets,
        ];
    }
}
