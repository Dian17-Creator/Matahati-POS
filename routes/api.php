<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\VoucherController;
use App\Http\Controllers\Master\PaymentController;
use App\Models\MposSalesH;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'apiIndex']);
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/customers', [CustomerController::class, 'apiIndex']);
Route::get('/vouchers', [VoucherController::class, 'apiIndex']);
Route::get('/payments', [PaymentController::class, 'apiIndex']);
Route::get('/payment-methods', [PaymentController::class, 'apiIndex']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//API ORDER-TYPE
Route::get('/pos/order-types', function () {
    return response()->json([
        'success' => true,
        'data' => collect(MposSalesH::ORDER_TYPES)->map(function ($type) {
            return [
                'value' => $type,
                'label' => match ($type) {
                    MposSalesH::ORDER_TYPE_DINE_IN => 'DINE-IN',
                    MposSalesH::ORDER_TYPE_TAKE_AWAY => 'TAKE-AWAY',
                    MposSalesH::ORDER_TYPE_ONLINE => 'ONLINE',
                    default => $type,
                },
            ];
        })->values(),
    ]);
});
