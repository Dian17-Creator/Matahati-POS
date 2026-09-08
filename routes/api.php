<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'apiIndex']);
Route::get('/products', [ProductController::class, 'apiIndex']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
