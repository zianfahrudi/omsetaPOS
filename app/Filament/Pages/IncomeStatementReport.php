<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class IncomeStatementReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.income-statement';

    public string $companyId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->companyId = (string) (Company::query()->value('id') ?? '');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    public function report(): array
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return ['revenue' => collect(), 'expense' => collect(), 'total_revenue' => 0, 'total_expense' => 0, 'net_income' => 0, 'from' => $this->dateFrom, 'to' => $this->dateTo];
        }

        return app(ReportService::class)->incomeStatement($company, $this->dateFrom, $this->dateTo);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
