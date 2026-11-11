<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposSupplier;
use App\Models\MposOutlet;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
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

        $query = MposSupplier::query();

        if (!empty($selectedOutletId)) {
            $query->where(function ($q) use ($selectedOutletId) {
                $q->where('nid_outlet', $selectedOutletId)
                  ->orWhereNull('nid_outlet');
            });
        } elseif (!$this->isSuperAdminUser()) {
            $query->where(function ($q) use ($allowedOutletIds) {
                $q->whereIn('nid_outlet', $allowedOutletIds)
                  ->orWhereNull('nid_outlet');
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cname', 'like', "%{$search}%")
                  ->orWhere('ccontact_person', 'like', "%{$search}%")
                  ->orWhere('cphone', 'like', "%{$search}%")
                  ->orWhere('cemail', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('factive', $request->status === 'active' ? 1 : 0);
        }

        // Group by cname so duplicate multi-outlet rows don't repeat in the table!
        $query->whereIn('nid', function ($q) use ($selectedOutletId, $allowedOutletIds) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_supplier')
              ->groupBy('cname');

            if (!empty($selectedOutletId)) {
                $q->where(function ($sub) use ($selectedOutletId) {
                    $sub->where('nid_outlet', $selectedOutletId)
                        ->orWhereNull('nid_outlet');
                });
            } elseif (!$this->isSuperAdminUser()) {
                $q->where(function ($sub) use ($allowedOutletIds) {
                    $sub->whereIn('nid_outlet', $allowedOutletIds)
                        ->orWhereNull('nid_outlet');
                });
            }
        });

        $suppliers = $query->withCount('purchases')
            ->with('outlet')
            ->orderBy('cname', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Prefetch all outlet assignments for suppliers on this page
        $allSupplierOutlets = MposSupplier::with('outlet')
            ->whereIn('cname', $suppliers->pluck('cname'))
            ->get(['nid', 'cname', 'nid_outlet', 'clogo'])
            ->groupBy('cname');

        // Fallback in-memory logo from sibling outlet rows when an older row has empty clogo
        foreach ($suppliers as $supplierItem) {
            if (empty($supplierItem->clogo)) {
                $siblingLogo = $allSupplierOutlets->get($supplierItem->cname, collect())
                    ->pluck('clogo')
                    ->first(function (?string $path): bool {
                        return !empty($path);
                    });

                if (!empty($siblingLogo)) {
                    $supplierItem->clogo = $siblingLogo;
                }
            }
        }

        return view('suppliers.index', compact(
            'suppliers',
            'outlets',
            'selectedOutletId',
            'isOutletLocked',
            'isCashierOnly',
            'isSuperAdmin',
            'allowedOutletIds',
            'allSupplierOutlets'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cname' => 'required|string|max:255',
            'ccontact_person' => 'nullable|string|max:255',
            'cphone' => 'nullable|string|max:50',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
            'cnote' => 'nullable|string',
            'clogo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'cname.required' => 'Nama Supplier wajib diisi.',
            'outlet_ids.required' => 'Pilih minimal satu outlet.',
            'outlet_ids.min' => 'Pilih minimal satu outlet.',
            'clogo.image' => 'File logo harus berupa gambar (jpg, png, webp).',
            'clogo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'create');
        }

        $data = $validator->validated();
        unset($data['outlet_ids']);
        $data['factive'] = $request->has('factive') ? 1 : 1;
        $data['dcreated'] = now();

        if ($request->hasFile('clogo')) {
            $data['clogo'] = $this->storeLogoFile($request->file('clogo'));
        } else {
            $existingLogo = $this->resolveExistingLogoPath($request->cname);
            if (!empty($existingLogo)) {
                $data['clogo'] = $existingLogo;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->outlet_ids as $outletId) {
                $exists = MposSupplier::where('cname', $request->cname)
                            ->where('nid_outlet', $outletId)
                            ->exists();

                if (!$exists) {
                    $insertData = $data;
                    $insertData['nid_outlet'] = $outletId;
                    MposSupplier::create($insertData);
                }
            }
            DB::commit();

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        $supplier = MposSupplier::with(['outlet', 'purchases' => function ($q) {
            $q->orderBy('dpurchase', 'desc');
        }])->findOrFail($id);

        if (empty($supplier->clogo)) {
            $fallbackLogo = $this->resolveExistingLogoPath($supplier->cname);
            if (!empty($fallbackLogo)) {
                $supplier->clogo = $fallbackLogo;
            }
        }

        $outlets = $this->getAvailableOutlets();
        $isOutletLocked = $this->isOutletLocked();
        $allowedOutletIds = $this->getUserAllowedOutletIds();

        $assignedOutlets = MposSupplier::where('cname', $supplier->cname)
            ->whereNotNull('nid_outlet')
            ->with('outlet')
            ->get()
            ->pluck('outlet')
            ->filter();

        return view('suppliers.show', compact(
            'supplier',
            'outlets',
            'isOutletLocked',
            'allowedOutletIds',
            'assignedOutlets'
        ));
    }

    public function update(Request $request, string $id)
    {
        $supplier = MposSupplier::findOrFail($id);
        $originalCname = $supplier->cname;

        $validator = Validator::make($request->all(), [
            'cname' => 'required|string|max:255',
            'ccontact_person' => 'nullable|string|max:255',
            'cphone' => 'nullable|string|max:50',
            'cemail' => 'nullable|email|max:255',
            'caddress' => 'nullable|string',
            'cnote' => 'nullable|string',
            'factive' => 'nullable|boolean',
            'clogo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:mpos_outlet,nid',
        ], [
            'cname.required' => 'Nama Supplier wajib diisi.',
            'outlet_ids.required' => 'Pilih minimal satu outlet.',
            'outlet_ids.min' => 'Pilih minimal satu outlet.',
            'clogo.image' => 'File logo harus berupa gambar (jpg, png, webp).',
            'clogo.max' => 'Ukuran logo maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal', 'edit_' . $id);
        }

        $data = $validator->validated();
        unset($data['outlet_ids']);
        $data['factive'] = $request->has('factive') ? 1 : 0;

        if ($request->hasFile('clogo')) {
            $oldLogoPaths = MposSupplier::where('cname', $originalCname)
                ->whereNotNull('clogo')
                ->where('clogo', '!=', '')
                ->pluck('clogo')
                ->unique();

            foreach ($oldLogoPaths as $oldLogoPath) {
                $this->deleteLogoFile($oldLogoPath);
            }

            $data['clogo'] = $this->storeLogoFile($request->file('clogo'));
        } else {
            $existingLogo = $this->resolveExistingLogoPath($originalCname, $supplier->clogo);
            if (!empty($existingLogo)) {
                $data['clogo'] = $existingLogo;
            }
        }

        DB::beginTransaction();
        try {
            $targetOutletIds = array_values(array_unique(array_map('intval', $request->outlet_ids)));
            $primaryOutletId = in_array((int) $supplier->nid_outlet, $targetOutletIds, true)
                ? (int) $supplier->nid_outlet
                : $targetOutletIds[0];

            // 1. Update this primary instance keeping its outlet if still selected
            $data['nid_outlet'] = $primaryOutletId;
            $supplier->update($data);

            // Clean up any extra duplicate instances for primaryOutletId
            $duplicatesPrimary = MposSupplier::where('cname', $originalCname)
                ->where('nid_outlet', $primaryOutletId)
                ->where('nid', '!=', $supplier->nid)
                ->get();
            foreach ($duplicatesPrimary as $dup) {
                if (!$dup->purchases()->exists()) {
                    $dup->delete();
                }
            }

            // 2. For remaining selected outlets, create or update matching records (including clogo)
            foreach ($targetOutletIds as $outId) {
                if ($outId === $primaryOutletId) {
                    continue;
                }

                $existing = MposSupplier::where('cname', $originalCname)
                    ->where('nid_outlet', $outId)
                    ->where('nid', '!=', $supplier->nid)
                    ->first();

                if ($existing) {
                    $existing->update($data);
                } else {
                    $newData = $data;
                    $newData['nid_outlet'] = $outId;
                    $newData['dcreated'] = $supplier->dcreated ?? now();
                    MposSupplier::create($newData);
                }
            }

            // 3. For any other instances of this supplier that are in unselected outlets,
            // remove them if they have NO purchase records!
            $unselected = MposSupplier::where('cname', $originalCname)
                ->where(function ($q) use ($targetOutletIds) {
                    $q->whereNotIn('nid_outlet', $targetOutletIds)
                      ->orWhereNull('nid_outlet');
                })
                ->where('nid', '!=', $supplier->nid)
                ->get();

            foreach ($unselected as $item) {
                if (!$item->purchases()->exists()) {
                    $item->delete();
                }
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'Data supplier berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        $supplier = MposSupplier::findOrFail($id);
        $originalCname = $supplier->cname;

        // Check purchases across all instances of this supplier with same cname
        $instancesWithPurchases = MposSupplier::where('cname', $originalCname)
            ->whereHas('purchases')
            ->count();

        if ($instancesWithPurchases > 0) {
            return redirect()->back()
                ->with('error', 'Supplier tidak dapat dihapus karena sudah memiliki riwayat transaksi pembelian.');
        }

        $logoPaths = MposSupplier::where('cname', $originalCname)
            ->whereNotNull('clogo')
            ->where('clogo', '!=', '')
            ->pluck('clogo')
            ->unique();

        foreach ($logoPaths as $logoPath) {
            $this->deleteLogoFile($logoPath);
        }

        // Delete all instances with this cname
        MposSupplier::where('cname', $originalCname)->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }

    /**
     * Store uploaded supplier logo file into public uploads directory.
     */
    private function storeLogoFile(UploadedFile $file): string
    {
        $filename = 'supplier_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/supplier');
        if (!file_exists($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $file->move($targetDir, $filename);

        return 'uploads/supplier/' . $filename;
    }

    /**
     * Remove a supplier logo file from public directory or public storage disk.
     */
    private function deleteLogoFile(?string $logoPath): void
    {
        if (empty($logoPath)) {
            return;
        }

        if (file_exists(public_path($logoPath))) {
            @unlink(public_path($logoPath));
        } elseif (Storage::disk('public')->exists($logoPath)) {
            Storage::disk('public')->delete($logoPath);
        }
    }

    /**
     * Resolve an existing logo path from the supplier or its sibling outlet rows.
     */
    private function resolveExistingLogoPath(string $cname, ?string $preferredLogo = null): ?string
    {
        if (!empty($preferredLogo)) {
            return $preferredLogo;
        }

        return MposSupplier::where('cname', $cname)
            ->whereNotNull('clogo')
            ->where('clogo', '!=', '')
            ->value('clogo');
    }
}
