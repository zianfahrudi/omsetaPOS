<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a receipt against a sales invoice (customer receivable).
 *
 *   Dr Kas / Bank
 *     Cr Piutang Usaha
 */
class SalesInvoicePaymentService
{
    public function __construct(private readonly PostingService $posting) {}

    public function pay(
        SalesInvoice $invoice,
        float $amount,
        string $method = 'cash',
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): SalesInvoicePayment {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih dari nol.');
        }

        if (! in_array($method, ['cash', 'bank'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($invoice, $amount, $method, $date, $notes, $createdBy) {
            $invoice = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $company = $invoice->company;

            if ($amount > (float) $invoice->outstanding_amount + 0.001) {
                throw new InvalidArgumentException('Nominal melebihi sisa piutang.');
            }

            $payment = SalesInvoicePayment::create([
                'company_id' => $company->id,
                'sales_invoice_id' => $invoice->id,
                'contact_id' => $invoice->contact_id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'method' => $method,
                'amount' => $amount,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $invoice->increment('paid_amount', $amount);
            $invoice->forceFill([
                'outstanding_amount' => max(0, round((float) $invoice->outstanding_amount - $amount, 2)),
            ])->save();

            $invoice->customer?->forceFill([
                'receivable_balance' => max(0, round((float) $invoice->customer->receivable_balance - $amount, 2)),
            ])->save();

            $this->posting->post(
                company: $company,
                date: $date,
                lines: [
                    ['account_id' => $this->account($company, $method === 'bank' ? 'bank' : 'cash'), 'debit' => $amount, 'memo' => 'Penerimaan piutang '.$invoice->number],
                    ['account_id' => $this->account($company, 'accounts_receivable'), 'credit' => $amount, 'contact_id' => $invoice->contact_id, 'memo' => 'Pelunasan piutang'],
                ],
                type: 'cash_receipt',
                description: "Pembayaran piutang {$invoice->number}",
                reference: $payment->number,
                source: $payment,
                createdBy: $createdBy,
            );

            ActivityLogger::log('sales_invoice.paid', "Pembayaran piutang {$invoice->number}", $invoice->store_id, $payment, [
                'amount' => $amount,
                'method' => $method,
            ]);

            return $payment;
        });
    }

    private function account($company, string $subtype): int
    {
        $account = $company->account($subtype);

        if (! $account) {
            throw new InvalidArgumentException("Akun sistem '{$subtype}' belum dikonfigurasi.");
        }

        return $account->id;
    }

    private function number($company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = SalesInvoicePayment::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('RCV/%s/%04d', $period, $sequence);
            $sequence++;
        } while (SalesInvoicePayment::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
