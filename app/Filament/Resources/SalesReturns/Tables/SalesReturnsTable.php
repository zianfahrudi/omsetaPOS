<?php

namespace App\Filament\Resources\SalesReturns\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
                TextColumn::make('invoice.number')->label('Faktur')->searchable(),
                TextColumn::make('total')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
