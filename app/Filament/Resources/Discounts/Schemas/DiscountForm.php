<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->label('Toko')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama diskon')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode diskon')
                    ->required()
                    ->maxLength(60)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? mb_strtoupper(trim($state)) : null),
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        'fixed' => 'Fixed amount',
                        'percentage' => 'Percentage',
                    ])
                    ->default('fixed')
                    ->required(),
                TextInput::make('value')
                    ->label('Nilai')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('minimum_spend')
                    ->label('Minimal belanja')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('usage_limit')
                    ->label('Kuota pemakaian')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('Tanpa batas'),
                TextInput::make('used_count')
                    ->label('Terpakai')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Mulai aktif'),
                DateTimePicker::make('ends_at')
                    ->label('Berakhir'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
