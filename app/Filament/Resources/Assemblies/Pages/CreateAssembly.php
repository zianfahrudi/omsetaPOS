<?php

namespace App\Filament\Resources\Assemblies\Pages;

use App\Filament\Resources\Assemblies\AssemblyResource;
use App\Models\Assembly;
use App\Models\Company;
use App\Services\AssemblyService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateAssembly extends CreateRecord
{
    protected static string $resource = AssemblyResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Company::query()->findOrFail($data['company_id']);

        try {
            return app(AssemblyService::class)->create(
                company: $company,
                finishedProductId: ! empty($data['product_id']) ? (int) $data['product_id'] : null,
                finishedProductName: $data['product_name'] ?? null,
                quantity: (int) $data['quantity'],
                components: $data['components'] ?? [],
                date: $data['date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Perakitan gagal')->body($e->getMessage())->danger()->send();
            $this->halt();
        }

        return new Assembly;
    }
}
