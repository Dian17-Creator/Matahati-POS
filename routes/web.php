<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\IngredientController;

use App\Http\Controllers\Master\ComboController;
use App\Http\Controllers\Master\CustomerTypeController;
use App\Http\Controllers\Master\PriceCategoryController;
use App\Http\Controllers\Master\ProductPriceController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\PosUserController;
use App\Http\Controllers\Master\PaymentController;
use App\Http\Controllers\Master\VoucherController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Purchase\PurchaseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Invoice View Route (Mendukung URL /pos/invoice/ dan /uploads/invoice/)
$invoiceHandler = function ($filename) {
    $sharedPath = '/var/www/shared/pos/invoice/' . $filename;
    $localPath = public_path('uploads' . DIRECTORY_SEPARATOR . 'invoice' . DIRECTORY_SEPARATOR . $filename);

    $path = file_exists($sharedPath) ? $sharedPath : (file_exists($localPath) ? $localPath : null);

    // Jika file fisik belum ada di storage, coba buatkan secara otomatis (on-demand fallback)
    if (! $path || ! file_exists($path)) {
        if (preg_match('/^invoice_([A-Za-z0-9]+)_(\d+)\.pdf$/', $filename, $matches)) {
            $cleanTrx = $matches[1];
            $salesH = \App\Models\MposSalesH::where('cnotransaction', 'like', "%{$cleanTrx}%")
                ->orWhere('cnotransaction', '#' . $cleanTrx)
                ->first();

            if ($salesH && class_exists(\App\Services\InvoicePdfService::class)) {
                $genFile = app(\App\Services\InvoicePdfService::class)->generate($salesH);
                if ($genFile) {
                    if (file_exists($sharedPath)) {
                        $path = $sharedPath;
                    } elseif (file_exists($localPath)) {
                        $path = $localPath;
                    }
                }
            }
        }
    }

    if (! $path || ! file_exists($path)) {
        abort(404, 'Invoice tidak ditemukan.');
    }

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
};

Route::get('/pos/invoice/{filename}', $invoiceHandler)->name('invoice.view');
Route::get('/uploads/invoice/{filename}', $invoiceHandler);

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Master Data Routes
    Route::resource('categories', CategoryController::class);
    Route::resource('customer-types', CustomerTypeController::class)->except(['create', 'show', 'edit']);
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/{product}/detail', [ProductController::class, 'show'])->name('products.show');
    Route::post('products/{product}/prices', [ProductPriceController::class, 'store'])->name('products.prices.store');
    Route::put('products/{product}/prices/{price}', [ProductPriceController::class, 'update'])->name('products.prices.update');
    Route::delete('products/{product}/prices/{price}', [ProductPriceController::class, 'destroy'])->name('products.prices.destroy');
    Route::resource('ingredients', IngredientController::class);

    Route::resource('combos', ComboController::class);

    // POS Management Routes
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('customers', CustomerController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('vouchers', VoucherController::class);

    // Supplier & Purchase / Kontrol Stok
    Route::resource('suppliers', SupplierController::class);
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
        Route::post('/{id}/items', [PurchaseController::class, 'addItem'])->name('add-item');
        Route::delete('/{id}/items/{detailId}', [PurchaseController::class, 'removeItem'])->name('remove-item');
        Route::post('/{id}/receive', [PurchaseController::class, 'receive'])->name('receive');
        Route::post('/{id}/cancel', [PurchaseController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/payment', [PurchaseController::class, 'updatePayment'])->name('update-payment');
        Route::get('/{id}/print', [PurchaseController::class, 'print'])->name('print');
    });

    // Routes khusus level Owner / Super Admin
    Route::middleware(['owner'])->group(function () {
        Route::resource('outlets', OutletController::class);
        Route::resource('pos-users', PosUserController::class);
    });

    // Report Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('customer-product', [\App\Http\Controllers\Report\CustomerProductReportController::class, 'index'])->name('customer-product');
        Route::get('customer-product/products', [\App\Http\Controllers\Report\CustomerProductReportController::class, 'getProductsByCategory'])->name('customer-product.products');
        Route::get('customer-product/export', [\App\Http\Controllers\Report\CustomerProductReportController::class, 'exportExcel'])->name('customer-product.export');
        
        Route::get('sales-detail', [\App\Http\Controllers\Report\SalesDetailReportController::class, 'index'])->name('sales-detail');
        Route::get('sales-detail/export', [\App\Http\Controllers\Report\SalesDetailReportController::class, 'exportExcel'])->name('sales-detail.export');
        Route::post('sales-detail/import', [\App\Http\Controllers\Report\SalesDetailReportController::class, 'import'])->name('sales-detail.import');

        Route::get('sales-item', [\App\Http\Controllers\Report\SalesItemReportController::class, 'index'])->name('sales-item');
        Route::get('sales-item/export', [\App\Http\Controllers\Report\SalesItemReportController::class, 'exportExcel'])->name('sales-item.export');
        Route::post('sales-item/import', [\App\Http\Controllers\Report\SalesItemReportController::class, 'import'])->name('sales-item.import');
    });
});
