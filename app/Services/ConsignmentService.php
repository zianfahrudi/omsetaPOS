<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Consignment (penjualan konsinyasi). Goods are shipped to a consignee but
 * ownership stays with us until sold.
 *
 *   ship:   Dr Persediaan Konsinyasi  Cr Persediaan        (at cost)
 *   settle: Dr Kas/Bank               Cr Penjualan         (proceeds)
 *           Dr HPP                     Cr Persediaan Konsinyasi (cost)
 *   return: Dr Persediaan             Cr Persediaan Konsinyasi (at cost)
 */
class ConsignmentService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int|float, unit_price:float|int}>  $items
     */
    public function ship(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): Consignment {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));
        if ($items === []) {
            throw new InvalidArgumentException('Konsinyasi harus punya minimal 1 item.');
        }

        $consignee = Contact::query()->where('company_id', $company->id)->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Penerima titipan tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $consignee, $items, $date, $notes, $createdBy) {
            $consignment = Consignment::create([
                'company_id' => $company->id,
                'contact_id' => $consignee->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'status' => 'open',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $totalCost = 0.0;

            foreach ($items as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail((int) $line['product_id']);
                if (! $product->tracksStock()) {
                    throw new InvalidArgumentException('Hanya barang berstok yang bisa dikonsinyasikan.');
                }

                $qty = (int) $line['quantity'];
                if ($product->stock < $qty) {
                    throw new InvalidArgumentException("Stok {$product->name} tidak cukup.");
                }

                $unitCost = (float) $product->cost_price;
                $unitPrice = round((float) $line['unit_price'], 2);
                $before = (int) $product->stock;

                $product->decrement('stock', $qty);
                app(WarehouseStockService::class)->adjustDefault($product, -$qty);

                StockMovement::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $createdBy,
                    'type' => 'consignment_out',
                    'quantity' => -$qty,
                    'stock_before' => $before,
                    'stock_after' => $before - $qty,
                    'reference_type' => Consignment::class,
                    'reference_id' => $consignment->id,
                    'notes' => "Kirim konsinyasi {$consignment->number}",
                ]);

                $consignment->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                ]);

                $totalCost += $unitCost * $qty;
            }

            $totalCost = round($totalCost, 2);
            $consignment->forceFill(['total_cost' => $totalCost])->save();

            if ($totalCost > 0) {
                $this->posting->post(
                    company: $company,
                    date: $date,
                    lines: [
                        ['account_id' => $this->account($company, 'consignment_inventory'), 'debit' => $totalCost, 'memo' => 'Barang konsinyasi dikirim'],
                        ['account_id' => $this->account($company, 'inventory'), 'credit' => $totalCost, 'memo' => 'Pengurangan persediaan'],
                    ],
                    type: 'inventory',
                    description: "Kirim konsinyasi {$consignment->number}",
                    reference: $consignment->number,
                    source: $consignment,
                    createdBy: $createdBy,
                );
            }

            ActivityLogger::log('consignment.shipped', "Konsinyasi {$consignment->number}", null, $consignment, ['cost' => $totalCost]);

            return $consignment->load('items', 'consignee');
        });
    }

    /**
     * Record items sold by the consignee. Proceeds received into a cash/bank account.
     *
     * @param  array<int, array{item_id:int, sold_quantity:int|float}>  $lines
     */
    public function settle(Consignment $consignment, array $lines, int $cashAccountId, Carbon|string|null $date = null, ?int $createdBy = null): Consignment
    {
        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($consignment, $lines, $cashAccountId, $date, $createdBy) {
            $consignment = Consignment::query()->with('items')->whereKey($consignment->id)->lockForUpdate()->firstOrFail();
            $company = $consignment->company;

            $revenue = 0.0;
            $cost = 0.0;

            foreach ($lines as $line) {
                $qty = (int) ($line['sold_quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $item = $consignment->items->firstWhere('id', (int) $line['item_id']);
                if (! $item) {
                    throw new InvalidArgumentException('Item konsinyasi tidak ditemukan.');
                }
                if ($qty > $item->remaining()) {
                    throw new InvalidArgumentException("Qty terjual {$item->product_name} melebihi sisa titipan.");
                }

                $item->increment('sold_quantity', $qty);

                $revenue += (float) $item->unit_price * $qty;
                $cost += (float) $item->unit_cost * $qty;

                // Stok jasa: barang sudah keluar saat kirim; di sini hanya pengakuan jual.
                StockMovement::create([
                    'store_id' => $item->product?->store_id,
                    'product_id' => $item->product_id,
                    'user_id' => $createdBy,
                    'type' => 'consignment_sold',
                    'quantity' => 0,
                    'stock_before' => (int) ($item->product?->stock ?? 0),
                    'stock_after' => (int) ($item->product?->stock ?? 0),
                    'reference_type' => Consignment::class,
                    'reference_id' => $consignment->id,
                    'notes' => "Terjual {$qty} via konsinyasi {$consignment->number}",
                ]);
            }

            $revenue = round($revenue, 2);
            $cost = round($cost, 2);

            if ($revenue <= 0) {
                throw new InvalidArgumentException('Tidak ada item terjual.');
            }

            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    ['account_id' => $cashAccountId, 'debit' => $revenue, 'memo' => 'Hasil konsinyasi '.$consignment->number],
                    ['account_id' => $this->account($company, 'sales'), 'credit' => $revenue, 'memo' => 'Penjualan konsinyasi'],
                    ['account_id' => $this->account($company, 'cogs'), 'debit' => $cost, 'memo' => 'HPP konsinyasi'],
                    ['account_id' => $this->account($company, 'consignment_inventory'), 'credit' => $cost, 'memo' => 'Pengurangan persediaan konsinyasi'],
                ],
                type: 'sales',
                description: "Penjualan konsinyasi {$consignment->number}",
                reference: $consignment->number,
                source: $consignment,
                createdBy: $createdBy,
            );

            $consignment->increment('total_sold', $revenue);
            $this->refreshStatus($consignment);

            ActivityLogger::log('consignment.settled', "Settle konsinyasi {$consignment->number}", null, $consignment, ['revenue' => $revenue]);

            return $consignment->fresh(['items', 'consignee']);
        });
    }

    /**
     * Return unsold items from the consignee back into our stock.
     *
     * @param  array<int, array{item_id:int, quantity:int|float}>  $lines
     */
    public function returnItems(Consignment $consignment, array $lines, Carbon|string|null $date = null, ?int $createdBy = null): Consignment
    {
        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($consignment, $lines, $date, $createdBy) {
            $consignment = Consignment::query()->with('items')->whereKey($consignment->id)->lockForUpdate()->firstOrFail();
            $company = $consignment->company;

            $cost = 0.0;

            foreach ($lines as $line) {
                $qty = (int) ($line['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $item = $consignment->items->firstWhere('id', (int) $line['item_id']);
                if (! $item) {
                    throw new InvalidArgumentException('Item konsinyasi tidak ditemukan.');
                }
                if ($qty > $item->remaining()) {
                    throw new InvalidArgumentException("Qty retur {$item->product_name} melebihi sisa titipan.");
                }

                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if ($product) {
                    $before = (int) $product->stock;
                    $product->increment('stock', $qty);
                    app(WarehouseStockService::class)->adjustDefault($product, $qty);

                    StockMovement::create([
                        'store_id' => $product->store_id,
                        'product_id' => $product->id,
                        'user_id' => $createdBy,
                        'type' => 'consignment_return',
                        'quantity' => $qty,
                        'stock_before' => $before,
                        'stock_after' => $before + $qty,
                        'reference_type' => Consignment::class,
                        'reference_id' => $consignment->id,
                        'notes' => "Retur konsinyasi {$consignment->number}",
                    ]);
                }

                $item->increment('returned_quantity', $qty);
                $cost += (float) $item->unit_cost * $qty;
            }

            $cost = round($cost, 2);

            if ($cost > 0) {
                $this->posting->post(
                    company: $company,
                    date: $date,
                    lines: [
                        ['account_id' => $this->account($company, 'inventory'), 'debit' => $cost, 'memo' => 'Barang konsinyasi kembali'],
                        ['account_id' => $this->account($company, 'consignment_inventory'), 'credit' => $cost, 'memo' => 'Pengurangan persediaan konsinyasi'],
                    ],
                    type: 'inventory',
                    description: "Retur konsinyasi {$consignment->number}",
                    reference: $consignment->number,
                    source: $consignment,
                    createdBy: $createdBy,
                );
            }

            $this->refreshStatus($consignment);

            ActivityLogger::log('consignment.returned', "Retur konsinyasi {$consignment->number}", null, $consignment, ['cost' => $cost]);

            return $consignment->fresh(['items', 'consignee']);
        });
    }

    private function refreshStatus(Consignment $consignment): void
    {
        $remaining = $consignment->items->sum(fn (ConsignmentItem $i) => $i->fresh()->remaining());
        $consignment->forceFill(['status' => $remaining <= 0 ? 'closed' : 'open'])->save();
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
        $sequence = Consignment::query()->where('company_id', $company->id)
            ->whereYear('date', $date->year)->whereMonth('date', $date->month)->count() + 1;

        do {
            $number = sprintf('KSN/%s/%04d', $period, $sequence);
            $sequence++;
        } while (Consignment::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
