<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\Company;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(StockAdjustmentService::class)->adjust(
                company: $company,
                productId: (int) $data['product_id'],
                quantityAfter: (int) $data['quantity_after'],
                reason: $data['reason'] ?? 'opname',
                date: $data['date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Penyesuaian gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new StockAdjustment();
    }
}
