<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds financial statements (balance sheet, income statement) from the
 * posted ledger via LedgerService.
 */
class ReportService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Income statement (laba rugi) for a period.
     *
     * @return array{revenue:Collection, expense:Collection, total_revenue:float, total_expense:float, net_income:float, from:string, to:string}
     */
    public function incomeStatement(Company $company, Carbon|string $from, Carbon|string $to): array
    {
        $accounts = $this->postableAccounts($company, ['revenue', 'expense']);

        $revenue = $this->section($accounts->where('type', 'revenue'), fn (Account $a) => $this->ledger->periodActivity($a, $from, $to));
        $expense = $this->section($accounts->where('type', 'expense'), fn (Account $a) => $this->ledger->periodActivity($a, $from, $to));

        $totalRevenue = round($revenue->sum('amount'), 2);
        $totalExpense = round($expense->sum('amount'), 2);

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => round($totalRevenue - $totalExpense, 2),
            'from' => Carbon::parse($from)->toDateString(),
            'to' => Carbon::parse($to)->toDateString(),
        ];
    }

    /**
     * Balance sheet (neraca) as of a date. Current-period net income is folded
     * into equity (not yet closed to retained earnings).
     *
     * @return array{assets:Collection, liabilities:Collection, equity:Collection, total_assets:float, total_liabilities:float, total_equity:float, net_income:float, balanced:bool, as_of:string}
     */
    public function balanceSheet(Company $company, Carbon|string $asOf): array
    {
        $accounts = $this->postableAccounts($company, ['asset', 'liability', 'equity', 'revenue', 'expense']);

        $assets = $this->section($accounts->where('type', 'asset'), fn (Account $a) => $this->ledger->balance($a, $asOf));
        $liabilities = $this->section($accounts->where('type', 'liability'), fn (Account $a) => $this->ledger->balance($a, $asOf));
        $equityAccounts = $this->section($accounts->where('type', 'equity'), fn (Account $a) => $this->ledger->balance($a, $asOf));

        $revenueTotal = $accounts->where('type', 'revenue')->sum(fn (Account $a) => $this->ledger->balance($a, $asOf));
        $expenseTotal = $accounts->where('type', 'expense')->sum(fn (Account $a) => $this->ledger->balance($a, $asOf));
        $netIncome = round((float) $revenueTotal - (float) $expenseTotal, 2);

        $equity = $equityAccounts->push([
            'code' => '',
            'name' => 'Laba (Rugi) Berjalan',
            'amount' => $netIncome,
        ]);

        $totalAssets = round($assets->sum('amount'), 2);
        $totalLiabilities = round($liabilities->sum('amount'), 2);
        $totalEquity = round($equity->sum('amount'), 2);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'net_income' => $netIncome,
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            'as_of' => Carbon::parse($asOf)->toDateString(),
        ];
    }

    /**
     * Cash flow statement (direct method) from cash/bank account movements.
     *
     * @return array{opening:float, closing:float, total_in:float, total_out:float, net:float, groups:Collection, from:string, to:string}
     */
    public function cashFlow(Company $company, Carbon|string $from, Carbon|string $to): array
    {
        $cashAccounts = Account::query()
            ->where('company_id', $company->id)
            ->whereIn('subtype', ['cash', 'bank'])
            ->get();

        $cashIds = $cashAccounts->pluck('id');

        $opening = round((float) $cashAccounts->sum(
            fn (Account $a) => $this->ledger->balance($a, Carbon::parse($from)->subDay())
        ), 2);

        $lines = JournalLine::query()
            ->with('journal:id,type,number,date,description')
            ->whereIn('account_id', $cashIds)
            ->whereHas('journal', fn ($q) => $q
                ->where('status', 'posted')
                ->whereBetween('date', [$from, $to]))
            ->get();

        $labels = [
            'sales' => 'Penjualan',
            'cash_receipt' => 'Penerimaan Kas',
            'cash_payment' => 'Pengeluaran Kas',
            'purchase' => 'Pembelian',
            'general' => 'Transfer & Lainnya',
            'adjustment' => 'Penyesuaian',
            'opening' => 'Saldo Awal',
            'inventory' => 'Persediaan',
        ];

        $groups = $lines
            ->groupBy(fn (JournalLine $line) => $line->journal->type)
            ->map(function ($group, $type) use ($labels) {
                $in = round((float) $group->sum('debit'), 2);
                $out = round((float) $group->sum('credit'), 2);

                return [
                    'label' => $labels[$type] ?? ucfirst((string) $type),
                    'in' => $in,
                    'out' => $out,
                    'net' => round($in - $out, 2),
                ];
            })
            ->sortByDesc('net')
            ->values();

        $totalIn = round((float) $lines->sum('debit'), 2);
        $totalOut = round((float) $lines->sum('credit'), 2);
        $net = round($totalIn - $totalOut, 2);

        return [
            'opening' => $opening,
            'closing' => round($opening + $net, 2),
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net' => $net,
            'groups' => $groups,
            'from' => Carbon::parse($from)->toDateString(),
            'to' => Carbon::parse($to)->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return Collection<int, array{code:string, name:string, amount:float}>
     */
    private function section(Collection $accounts, callable $amountFn): Collection
    {
        return $accounts
            ->map(fn (Account $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'amount' => round((float) $amountFn($a), 2),
            ])
            ->filter(fn (array $row) => $row['amount'] != 0.0)
            ->sortBy('code')
            ->values();
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, Account>
     */
    private function postableAccounts(Company $company, array $types): Collection
    {
        return Account::query()
            ->where('company_id', $company->id)
            ->where('is_postable', true)
            ->whereIn('type', $types)
            ->orderBy('code')
            ->get();
    }
}
