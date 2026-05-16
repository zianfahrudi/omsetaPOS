<?php

namespace App\Filament\Resources\CustomerVehicles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerVehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama / merek kendaraan')
                    ->maxLength(255),
                TextInput::make('plate_number')
                    ->label('Nomor plat')
                    ->required()
                    ->maxLength(30)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? preg_replace('/\s+/', ' ', mb_strtoupper(trim($state))) : null),
                TextInput::make('mileage')
                    ->label('Kilometer')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
