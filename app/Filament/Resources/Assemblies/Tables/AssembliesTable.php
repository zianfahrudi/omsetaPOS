<?php

namespace App\Filament\Resources\Assemblies\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssembliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('product.name')->label('Produk Jadi')->searchable(),
                TextColumn::make('quantity')->label('Qty'),
                TextColumn::make('total_cost')->label('Total Biaya')->numeric(decimalPlaces: 2),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
