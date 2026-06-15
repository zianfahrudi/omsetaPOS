<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Warehouse;
use App\Services\Accounting\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class WarehouseStockReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Stok per Gudang';

    protected static ?string $title = 'Laporan Stok per Gudang';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.warehouse-stock';

    public string $companyId = '';

    public string $warehouseId = '';

    public function mount(): void
    {
        $this->companyId = (string) (Company::query()->value('id') ?? '');
    }

    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    public function warehouses(): Collection
    {
        return Warehouse::query()
            ->when($this->companyId !== '', fn ($q) => $q->where('company_id', $this->companyId))
            ->orderBy('name')
            ->get();
    }

    public function report(): array
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return ['rows' => collect(), 'total_value' => 0];
        }

        return app(ReportService::class)->warehouseStockReport(
            $company,
            $this->warehouseId !== '' ? (int) $this->warehouseId : null,
        );
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
