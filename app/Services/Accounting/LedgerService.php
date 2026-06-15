<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-side queries over the posted ledger: account balances, account ledger
 * (mutasi), and trial balance. Foundation for financial statements.
 */
class LedgerService
{
    /**
     * Net balance of an account expressed on its normal side (positive = normal).
     */
    public function balance(Account $account, Carbon|string|null $asOf = null): float
    {
        $query = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'));

        if ($asOf !== null) {
            $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $asOf));
        }

        $debit = (float) (clone $query)->sum('debit');
        $credit = (float) (clone $query)->sum('credit');
        $signed = $debit - $credit; // positive means net debit

        $balance = $account->isDebitNormal() ? $signed : -$signed;

        return round((float) $account->opening_balance + $balance, 2);
    }

    /**
     * Account ledger entries with running balance for a period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ledger(Account $account, Carbon|string $from, Carbon|string $to): Collection
    {
        $opening = $this->balance($account, Carbon::parse($from)->subDay());
        $running = $opening;

        $lines = JournalLine::query()
            ->with('journal')
            ->where('account_id', $account->id)
            ->whereHas('journal', fn ($q) => $q
                ->where('status', 'posted')
                ->whereBetween('date', [$from, $to]))
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->journal->date <=> $b->journal->date,
                fn ($a, $b) => $a->id <=> $b->id,
            ])
            ->values();

        return $lines->map(function (JournalLine $line) use (&$running, $account) {
            $delta = $account->isDebitNormal()
                ? (float) $line->debit - (float) $line->credit
                : (float) $line->credit - (float) $line->debit;
            $running = round($running + $delta, 2);

            return [
                'date' => $line->journal->date->toDateString(),
                'number' => $line->journal->number,
                'description' => $line->memo ?? $line->journal->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $running,
            ];
        });
    }

    /**
     * Net movement of an account on its normal side within a period (no opening balance).
     * Used for income statement figures.
     */
    public function periodActivity(Account $account, Carbon|string $from, Carbon|string $to): float
    {
        $query = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journal', fn ($q) => $q
                ->where('status', 'posted')
                ->whereBetween('date', [$from, $to]));

        $debit = (float) (clone $query)->sum('debit');
        $credit = (float) (clone $query)->sum('credit');
        $signed = $debit - $credit;

        return round($account->isDebitNormal() ? $signed : -$signed, 2);
    }

    /**
     * Trial balance for all postable accounts as of a date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trialBalance(Company $company, Carbon|string|null $asOf = null): Collection
    {
        return Account::query()
            ->where('company_id', $company->id)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($asOf) {
                $balance = $this->balance($account, $asOf);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => $account->isDebitNormal() ? max(0, $balance) : 0.0,
                    'credit' => ! $account->isDebitNormal() ? max(0, $balance) : 0.0,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($row) => $row['balance'] != 0.0)
            ->values();
    }
}
