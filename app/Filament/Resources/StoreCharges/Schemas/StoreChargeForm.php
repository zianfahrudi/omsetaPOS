<?php

namespace App\Filament\Resources\StoreCharges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->label('Toko')
                    ->relationship('store', 'name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_tax_active')
                    ->label('Aktifkan tax')
                    ->default(false),
                TextInput::make('tax_percentage')
                    ->label('Tax (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%')
                    ->required(),
                Toggle::make('is_service_fee_active')
                    ->label('Aktifkan service fee')
                    ->default(false),
                TextInput::make('service_fee_percentage')
                    ->label('Service fee (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%')
                    ->required(),
            ]);
    }
}
