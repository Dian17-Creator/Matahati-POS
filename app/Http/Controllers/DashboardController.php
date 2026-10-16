<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MposGrpProduct;
use App\Models\MposProduct;
use App\Models\MposCust;
use App\Models\MposVoucher;
use App\Traits\ResolvesOutletScope;

class DashboardController extends Controller
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

        // 1. Kategori
        $categoriesQuery = MposGrpProduct::query();
        if (!empty($selectedOutletId)) {
            $categoriesQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $categoriesQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $categoriesCount = $categoriesQuery->count();

        // 2. Produk
        $productsQuery = MposProduct::query();
        if (!empty($selectedOutletId)) {
            $productsQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $productsQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $productsTotal = (clone $productsQuery)->count();
        $productsActive = (clone $productsQuery)->where(function ($q) {
            $q->where('cstatus', 'Active')
              ->orWhere('cstatus', 'ACTIVE')
              ->orWhere('cstatus', '1');
        })->count();
        $productsCombo = (clone $productsQuery)->where('fcombo', 1)->count();

        // 3. Pelanggan
        $customersQuery = MposCust::query();
        if (!empty($selectedOutletId)) {
            $customersQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $customersQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $customersCount = $customersQuery->count();

        // 4. Voucher (Voucher berlaku global)
        $vouchersTotal = MposVoucher::count();
        $vouchersActive = MposVoucher::where(function ($q) {
            $q->where('cstatus', 'Active')
              ->orWhere('cstatus', 'ACTIVE')
              ->orWhere('cstatus', 'Aktif')
              ->orWhere('cstatus', '1');
        })->count();

        $counts = [
            'categories' => $categoriesCount,
            'products_total' => $productsTotal,
            'products_active' => $productsActive,
            'products_combo' => $productsCombo,
            'customers' => $customersCount,
            'vouchers_total' => $vouchersTotal,
            'vouchers_active' => $vouchersActive,
        ];

        // 5. Produk Terakhir Ditambahkan
        $recentProductsQuery = MposProduct::with('category')->orderBy('nid', 'desc');
        if (!empty($selectedOutletId)) {
            $recentProductsQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$this->isSuperAdminUser()) {
            $recentProductsQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $recentProducts = $recentProductsQuery->take(5)->get();

        return view('dashboard.index', compact(
            'counts',
            'recentProducts',
            'outlets',
            'selectedOutletId',
            'isCashierOnly',
            'isSuperAdmin',
            'isOutletLocked',
            'allowedOutletIds'
        ));
    }
}
