<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class TaxReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Pajak (PPN)';

    protected static ?string $title = 'Laporan Pajak';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.tax-report';

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
            return ['output' => 0, 'input' => 0, 'net' => 0, 'status' => '', 'rows' => collect(), 'from' => $this->dateFrom, 'to' => $this->dateTo];
        }

        return app(ReportService::class)->taxReport($company, $this->dateFrom, $this->dateTo);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
