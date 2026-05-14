<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('visit_count')
                    ->label('Jumlah transaksi')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_spent')
                    ->label('Total belanja')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('outstanding_debt')
                    ->label('Hutang berjalan')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('debt_total')
                    ->label('Total riwayat hutang')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                DateTimePicker::make('last_purchase_at')
                    ->label('Pembelian terakhir'),
                DateTimePicker::make('last_debt_at')
                    ->label('Hutang terakhir'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
