<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class BalanceSheetReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Neraca';

    protected static ?string $title = 'Laporan Neraca';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.balance-sheet';

    public string $companyId = '';

    public string $asOf = '';

    public function mount(): void
    {
        $this->companyId = (string) (Company::query()->value('id') ?? '');
        $this->asOf = now()->toDateString();
    }

    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    public function report(): array
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return ['assets' => collect(), 'liabilities' => collect(), 'equity' => collect(), 'total_assets' => 0, 'total_liabilities' => 0, 'total_equity' => 0, 'net_income' => 0, 'balanced' => true, 'as_of' => $this->asOf];
        }

        return app(ReportService::class)->balanceSheet($company, $this->asOf);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
