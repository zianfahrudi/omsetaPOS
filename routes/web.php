<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\V2\AccountController;
use App\Http\Controllers\V2\ArisanController;
use App\Http\Controllers\V2\AssemblyController;
use App\Http\Controllers\V2\AuthController;
use App\Http\Controllers\V2\BankReconciliationController;
use App\Http\Controllers\V2\CashController;
use App\Http\Controllers\V2\ConsignmentController;
use App\Http\Controllers\V2\ContactController;
use App\Http\Controllers\V2\CustomerController;
use App\Http\Controllers\V2\DashboardController;
use App\Http\Controllers\V2\FixedAssetController;
use App\Http\Controllers\V2\GiroController;
use App\Http\Controllers\V2\InventoryController;
use App\Http\Controllers\V2\JournalController;
use App\Http\Controllers\V2\LedgerController;
use App\Http\Controllers\V2\Master\CategoryController;
use App\Http\Controllers\V2\Master\CurrencyController;
use App\Http\Controllers\V2\Master\DepartmentController;
use App\Http\Controllers\V2\Master\FeatureSettingController;
use App\Http\Controllers\V2\Master\InvoiceSettingController;
use App\Http\Controllers\V2\Master\MaterialCategoryController;
use App\Http\Controllers\V2\Master\MaterialController;
use App\Http\Controllers\V2\Master\PositionController;
use App\Http\Controllers\V2\Master\ProjectController;
use App\Http\Controllers\V2\Master\ProjectSettingController;
use App\Http\Controllers\V2\Master\TaxController;
use App\Http\Controllers\V2\Master\UnitController;
use App\Http\Controllers\V2\Master\WarehouseController;
use App\Http\Controllers\V2\MaterialPurchaseController;
use App\Http\Controllers\V2\PageController;
use App\Http\Controllers\V2\Payroll\AttendanceController;
use App\Http\Controllers\V2\Payroll\AttendanceLocationController;
use App\Http\Controllers\V2\Payroll\EmployeeComponentController;
use App\Http\Controllers\V2\Payroll\EmployeeController;
use App\Http\Controllers\V2\Payroll\PayrollController;
use App\Http\Controllers\V2\Payroll\PayrollDashboardController;
use App\Http\Controllers\V2\Payroll\PayrollRecapController;
use App\Http\Controllers\V2\Payroll\ScheduleController;
use App\Http\Controllers\V2\Payroll\ShiftController;
use App\Http\Controllers\V2\PosController;
use App\Http\Controllers\V2\ProductController;
use App\Http\Controllers\V2\ProfitSharingController;
use App\Http\Controllers\V2\PurchaseController;
use App\Http\Controllers\V2\RegionController;
use App\Http\Controllers\V2\ReportController;
use App\Http\Controllers\V2\SalesController;
use App\Http\Controllers\V2\StoreController;
use App\Http\Controllers\V2\UserController;
use App\Http\Controllers\V2\VehicleController;
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
    Route::get('/employees', [CashierController::class, 'employees'])->middleware('auth')->name('employees');
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
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    Route::middleware(['auth', 'restrict.modules'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('produk', [ProductController::class, 'index'])->name('products.index');
        Route::get('produk/baru', [ProductController::class, 'create'])->name('products.create');
        Route::post('produk', [ProductController::class, 'store'])->name('products.store');
        Route::get('produk/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('produk/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('produk/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('laporan/neraca', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('laporan/laba-rugi', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('laporan/arus-kas', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
        Route::get('laporan/rekap-kas', [ReportController::class, 'cashWeekly'])->name('reports.cash-weekly');
        Route::get('laporan/penjualan', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('laporan/pembelian', [ReportController::class, 'purchases'])->name('reports.purchases');
        Route::get('laporan/persediaan', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('laporan/pajak', [ReportController::class, 'tax'])->name('reports.tax');
        Route::get('laporan/neraca-saldo', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('laporan/stok-gudang', [ReportController::class, 'warehouseStock'])->name('reports.warehouse-stock');
        Route::get('laporan/performa-mekanik', [ReportController::class, 'mechanicPerformance'])->name('reports.mechanic-performance');

        Route::get('akuntansi/akun', [AccountController::class, 'index'])->name('accounting.accounts');
        Route::get('akuntansi/akun/baru', [AccountController::class, 'create'])->name('accounting.accounts.create');
        Route::post('akuntansi/akun', [AccountController::class, 'store'])->name('accounting.accounts.store');
        Route::get('akuntansi/akun/{id}/edit', [AccountController::class, 'edit'])->name('accounting.accounts.edit');
        Route::put('akuntansi/akun/{id}', [AccountController::class, 'update'])->name('accounting.accounts.update');
        Route::delete('akuntansi/akun/{id}', [AccountController::class, 'destroy'])->name('accounting.accounts.destroy');
        Route::get('akuntansi/buku-besar', [LedgerController::class, 'index'])->name('accounting.ledger');
        Route::get('akuntansi/jurnal', [JournalController::class, 'index'])->name('accounting.journals');
        Route::get('akuntansi/jurnal/baru', [JournalController::class, 'create'])->name('accounting.journals.create');
        Route::post('akuntansi/jurnal', [JournalController::class, 'store'])->name('accounting.journals.store');
        Route::get('akuntansi/jurnal/{journal}/edit', [JournalController::class, 'edit'])->name('accounting.journals.edit');
        Route::put('akuntansi/jurnal/{journal}', [JournalController::class, 'update'])->name('accounting.journals.update');
        Route::delete('akuntansi/jurnal/{journal}', [JournalController::class, 'destroy'])->name('accounting.journals.destroy');
        Route::get('akuntansi/jurnal/{journal}', [JournalController::class, 'show'])->name('accounting.journals.show');

        // Penjualan
        Route::get('penjualan/penawaran', [SalesController::class, 'quotations'])->name('sales.quotations');
        Route::get('penjualan/penawaran/baru', [SalesController::class, 'quotationCreate'])->name('sales.quotations.create');
        Route::post('penjualan/penawaran', [SalesController::class, 'quotationStore'])->name('sales.quotations.store');
        Route::post('penjualan/penawaran/{quotation}/konversi', [SalesController::class, 'quotationConvert'])->name('sales.quotations.convert');

        Route::get('penjualan/pesanan', [SalesController::class, 'orders'])->name('sales.orders');
        Route::get('penjualan/pesanan/baru', [SalesController::class, 'orderCreate'])->name('sales.orders.create');
        Route::post('penjualan/pesanan', [SalesController::class, 'orderStore'])->name('sales.orders.store');
        Route::post('penjualan/pesanan/{order}/konversi', [SalesController::class, 'orderConvert'])->name('sales.orders.convert');

        Route::get('penjualan/faktur', [SalesController::class, 'invoices'])->name('sales.invoices');
        Route::get('penjualan/faktur/baru', [SalesController::class, 'invoiceCreate'])->name('sales.invoices.create');
        Route::post('penjualan/faktur', [SalesController::class, 'invoiceStore'])->name('sales.invoices.store');
        Route::get('penjualan/faktur/{invoice}', [SalesController::class, 'invoiceShow'])->name('sales.invoices.show');
        Route::get('penjualan/faktur/{invoice}/cetak', [SalesController::class, 'invoicePrint'])->name('sales.invoices.print');
        Route::get('penjualan/faktur/{invoice}/bayar', [SalesController::class, 'paymentCreate'])->name('sales.invoices.payment');
        Route::post('penjualan/faktur/{invoice}/bayar', [SalesController::class, 'paymentStore'])->name('sales.invoices.payment.store');
        Route::get('penjualan/faktur/{invoice}/retur', [SalesController::class, 'returnCreate'])->name('sales.invoices.return');
        Route::post('penjualan/faktur/{invoice}/retur', [SalesController::class, 'returnStore'])->name('sales.invoices.return.store');

        Route::get('penjualan/piutang', [SalesController::class, 'receivables'])->name('sales.receivables');

        // Pembelian
        Route::get('pembelian/permintaan', [PurchaseController::class, 'requests'])->name('purchase.requests');
        Route::get('pembelian/permintaan/baru', [PurchaseController::class, 'requestCreate'])->name('purchase.requests.create');
        Route::post('pembelian/permintaan', [PurchaseController::class, 'requestStore'])->name('purchase.requests.store');
        Route::post('pembelian/permintaan/{purchaseRequest}/konversi', [PurchaseController::class, 'requestConvert'])->name('purchase.requests.convert');

        Route::get('pembelian/pesanan', [PurchaseController::class, 'orders'])->name('purchase.orders');
        Route::get('pembelian/pesanan/baru', [PurchaseController::class, 'orderCreate'])->name('purchase.orders.create');
        Route::post('pembelian/pesanan', [PurchaseController::class, 'orderStore'])->name('purchase.orders.store');
        Route::post('pembelian/pesanan/{purchaseOrder}/konversi', [PurchaseController::class, 'orderConvert'])->name('purchase.orders.convert');

        Route::get('pembelian/faktur', [PurchaseController::class, 'invoices'])->name('purchase.invoices');
        Route::get('pembelian/faktur/baru', [PurchaseController::class, 'invoiceCreate'])->name('purchase.invoices.create');
        Route::post('pembelian/faktur', [PurchaseController::class, 'invoiceStore'])->name('purchase.invoices.store');
        Route::get('pembelian/faktur/{invoice}', [PurchaseController::class, 'invoiceShow'])->name('purchase.invoices.show');
        Route::get('pembelian/faktur/{invoice}/cetak', [PurchaseController::class, 'invoicePrint'])->name('purchase.invoices.print');
        Route::get('pembelian/faktur/{invoice}/bayar', [PurchaseController::class, 'paymentCreate'])->name('purchase.invoices.payment');
        Route::post('pembelian/faktur/{invoice}/bayar', [PurchaseController::class, 'paymentStore'])->name('purchase.invoices.payment.store');
        Route::get('pembelian/faktur/{invoice}/retur', [PurchaseController::class, 'returnCreate'])->name('purchase.invoices.return');
        Route::post('pembelian/faktur/{invoice}/retur', [PurchaseController::class, 'returnStore'])->name('purchase.invoices.return.store');

        Route::get('pembelian/hutang', [PurchaseController::class, 'payables'])->name('purchase.payables');

        // Belanja Bahan (pembelian material)
        Route::get('pembelian/bahan', [MaterialPurchaseController::class, 'index'])->name('purchase.materials');
        Route::get('pembelian/bahan/baru', [MaterialPurchaseController::class, 'create'])->name('purchase.materials.create');
        Route::get('pembelian/bahan/kartu-stok', [MaterialPurchaseController::class, 'stockCard'])->name('purchase.materials.stock-card');
        Route::post('pembelian/bahan', [MaterialPurchaseController::class, 'store'])->name('purchase.materials.store');
        Route::get('pembelian/bahan/{id}', [MaterialPurchaseController::class, 'show'])->name('purchase.materials.show');

        // Persediaan
        Route::get('persediaan/penyesuaian', [InventoryController::class, 'adjustments'])->name('inventory.adjustments');
        Route::get('persediaan/penyesuaian/baru', [InventoryController::class, 'adjustmentCreate'])->name('inventory.adjustments.create');
        Route::post('persediaan/penyesuaian', [InventoryController::class, 'adjustmentStore'])->name('inventory.adjustments.store');
        Route::get('persediaan/pemindahan', [InventoryController::class, 'transfers'])->name('inventory.transfers');
        Route::get('persediaan/pemindahan/baru', [InventoryController::class, 'transferCreate'])->name('inventory.transfers.create');
        Route::post('persediaan/pemindahan', [InventoryController::class, 'transferStore'])->name('inventory.transfers.store');
        Route::get('persediaan/kartu-stok', [InventoryController::class, 'stockCard'])->name('inventory.stock-card');
        Route::get('persediaan/perakitan', [AssemblyController::class, 'index'])->name('inventory.assemblies');
        Route::get('persediaan/perakitan/baru', [AssemblyController::class, 'create'])->name('inventory.assemblies.create');
        Route::post('persediaan/perakitan', [AssemblyController::class, 'store'])->name('inventory.assemblies.store');
        Route::get('persediaan/perakitan/{assembly}', [AssemblyController::class, 'show'])->name('inventory.assemblies.show');
        Route::post('persediaan/perakitan/{assembly}/selesai', [AssemblyController::class, 'complete'])->name('inventory.assemblies.complete');
        Route::post('persediaan/perakitan/{assembly}/batal', [AssemblyController::class, 'cancel'])->name('inventory.assemblies.cancel');
        // Konsinyasi
        Route::get('persediaan/konsinyasi', [ConsignmentController::class, 'index'])->name('inventory.consignments');
        Route::get('persediaan/konsinyasi/baru', [ConsignmentController::class, 'create'])->name('inventory.consignments.create');
        Route::post('persediaan/konsinyasi', [ConsignmentController::class, 'store'])->name('inventory.consignments.store');
        Route::get('persediaan/konsinyasi/{consignment}', [ConsignmentController::class, 'show'])->name('inventory.consignments.show');
        Route::post('persediaan/konsinyasi/{consignment}/settle', [ConsignmentController::class, 'settle'])->name('inventory.consignments.settle');
        Route::post('persediaan/konsinyasi/{consignment}/retur', [ConsignmentController::class, 'returnItems'])->name('inventory.consignments.return');

        // Kas & Bank
        Route::get('kas/transaksi', [CashController::class, 'transactions'])->name('cash.transactions');
        Route::get('kas/transaksi/baru', [CashController::class, 'create'])->name('cash.transactions.create');
        Route::post('kas/transaksi', [CashController::class, 'store'])->name('cash.transactions.store');

        // Giro
        Route::get('kas/giro', [GiroController::class, 'index'])->name('cash.giros');
        Route::get('kas/giro/baru', [GiroController::class, 'create'])->name('cash.giros.create');
        Route::post('kas/giro', [GiroController::class, 'store'])->name('cash.giros.store');
        Route::post('kas/giro/{giro}/setor', [GiroController::class, 'deposit'])->name('cash.giros.deposit');
        Route::get('kas/giro/{giro}/cair', [GiroController::class, 'clearForm'])->name('cash.giros.clear');
        Route::post('kas/giro/{giro}/cair', [GiroController::class, 'clear'])->name('cash.giros.clear.store');
        Route::post('kas/giro/{giro}/tolak', [GiroController::class, 'reject'])->name('cash.giros.reject');

        // Rekonsiliasi Bank
        Route::get('kas/rekonsiliasi', [BankReconciliationController::class, 'index'])->name('cash.reconciliations');
        Route::get('kas/rekonsiliasi/baru', [BankReconciliationController::class, 'create'])->name('cash.reconciliations.create');
        Route::post('kas/rekonsiliasi', [BankReconciliationController::class, 'store'])->name('cash.reconciliations.store');
        Route::get('kas/rekonsiliasi/{reconciliation}', [BankReconciliationController::class, 'show'])->name('cash.reconciliations.show');

        // Bagi Hasil Laba (profit sharing) + jurnal
        Route::get('akuntansi/bagi-hasil', [ProfitSharingController::class, 'index'])->name('profit-sharing.index');
        Route::get('akuntansi/bagi-hasil/baru', [ProfitSharingController::class, 'create'])->name('profit-sharing.create');
        Route::post('akuntansi/bagi-hasil', [ProfitSharingController::class, 'store'])->name('profit-sharing.store');
        Route::get('akuntansi/bagi-hasil/{id}', [ProfitSharingController::class, 'show'])->name('profit-sharing.show');
        Route::delete('akuntansi/bagi-hasil/{id}', [ProfitSharingController::class, 'destroy'])->name('profit-sharing.destroy');

        // Point of Sale
        Route::get('pos/transaksi', [PosController::class, 'transactions'])->name('pos.transactions');
        Route::get('pos/transaksi/{sale}', [PosController::class, 'transactionShow'])->name('pos.transactions.show');
        Route::get('pos/transaksi/{sale}/struk', [PosController::class, 'receipt'])->name('pos.transactions.receipt');
        Route::post('pos/transaksi/{sale}/void', [PosController::class, 'void'])->name('pos.transactions.void');
        Route::get('pos/sesi-kasir', [PosController::class, 'sessions'])->name('pos.sessions');
        Route::post('pos/sesi-kasir/buka', [PosController::class, 'sessionOpen'])->name('pos.sessions.open');
        Route::post('pos/sesi-kasir/{session}/tutup', [PosController::class, 'sessionClose'])->name('pos.sessions.close');

        // Outlet (Store)
        Route::get('outlet', [StoreController::class, 'index'])->name('stores.index');
        Route::post('outlet/pilih', [StoreController::class, 'switch'])->name('stores.switch');
        Route::get('outlet/baru', [StoreController::class, 'create'])->name('stores.create');
        Route::post('outlet', [StoreController::class, 'store'])->name('stores.store');
        Route::get('outlet/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('outlet/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('outlet/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');

        // Kontak (Data Master)
        Route::get('kontak', [ContactController::class, 'index'])->name('contacts');
        Route::get('kontak/baru', [ContactController::class, 'create'])->name('contacts.create');
        Route::post('kontak', [ContactController::class, 'store'])->name('contacts.store');
        Route::get('kontak/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('kontak/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('kontak/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        // Pelanggan POS (model Customer, dipakai Kasir)
        Route::get('pelanggan', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('pelanggan/baru', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('pelanggan', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('pelanggan/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('pelanggan/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('pelanggan/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Kendaraan (model CustomerVehicle)
        Route::get('kendaraan', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('kendaraan/baru', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('kendaraan', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('kendaraan/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('kendaraan/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('kendaraan/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        // Wilayah (Provinsi & Kabupaten/Kota)
        Route::get('wilayah/provinsi', [RegionController::class, 'provinces'])->name('regions.provinces');
        Route::get('wilayah/kabupaten-kota', [RegionController::class, 'regencies'])->name('regions.regencies');
        Route::get('wilayah/kecamatan', [RegionController::class, 'districts'])->name('regions.districts');

        // Harta Tetap
        Route::get('harta-tetap', [FixedAssetController::class, 'index'])->name('assets.index');
        Route::get('harta-tetap/baru', [FixedAssetController::class, 'create'])->name('assets.create');
        Route::post('harta-tetap', [FixedAssetController::class, 'store'])->name('assets.store');
        Route::get('harta-tetap/{asset}', [FixedAssetController::class, 'show'])->name('assets.show');
        Route::get('harta-tetap/{asset}/edit', [FixedAssetController::class, 'edit'])->name('assets.edit');
        Route::put('harta-tetap/{asset}', [FixedAssetController::class, 'update'])->name('assets.update');
        Route::post('harta-tetap/{asset}/susut', [FixedAssetController::class, 'depreciate'])->name('assets.depreciate');

        // Data Master sederhana (Satuan, Gudang, Departemen, Proyek, Mata Uang, Pajak)
        foreach ([
            'satuan' => ['units', UnitController::class],
            'gudang' => ['warehouses', WarehouseController::class],
            'departemen' => ['departments', DepartmentController::class],
            'proyek' => ['projects', ProjectController::class],
            'material' => ['materials', MaterialController::class],
            'kategori-material' => ['material-categories', MaterialCategoryController::class],
            'kategori-produk' => ['categories', CategoryController::class],
            'jabatan' => ['positions', PositionController::class],
            'mata-uang' => ['currencies', CurrencyController::class],
            'pajak' => ['taxes', TaxController::class],
        ] as $slug => [$name, $controller]) {
            Route::get($slug, [$controller, 'index'])->name("{$name}.index");
            Route::get("{$slug}/baru", [$controller, 'create'])->name("{$name}.create");
            Route::post($slug, [$controller, 'store'])->name("{$name}.store");
            Route::get("{$slug}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
            Route::put("{$slug}/{id}", [$controller, 'update'])->name("{$name}.update");
            Route::delete("{$slug}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
        }

        // Proyek: detail + rincian biaya
        Route::get('proyek/{id}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('proyek/{id}/cetak', [ProjectController::class, 'print'])->name('projects.print');
        Route::get('proyek/{id}/faktur', [ProjectController::class, 'invoice'])->name('projects.invoice');
        Route::get('proyek/{id}/export/excel', [ProjectController::class, 'exportExcel'])->name('projects.export.excel');
        Route::get('proyek/{id}/export/word', [ProjectController::class, 'exportWord'])->name('projects.export.word');
        Route::post('proyek/{id}/biaya', [ProjectController::class, 'storeCost'])->name('projects.costs.store');
        Route::put('proyek/{id}/biaya/{cost}', [ProjectController::class, 'updateCost'])->name('projects.costs.update');
        Route::delete('proyek/{id}/biaya/{cost}', [ProjectController::class, 'destroyCost'])->name('projects.costs.destroy');
        Route::post('proyek/{id}/penawaran', [ProjectController::class, 'updatePenawaran'])->name('projects.penawaran.update');
        Route::post('proyek/{id}/setujui', [ProjectController::class, 'approve'])->name('projects.approve');
        Route::post('proyek/{id}/status', [ProjectController::class, 'updateStatus'])->name('projects.status.update');
        Route::post('proyek/{id}/dp', [ProjectController::class, 'updateDownPayment'])->name('projects.dp.update');
        // Realisasi biaya (anggaran vs aktual)
        Route::post('proyek/{id}/realisasi', [ProjectController::class, 'storeExpense'])->name('projects.expenses.store');
        Route::delete('proyek/{id}/realisasi/{expense}', [ProjectController::class, 'destroyExpense'])->name('projects.expenses.destroy');
        // Termin pembayaran
        Route::post('proyek/{id}/termin', [ProjectController::class, 'storeTerm'])->name('projects.terms.store');
        Route::post('proyek/{id}/termin/{term}/bayar', [ProjectController::class, 'payTerm'])->name('projects.terms.pay');
        Route::delete('proyek/{id}/termin/{term}', [ProjectController::class, 'destroyTerm'])->name('projects.terms.destroy');

        // Pengaturan default penawaran proyek (overhead & profit global)
        Route::get('pengaturan/proyek', [ProjectSettingController::class, 'edit'])->name('settings.project');
        Route::put('pengaturan/proyek', [ProjectSettingController::class, 'update'])->name('settings.project.update');

        // Pengaturan faktur (prefix, jatuh tempo, rekening, catatan)
        Route::get('pengaturan/faktur', [InvoiceSettingController::class, 'edit'])->name('settings.invoice');
        Route::put('pengaturan/faktur', [InvoiceSettingController::class, 'update'])->name('settings.invoice.update');

        // Pengaturan modul & fitur (on/off) — superuser
        Route::get('pengaturan/modul', [FeatureSettingController::class, 'edit'])->name('settings.features');
        Route::put('pengaturan/modul', [FeatureSettingController::class, 'update'])->name('settings.features.update');

        // Manajemen pengguna (admin & kasir) — superuser
        Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('pengguna/baru', [UserController::class, 'create'])->name('users.create');
        Route::post('pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('pengguna/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // ═══════════ Absensi & Payroll ═══════════
        Route::get('payroll', [PayrollDashboardController::class, 'index'])->name('payroll.dashboard');

        // Karyawan
        Route::get('karyawan', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('karyawan/baru', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('karyawan', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('karyawan/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('karyawan/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('karyawan/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('karyawan/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        // Komponen karyawan (bonus, kasbon, arisan, tabungan)
        Route::post('karyawan/{employee}/bonus', [EmployeeComponentController::class, 'storeBonus'])->name('employees.bonus.store');
        Route::delete('karyawan/{employee}/bonus/{bonus}', [EmployeeComponentController::class, 'destroyBonus'])->name('employees.bonus.destroy');
        Route::post('karyawan/{employee}/kasbon', [EmployeeComponentController::class, 'storeLoan'])->name('employees.loan.store');
        Route::post('karyawan/{employee}/kasbon/{loan}/status', [EmployeeComponentController::class, 'updateLoan'])->name('employees.loan.update');
        Route::delete('karyawan/{employee}/kasbon/{loan}', [EmployeeComponentController::class, 'destroyLoan'])->name('employees.loan.destroy');
        Route::post('karyawan/{employee}/kasbon/{loan}/cicilan', [EmployeeComponentController::class, 'storeRepayment'])->name('employees.loan.repayment.store');
        Route::delete('karyawan/{employee}/kasbon/{loan}/cicilan/{repayment}', [EmployeeComponentController::class, 'destroyRepayment'])->name('employees.loan.repayment.destroy');
        Route::post('karyawan/{employee}/potongan', [EmployeeComponentController::class, 'storeDeduction'])->name('employees.deduction.store');
        Route::delete('karyawan/{employee}/potongan/{deduction}', [EmployeeComponentController::class, 'destroyDeduction'])->name('employees.deduction.destroy');
        Route::post('karyawan/{employee}/borongan', [EmployeeComponentController::class, 'storeWorkItem'])->name('employees.workitem.store');
        Route::delete('karyawan/{employee}/borongan/{workItem}', [EmployeeComponentController::class, 'destroyWorkItem'])->name('employees.workitem.destroy');
        Route::post('karyawan/{employee}/tabungan', [EmployeeComponentController::class, 'saveSaving'])->name('employees.saving.save');
        Route::post('karyawan/{employee}/tabungan/transaksi', [EmployeeComponentController::class, 'storeSavingEntry'])->name('employees.saving.entry.store');
        Route::delete('karyawan/{employee}/tabungan/transaksi/{entry}', [EmployeeComponentController::class, 'destroySavingEntry'])->name('employees.saving.entry.destroy');

        // Titik Lokasi Presensi (geofence untuk presensi mobile)
        Route::get('lokasi-presensi', [AttendanceLocationController::class, 'index'])->name('attendance-locations.index');
        Route::get('lokasi-presensi/baru', [AttendanceLocationController::class, 'create'])->name('attendance-locations.create');
        Route::post('lokasi-presensi', [AttendanceLocationController::class, 'store'])->name('attendance-locations.store');
        Route::get('lokasi-presensi/{id}/edit', [AttendanceLocationController::class, 'edit'])->name('attendance-locations.edit');
        Route::put('lokasi-presensi/{id}', [AttendanceLocationController::class, 'update'])->name('attendance-locations.update');
        Route::delete('lokasi-presensi/{id}', [AttendanceLocationController::class, 'destroy'])->name('attendance-locations.destroy');

        // Shift
        Route::get('shift', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('shift/baru', [ShiftController::class, 'create'])->name('shifts.create');
        Route::post('shift', [ShiftController::class, 'store'])->name('shifts.store');
        Route::get('shift/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
        Route::put('shift/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('shift/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

        // Jadwal Shift
        Route::get('jadwal-shift', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('jadwal-shift', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('jadwal-shift/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Absensi
        Route::get('absensi', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('absensi/mingguan', [AttendanceController::class, 'weekly'])->name('attendances.weekly');
        Route::post('absensi/mingguan', [AttendanceController::class, 'weeklySave'])->name('attendances.weekly.save');
        Route::post('absensi', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::post('absensi/dari-jadwal', [AttendanceController::class, 'generateFromSchedule'])->name('attendances.from-schedule');
        Route::post('absensi/{attendance}/checkin', [AttendanceController::class, 'checkIn'])->name('attendances.checkin');
        Route::post('absensi/{attendance}/checkout', [AttendanceController::class, 'checkOut'])->name('attendances.checkout');
        Route::put('absensi/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');
        Route::delete('absensi/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

        // Payroll
        Route::get('payroll/list', [PayrollController::class, 'index'])->name('payrolls.index');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
        Route::post('payroll/bulk/approve', [PayrollController::class, 'bulkApprove'])->name('payrolls.bulk.approve');
        Route::post('payroll/bulk/bayar', [PayrollController::class, 'bulkPay'])->name('payrolls.bulk.pay');
        Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payrolls.show');
        Route::get('payroll/{payroll}/slip/cetak', [PayrollController::class, 'slipPrint'])->name('payrolls.slip.print');
        Route::post('payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve');
        Route::post('payroll/{payroll}/paid', [PayrollController::class, 'markPaid'])->name('payrolls.paid');
        Route::post('payroll/{payroll}/sisa-gaji', [PayrollController::class, 'updateCarryOver'])->name('payrolls.carryover');
        Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');

        // Rekap bulanan: gaji & bon (kasbon) dipisah
        Route::get('payroll/rekap/gaji', [PayrollRecapController::class, 'salary'])->name('payrolls.recap.salary');
        Route::get('payroll/rekap/gaji/cetak', [PayrollRecapController::class, 'salaryPrint'])->name('payrolls.recap.salary.print');
        Route::get('payroll/rekap/bon', [PayrollRecapController::class, 'loan'])->name('payrolls.recap.loan');
        Route::get('payroll/rekap/bon/cetak', [PayrollRecapController::class, 'loanPrint'])->name('payrolls.recap.loan.print');

        // ═══════════ Arisan Karyawan ═══════════
        Route::get('arisan', [ArisanController::class, 'dashboard'])->name('arisan.dashboard');
        Route::get('arisan/kelompok', [ArisanController::class, 'index'])->name('arisan.index');
        Route::get('arisan/kelompok/baru', [ArisanController::class, 'create'])->name('arisan.create');
        Route::post('arisan/kelompok', [ArisanController::class, 'store'])->name('arisan.store');
        Route::get('arisan/kelompok/{id}', [ArisanController::class, 'show'])->name('arisan.show');
        Route::get('arisan/kelompok/{id}/edit', [ArisanController::class, 'edit'])->name('arisan.edit');
        Route::put('arisan/kelompok/{id}', [ArisanController::class, 'update'])->name('arisan.update');
        Route::delete('arisan/kelompok/{id}', [ArisanController::class, 'destroy'])->name('arisan.destroy');
        Route::post('arisan/kelompok/{id}/anggota', [ArisanController::class, 'addMember'])->name('arisan.members.add');
        Route::delete('arisan/kelompok/{id}/anggota/{member}', [ArisanController::class, 'removeMember'])->name('arisan.members.remove');
        Route::post('arisan/kelompok/{id}/periode', [ArisanController::class, 'openPeriod'])->name('arisan.periods.open');
        Route::post('arisan/kelompok/{id}/periode/{period}/collect', [ArisanController::class, 'collectPeriod'])->name('arisan.periods.collect');
        Route::post('arisan/kelompok/{id}/periode/{period}/undi', [ArisanController::class, 'drawWinner'])->name('arisan.periods.draw');

        Route::get('segera', [PageController::class, 'soon'])->name('soon');
    });
});
