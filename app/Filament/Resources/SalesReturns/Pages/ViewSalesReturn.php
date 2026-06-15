<?php

namespace App\Filament\Resources\SalesReturns\Pages;

use App\Filament\Resources\SalesReturns\SalesReturnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesReturn extends ViewRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
