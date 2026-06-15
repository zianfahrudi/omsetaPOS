<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockAdjustmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penyesuaian Stok')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('product.name')->label('Produk'),
                        TextEntry::make('reason')->label('Alasan'),
                        TextEntry::make('quantity_before')->label('Stok Sebelum'),
                        TextEntry::make('quantity_after')->label('Stok Sesudah'),
                        TextEntry::make('difference')->label('Selisih'),
                        TextEntry::make('unit_cost')->label('Harga Pokok')->numeric(decimalPlaces: 2),
                        TextEntry::make('value')->label('Nilai Penyesuaian')->numeric(decimalPlaces: 2),
                        TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
