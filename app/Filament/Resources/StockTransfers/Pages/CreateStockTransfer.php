<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\Company;
use App\Models\StockTransfer;
use App\Services\WarehouseStockService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(WarehouseStockService::class)->transfer(
                company: $company,
                fromWarehouseId: (int) $data['from_warehouse_id'],
                toWarehouseId: (int) $data['to_warehouse_id'],
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Pemindahan gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new StockTransfer();
    }
}
