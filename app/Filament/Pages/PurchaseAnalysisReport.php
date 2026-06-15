<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class PurchaseAnalysisReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Analisa Pembelian';

    protected static ?string $title = 'Laporan Pembelian';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.purchase-analysis';

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
            return ['by_product' => collect(), 'by_supplier' => collect(), 'total' => 0, 'from' => $this->dateFrom, 'to' => $this->dateTo];
        }

        return app(ReportService::class)->purchaseAnalysis($company, $this->dateFrom, $this->dateTo);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
