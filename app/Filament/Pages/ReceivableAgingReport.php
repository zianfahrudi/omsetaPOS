<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ReceivableAgingReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Piutang (Aging)';

    protected static ?string $title = 'Laporan Piutang';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.aging';

    public string $kind = 'receivable';

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

    public function partyLabel(): string
    {
        return 'Pelanggan';
    }

    public function report(): array
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return ['rows' => collect(), 'buckets' => [], 'total' => 0, 'as_of' => $this->asOf];
        }

        return app(ReportService::class)->receivableAging($company, $this->asOf);
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
