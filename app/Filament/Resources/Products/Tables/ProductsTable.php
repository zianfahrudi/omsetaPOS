<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Gambar')
                    ->square(),
                TextColumn::make('store.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('product_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'service' ? 'Jasa' : 'Barang')
                    ->color(fn (?string $state): string => $state === 'service' ? 'info' : 'gray'),
                TextColumn::make('cost_price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('sell_price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('fee_amount')
                    ->label('Fee')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('product_service_fee_type')
                    ->label('Tipe service')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'percentage' ? 'Persentase' : 'Fixed')
                    ->color(fn (?string $state): string => $state === 'percentage' ? 'info' : 'gray'),
                TextColumn::make('product_service_fee')
                    ->label('Service fee')
                    ->formatStateUsing(fn ($state, $record): string => self::formatChargeValue((float) $state, $record?->product_service_fee_type))
                    ->sortable(),
                TextColumn::make('product_tax_type')
                    ->label('Tipe tax')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'percentage' ? 'Persentase' : 'Fixed')
                    ->color(fn (?string $state): string => $state === 'percentage' ? 'info' : 'gray'),
                TextColumn::make('product_tax_value')
                    ->label('Tax')
                    ->formatStateUsing(fn ($state, $record): string => self::formatChargeValue((float) $state, $record?->product_tax_type))
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('minimum_stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function formatChargeValue(float $value, ?string $type): string
    {
        if ($type === 'percentage') {
            return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
