<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CashFlowReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Arus Kas';

    protected static ?string $title = 'Laporan Arus Kas';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.cash-flow';

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
            return ['opening' => 0, 'closing' => 0, 'total_in' => 0, 'total_out' => 0, 'net' => 0, 'groups' => collect(), 'from' => $this->dateFrom, 'to' => $this->dateTo];
        }

        return app(ReportService::class)->cashFlow($company, $this->dateFrom, $this->dateTo);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
