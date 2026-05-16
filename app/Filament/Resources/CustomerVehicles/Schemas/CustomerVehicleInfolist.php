<?php

namespace App\Filament\Resources\CustomerVehicles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerVehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Store'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('customer.phone')
                    ->label('No. HP')
                    ->placeholder('-'),
                TextEntry::make('name')
                    ->label('Nama / merek kendaraan')
                    ->placeholder('-'),
                TextEntry::make('plate_number')
                    ->label('Nomor plat'),
                TextEntry::make('mileage')
                    ->label('Kilometer terdata')
                    ->numeric()
                    ->suffix(' km')
                    ->placeholder('-'),
                TextEntry::make('last_service_at')
                    ->label('Service terakhir')
                    ->state(fn ($record) => $record->last_service_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_service_mileage')
                    ->label('KM service terakhir')
                    ->state(fn ($record) => $record->last_service_mileage)
                    ->numeric()
                    ->suffix(' km')
                    ->placeholder('-'),
                TextEntry::make('last_service_summary')
                    ->label('History service terakhir')
                    ->state(fn ($record) => $record->last_service_summary)
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
