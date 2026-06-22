<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Models\Company;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(SalesOrderService::class)->create(
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

        return new SalesOrder;
    }
}
