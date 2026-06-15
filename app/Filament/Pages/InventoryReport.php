<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class InventoryReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Persediaan';

    protected static ?string $title = 'Laporan Persediaan';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.inventory-report';

    public string $companyId = '';

    public string $categoryId = '';

    public bool $lowStockOnly = false;

    public function mount(): void
    {
        $this->companyId = (string) (Company::query()->value('id') ?? '');
    }

    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    public function categories(): Collection
    {
        return Category::query()
            ->when($this->companyId !== '', fn ($q) => $q->where('company_id', $this->companyId))
            ->orderBy('name')
            ->get();
    }

    public function report(): array
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return ['rows' => collect(), 'total_value' => 0, 'total_items' => 0];
        }

        return app(ReportService::class)->inventoryReport(
            $company,
            $this->categoryId !== '' ? (int) $this->categoryId : null,
            $this->lowStockOnly,
        );
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
