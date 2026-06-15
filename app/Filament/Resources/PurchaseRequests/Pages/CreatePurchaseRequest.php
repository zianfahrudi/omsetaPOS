<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(PurchaseRequestService::class)->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $data['items'] ?? [],
                date: $data['date'] ?? null,
                neededDate: $data['needed_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Permintaan gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new PurchaseRequest();
    }
}
