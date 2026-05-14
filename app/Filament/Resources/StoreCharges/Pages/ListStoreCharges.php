<?php

namespace App\Filament\Resources\StoreCharges\Pages;

use App\Filament\Resources\StoreCharges\StoreChargeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreCharges extends ListRecords
{
    protected static string $resource = StoreChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
