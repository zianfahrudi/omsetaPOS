<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\WarehouseStock;
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
            'revenue_groups' => $this->sectionGrouped($accounts->where('type', 'revenue'), fn (Account $a) => $this->ledger->periodActivity($a, $from, $to)),
            'expense_groups' => $this->sectionGrouped($accounts->where('type', 'expense'), fn (Account $a) => $this->ledger->periodActivity($a, $from, $to)),
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

        // Versi berkelompok (per akun induk) untuk tampilan berjenjang.
        $assetGroups = $this->sectionGrouped($accounts->where('type', 'asset'), fn (Account $a) => $this->ledger->balance($a, $asOf));
        $liabilityGroups = $this->sectionGrouped($accounts->where('type', 'liability'), fn (Account $a) => $this->ledger->balance($a, $asOf));
        $equityGroups = $this->sectionGrouped($accounts->where('type', 'equity'), fn (Account $a) => $this->ledger->balance($a, $asOf));

        if (abs($netIncome) >= 0.01) {
            $equityGroups = $equityGroups->push([
                'group_code' => 'zzzz',
                'group_name' => 'Laba Berjalan',
                'rows' => collect([['code' => '', 'name' => 'Laba (Rugi) Berjalan', 'amount' => $netIncome]]),
                'subtotal' => $netIncome,
            ])->values();
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'asset_groups' => $assetGroups,
            'liability_groups' => $liabilityGroups,
            'equity_groups' => $equityGroups,
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
     * Accounts receivable aging (piutang) from credit invoices + POS debt sales.
     *
     * @return array{rows:Collection, buckets:array<string,float>, total:float, as_of:string}
     */
    public function receivableAging(Company $company, Carbon|string $asOf): array
    {
        $docs = collect();

        SalesInvoice::query()
            ->with('customer')
            ->where('company_id', $company->id)
            ->where('outstanding_amount', '>', 0)
            ->get()
            ->each(fn (SalesInvoice $i) => $docs->push([
                'party' => $i->customer?->name ?? 'Pelanggan',
                'number' => $i->number,
                'date' => $i->date,
                'due' => $i->due_date ?? $i->date,
                'amount' => (float) $i->outstanding_amount,
            ]));

        Sale::query()
            ->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('is_debt', true)
            ->where('debt_amount', '>', 0)
            ->get()
            ->each(fn (Sale $s) => $docs->push([
                'party' => $s->customer_name ?? 'Pelanggan Umum',
                'number' => $s->number,
                'date' => $s->created_at,
                'due' => $s->created_at,
                'amount' => (float) $s->debt_amount,
            ]));

        return $this->buildAging($docs, $asOf);
    }

    /**
     * Accounts payable aging (hutang) from purchase invoices.
     *
     * @return array{rows:Collection, buckets:array<string,float>, total:float, as_of:string}
     */
    public function payableAging(Company $company, Carbon|string $asOf): array
    {
        $docs = Purchase::query()
            ->with('supplier')
            ->where('company_id', $company->id)
            ->where('outstanding_amount', '>', 0)
            ->get()
            ->map(fn (Purchase $p) => [
                'party' => $p->supplier?->name ?? 'Supplier',
                'number' => $p->number,
                'date' => $p->date,
                'due' => $p->due_date ?? $p->date,
                'amount' => (float) $p->outstanding_amount,
            ]);

        return $this->buildAging(collect($docs), $asOf);
    }

    /**
     * @param  Collection<int, array{party:string, number:string, date:mixed, due:mixed, amount:float}>  $docs
     * @return array{rows:Collection, buckets:array<string,float>, total:float, as_of:string}
     */
    private function buildAging(Collection $docs, Carbon|string $asOf): array
    {
        $asOf = Carbon::parse($asOf)->startOfDay();
        $buckets = ['current' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];

        $rows = $docs->map(function (array $doc) use ($asOf, &$buckets) {
            $overdue = (int) Carbon::parse($doc['due'])->startOfDay()->diffInDays($asOf, false);
            $bucket = match (true) {
                $overdue <= 0 => 'current',
                $overdue <= 30 => '1_30',
                $overdue <= 60 => '31_60',
                $overdue <= 90 => '61_90',
                default => 'over_90',
            };
            $buckets[$bucket] += $doc['amount'];

            return [
                'party' => $doc['party'],
                'number' => $doc['number'],
                'date' => Carbon::parse($doc['date'])->toDateString(),
                'due' => Carbon::parse($doc['due'])->toDateString(),
                'overdue_days' => max(0, $overdue),
                'amount' => round($doc['amount'], 2),
                'bucket' => $bucket,
            ];
        })->sortByDesc('overdue_days')->values();

        return [
            'rows' => $rows,
            'buckets' => array_map(fn ($v) => round($v, 2), $buckets),
            'total' => round($rows->sum('amount'), 2),
            'as_of' => $asOf->toDateString(),
        ];
    }

    /**
     * VAT report: PPN Keluaran (output) vs PPN Masukan (input) for a period.
     *
     * @return array{output:float, input:float, net:float, status:string, rows:Collection, from:string, to:string}
     */
    public function taxReport(Company $company, Carbon|string $from, Carbon|string $to): array
    {
        $output = $company->account('tax_output');
        $input = $company->account('tax_input');

        $outputTotal = $output ? $this->ledger->periodActivity($output, $from, $to) : 0.0;
        $inputTotal = $input ? $this->ledger->periodActivity($input, $from, $to) : 0.0;
        $net = round($outputTotal - $inputTotal, 2);

        $accountIds = collect([$output?->id, $input?->id])->filter()->all();

        $rows = $accountIds === [] ? collect() : JournalLine::query()
            ->with('journal:id,number,date,description')
            ->whereIn('account_id', $accountIds)
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted')->whereBetween('date', [$from, $to]))
            ->get()
            ->map(fn (JournalLine $line) => [
                'date' => $line->journal->date->toDateString(),
                'number' => $line->journal->number,
                'description' => $line->journal->description,
                'output' => $output && $line->account_id === $output->id ? (float) $line->credit : 0.0,
                'input' => $input && $line->account_id === $input->id ? (float) $line->debit : 0.0,
            ])
            ->sortBy('date')
            ->values();

        return [
            'output' => round($outputTotal, 2),
            'input' => round($inputTotal, 2),
            'net' => $net,
            'status' => $net >= 0 ? 'Kurang Bayar (PPN harus disetor)' : 'Lebih Bayar (PPN dapat dikompensasi)',
            'rows' => $rows,
            'from' => Carbon::parse($from)->toDateString(),
            'to' => Carbon::parse($to)->toDateString(),
        ];
    }

    /**
     * Inventory valuation: quantity and value (qty * average cost) per product.
     *
     * @return array{rows:Collection, total_value:float, total_items:int}
     */
    public function inventoryReport(Company $company, ?int $categoryId = null, bool $lowStockOnly = false): array
    {
        $rows = Product::query()
            ->with('category')
            ->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($lowStockOnly, fn ($q) => $q->whereColumn('stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category?->name,
                'stock' => (int) $p->stock,
                'unit' => $p->unit,
                'minimum_stock' => (int) $p->minimum_stock,
                'low' => $p->stock <= $p->minimum_stock,
                'cost_price' => (float) $p->cost_price,
                'value' => round((float) $p->cost_price * (int) $p->stock, 2),
            ]);

        return [
            'rows' => $rows,
            'total_value' => round($rows->sum('value'), 2),
            'total_items' => $rows->count(),
        ];
    }

    /**
     * Sales analysis (faktur penjualan + POS) grouped by product and by customer.
     *
     * @return array{by_product:Collection, by_customer:Collection, total:float, from:string, to:string}
     */
    public function salesAnalysis(Company $company, Carbon|string $from, Carbon|string $to): array
    {
        $storeIds = $company->stores()->pluck('id');

        $invoiceItems = SalesInvoiceItem::query()
            ->with(['invoice.customer', 'product.category'])
            ->whereHas('invoice', fn ($q) => $q->where('company_id', $company->id)->whereBetween('date', [$from, $to]))
            ->get()
            ->map(fn ($i) => [
                'product' => $i->product_name,
                'category' => $i->product?->category?->name ?? 'Tanpa Kategori',
                'customer' => $i->invoice?->customer?->name ?? 'Pelanggan',
                'quantity' => (int) $i->quantity,
                'total' => (float) $i->line_total,
            ]);

        $posItems = SaleItem::query()
            ->with(['sale', 'product.category'])
            ->whereHas('sale', fn ($q) => $q->whereIn('store_id', $storeIds)->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay(),
            ]))
            ->get()
            ->map(fn ($i) => [
                'product' => $i->product_name,
                'category' => $i->product?->category?->name ?? 'Tanpa Kategori',
                'customer' => $i->sale?->customer_name ?? 'Pelanggan Umum',
                'quantity' => (int) $i->quantity,
                'total' => (float) $i->line_total,
            ]);

        $all = $invoiceItems->concat($posItems);

        return [
            'by_product' => $this->groupAnalysis($all, 'product'),
            'by_category' => $this->groupAnalysis($all, 'category'),
            'by_customer' => $this->groupAnalysis($all, 'customer'),
            'total' => round($all->sum('total'), 2),
            'from' => Carbon::parse($from)->toDateString(),
            'to' => Carbon::parse($to)->toDateString(),
        ];
    }

    /**
     * Purchase analysis grouped by product and by supplier.
     *
     * @return array{by_product:Collection, by_supplier:Collection, total:float, from:string, to:string}
     */
    public function purchaseAnalysis(Company $company, Carbon|string $from, Carbon|string $to): array
    {
        $items = PurchaseItem::query()
            ->with('purchase.supplier')
            ->whereHas('purchase', fn ($q) => $q->where('company_id', $company->id)->whereBetween('date', [$from, $to]))
            ->get()
            ->map(fn ($i) => [
                'product' => $i->product_name,
                'supplier' => $i->purchase?->supplier?->name ?? 'Supplier',
                'quantity' => (int) $i->quantity,
                'total' => (float) $i->line_total,
            ]);

        return [
            'by_product' => $this->groupAnalysis($items, 'product'),
            'by_supplier' => $this->groupAnalysis($items, 'supplier'),
            'total' => round($items->sum('total'), 2),
            'from' => Carbon::parse($from)->toDateString(),
            'to' => Carbon::parse($to)->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, array{quantity:int, total:float}>  $rows
     * @return Collection<int, array{label:string, quantity:int, total:float}>
     */
    private function groupAnalysis(Collection $rows, string $key): Collection
    {
        return $rows
            ->groupBy($key)
            ->map(fn ($group, $label) => [
                'label' => (string) $label,
                'quantity' => (int) $group->sum('quantity'),
                'total' => round((float) $group->sum('total'), 2),
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Profit (revenue - expense) for an analytic dimension (project/department)
     * based on tagged journal lines.
     *
     * @return array{revenue:float, expense:float, net:float}
     */
    public function dimensionProfit(Company $company, string $column, int $id, Carbon|string $from, Carbon|string $to): array
    {
        if (! in_array($column, ['project_id', 'department_id'], true)) {
            return ['revenue' => 0.0, 'expense' => 0.0, 'net' => 0.0];
        }

        $lines = JournalLine::query()
            ->with('account:id,type')
            ->where($column, $id)
            ->whereHas('journal', fn ($q) => $q
                ->where('company_id', $company->id)
                ->where('status', 'posted')
                ->whereBetween('date', [$from, $to]))
            ->get();

        $revenue = 0.0;
        $expense = 0.0;

        foreach ($lines as $line) {
            $type = $line->account?->type;
            if ($type === 'revenue') {
                $revenue += (float) $line->credit - (float) $line->debit;
            } elseif ($type === 'expense') {
                $expense += (float) $line->debit - (float) $line->credit;
            }
        }

        return [
            'revenue' => round($revenue, 2),
            'expense' => round($expense, 2),
            'net' => round($revenue - $expense, 2),
        ];
    }

    /**
     * Stock quantity & value per warehouse.
     *
     * @return array{rows:Collection, total_value:float}
     */
    public function warehouseStockReport(Company $company, ?int $warehouseId = null): array
    {
        $rows = WarehouseStock::query()
            ->with(['product', 'warehouse'])
            ->whereHas('warehouse', fn ($q) => $q->where('company_id', $company->id))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('quantity', '!=', 0)
            ->get()
            ->map(fn (WarehouseStock $s) => [
                'warehouse' => $s->warehouse?->name,
                'product' => $s->product?->name,
                'sku' => $s->product?->sku,
                'quantity' => (int) $s->quantity,
                'unit' => $s->product?->unit,
                'cost_price' => (float) ($s->product?->cost_price ?? 0),
                'value' => round((float) ($s->product?->cost_price ?? 0) * (int) $s->quantity, 2),
            ])
            ->sortBy([['warehouse', 'asc'], ['product', 'asc']])
            ->values();

        return [
            'rows' => $rows,
            'total_value' => round($rows->sum('value'), 2),
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
     * Kelompokkan akun berdasarkan akun induk langsung (mis. sub-akun BCA/BNI di
     * bawah induk "Bank") dengan subtotal per kelompok. Untuk laporan berjenjang.
     *
     * @param  Collection<int, Account>  $accounts
     * @return Collection<int, array{group_code:string, group_name:string, rows:Collection, subtotal:float}>
     */
    private function sectionGrouped(Collection $accounts, callable $amountFn): Collection
    {
        return $accounts
            ->map(fn (Account $a) => [
                'account' => $a,
                'code' => $a->code,
                'name' => $a->name,
                'amount' => round((float) $amountFn($a), 2),
            ])
            ->filter(fn (array $row) => $row['amount'] != 0.0)
            ->groupBy(fn (array $row) => $row['account']->parent_id ?? 0)
            ->map(function (Collection $rows) {
                $parent = $rows->first()['account']->parent;
                $out = $rows
                    ->map(fn (array $r) => ['code' => $r['code'], 'name' => $r['name'], 'amount' => $r['amount']])
                    ->sortBy('code')
                    ->values();

                return [
                    'group_code' => (string) ($parent?->code ?? ''),
                    'group_name' => (string) ($parent?->name ?? 'Lainnya'),
                    'rows' => $out,
                    'subtotal' => round($out->sum('amount'), 2),
                ];
            })
            ->sortBy('group_code')
            ->values();
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, Account>
     */
    private function postableAccounts(Company $company, array $types): Collection
    {
        return Account::query()
            ->with('parent:id,code,name')
            ->where('company_id', $company->id)
            ->where('is_postable', true)
            ->whereIn('type', $types)
            ->orderBy('code')
            ->get();
    }
}
