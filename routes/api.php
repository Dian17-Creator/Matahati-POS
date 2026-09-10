<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\VoucherController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'apiIndex']);
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/customers', [CustomerController::class, 'apiIndex']);
Route::get('/vouchers', [VoucherController::class, 'apiIndex']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
