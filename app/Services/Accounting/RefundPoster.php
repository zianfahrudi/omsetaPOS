<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SaleItem;

/**
 * Posts the accounting journal for a refund / exchange.
 *
 *   Dr Retur Penjualan (returned at sale price)
 *     Cr Penjualan       (replacement at sale price)
 *   Dr Kas (additional payment in)  /  Cr Kas (refund out)
 *
 * Inventory (at cost):
 *   returned goods:    Dr Persediaan / Cr HPP
 *   replacement goods: Dr HPP        / Cr Persediaan
 */
class RefundPoster
{
    public function __construct(private readonly PostingService $posting) {}

    public function post(Refund $refund): ?Journal
    {
        $company = $refund->loadMissing('store')->store?->company;

        if (! $company || $this->alreadyPosted($refund)) {
            return null;
        }

        $refund->loadMissing('items');

        $returnedTotal = (float) $refund->returned_total;
        $replacementTotal = (float) $refund->replacement_total;
        $refundAmount = (float) $refund->refund_amount;
        $additional = (float) $refund->additional_payment_amount;

        $cash = $this->account($company, 'cash');
        $lines = [];

        if ($returnedTotal > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'sales_return')->id,
                'debit' => $returnedTotal,
                'store_id' => $refund->store_id,
                'memo' => 'Retur penjualan',
            ];
        }

        if ($replacementTotal > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'sales')->id,
                'credit' => $replacementTotal,
                'store_id' => $refund->store_id,
                'memo' => 'Penjualan barang pengganti',
            ];
        }

        if ($refundAmount > 0) {
            $lines[] = [
                'account_id' => $cash->id,
                'credit' => $refundAmount,
                'store_id' => $refund->store_id,
                'memo' => 'Pengembalian dana ke pelanggan',
            ];
        }

        if ($additional > 0) {
            $lines[] = [
                'account_id' => $cash->id,
                'debit' => $additional,
                'store_id' => $refund->store_id,
                'memo' => 'Tambahan bayar pelanggan',
            ];
        }

        $this->appendInventoryLines($company, $refund, $lines);

        if ($lines === []) {
            return null;
        }

        return $this->posting->post(
            company: $company,
            date: $refund->created_at->toDateString(),
            lines: $lines,
            type: 'sales',
            description: "Refund {$refund->number}",
            reference: $refund->number,
            source: $refund,
            createdBy: $refund->handled_by_id,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function appendInventoryLines(Company $company, Refund $refund, array &$lines): void
    {
        $inventory = $this->account($company, 'inventory');
        $cogs = $this->account($company, 'cogs');

        $returnedCost = 0.0;
        $replacementCost = 0.0;

        foreach ($refund->items as $item) {
            if ($item->direction === 'returned' && $item->sale_item_id) {
                $saleItem = SaleItem::find($item->sale_item_id);
                if ($saleItem && $saleItem->product_type !== 'service') {
                    $returnedCost += (float) $saleItem->cost_price * (int) $item->quantity;
                }
            }

            if ($item->direction === 'replacement' && $item->product_id) {
                $product = Product::find($item->product_id);
                if ($product && $product->tracksStock()) {
                    $replacementCost += (float) $product->cost_price * (int) $item->quantity;
                }
            }
        }

        $returnedCost = round($returnedCost, 2);
        $replacementCost = round($replacementCost, 2);

        if ($returnedCost > 0) {
            $lines[] = ['account_id' => $inventory->id, 'debit' => $returnedCost, 'store_id' => $refund->store_id, 'memo' => 'Barang kembali ke stok'];
            $lines[] = ['account_id' => $cogs->id, 'credit' => $returnedCost, 'store_id' => $refund->store_id, 'memo' => 'Pembalikan HPP retur'];
        }

        if ($replacementCost > 0) {
            $lines[] = ['account_id' => $cogs->id, 'debit' => $replacementCost, 'store_id' => $refund->store_id, 'memo' => 'HPP barang pengganti'];
            $lines[] = ['account_id' => $inventory->id, 'credit' => $replacementCost, 'store_id' => $refund->store_id, 'memo' => 'Pengurangan stok pengganti'];
        }
    }

    private function alreadyPosted(Refund $refund): bool
    {
        return Journal::query()
            ->where('source_type', $refund->getMorphClass())
            ->where('source_id', $refund->getKey())
            ->exists();
    }

    private function account(Company $company, string $subtype): Account
    {
        $account = $company->account($subtype);

        if (! $account) {
            throw new \RuntimeException("Akun sistem '{$subtype}' belum dikonfigurasi untuk {$company->name}.");
        }

        return $account;
    }
}
