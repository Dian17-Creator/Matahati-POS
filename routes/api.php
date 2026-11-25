<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Office\OfficeAuthController;
use App\Http\Controllers\Api\Office\OfficeDashboardController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\PaymentController;
use App\Http\Controllers\Master\PosUserController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\VoucherController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Api\PosDashboardController;
use App\Http\Controllers\Api\PosReportController;
use App\Http\Controllers\Api\TaxSettingController;
use App\Models\MposSalesH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Office\OfficeInventoryController;
use App\Http\Controllers\Api\Office\OfficeProductController;
use App\Http\Controllers\Api\Office\OfficePurchaseController;
use App\Http\Controllers\Api\Office\OfficeReportController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/user/outlets', [AuthController::class, 'outlets']);

// API khusus APK Web Office (Tablet)
Route::prefix('office')->group(function () {
    Route::post('/login', [OfficeAuthController::class, 'login']);
    Route::get('/me', [OfficeAuthController::class, 'me']);
    Route::post('/logout', [OfficeAuthController::class, 'logout']);
    Route::get('/dashboard', [OfficeDashboardController::class, 'index']);
    Route::get('/purchases', [OfficePurchaseController::class, 'index']);
    Route::get('/purchases/{id}', [OfficePurchaseController::class, 'show']);
    Route::get('/purchases/{id}/print-pdf', [OfficePurchaseController::class, 'printPdf']);
    Route::get('/reports', [OfficeReportController::class, 'index']);
    Route::get('/reports/export', [OfficeReportController::class, 'export']);
    Route::get('/inventory', [OfficeInventoryController::class, 'index']);
    Route::get('/inventory/detail', [OfficeInventoryController::class, 'showDetail']);
    Route::get('/inventory/print-pdf', [OfficeInventoryController::class, 'printPdf']);
    Route::get('/products', [OfficeProductController::class, 'getProducts']);
    Route::get('/categories', [OfficeProductController::class, 'getCategories']);
    Route::post('/categories', [OfficeProductController::class, 'storeCategory']);
});

Route::get('/categories', [CategoryController::class, 'apiIndex']);
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/vouchers', [VoucherController::class, 'apiIndex']);
Route::get('/payments', [PaymentController::class, 'apiIndex']);
Route::get('/payment-methods', [PaymentController::class, 'apiIndex']);
Route::get('/pos-users', [PosUserController::class, 'apiIndex']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Shift Endpoints (tanpa wajib token Sanctum)
Route::get('/shifts/current', [ShiftController::class, 'current']);
Route::post('/shifts/open', [ShiftController::class, 'open']);
Route::post('/shifts/close', [ShiftController::class, 'close']);
Route::post('/shifts/cash-in', [ShiftController::class, 'cashIn']);
Route::post('/shifts/cash-out', [ShiftController::class, 'cashOut']);
Route::get('/shifts/history', [ShiftController::class, 'history']);
Route::get('/shifts/{id}', [ShiftController::class, 'show']);

// Api Transaction
Route::get('/pos/dashboard', [PosDashboardController::class, 'dashboard']);
Route::get('/pos/reports/product-summary', [PosReportController::class, 'productSummary']);
Route::get('/pos/transactions', [TransactionController::class, 'index']);
Route::get('/pos/transactions/history', [TransactionController::class, 'index']);
Route::get('/pos/transactions/{id}', [TransactionController::class, 'show']);

// Customer API
Route::get('/customers', [CustomerController::class, 'apiIndex']);
Route::get('/customers/{id}', [CustomerController::class, 'apiShow']);
Route::post('/customers', [CustomerController::class, 'apiStore']);
Route::put('/customers/{id}', [CustomerController::class, 'apiUpdate']);
Route::delete('/customers/{id}', [CustomerController::class, 'apiDestroy']);
Route::get('/customer-types', [CustomerController::class, 'apiTypes']);
Route::post('/pos/transactions', [TransactionController::class, 'store']);
Route::delete('/pos/transactions/{id}', [TransactionController::class, 'destroy']);
Route::post('/pos/transactions/{id}/void', [TransactionController::class, 'voidTransaction']);
Route::post('/pos/transactions/{id}/refund', [TransactionController::class, 'refundTransaction']);
Route::post('/pos/transactions/{id}/finish', [TransactionController::class, 'markFinished']);
Route::post('/pos/transactions/{id}/pay', [TransactionController::class, 'settlePayment']);
Route::post('/pos/transactions/{id}/items/void', [TransactionController::class, 'voidItems']);
Route::post('/pos/transactions/{id}/items/refund', [TransactionController::class, 'refundItems']);

Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/history', [TransactionController::class, 'index']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);

// Tax & Receipt Settings
Route::get('/pos/tax-settings', [TaxSettingController::class, 'index']);
Route::put('/pos/tax-settings', [TaxSettingController::class, 'update']);

// API ORDER-TYPE
Route::get('/pos/order-types', function (Request $request) {
    $baseTypes = [
        MposSalesH::ORDER_TYPE_DINE_IN => 'DINE-IN',
        MposSalesH::ORDER_TYPE_TAKE_AWAY => 'TAKE-AWAY',
        MposSalesH::ORDER_TYPE_ONLINE => 'ONLINE',
        MposSalesH::ORDER_TYPE_RESERVATION => 'RESERVASI',
    ];

    $items = collect($baseTypes)->map(function (string $label, string $value): array {
        return [
            'value' => $value,
            'label' => $label,
        ];
    })->values();

    $outletId = (int) ($request->input('nid_outlet') ?? $request->user()?->nid_outlet ?? 0);
    if ($outletId > 0) {
        $activeDeliveries = \App\Models\MposDeliveryS::getActiveForOutlet($outletId);
        foreach ($activeDeliveries as $delivery) {
            $items->push([
                'value' => strtoupper((string) $delivery->cplatform),
                'label' => strtoupper((string) $delivery->cname),
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'data' => $items,
    ]);
});
