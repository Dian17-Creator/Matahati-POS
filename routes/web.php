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
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Invoice View Route
Route::get('/pos/invoice/{filename}', function ($filename) {
    $sharedPath = '/var/www/shared/pos/invoice/' . $filename;
    $localPath = public_path('uploads/invoice/' . $filename);

    $path = file_exists($sharedPath) ? $sharedPath : $localPath;

    if (!file_exists($path)) {
        abort(404, 'Invoice tidak ditemukan.');
    }
    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
    Route::resource('outlets', OutletController::class);
    Route::resource('pos-users', PosUserController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('vouchers', VoucherController::class);

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
