<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposIngredients;
use App\Models\MposPurchaseD;
use App\Models\MposRecipe;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request)
    {
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $isCashierOnly = $this->isCashierOnlyUser();
        $isSuperAdmin = $this->isSuperAdminUser();
        $isOutletLocked = $this->isOutletLocked();
        $outlets = $this->getAvailableOutlets();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $query = MposIngredients::with('outlet');

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $query->where(function ($q) use ($allowedOutletIds) {
                $q->whereIn('nid_outlet', $allowedOutletIds)
                  ->orWhereNull('nid_outlet');
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('cname', 'like', "%{$search}%");
        }

        // Kelompokkan per nama bahan baku agar tidak duplikat baris saat mode Semua Outlet
        $query->whereIn('nid', function ($q) use ($selectedOutletId, $allowedOutletIds, $isSuperAdmin) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_ingredients')
              ->groupBy('cname');

            if (!empty($selectedOutletId)) {
                $q->where('nid_outlet', $selectedOutletId);
            } elseif (!$isSuperAdmin) {
                $q->where(function ($sub) use ($allowedOutletIds) {
                    $sub->whereIn('nid_outlet', $allowedOutletIds)
                        ->orWhereNull('nid_outlet');
                });
            }
        });

        $ingredients = $query->orderBy('cname')->paginate(10)->withQueryString();

        // Preload pemetaan outlet untuk setiap nama bahan baku agar bebas N+1 query di tabel & modal
        $ingredientOutletsQuery = MposIngredients::with('outlet');
        if (!$isSuperAdmin && !empty($allowedOutletIds)) {
            $ingredientOutletsQuery->where(function ($q) use ($allowedOutletIds) {
                $q->whereIn('nid_outlet', $allowedOutletIds)
                  ->orWhereNull('nid_outlet');
            });
        }
        $ingredientOutletsMap = $ingredientOutletsQuery->get()->groupBy('cname');

        $satuans = MposIngredients::SATUAN_LIST;

        return view('ingredients.index', compact(
            'ingredients',
            'ingredientOutletsMap',
            'satuans',
            'outlets',
            'selectedOutletId',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'csatuan' => 'required|string|max:50',
            'nprice' => 'nullable|numeric|min:0',
            'nstock' => 'nullable|numeric|min:0',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'outlet_ids.required' => 'Pilih minimal 1 outlet.',
            'outlet_ids.min' => 'Pilih minimal 1 outlet.',
        ]);

        $cname = trim((string) $request->input('cname'));
        $csatuan = trim((string) $request->input('csatuan'));
        $nprice = $request->filled('nprice') ? (float) $request->input('nprice') : 0;
        $nstock = $request->filled('nstock') ? (float) $request->input('nstock') : 0;
        $hasPriceCol = MposIngredients::hasPriceColumn();

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower($cname)])
                    ->where('nid_outlet', $outletId)
                    ->exists();

                if (!$exists) {
                    $payload = [
                        'cname' => $cname,
                        'nid_outlet' => $outletId,
                        'csatuan' => $csatuan,
                        'nstock' => $nstock,
                    ];
                    if ($hasPriceCol) {
                        $payload['nprice'] = $nprice;
                    }
                    MposIngredients::create($payload);
                }
            }

            DB::commit();
            return redirect()->route('ingredients.index')
                ->with('success', 'Bahan baku berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'csatuan' => 'required|string|max:50',
            'nprice' => 'nullable|numeric|min:0',
            'nstock' => 'nullable|numeric|min:0',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'outlet_ids.required' => 'Pilih minimal 1 outlet.',
            'outlet_ids.min' => 'Pilih minimal 1 outlet.',
        ]);

        $ingredient = MposIngredients::findOrFail($id);
        $originalCname = $ingredient->cname;
        $newCname = trim((string) $request->input('cname'));
        $newSatuan = trim((string) $request->input('csatuan'));
        $hasPriceCol = MposIngredients::hasPriceColumn();
        $newPrice = $request->has('nprice') && $request->input('nprice') !== null && $request->input('nprice') !== ''
            ? (float) $request->input('nprice')
            : (float) ($ingredient->nprice ?? 0);
        $newStock = $request->has('nstock') && $request->input('nstock') !== null && $request->input('nstock') !== ''
            ? (float) $request->input('nstock')
            : $ingredient->nstock;

        DB::beginTransaction();
        try {
            $selectedOutletIds = collect($request->outlet_ids)->map(fn ($oid) => (int) $oid)->unique()->values()->all();

            // Jika record asal belum punya nid_outlet, arahkan ke outlet terpilih pertama
            $targetPrimaryOutlet = $ingredient->nid_outlet ?: $selectedOutletIds[0];

            $primaryPayload = [
                'cname' => $newCname,
                'nid_outlet' => $targetPrimaryOutlet,
                'csatuan' => $newSatuan,
                'nstock' => $newStock,
            ];
            if ($hasPriceCol) {
                $primaryPayload['nprice'] = $newPrice;
            }
            $ingredient->update($primaryPayload);

            foreach ($selectedOutletIds as $outletId) {
                if ((int) $ingredient->nid_outlet === (int) $outletId) {
                    continue;
                }

                $existingInOutlet = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower($originalCname)])
                    ->where('nid_outlet', $outletId)
                    ->first();

                if ($existingInOutlet) {
                    $syncPayload = [
                        'cname' => $newCname,
                        'csatuan' => $newSatuan,
                    ];
                    if ($hasPriceCol) {
                        $syncPayload['nprice'] = $newPrice;
                    }
                    $existingInOutlet->update($syncPayload);
                } else {
                    $dupCheck = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower($newCname)])
                        ->where('nid_outlet', $outletId)
                        ->exists();

                    if (!$dupCheck) {
                        $createPayload = [
                            'cname' => $newCname,
                            'nid_outlet' => $outletId,
                            'csatuan' => $newSatuan,
                            'nstock' => 0,
                        ];
                        if ($hasPriceCol) {
                            $createPayload['nprice'] = $newPrice;
                        }
                        MposIngredients::create($createPayload);
                    }
                }
            }

            // Hapus record di outlet yang di-uncheck jika belum terpakai di resep / pembelian
            $unselectedRecords = MposIngredients::whereRaw('LOWER(cname) = ?', [strtolower($originalCname)])
                ->where('nid', '!=', $ingredient->nid)
                ->whereNotIn('nid_outlet', $selectedOutletIds)
                ->get();

            foreach ($unselectedRecords as $unselected) {
                $usedInRecipe = MposRecipe::where('nid_ingredient', $unselected->nid)->exists();
                $usedInPurchase = MposPurchaseD::where('nid_ingredient', $unselected->nid)->exists();
                if (!$usedInRecipe && !$usedInPurchase) {
                    $unselected->delete();
                }
            }

            DB::commit();
            return redirect()->route('ingredients.index')
                ->with('success', 'Bahan baku berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $ingredient = MposIngredients::findOrFail($id);
            $ingredient->delete();

            return redirect()->route('ingredients.index')
                ->with('success', 'Bahan baku berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('ingredients.index')
                ->with('error', 'Bahan baku tidak dapat dihapus karena masih digunakan pada resep produk atau transaksi pembelian.');
        }
    }
}
