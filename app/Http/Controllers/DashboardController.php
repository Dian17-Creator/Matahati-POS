<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MposGrpProduct;
use App\Models\MposProduct;
use App\Models\MposCust;
use App\Models\MposVoucher;

class DashboardController extends Controller
{
    public function index()
    {
        // Kumpulkan data statistik mendetail
        $counts = [
            'categories' => MposGrpProduct::count(),
            'products_total' => MposProduct::count(),
            'products_active' => MposProduct::where('cstatus', 'Active')->count(),
            'products_combo' => MposProduct::where('fcombo', 1)->count(),
            'customers' => MposCust::count(),
            'vouchers_total' => MposVoucher::count(),
            'vouchers_active' => MposVoucher::where('cstatus', 'Active')->orWhere('cstatus', 'Aktif')->count(),
        ];
        
        // Ambil 5 produk terakhir yang ditambahkan untuk tabel ringkasan
        $recentProducts = MposProduct::orderBy('nid', 'desc')->take(5)->get();

        return view('dashboard.index', compact('counts', 'recentProducts'));
    }
}
