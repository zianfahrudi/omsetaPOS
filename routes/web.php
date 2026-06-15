<?php

use App\Http\Controllers\CashierController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('v2.dashboard'));

Route::get('/login', fn () => redirect()->route('v2.login'))->name('login');

Route::prefix('kasir')->name('cashier.')->group(function () {
    Route::get('/', [CashierController::class, 'index'])->name('index');
    Route::get('/products', [CashierController::class, 'products'])->middleware('auth')->name('products');
    Route::get('/transactions', [CashierController::class, 'transactions'])->middleware('auth')->name('transactions');
    Route::post('/transactions/{sale}/mark-paid', [CashierController::class, 'markTransactionPaid'])->middleware('auth')->name('transactions.mark-paid');
    Route::get('/customers', [CashierController::class, 'customers'])->middleware('auth')->name('customers');
    Route::post('/customers', [CashierController::class, 'storeCustomer'])->middleware('auth')->name('customers.store');
    Route::get('/customers/check', [CashierController::class, 'checkCustomer'])->middleware('auth')->name('customers.check');
    Route::get('/vehicles', [CashierController::class, 'vehicles'])->middleware('auth')->name('vehicles');
    Route::post('/vehicles', [CashierController::class, 'storeVehicle'])->middleware('auth')->name('vehicles.store');
    Route::get('/pricing', [CashierController::class, 'pricing'])->middleware('auth')->name('pricing');
    Route::post('/checkout', [CashierController::class, 'checkout'])->middleware('auth')->name('checkout');
    Route::post('/refunds', [CashierController::class, 'refund'])->middleware('auth')->name('refunds.store');
});

/*
|--------------------------------------------------------------------------
| Admin v2 (Blade + Tailwind, tanpa Filament)
|--------------------------------------------------------------------------
*/
Route::prefix('app')->name('v2.')->group(function () {
    Route::get('login', [\App\Http\Controllers\V2\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [\App\Http\Controllers\V2\AuthController::class, 'login'])->name('login.attempt');

    Route::middleware('auth')->group(function () {
        Route::post('logout', [\App\Http\Controllers\V2\AuthController::class, 'logout'])->name('logout');

        Route::get('/', [\App\Http\Controllers\V2\DashboardController::class, 'index'])->name('dashboard');

        Route::get('produk', [\App\Http\Controllers\V2\ProductController::class, 'index'])->name('products.index');
        Route::get('produk/baru', [\App\Http\Controllers\V2\ProductController::class, 'create'])->name('products.create');
        Route::post('produk', [\App\Http\Controllers\V2\ProductController::class, 'store'])->name('products.store');
        Route::get('produk/{product}/edit', [\App\Http\Controllers\V2\ProductController::class, 'edit'])->name('products.edit');
        Route::put('produk/{product}', [\App\Http\Controllers\V2\ProductController::class, 'update'])->name('products.update');
        Route::delete('produk/{product}', [\App\Http\Controllers\V2\ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('laporan/neraca', [\App\Http\Controllers\V2\ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('laporan/laba-rugi', [\App\Http\Controllers\V2\ReportController::class, 'incomeStatement'])->name('reports.income-statement');

        Route::get('akuntansi/akun', [\App\Http\Controllers\V2\AccountController::class, 'index'])->name('accounting.accounts');
        Route::get('akuntansi/jurnal', [\App\Http\Controllers\V2\JournalController::class, 'index'])->name('accounting.journals');
        Route::get('akuntansi/jurnal/{journal}', [\App\Http\Controllers\V2\JournalController::class, 'show'])->name('accounting.journals.show');

        // Penjualan
        Route::get('penjualan/penawaran', [\App\Http\Controllers\V2\SalesController::class, 'quotations'])->name('sales.quotations');
        Route::get('penjualan/pesanan', [\App\Http\Controllers\V2\SalesController::class, 'orders'])->name('sales.orders');
        Route::get('penjualan/faktur', [\App\Http\Controllers\V2\SalesController::class, 'invoices'])->name('sales.invoices');
        Route::get('penjualan/faktur/{invoice}', [\App\Http\Controllers\V2\SalesController::class, 'invoiceShow'])->name('sales.invoices.show');

        // Pembelian
        Route::get('pembelian/pesanan', [\App\Http\Controllers\V2\PurchaseController::class, 'orders'])->name('purchase.orders');
        Route::get('pembelian/faktur', [\App\Http\Controllers\V2\PurchaseController::class, 'invoices'])->name('purchase.invoices');
        Route::get('pembelian/faktur/{invoice}', [\App\Http\Controllers\V2\PurchaseController::class, 'invoiceShow'])->name('purchase.invoices.show');

        // Persediaan
        Route::get('persediaan/penyesuaian', [\App\Http\Controllers\V2\InventoryController::class, 'adjustments'])->name('inventory.adjustments');
        Route::get('persediaan/pemindahan', [\App\Http\Controllers\V2\InventoryController::class, 'transfers'])->name('inventory.transfers');

        // Kas & Bank
        Route::get('kas/transaksi', [\App\Http\Controllers\V2\CashController::class, 'transactions'])->name('cash.transactions');

        // Kontak (Data Master)
        Route::get('kontak', [\App\Http\Controllers\V2\ContactController::class, 'index'])->name('contacts');
        Route::get('kontak/baru', [\App\Http\Controllers\V2\ContactController::class, 'create'])->name('contacts.create');
        Route::post('kontak', [\App\Http\Controllers\V2\ContactController::class, 'store'])->name('contacts.store');
        Route::get('kontak/{contact}/edit', [\App\Http\Controllers\V2\ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('kontak/{contact}', [\App\Http\Controllers\V2\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('kontak/{contact}', [\App\Http\Controllers\V2\ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('segera', [\App\Http\Controllers\V2\PageController::class, 'soon'])->name('soon');
    });
});
