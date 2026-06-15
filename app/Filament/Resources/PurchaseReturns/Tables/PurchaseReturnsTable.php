<?php

namespace App\Filament\Resources\PurchaseReturns\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('purchase.number')->label('Faktur')->searchable(),
                TextColumn::make('total')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
