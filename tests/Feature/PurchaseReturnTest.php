<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_reduces_stock_inventory_and_payable(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $owner = User::create([
            'name' => 'Owner', 'email' => 'o@test.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $owner->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        // Buy 10 @ 6.000 -> stock 10, inventory 60.000, payable 60.000.
        $purchase = app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 6000]],
        );

        // Return 3 @ 6.000 = 18.000.
        app(PurchaseReturnService::class)->create(
            purchase: $purchase,
            items: [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 6000]],
        );

        $this->assertSame(7, $product->refresh()->stock);

        $ledger = app(LedgerService::class);
        // inventory 60.000 - 18.000 = 42.000
        $this->assertSame(42000.0, $ledger->balance($company->account('inventory')));
        // payable 60.000 - 18.000 = 42.000
        $this->assertSame(42000.0, $ledger->balance($company->account('accounts_payable')));

        $purchase->refresh();
        $this->assertSame(42000.0, (float) $purchase->outstanding_amount);
        $this->assertSame('42000.00', (string) $supplier->refresh()->payable_balance);
    }
}
