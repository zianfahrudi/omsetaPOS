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
        Route::get('penjualan/penawaran/baru', [\App\Http\Controllers\V2\SalesController::class, 'quotationCreate'])->name('sales.quotations.create');
        Route::post('penjualan/penawaran', [\App\Http\Controllers\V2\SalesController::class, 'quotationStore'])->name('sales.quotations.store');
        Route::post('penjualan/penawaran/{quotation}/konversi', [\App\Http\Controllers\V2\SalesController::class, 'quotationConvert'])->name('sales.quotations.convert');

        Route::get('penjualan/pesanan', [\App\Http\Controllers\V2\SalesController::class, 'orders'])->name('sales.orders');
        Route::get('penjualan/pesanan/baru', [\App\Http\Controllers\V2\SalesController::class, 'orderCreate'])->name('sales.orders.create');
        Route::post('penjualan/pesanan', [\App\Http\Controllers\V2\SalesController::class, 'orderStore'])->name('sales.orders.store');
        Route::post('penjualan/pesanan/{order}/konversi', [\App\Http\Controllers\V2\SalesController::class, 'orderConvert'])->name('sales.orders.convert');

        Route::get('penjualan/faktur', [\App\Http\Controllers\V2\SalesController::class, 'invoices'])->name('sales.invoices');
        Route::get('penjualan/faktur/baru', [\App\Http\Controllers\V2\SalesController::class, 'invoiceCreate'])->name('sales.invoices.create');
        Route::post('penjualan/faktur', [\App\Http\Controllers\V2\SalesController::class, 'invoiceStore'])->name('sales.invoices.store');
        Route::get('penjualan/faktur/{invoice}', [\App\Http\Controllers\V2\SalesController::class, 'invoiceShow'])->name('sales.invoices.show');
        Route::get('penjualan/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\SalesController::class, 'paymentCreate'])->name('sales.invoices.payment');
        Route::post('penjualan/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\SalesController::class, 'paymentStore'])->name('sales.invoices.payment.store');
        Route::get('penjualan/faktur/{invoice}/retur', [\App\Http\Controllers\V2\SalesController::class, 'returnCreate'])->name('sales.invoices.return');
        Route::post('penjualan/faktur/{invoice}/retur', [\App\Http\Controllers\V2\SalesController::class, 'returnStore'])->name('sales.invoices.return.store');

        Route::get('penjualan/piutang', [\App\Http\Controllers\V2\SalesController::class, 'receivables'])->name('sales.receivables');

        // Pembelian
        Route::get('pembelian/permintaan', [\App\Http\Controllers\V2\PurchaseController::class, 'requests'])->name('purchase.requests');
        Route::get('pembelian/permintaan/baru', [\App\Http\Controllers\V2\PurchaseController::class, 'requestCreate'])->name('purchase.requests.create');
        Route::post('pembelian/permintaan', [\App\Http\Controllers\V2\PurchaseController::class, 'requestStore'])->name('purchase.requests.store');
        Route::post('pembelian/permintaan/{purchaseRequest}/konversi', [\App\Http\Controllers\V2\PurchaseController::class, 'requestConvert'])->name('purchase.requests.convert');

        Route::get('pembelian/pesanan', [\App\Http\Controllers\V2\PurchaseController::class, 'orders'])->name('purchase.orders');
        Route::get('pembelian/pesanan/baru', [\App\Http\Controllers\V2\PurchaseController::class, 'orderCreate'])->name('purchase.orders.create');
        Route::post('pembelian/pesanan', [\App\Http\Controllers\V2\PurchaseController::class, 'orderStore'])->name('purchase.orders.store');
        Route::post('pembelian/pesanan/{purchaseOrder}/konversi', [\App\Http\Controllers\V2\PurchaseController::class, 'orderConvert'])->name('purchase.orders.convert');

        Route::get('pembelian/faktur', [\App\Http\Controllers\V2\PurchaseController::class, 'invoices'])->name('purchase.invoices');
        Route::get('pembelian/faktur/baru', [\App\Http\Controllers\V2\PurchaseController::class, 'invoiceCreate'])->name('purchase.invoices.create');
        Route::post('pembelian/faktur', [\App\Http\Controllers\V2\PurchaseController::class, 'invoiceStore'])->name('purchase.invoices.store');
        Route::get('pembelian/faktur/{invoice}', [\App\Http\Controllers\V2\PurchaseController::class, 'invoiceShow'])->name('purchase.invoices.show');
        Route::get('pembelian/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\PurchaseController::class, 'paymentCreate'])->name('purchase.invoices.payment');
        Route::post('pembelian/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\PurchaseController::class, 'paymentStore'])->name('purchase.invoices.payment.store');
        Route::get('pembelian/faktur/{invoice}/retur', [\App\Http\Controllers\V2\PurchaseController::class, 'returnCreate'])->name('purchase.invoices.return');
        Route::post('pembelian/faktur/{invoice}/retur', [\App\Http\Controllers\V2\PurchaseController::class, 'returnStore'])->name('purchase.invoices.return.store');

        Route::get('pembelian/hutang', [\App\Http\Controllers\V2\PurchaseController::class, 'payables'])->name('purchase.payables');

        // Persediaan
        Route::get('persediaan/penyesuaian', [\App\Http\Controllers\V2\InventoryController::class, 'adjustments'])->name('inventory.adjustments');
        Route::get('persediaan/pemindahan', [\App\Http\Controllers\V2\InventoryController::class, 'transfers'])->name('inventory.transfers');

        // Kas & Bank
        Route::get('kas/transaksi', [\App\Http\Controllers\V2\CashController::class, 'transactions'])->name('cash.transactions');
        Route::get('kas/transaksi/baru', [\App\Http\Controllers\V2\CashController::class, 'create'])->name('cash.transactions.create');
        Route::post('kas/transaksi', [\App\Http\Controllers\V2\CashController::class, 'store'])->name('cash.transactions.store');

        // Kontak (Data Master)
        Route::get('kontak', [\App\Http\Controllers\V2\ContactController::class, 'index'])->name('contacts');
        Route::get('kontak/baru', [\App\Http\Controllers\V2\ContactController::class, 'create'])->name('contacts.create');
        Route::post('kontak', [\App\Http\Controllers\V2\ContactController::class, 'store'])->name('contacts.store');
        Route::get('kontak/{contact}/edit', [\App\Http\Controllers\V2\ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('kontak/{contact}', [\App\Http\Controllers\V2\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('kontak/{contact}', [\App\Http\Controllers\V2\ContactController::class, 'destroy'])->name('contacts.destroy');

        // Pelanggan POS (model Customer, dipakai Kasir)
        Route::get('pelanggan', [\App\Http\Controllers\V2\CustomerController::class, 'index'])->name('customers.index');
        Route::get('pelanggan/baru', [\App\Http\Controllers\V2\CustomerController::class, 'create'])->name('customers.create');
        Route::post('pelanggan', [\App\Http\Controllers\V2\CustomerController::class, 'store'])->name('customers.store');
        Route::get('pelanggan/{customer}/edit', [\App\Http\Controllers\V2\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('pelanggan/{customer}', [\App\Http\Controllers\V2\CustomerController::class, 'update'])->name('customers.update');
        Route::delete('pelanggan/{customer}', [\App\Http\Controllers\V2\CustomerController::class, 'destroy'])->name('customers.destroy');

        // Data Master sederhana (Satuan, Gudang, Departemen, Proyek, Mata Uang, Pajak)
        foreach ([
            'satuan' => ['units', \App\Http\Controllers\V2\Master\UnitController::class],
            'gudang' => ['warehouses', \App\Http\Controllers\V2\Master\WarehouseController::class],
            'departemen' => ['departments', \App\Http\Controllers\V2\Master\DepartmentController::class],
            'proyek' => ['projects', \App\Http\Controllers\V2\Master\ProjectController::class],
            'mata-uang' => ['currencies', \App\Http\Controllers\V2\Master\CurrencyController::class],
            'pajak' => ['taxes', \App\Http\Controllers\V2\Master\TaxController::class],
        ] as $slug => [$name, $controller]) {
            Route::get($slug, [$controller, 'index'])->name("{$name}.index");
            Route::get("{$slug}/baru", [$controller, 'create'])->name("{$name}.create");
            Route::post($slug, [$controller, 'store'])->name("{$name}.store");
            Route::get("{$slug}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
            Route::put("{$slug}/{id}", [$controller, 'update'])->name("{$name}.update");
            Route::delete("{$slug}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
        }

        Route::get('segera', [\App\Http\Controllers\V2\PageController::class, 'soon'])->name('soon');
    });
});
