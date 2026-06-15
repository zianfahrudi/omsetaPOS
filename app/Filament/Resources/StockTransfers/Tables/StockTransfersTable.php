<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('fromWarehouse.name')->label('Dari'),
                TextColumn::make('toWarehouse.name')->label('Ke'),
                TextColumn::make('items_count')->label('Item')->counts('items'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
