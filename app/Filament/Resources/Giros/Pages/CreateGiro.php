<?php

namespace App\Filament\Resources\Giros\Pages;

use App\Filament\Resources\Giros\GiroResource;
use App\Models\Company;
use App\Models\Giro;
use App\Services\GiroService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateGiro extends CreateRecord
{
    protected static string $resource = GiroResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(GiroService::class)->receive(
                company: $company,
                contactId: (int) $data['contact_id'],
                amount: (float) $data['amount'],
                date: $data['date'] ?? null,
                giroNumber: $data['giro_number'] ?? null,
                bankName: $data['bank_name'] ?? null,
                dueDate: $data['due_date'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Giro gagal dicatat')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new Giro;
    }
}
