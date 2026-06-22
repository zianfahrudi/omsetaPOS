<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Sale;
use App\Models\SaleItem;
use RuntimeException;

/**
 * Builds and posts the accounting journal for a completed POS sale.
 *
 * Revenue side:
 *   Dr Kas/Bank      (amount settled now)
 *   Dr Piutang Usaha (debt portion)
 *   Dr Diskon Penjualan (contra revenue)
 *     Cr Penjualan        (gross subtotal)
 *     Cr Pendapatan Jasa  (store service fee)
 *     Cr PPN Keluaran     (store tax)
 *
 * Cost side (goods only):
 *   Dr HPP
 *     Cr Persediaan
 */
class SalePoster
{
    public function __construct(private readonly PostingService $posting) {}

    public function post(Sale $sale): ?Journal
    {
        $company = $sale->store?->company;

        if (! $company) {
            return null; // legacy store without a company; skip gracefully
        }

        if ($this->alreadyPosted($sale)) {
            return null;
        }

        $sale->loadMissing('items');

        $grandTotal = (float) $sale->grand_total;
        $debt = (float) $sale->debt_amount;
        $settledNow = round($grandTotal - $debt, 2);

        $lines = [];

        // Penerimaan: split per metode bila ada rincian pembayaran, jika tidak pakai metode tunggal.
        $paymentRows = $sale->relationLoaded('payments')
            ? $sale->payments->where('is_settlement', false)
            : $sale->payments()->where('is_settlement', false)->get();

        if ($paymentRows->isNotEmpty()) {
            foreach ($paymentRows as $payment) {
                $amount = round((float) $payment->amount, 2);
                if ($amount <= 0) {
                    continue;
                }
                $lines[] = [
                    'account_id' => $this->account($company, $payment->accountSubtype())->id,
                    'debit' => $amount,
                    'store_id' => $sale->store_id,
                    'memo' => 'Penerimaan '.$payment->method,
                ];
            }
        } elseif ($settledNow > 0) {
            $paymentSubtype = in_array($sale->payment_method, ['qris', 'transfer'], true) ? 'bank' : 'cash';
            $lines[] = [
                'account_id' => $this->account($company, $paymentSubtype)->id,
                'debit' => $settledNow,
                'store_id' => $sale->store_id,
                'memo' => 'Penerimaan '.$sale->payment_method,
            ];
        }

        if ($debt > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'accounts_receivable')->id,
                'debit' => $debt,
                'contact_id' => $sale->customer_id,
                'store_id' => $sale->store_id,
                'memo' => 'Piutang penjualan',
            ];
        }

        if ((float) $sale->discount_total > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'sales_discount')->id,
                'debit' => (float) $sale->discount_total,
                'store_id' => $sale->store_id,
                'memo' => 'Diskon penjualan',
            ];
        }

        $salesAccountId = $this->account($company, 'sales')->id;
        $revenueSplit = app(RevenueAccountResolver::class)->split(
            $company,
            $sale->items->map(fn (SaleItem $i) => [
                'product_id' => $i->product_id,
                'amount' => (float) $i->line_total,
            ])->all(),
            $salesAccountId,
            reconcileTo: (float) $sale->subtotal,
        );

        foreach ($revenueSplit as $accountId => $amount) {
            $lines[] = [
                'account_id' => $accountId,
                'credit' => round($amount, 2),
                'store_id' => $sale->store_id,
                'memo' => 'Penjualan',
            ];
        }

        if ((float) $sale->service_fee_total > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'other_income')->id,
                'credit' => (float) $sale->service_fee_total,
                'store_id' => $sale->store_id,
                'memo' => 'Biaya layanan',
            ];
        }

        if ((float) $sale->tax_total > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'tax_output')->id,
                'credit' => (float) $sale->tax_total,
                'store_id' => $sale->store_id,
                'memo' => 'PPN Keluaran',
            ];
        }

        // Cost of goods sold (services carry no inventory cost).
        $totalCost = round($sale->items
            ->filter(fn (SaleItem $item) => $item->product_type !== 'service')
            ->sum(fn (SaleItem $item) => (float) $item->cost_price * (int) $item->quantity), 2);

        if ($totalCost > 0) {
            $lines[] = [
                'account_id' => $this->account($company, 'cogs')->id,
                'debit' => $totalCost,
                'store_id' => $sale->store_id,
                'memo' => 'Harga pokok penjualan',
            ];
            $lines[] = [
                'account_id' => $this->account($company, 'inventory')->id,
                'credit' => $totalCost,
                'store_id' => $sale->store_id,
                'memo' => 'Pengurangan persediaan',
            ];
        }

        return $this->posting->post(
            company: $company,
            date: ($sale->paid_at ?? $sale->created_at)->toDateString(),
            lines: $lines,
            type: 'sales',
            description: "Penjualan POS {$sale->number}",
            reference: $sale->number,
            source: $sale,
            createdBy: $sale->cashier_id,
        );
    }

    private function alreadyPosted(Sale $sale): bool
    {
        return Journal::query()
            ->where('source_type', $sale->getMorphClass())
            ->where('source_id', $sale->getKey())
            ->where('type', 'sales')
            ->exists();
    }

    private function account(Company $company, string $subtype): Account
    {
        $account = $company->account($subtype);

        if (! $account) {
            throw new RuntimeException("Akun sistem '{$subtype}' belum dikonfigurasi untuk {$company->name}.");
        }

        return $account;
    }
}
