<?php

namespace App\Filament\Resources\StoreCharges\Pages;

use App\Filament\Resources\StoreCharges\StoreChargeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreCharge extends EditRecord
{
    protected static string $resource = StoreChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
