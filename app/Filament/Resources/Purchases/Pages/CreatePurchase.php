<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Company;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(PurchaseService::class)->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                warehouseId: $data['warehouse_id'] ?? null,
                supplierInvoiceNo: $data['supplier_invoice_no'] ?? null,
                dueDate: $data['due_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title('Pembelian gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return new Purchase;
    }
}
