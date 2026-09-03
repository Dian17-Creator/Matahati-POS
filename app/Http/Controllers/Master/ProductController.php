<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MposProduct;
use App\Models\MposGrpProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = MposProduct::with('category')->paginate(10);
        $categories = MposGrpProduct::all();
        
        return view('products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $data = $request->except('photo');
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        }

        MposProduct::create($data);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'nid_category' => 'required|exists:mpos_grp_product,nid',
            'nprice' => 'required|numeric|min:0',
            'cstatus' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $product = MposProduct::findOrFail($id);
        $data = $request->except('photo');
        $data['fcombo'] = $request->has('fcombo') ? 1 : 0;

        if ($request->hasFile('photo')) {
            if ($product->cphotos && file_exists(public_path($product->cphotos))) {
                unlink(public_path($product->cphotos));
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/product'), $filename);
            $data['cphotos'] = 'uploads/product/' . $filename;
        }

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = MposProduct::findOrFail($id);
        
        if ($product->cphotos && file_exists(public_path($product->cphotos))) {
            unlink(public_path($product->cphotos));
        }
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
