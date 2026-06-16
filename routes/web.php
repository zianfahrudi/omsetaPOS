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
        Route::get('laporan/arus-kas', [\App\Http\Controllers\V2\ReportController::class, 'cashFlow'])->name('reports.cash-flow');
        Route::get('laporan/penjualan', [\App\Http\Controllers\V2\ReportController::class, 'sales'])->name('reports.sales');
        Route::get('laporan/pembelian', [\App\Http\Controllers\V2\ReportController::class, 'purchases'])->name('reports.purchases');
        Route::get('laporan/persediaan', [\App\Http\Controllers\V2\ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('laporan/pajak', [\App\Http\Controllers\V2\ReportController::class, 'tax'])->name('reports.tax');
        Route::get('laporan/neraca-saldo', [\App\Http\Controllers\V2\ReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('laporan/stok-gudang', [\App\Http\Controllers\V2\ReportController::class, 'warehouseStock'])->name('reports.warehouse-stock');

        Route::get('akuntansi/akun', [\App\Http\Controllers\V2\AccountController::class, 'index'])->name('accounting.accounts');
        Route::get('akuntansi/buku-besar', [\App\Http\Controllers\V2\LedgerController::class, 'index'])->name('accounting.ledger');
        Route::get('akuntansi/jurnal', [\App\Http\Controllers\V2\JournalController::class, 'index'])->name('accounting.journals');
        Route::get('akuntansi/jurnal/baru', [\App\Http\Controllers\V2\JournalController::class, 'create'])->name('accounting.journals.create');
        Route::post('akuntansi/jurnal', [\App\Http\Controllers\V2\JournalController::class, 'store'])->name('accounting.journals.store');
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
        Route::get('penjualan/faktur/{invoice}/cetak', [\App\Http\Controllers\V2\SalesController::class, 'invoicePrint'])->name('sales.invoices.print');
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
        Route::get('pembelian/faktur/{invoice}/cetak', [\App\Http\Controllers\V2\PurchaseController::class, 'invoicePrint'])->name('purchase.invoices.print');
        Route::get('pembelian/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\PurchaseController::class, 'paymentCreate'])->name('purchase.invoices.payment');
        Route::post('pembelian/faktur/{invoice}/bayar', [\App\Http\Controllers\V2\PurchaseController::class, 'paymentStore'])->name('purchase.invoices.payment.store');
        Route::get('pembelian/faktur/{invoice}/retur', [\App\Http\Controllers\V2\PurchaseController::class, 'returnCreate'])->name('purchase.invoices.return');
        Route::post('pembelian/faktur/{invoice}/retur', [\App\Http\Controllers\V2\PurchaseController::class, 'returnStore'])->name('purchase.invoices.return.store');

        Route::get('pembelian/hutang', [\App\Http\Controllers\V2\PurchaseController::class, 'payables'])->name('purchase.payables');

        // Persediaan
        Route::get('persediaan/penyesuaian', [\App\Http\Controllers\V2\InventoryController::class, 'adjustments'])->name('inventory.adjustments');
        Route::get('persediaan/penyesuaian/baru', [\App\Http\Controllers\V2\InventoryController::class, 'adjustmentCreate'])->name('inventory.adjustments.create');
        Route::post('persediaan/penyesuaian', [\App\Http\Controllers\V2\InventoryController::class, 'adjustmentStore'])->name('inventory.adjustments.store');
        Route::get('persediaan/pemindahan', [\App\Http\Controllers\V2\InventoryController::class, 'transfers'])->name('inventory.transfers');
        Route::get('persediaan/pemindahan/baru', [\App\Http\Controllers\V2\InventoryController::class, 'transferCreate'])->name('inventory.transfers.create');
        Route::post('persediaan/pemindahan', [\App\Http\Controllers\V2\InventoryController::class, 'transferStore'])->name('inventory.transfers.store');
        Route::get('persediaan/kartu-stok', [\App\Http\Controllers\V2\InventoryController::class, 'stockCard'])->name('inventory.stock-card');
        Route::get('persediaan/perakitan', [\App\Http\Controllers\V2\AssemblyController::class, 'index'])->name('inventory.assemblies');
        Route::get('persediaan/perakitan/baru', [\App\Http\Controllers\V2\AssemblyController::class, 'create'])->name('inventory.assemblies.create');
        Route::post('persediaan/perakitan', [\App\Http\Controllers\V2\AssemblyController::class, 'store'])->name('inventory.assemblies.store');
        Route::get('persediaan/perakitan/{assembly}', [\App\Http\Controllers\V2\AssemblyController::class, 'show'])->name('inventory.assemblies.show');
        // Konsinyasi
        Route::get('persediaan/konsinyasi', [\App\Http\Controllers\V2\ConsignmentController::class, 'index'])->name('inventory.consignments');
        Route::get('persediaan/konsinyasi/baru', [\App\Http\Controllers\V2\ConsignmentController::class, 'create'])->name('inventory.consignments.create');
        Route::post('persediaan/konsinyasi', [\App\Http\Controllers\V2\ConsignmentController::class, 'store'])->name('inventory.consignments.store');
        Route::get('persediaan/konsinyasi/{consignment}', [\App\Http\Controllers\V2\ConsignmentController::class, 'show'])->name('inventory.consignments.show');
        Route::post('persediaan/konsinyasi/{consignment}/settle', [\App\Http\Controllers\V2\ConsignmentController::class, 'settle'])->name('inventory.consignments.settle');
        Route::post('persediaan/konsinyasi/{consignment}/retur', [\App\Http\Controllers\V2\ConsignmentController::class, 'returnItems'])->name('inventory.consignments.return');

        // Kas & Bank
        Route::get('kas/transaksi', [\App\Http\Controllers\V2\CashController::class, 'transactions'])->name('cash.transactions');
        Route::get('kas/transaksi/baru', [\App\Http\Controllers\V2\CashController::class, 'create'])->name('cash.transactions.create');
        Route::post('kas/transaksi', [\App\Http\Controllers\V2\CashController::class, 'store'])->name('cash.transactions.store');

        // Giro
        Route::get('kas/giro', [\App\Http\Controllers\V2\GiroController::class, 'index'])->name('cash.giros');
        Route::get('kas/giro/baru', [\App\Http\Controllers\V2\GiroController::class, 'create'])->name('cash.giros.create');
        Route::post('kas/giro', [\App\Http\Controllers\V2\GiroController::class, 'store'])->name('cash.giros.store');
        Route::post('kas/giro/{giro}/setor', [\App\Http\Controllers\V2\GiroController::class, 'deposit'])->name('cash.giros.deposit');
        Route::get('kas/giro/{giro}/cair', [\App\Http\Controllers\V2\GiroController::class, 'clearForm'])->name('cash.giros.clear');
        Route::post('kas/giro/{giro}/cair', [\App\Http\Controllers\V2\GiroController::class, 'clear'])->name('cash.giros.clear.store');
        Route::post('kas/giro/{giro}/tolak', [\App\Http\Controllers\V2\GiroController::class, 'reject'])->name('cash.giros.reject');

        // Rekonsiliasi Bank
        Route::get('kas/rekonsiliasi', [\App\Http\Controllers\V2\BankReconciliationController::class, 'index'])->name('cash.reconciliations');
        Route::get('kas/rekonsiliasi/baru', [\App\Http\Controllers\V2\BankReconciliationController::class, 'create'])->name('cash.reconciliations.create');
        Route::post('kas/rekonsiliasi', [\App\Http\Controllers\V2\BankReconciliationController::class, 'store'])->name('cash.reconciliations.store');
        Route::get('kas/rekonsiliasi/{reconciliation}', [\App\Http\Controllers\V2\BankReconciliationController::class, 'show'])->name('cash.reconciliations.show');

        // Point of Sale
        Route::get('pos/transaksi', [\App\Http\Controllers\V2\PosController::class, 'transactions'])->name('pos.transactions');
        Route::get('pos/transaksi/{sale}', [\App\Http\Controllers\V2\PosController::class, 'transactionShow'])->name('pos.transactions.show');
        Route::get('pos/transaksi/{sale}/struk', [\App\Http\Controllers\V2\PosController::class, 'receipt'])->name('pos.transactions.receipt');
        Route::post('pos/transaksi/{sale}/void', [\App\Http\Controllers\V2\PosController::class, 'void'])->name('pos.transactions.void');
        Route::get('pos/sesi-kasir', [\App\Http\Controllers\V2\PosController::class, 'sessions'])->name('pos.sessions');
        Route::post('pos/sesi-kasir/buka', [\App\Http\Controllers\V2\PosController::class, 'sessionOpen'])->name('pos.sessions.open');
        Route::post('pos/sesi-kasir/{session}/tutup', [\App\Http\Controllers\V2\PosController::class, 'sessionClose'])->name('pos.sessions.close');

        // Outlet (Store)
        Route::get('outlet', [\App\Http\Controllers\V2\StoreController::class, 'index'])->name('stores.index');
        Route::get('outlet/baru', [\App\Http\Controllers\V2\StoreController::class, 'create'])->name('stores.create');
        Route::post('outlet', [\App\Http\Controllers\V2\StoreController::class, 'store'])->name('stores.store');
        Route::get('outlet/{store}/edit', [\App\Http\Controllers\V2\StoreController::class, 'edit'])->name('stores.edit');
        Route::put('outlet/{store}', [\App\Http\Controllers\V2\StoreController::class, 'update'])->name('stores.update');
        Route::delete('outlet/{store}', [\App\Http\Controllers\V2\StoreController::class, 'destroy'])->name('stores.destroy');

        // Kontak (Data Master)        Route::get('kontak', [\App\Http\Controllers\V2\ContactController::class, 'index'])->name('contacts');
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

        // Kendaraan (model CustomerVehicle)
        Route::get('kendaraan', [\App\Http\Controllers\V2\VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('kendaraan/baru', [\App\Http\Controllers\V2\VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('kendaraan', [\App\Http\Controllers\V2\VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('kendaraan/{vehicle}/edit', [\App\Http\Controllers\V2\VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('kendaraan/{vehicle}', [\App\Http\Controllers\V2\VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('kendaraan/{vehicle}', [\App\Http\Controllers\V2\VehicleController::class, 'destroy'])->name('vehicles.destroy');

        // Wilayah (Provinsi & Kabupaten/Kota)
        Route::get('wilayah/provinsi', [\App\Http\Controllers\V2\RegionController::class, 'provinces'])->name('regions.provinces');
        Route::get('wilayah/kabupaten-kota', [\App\Http\Controllers\V2\RegionController::class, 'regencies'])->name('regions.regencies');

        // Harta Tetap
        Route::get('harta-tetap', [\App\Http\Controllers\V2\FixedAssetController::class, 'index'])->name('assets.index');
        Route::get('harta-tetap/baru', [\App\Http\Controllers\V2\FixedAssetController::class, 'create'])->name('assets.create');
        Route::post('harta-tetap', [\App\Http\Controllers\V2\FixedAssetController::class, 'store'])->name('assets.store');
        Route::get('harta-tetap/{asset}', [\App\Http\Controllers\V2\FixedAssetController::class, 'show'])->name('assets.show');
        Route::get('harta-tetap/{asset}/edit', [\App\Http\Controllers\V2\FixedAssetController::class, 'edit'])->name('assets.edit');
        Route::put('harta-tetap/{asset}', [\App\Http\Controllers\V2\FixedAssetController::class, 'update'])->name('assets.update');
        Route::post('harta-tetap/{asset}/susut', [\App\Http\Controllers\V2\FixedAssetController::class, 'depreciate'])->name('assets.depreciate');

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

        // Proyek: detail + rincian biaya
        Route::get('proyek/{id}', [\App\Http\Controllers\V2\Master\ProjectController::class, 'show'])->name('projects.show');
        Route::post('proyek/{id}/biaya', [\App\Http\Controllers\V2\Master\ProjectController::class, 'storeCost'])->name('projects.costs.store');
        Route::delete('proyek/{id}/biaya/{cost}', [\App\Http\Controllers\V2\Master\ProjectController::class, 'destroyCost'])->name('projects.costs.destroy');

        Route::get('segera', [\App\Http\Controllers\V2\PageController::class, 'soon'])->name('soon');
    });
});
