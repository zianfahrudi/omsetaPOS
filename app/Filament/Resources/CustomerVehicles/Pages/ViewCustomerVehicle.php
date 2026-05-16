<?php

namespace App\Filament\Resources\CustomerVehicles\Pages;

use App\Filament\Resources\CustomerVehicles\CustomerVehicleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerVehicle extends ViewRecord
{
    protected static string $resource = CustomerVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
