<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposProductPrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductPriceController extends Controller
{
    public function store(Request $request, string $product)
    {
        $request->validate([
            'nid_cust_type' => 'required|exists:mpos_cust_type,nid',
            'nqty_start' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('mpos_product_price')->where(function ($query) use ($product, $request) {
                    return $query->where('nid_product', $product)
                                 ->where('nid_cust_type', $request->nid_cust_type);
                }),
            ],
            'nprice' => 'required|numeric|min:0',
        ], [
            'nqty_start.unique' => 'Tingkatan harga untuk tipe pelanggan dan Qty Mulai tersebut sudah ada.',
        ]);

        try {
            MposProductPrice::create([
                'nid_product' => $product,
                'nid_cust_type' => $request->nid_cust_type,
                'nqty_start' => $request->nqty_start,
                'nprice' => $request->nprice,
            ]);

            return redirect()->back()->with('success', 'Tingkatan harga berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, string $product, string $price)
    {
        $request->validate([
            'nid_cust_type' => 'required|exists:mpos_cust_type,nid',
            'nqty_start' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('mpos_product_price')->where(function ($query) use ($product, $request) {
                    return $query->where('nid_product', $product)
                                 ->where('nid_cust_type', $request->nid_cust_type);
                })->ignore($price, 'nid'),
            ],
            'nprice' => 'required|numeric|min:0',
        ], [
            'nqty_start.unique' => 'Tingkatan harga untuk tipe pelanggan dan Qty Mulai tersebut sudah ada.',
        ]);

        try {
            $productPrice = MposProductPrice::where('nid', $price)->where('nid_product', $product)->firstOrFail();
            
            $productPrice->update([
                'nid_cust_type' => $request->nid_cust_type,
                'nqty_start' => $request->nqty_start,
                'nprice' => $request->nprice,
            ]);

            return redirect()->back()->with('success', 'Tingkatan harga berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(string $product, string $price)
    {
        try {
            $productPrice = MposProductPrice::where('nid', $price)->where('nid_product', $product)->firstOrFail();
            $productPrice->delete();

            return redirect()->back()->with('success', 'Tingkatan harga berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
