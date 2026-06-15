<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\LedgerService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class TrialBalanceReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Neraca Saldo';

    protected static ?string $title = 'Neraca Saldo (Trial Balance)';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.trial-balance';

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

    public function rows(): Collection
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return collect();
        }

        return app(LedgerService::class)->trialBalance($company, $this->asOf);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
