<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\SyncsUserStores;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use SyncsUserStores;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedStoreIds = $data['store_ids'] ?? [];
        unset($data['store_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncUserStores($this->record, $this->selectedStoreIds, $this->record->role);
    }
}
