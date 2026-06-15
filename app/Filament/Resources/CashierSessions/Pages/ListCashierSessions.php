<?php

namespace App\Filament\Resources\CashierSessions\Pages;

use App\Filament\Resources\CashierSessions\CashierSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashierSessions extends ListRecords
{
    protected static string $resource = CashierSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
