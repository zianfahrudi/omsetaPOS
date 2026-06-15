<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    private const REASONS = [
        'opname' => 'Stock Opname',
        'damaged' => 'Rusak',
        'lost' => 'Hilang',
        'expired' => 'Kadaluarsa',
        'correction' => 'Koreksi',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('product.name')->label('Produk')->searchable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::REASONS[$state] ?? $state),
                TextColumn::make('quantity_before')->label('Sebelum'),
                TextColumn::make('quantity_after')->label('Sesudah'),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->color(fn ($state): string => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->formatStateUsing(fn ($state): string => $state > 0 ? "+{$state}" : (string) $state),
                TextColumn::make('value')->label('Nilai')->numeric(decimalPlaces: 2)->sortable(),
            ])
            ->filters([
                SelectFilter::make('reason')->label('Alasan')->options(self::REASONS),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
