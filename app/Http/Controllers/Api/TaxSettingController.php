<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposTaxSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $outletId = (int) ($request->input('nid_outlet') ?? $request->user()?->nid_outlet ?? 0);

        if ($outletId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter nid_outlet wajib diisi.',
            ], 422);
        }

        $offline = MposTaxSetting::getForOutlet($outletId, MposTaxSetting::CATEGORY_OFFLINE);
        $online = MposTaxSetting::getForOutlet($outletId, MposTaxSetting::CATEGORY_ONLINE);
        $outletTransactionType = \App\Models\MposOutlet::resolveTransactionTypeForOutlet($outletId) ?? MposTaxSetting::CATEGORY_ONLINE;

        return response()->json([
            'success' => true,
            'data' => [
                'outlet_transaction_type' => $outletTransactionType,
                'offline' => $offline ?? $this->defaultPayload($outletId, MposTaxSetting::CATEGORY_OFFLINE),
                'online' => $online ?? $this->defaultPayload($outletId, MposTaxSetting::CATEGORY_ONLINE),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
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
            'creceipt_title' => 'nullable|string',
            'creceipt_note' => 'nullable|string',
            'bhide_order_no' => 'nullable|boolean',
        ]);

        $category = strtoupper($validated['ccategory']);
        $isOnline = $category === MposTaxSetting::CATEGORY_ONLINE;

        $setting = MposTaxSetting::updateOrCreate(
            [
                'nid_outlet' => (int) $validated['nid_outlet'],
                'ccategory' => $category,
            ],
            [
                'ctax_name' => $isOnline ? null : ($validated['ctax_name'] ?? null),
                'ntax_percent' => $isOnline ? 0.00 : (float) ($validated['ntax_percent'] ?? 0),
                'bis_tax_included' => $isOnline ? false : (bool) ($validated['bis_tax_included'] ?? false),
                'bppn_11_12' => $isOnline ? false : (bool) ($validated['bppn_11_12'] ?? false),
                'btakeaway_tax_exempt' => $isOnline ? false : (bool) ($validated['btakeaway_tax_exempt'] ?? false),
                'ballow_manual_tax_exempt' => $isOnline ? false : (bool) ($validated['ballow_manual_tax_exempt'] ?? false),
                'btax_before_discount' => $isOnline ? false : (bool) ($validated['btax_before_discount'] ?? false),
                'nservice_charge_percent' => (float) ($validated['nservice_charge_percent'] ?? 0),
                'creceipt_title' => $validated['creceipt_title'] ?? null,
                'creceipt_note' => $validated['creceipt_note'] ?? null,
                'bhide_order_no' => (bool) ($validated['bhide_order_no'] ?? false),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan pajak & resi berhasil disimpan.',
            'data' => $setting,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultPayload(int $outletId, string $category): array
    {
        return [
            'nid_outlet' => $outletId,
            'ccategory' => $category,
            'ctax_name' => null,
            'ntax_percent' => 0.0,
            'bis_tax_included' => false,
            'bppn_11_12' => false,
            'btakeaway_tax_exempt' => false,
            'ballow_manual_tax_exempt' => false,
            'btax_before_discount' => false,
            'nservice_charge_percent' => 0.0,
            'creceipt_title' => null,
            'creceipt_note' => null,
            'bhide_order_no' => false,
        ];
    }
}
