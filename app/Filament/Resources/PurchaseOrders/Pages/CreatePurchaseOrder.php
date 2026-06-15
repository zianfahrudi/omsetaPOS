<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(PurchaseOrderService::class)->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                expectedDate: $data['expected_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Pesanan gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new PurchaseOrder();
    }
}
