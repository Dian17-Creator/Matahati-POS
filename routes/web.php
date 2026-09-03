<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\IngredientController;
use App\Http\Controllers\Master\RecipeController;
use App\Http\Controllers\Master\ComboController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\PosUserController;
use App\Http\Controllers\Master\PaymentController;
use App\Http\Controllers\Master\VoucherController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect()->route('dashboard');
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
    Route::resource('products', ProductController::class);
    Route::resource('ingredients', IngredientController::class);
    Route::resource('recipes', RecipeController::class);
    Route::resource('combos', ComboController::class);

    // POS Management Routes
    Route::resource('customers', CustomerController::class);
    Route::resource('outlets', OutletController::class);
    Route::resource('pos-users', PosUserController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('vouchers', VoucherController::class);
});
