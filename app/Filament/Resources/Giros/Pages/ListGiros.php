<?php

namespace App\Filament\Resources\Giros\Pages;

use App\Filament\Resources\Giros\GiroResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGiros extends ListRecords
{
    protected static string $resource = GiroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
