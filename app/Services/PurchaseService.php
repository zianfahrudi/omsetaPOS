<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a purchase invoice: receives goods (raising stock with weighted-average
 * costing), books the payable, and posts the accounting journal.
 *
 *   Dr Persediaan   (goods at cost)
 *   Dr Beban        (expense/service lines)
 *   Dr PPN Masukan  (input tax)
 *     Cr Hutang Usaha (grand total)
 */
class PurchaseService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, line_type?:string, quantity:int|float, unit_cost:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        ?int $warehouseId = null,
        ?int $storeId = null,
        ?string $supplierInvoiceNo = null,
        Carbon|string|null $dueDate = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): Purchase {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pembelian harus memiliki minimal 1 item.');
        }

        $supplier = Contact::query()
            ->where('company_id', $company->id)
            ->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Supplier tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $supplier, $items, $date, $warehouseId, $storeId, $supplierInvoiceNo, $dueDate, $createdBy) {
            $purchase = Purchase::create([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'warehouse_id' => $warehouseId ?? $company->defaultWarehouse()?->id,
                'store_id' => $storeId,
                'number' => $this->number($company, $date),
                'supplier_invoice_no' => $supplierInvoiceNo,
                'date' => $date,
                'due_date' => $dueDate ? Carbon::parse($dueDate) : null,
                'status' => 'posted',
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            $goodsTotal = 0.0;
            $expenseTotal = 0.0;
            $taxTotal = 0.0;

            foreach ($items as $line) {
                $product = isset($line['product_id']) && $line['product_id']
                    ? Product::find($line['product_id'])
                    : null;

                $quantity = (int) $line['quantity'];
                $unitCost = round((float) $line['unit_cost'], 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitCost * $quantity, 2);

                $isGoods = $product && $product->tracksStock()
                    && ($line['line_type'] ?? 'goods') !== 'expense';

                $purchase->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'line_type' => $isGoods ? 'goods' : 'expense',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $taxTotal += $taxAmount;

                if ($isGoods) {
                    $goodsTotal += $lineTotal;
                    $this->receiveStock($product, $quantity, $unitCost, $purchase, $createdBy);
                } else {
                    $expenseTotal += $lineTotal;
                }
            }

            $subtotal = round($goodsTotal + $expenseTotal, 2);
            $grandTotal = round($subtotal + $taxTotal, 2);

            $purchase->forceFill([
                'subtotal' => $subtotal,
                'tax_total' => round($taxTotal, 2),
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'outstanding_amount' => $grandTotal,
            ])->save();

            $supplier->increment('payable_balance', $grandTotal);

            $this->postJournal($company, $purchase, round($goodsTotal, 2), round($expenseTotal, 2), round($taxTotal, 2));

            ActivityLogger::log('purchase.created', "Faktur pembelian {$purchase->number}", $storeId, $purchase, [
                'supplier' => $supplier->name,
                'grand_total' => $grandTotal,
            ]);

            return $purchase->load('items', 'supplier');
        });
    }

    private function receiveStock(Product $product, int $quantity, float $unitCost, Purchase $purchase, ?int $userId): void
    {
        $oldStock = (int) $product->stock;
        $oldCost = (float) $product->cost_price;
        $newStock = $oldStock + $quantity;

        // Weighted-average cost.
        $newCost = $newStock > 0
            ? round((($oldStock * $oldCost) + ($quantity * $unitCost)) / $newStock, 2)
            : $unitCost;

        $product->forceFill([
            'stock' => $newStock,
            'cost_price' => $newCost,
        ])->save();

        app(WarehouseStockService::class)->adjustDefault($product, $quantity);

        StockMovement::create([
            'store_id' => $purchase->store_id ?? $product->store_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => 'purchase',
            'quantity' => $quantity,
            'stock_before' => $oldStock,
            'stock_after' => $newStock,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'notes' => "Penerimaan pembelian {$purchase->number}",
        ]);
    }

    private function postJournal(Company $company, Purchase $purchase, float $goods, float $expense, float $tax): void
    {
        $lines = [];

        if ($goods > 0) {
            $lines[] = ['account_id' => $this->account($company, 'inventory'), 'debit' => $goods, 'memo' => 'Pembelian persediaan'];
        }

        if ($expense > 0) {
            $lines[] = ['account_id' => $this->account($company, 'operating_expense'), 'debit' => $expense, 'memo' => 'Pembelian beban'];
        }

        if ($tax > 0) {
            $lines[] = ['account_id' => $this->account($company, 'tax_input'), 'debit' => $tax, 'memo' => 'PPN Masukan'];
        }

        $lines[] = [
            'account_id' => $this->account($company, 'accounts_payable'),
            'credit' => (float) $purchase->grand_total,
            'contact_id' => $purchase->contact_id,
            'memo' => 'Hutang pembelian',
        ];

        $this->posting->post(
            company: $company,
            date: $purchase->date,
            lines: $lines,
            type: 'purchase',
            description: "Pembelian {$purchase->number}",
            reference: $purchase->number,
            source: $purchase,
            createdBy: $purchase->created_by,
        );
    }

    private function account(Company $company, string $subtype): int
    {
        $account = $company->account($subtype);

        if (! $account) {
            throw new InvalidArgumentException("Akun sistem '{$subtype}' belum dikonfigurasi.");
        }

        return $account->id;
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = Purchase::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('PB/%s/%04d', $period, $sequence);
            $sequence++;
        } while (Purchase::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
