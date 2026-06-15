<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\Company;
use App\Models\SalesInvoice;
use App\Services\SalesInvoiceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(SalesInvoiceService::class)->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                warehouseId: $data['warehouse_id'] ?? null,
                customerRef: $data['customer_ref'] ?? null,
                dueDate: $data['due_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Faktur gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new SalesInvoice();
    }
}
