<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('store.name')
                    ->label('Store'),
                TextEntry::make('name'),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('barcode')
                    ->placeholder('-'),
                TextEntry::make('product_type')
                    ->label('Tipe produk')
                    ->formatStateUsing(fn (?string $state): string => $state === 'service' ? 'Jasa' : 'Barang'),
                TextEntry::make('cost_price')
                    ->money(),
                TextEntry::make('sell_price')
                    ->money(),
                TextEntry::make('fee_amount')
                    ->label('Fee produk')
                    ->money('IDR'),
                TextEntry::make('product_service_fee_type')
                    ->label('Tipe service fee')
                    ->formatStateUsing(fn (?string $state): string => $state === 'percentage' ? 'Persentase' : 'Fixed price'),
                TextEntry::make('product_service_fee')
                    ->label('Nilai service fee')
                    ->formatStateUsing(fn ($state, $record): string => self::formatChargeValue((float) $state, $record?->product_service_fee_type)),
                TextEntry::make('product_tax_type')
                    ->label('Tipe tax')
                    ->formatStateUsing(fn (?string $state): string => $state === 'percentage' ? 'Persentase' : 'Fixed price'),
                TextEntry::make('product_tax_value')
                    ->label('Nilai tax')
                    ->formatStateUsing(fn ($state, $record): string => self::formatChargeValue((float) $state, $record?->product_tax_type)),
                TextEntry::make('stock')
                    ->numeric(),
                TextEntry::make('minimum_stock')
                    ->numeric(),
                TextEntry::make('unit'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
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
