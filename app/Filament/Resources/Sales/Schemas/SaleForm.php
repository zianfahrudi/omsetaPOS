<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                Select::make('cashier_id')
                    ->relationship('cashier', 'name'),
                TextInput::make('number')
                    ->required(),
                TextInput::make('customer_name'),
                TextInput::make('customer_phone'),
                TextInput::make('vehicle_plate_number')
                    ->label('Nomor plat'),
                TextInput::make('vehicle_mileage')
                    ->label('Kilometer')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('status')
                    ->required()
                    ->default('completed'),
                TextInput::make('payment_method')
                    ->required(),
                Select::make('payment_status')
                    ->label('Status pembayaran')
                    ->options([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum lunas',
                    ])
                    ->default('lunas')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('change_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('debt_amount')
                    ->label('Nominal hutang')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
