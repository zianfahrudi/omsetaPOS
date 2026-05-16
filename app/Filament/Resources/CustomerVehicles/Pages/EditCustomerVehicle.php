<?php

namespace App\Filament\Resources\CustomerVehicles\Pages;

use App\Filament\Resources\CustomerVehicles\CustomerVehicleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerVehicle extends EditRecord
{
    protected static string $resource = CustomerVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
