<?php

namespace App\Filament\Resources\SalesReturns\Pages;

use App\Filament\Resources\SalesReturns\SalesReturnResource;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateSalesReturn extends CreateRecord
{
    protected static string $resource = SalesReturnResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $invoice = SalesInvoice::query()->findOrFail($data['sales_invoice_id']);

        try {
            return app(SalesReturnService::class)->create(
                invoice: $invoice,
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                reason: $data['reason'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Retur gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new SalesReturn();
    }
}
