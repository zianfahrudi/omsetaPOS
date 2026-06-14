<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('products', [ProductController::class, 'index'])->name('products.index');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/check', [CustomerController::class, 'check'])->name('customers.check');

        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');

        Route::get('pricing', [PricingController::class, 'show'])->name('pricing.show');
        Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions/{sale}/mark-paid', [TransactionController::class, 'markPaid'])->name('transactions.mark-paid');

        Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');
    });
});
