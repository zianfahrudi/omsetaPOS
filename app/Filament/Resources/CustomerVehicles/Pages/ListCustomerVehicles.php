<?php

namespace App\Filament\Resources\CustomerVehicles\Pages;

use App\Filament\Resources\CustomerVehicles\CustomerVehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerVehicles extends ListRecords
{
    protected static string $resource = CustomerVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
