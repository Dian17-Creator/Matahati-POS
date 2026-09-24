<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\PaymentController;
use App\Http\Controllers\Master\PosUserController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\VoucherController;
use App\Http\Controllers\TransactionController;
use App\Models\MposSalesH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'apiIndex']);
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/customers', [CustomerController::class, 'apiIndex']);
Route::get('/vouchers', [VoucherController::class, 'apiIndex']);
Route::get('/payments', [PaymentController::class, 'apiIndex']);
Route::get('/payment-methods', [PaymentController::class, 'apiIndex']);
Route::get('/pos-users', [PosUserController::class, 'apiIndex']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Shift Endpoints (tanpa wajib token Sanctum)
Route::get('/shifts/current', [ShiftController::class, 'current']);
Route::post('/shifts/start', [ShiftController::class, 'start']);
Route::post('/shifts/cash-movement', [ShiftController::class, 'cashMovement']);
Route::post('/shifts/{id}/close', [ShiftController::class, 'close']);
Route::get('/shifts/history', [ShiftController::class, 'history']);
Route::get('/shifts/{id}', [ShiftController::class, 'show']);

// Api Transaction
Route::get('/pos/transactions', [TransactionController::class, 'index']);
Route::get('/pos/transactions/history', [TransactionController::class, 'index']);
Route::get('/pos/transactions/{id}', [TransactionController::class, 'show']);
Route::post('/pos/transactions', [TransactionController::class, 'store']);
Route::delete('/pos/transactions/{id}', [TransactionController::class, 'destroy']);

Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/history', [TransactionController::class, 'index']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);

// API ORDER-TYPE
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
