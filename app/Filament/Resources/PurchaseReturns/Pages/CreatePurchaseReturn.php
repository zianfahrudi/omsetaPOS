<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $purchase = Purchase::query()->findOrFail($data['purchase_id']);

        try {
            return app(PurchaseReturnService::class)->create(
                purchase: $purchase,
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                reason: $data['reason'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Retur gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new PurchaseReturn;
    }
}
