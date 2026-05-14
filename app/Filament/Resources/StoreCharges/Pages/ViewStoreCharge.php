<?php

namespace App\Filament\Resources\StoreCharges\Pages;

use App\Filament\Resources\StoreCharges\StoreChargeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreCharge extends ViewRecord
{
    protected static string $resource = StoreChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
