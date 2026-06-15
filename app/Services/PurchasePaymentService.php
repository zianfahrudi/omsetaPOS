<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a payment against a purchase (supplier payable).
 *
 *   Dr Hutang Usaha
 *     Cr Kas / Bank
 */
class PurchasePaymentService
{
    public function __construct(private readonly PostingService $posting) {}

    public function pay(
        Purchase $purchase,
        float $amount,
        string $method = 'cash',
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): PurchasePayment {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih dari nol.');
        }

        if (! in_array($method, ['cash', 'bank'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($purchase, $amount, $method, $date, $notes, $createdBy) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $company = $purchase->company;

            if ($amount > (float) $purchase->outstanding_amount + 0.001) {
                throw new InvalidArgumentException('Nominal melebihi sisa hutang.');
            }

            $payment = PurchasePayment::create([
                'company_id' => $company->id,
                'purchase_id' => $purchase->id,
                'contact_id' => $purchase->contact_id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'method' => $method,
                'amount' => $amount,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $purchase->increment('paid_amount', $amount);
            $purchase->forceFill([
                'outstanding_amount' => max(0, round((float) $purchase->outstanding_amount - $amount, 2)),
            ])->save();

            $purchase->supplier?->forceFill([
                'payable_balance' => max(0, round((float) $purchase->supplier->payable_balance - $amount, 2)),
            ])->save();

            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    [
                        'account_id' => $this->account($company, 'accounts_payable'),
                        'debit' => $amount,
                        'contact_id' => $purchase->contact_id,
                        'memo' => 'Pembayaran hutang',
                    ],
                    [
                        'account_id' => $this->account($company, $method === 'bank' ? 'bank' : 'cash'),
                        'credit' => $amount,
                        'memo' => 'Pembayaran hutang '.$purchase->number,
                    ],
                ],
                type: 'cash_payment',
                description: "Pembayaran hutang {$purchase->number}",
                reference: $payment->number,
                source: $payment,
                createdBy: $createdBy,
            );

            ActivityLogger::log('purchase.paid', "Pembayaran hutang {$purchase->number}", $purchase->store_id, $payment, [
                'amount' => $amount,
                'method' => $method,
            ]);

            return $payment;
        });
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
        $sequence = PurchasePayment::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('BYR/%s/%04d', $period, $sequence);
            $sequence++;
        } while (PurchasePayment::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
