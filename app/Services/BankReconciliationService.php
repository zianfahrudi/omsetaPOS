<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\Company;
use App\Services\Accounting\LedgerService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bank reconciliation snapshot: compares the ledger (book) balance of a
 * cash/bank account as of a statement date against the bank statement balance.
 */
class BankReconciliationService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function reconcile(
        Company $company,
        int $accountId,
        Carbon|string $statementDate,
        float $statementBalance,
        ?string $notes = null,
        ?int $createdBy = null,
    ): BankReconciliation {
        $account = Account::query()
            ->where('company_id', $company->id)
            ->whereKey($accountId)
            ->firstOr(fn () => throw new InvalidArgumentException('Akun tidak ditemukan.'));

        if (! in_array($account->subtype, ['cash', 'bank'], true)) {
            throw new InvalidArgumentException('Rekonsiliasi hanya untuk akun kas/bank.');
        }

        $statementDate = Carbon::parse($statementDate);
        $bookBalance = $this->ledger->balance($account, $statementDate);
        $difference = round($statementBalance - $bookBalance, 2);

        return DB::transaction(function () use ($company, $account, $statementDate, $statementBalance, $bookBalance, $difference, $notes, $createdBy) {
            $rec = BankReconciliation::create([
                'company_id' => $company->id,
                'account_id' => $account->id,
                'number' => $this->number($company, $statementDate),
                'statement_date' => $statementDate,
                'statement_balance' => round($statementBalance, 2),
                'book_balance' => $bookBalance,
                'difference' => $difference,
                'status' => abs($difference) < 0.01 ? 'balanced' : 'unbalanced',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            ActivityLogger::log('bank.reconciled', "Rekonsiliasi {$rec->number}", null, $rec, [
                'difference' => $difference,
            ]);

            return $rec;
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = BankReconciliation::query()
            ->where('company_id', $company->id)
            ->whereYear('statement_date', $date->year)
            ->whereMonth('statement_date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('REK/%s/%04d', $period, $sequence);
            $sequence++;
        } while (BankReconciliation::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
