<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposTaxSetting;
use App\Models\MposUser;
use App\Models\MposUserSpv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxSettingWebController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.tax', $this->loadOutletSettings($request));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'ccategory' => ['required', 'string', Rule::in(MposTaxSetting::CATEGORIES)],
            'ctax_name' => 'nullable|string|max:100',
            'ntax_percent' => 'nullable|numeric|min:0|max:100',
            'bis_tax_included' => 'nullable|boolean',
            'bppn_11_12' => 'nullable|boolean',
            'btakeaway_tax_exempt' => 'nullable|boolean',
            'ballow_manual_tax_exempt' => 'nullable|boolean',
            'btax_before_discount' => 'nullable|boolean',
            'nservice_charge_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $category = strtoupper($validated['ccategory']);
        $isOnline = $category === MposTaxSetting::CATEGORY_ONLINE;

        $setting = MposTaxSetting::firstOrNew([
            'nid_outlet' => $outletId,
            'ccategory' => $category,
        ]);

        $setting->fill([
            'ctax_name' => $isOnline ? null : ($validated['ctax_name'] ?? null),
            'ntax_percent' => $isOnline ? 0.00 : (float) ($validated['ntax_percent'] ?? 0),
            'bis_tax_included' => $isOnline ? false : $request->boolean('bis_tax_included'),
            'bppn_11_12' => $isOnline ? false : $request->boolean('bppn_11_12'),
            'btakeaway_tax_exempt' => $isOnline ? false : $request->boolean('btakeaway_tax_exempt'),
            'ballow_manual_tax_exempt' => $isOnline ? false : $request->boolean('ballow_manual_tax_exempt'),
            'btax_before_discount' => $isOnline ? false : $request->boolean('btax_before_discount'),
            'nservice_charge_percent' => (float) ($validated['nservice_charge_percent'] ?? 0),
        ]);
        $setting->save();

        return redirect()
            ->route('settings.tax', ['tab' => strtolower($category)])
            ->with('success', "Pengaturan pajak ({$category}) berhasil disimpan.");
    }

    public function receipt(Request $request): View
    {
        return view('settings.pos_receipt', $this->loadOutletSettings($request));
    }

    public function updateReceipt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nid_outlet' => 'required|integer|exists:mpos_outlet,nid',
            'ccategory' => ['required', 'string', Rule::in(MposTaxSetting::CATEGORIES)],
            'creceipt_title' => 'nullable|string',
            'creceipt_note' => 'nullable|string',
            'bhide_order_no' => 'nullable|boolean',
        ]);

        $outletId = (int) $validated['nid_outlet'];
        $category = strtoupper($validated['ccategory']);

        $setting = MposTaxSetting::firstOrNew([
            'nid_outlet' => $outletId,
            'ccategory' => $category,
        ]);

        $setting->fill([
            'creceipt_title' => $validated['creceipt_title'] ?? null,
            'creceipt_note' => $validated['creceipt_note'] ?? null,
            'bhide_order_no' => $request->boolean('bhide_order_no'),
        ]);
        $setting->save();

        return redirect()
            ->route('settings.pos.receipt', ['tab' => strtolower($category)])
            ->with('success', "Catatan resi ({$category}) berhasil disimpan.");
    }

    public static function resolveSelectedOutletId(): int
    {
        $user = Auth::user();
        $selectedOutletId = (int) (session('active_outlet_id') ?? 0);

        if ($selectedOutletId <= 0 && $user) {
            $userId = $user->nid ?? $user->id;
            $spvOutlets = MposUserSpv::where('nid_user', $userId)->pluck('nid_outlet')->filter()->toArray();
            $posOutlets = MposUser::where('nid_user', $userId)->pluck('nid_outlet')->filter()->toArray();
            $allowedIds = array_values(array_unique(array_merge($spvOutlets, $posOutlets)));

            if (count($allowedIds) === 1) {
                $selectedOutletId = (int) $allowedIds[0];
            }
        }

        return $selectedOutletId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadOutletSettings(Request $request): array
    {
        $selectedOutletId = self::resolveSelectedOutletId();
        $selectedOutlet = $selectedOutletId > 0 ? MposOutlet::find($selectedOutletId) : null;

        $offlineSetting = $selectedOutletId > 0
            ? MposTaxSetting::getForOutlet($selectedOutletId, MposTaxSetting::CATEGORY_OFFLINE)
            : null;

        $onlineSetting = $selectedOutletId > 0
            ? MposTaxSetting::getForOutlet($selectedOutletId, MposTaxSetting::CATEGORY_ONLINE)
            : null;

        $outletTransactionType = $selectedOutlet
            ? $selectedOutlet->resolvedTransactionType()
            : MposOutlet::TRANSACTION_TYPE_ONLINE;

        $activeTab = strtolower($outletTransactionType);

        return compact(
            'selectedOutletId',
            'selectedOutlet',
            'offlineSetting',
            'onlineSetting',
            'outletTransactionType',
            'activeTab'
        );
    }
}
