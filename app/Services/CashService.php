<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Company;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cash & bank transactions outside the trade cycle: cash in, cash out, and
 * transfers between cash/bank accounts. Each posts a balanced journal.
 */
class CashService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * Cash/bank received from a non-trade source.
     *   Dr Kas/Bank  Cr akun lawan (pendapatan/lainnya)
     */
    public function receive(Company $company, int $accountId, int $counterAccountId, float $amount, Carbon|string|null $date = null, ?string $description = null, ?int $contactId = null, ?int $createdBy = null): CashTransaction
    {
        return $this->record($company, 'in', $accountId, $amount, $date, $description, $counterAccountId, null, $contactId, $createdBy);
    }

    /**
     * Cash/bank paid for a non-trade purpose.
     *   Dr akun lawan (beban/lainnya)  Cr Kas/Bank
     */
    public function pay(Company $company, int $accountId, int $counterAccountId, float $amount, Carbon|string|null $date = null, ?string $description = null, ?int $contactId = null, ?int $createdBy = null): CashTransaction
    {
        return $this->record($company, 'out', $accountId, $amount, $date, $description, $counterAccountId, null, $contactId, $createdBy);
    }

    /**
     * Move funds between two cash/bank accounts.
     *   Dr Kas/Bank tujuan  Cr Kas/Bank asal
     */
    public function transfer(Company $company, int $fromAccountId, int $toAccountId, float $amount, Carbon|string|null $date = null, ?string $description = null, ?int $createdBy = null): CashTransaction
    {
        if ($fromAccountId === $toAccountId) {
            throw new InvalidArgumentException('Akun asal dan tujuan tidak boleh sama.');
        }

        return $this->record($company, 'transfer', $fromAccountId, $amount, $date, $description, null, $toAccountId, null, $createdBy);
    }

    private function record(Company $company, string $type, int $accountId, float $amount, Carbon|string|null $date, ?string $description, ?int $counterAccountId, ?int $toAccountId, ?int $contactId, ?int $createdBy): CashTransaction
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal harus lebih dari nol.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $type, $accountId, $amount, $date, $description, $counterAccountId, $toAccountId, $contactId, $createdBy) {
            $cash = CashTransaction::create([
                'company_id' => $company->id,
                'number' => $this->number($company, $type, $date),
                'date' => $date,
                'type' => $type,
                'account_id' => $accountId,
                'counter_account_id' => $counterAccountId,
                'to_account_id' => $toAccountId,
                'contact_id' => $contactId,
                'amount' => $amount,
                'description' => $description,
                'created_by' => $createdBy,
            ]);

            [$lines, $journalType] = match ($type) {
                'in' => [[
                    ['account_id' => $accountId, 'debit' => $amount, 'contact_id' => $contactId, 'memo' => $description],
                    ['account_id' => $counterAccountId, 'credit' => $amount, 'memo' => $description],
                ], 'cash_receipt'],
                'out' => [[
                    ['account_id' => $counterAccountId, 'debit' => $amount, 'memo' => $description],
                    ['account_id' => $accountId, 'credit' => $amount, 'contact_id' => $contactId, 'memo' => $description],
                ], 'cash_payment'],
                'transfer' => [[
                    ['account_id' => $toAccountId, 'debit' => $amount, 'memo' => $description],
                    ['account_id' => $accountId, 'credit' => $amount, 'memo' => $description],
                ], 'general'],
            };

            $this->posting->post(
                company: $company,
                date: $date,
                lines: $lines,
                type: $journalType,
                description: $description ?? $this->defaultDescription($type),
                reference: $cash->number,
                source: $cash,
                createdBy: $createdBy,
            );

            ActivityLogger::log("cash.{$type}", "{$this->defaultDescription($type)} {$cash->number}", null, $cash, [
                'amount' => $amount,
            ]);

            return $cash;
        });
    }

    private function defaultDescription(string $type): string
    {
        return match ($type) {
            'in' => 'Kas Masuk',
            'out' => 'Kas Keluar',
            'transfer' => 'Transfer Kas',
        };
    }

    private function number(Company $company, string $type, Carbon $date): string
    {
        $prefix = match ($type) {
            'in' => 'KM',
            'out' => 'KK',
            'transfer' => 'TF',
        };
        $period = $date->format('Ym');
        $sequence = CashTransaction::query()
            ->where('company_id', $company->id)
            ->where('type', $type)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('%s/%s/%04d', $prefix, $period, $sequence);
            $sequence++;
        } while (CashTransaction::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
