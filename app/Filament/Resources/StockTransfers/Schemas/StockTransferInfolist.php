<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pemindahan Barang')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('fromWarehouse.name')->label('Dari Gudang'),
                        TextEntry::make('toWarehouse.name')->label('Ke Gudang'),
                        TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('product_name')->label('Produk')->columnSpan(9),
                                TextEntry::make('quantity')->label('Qty')->columnSpan(3),
                            ]),
                    ]),
            ]);
    }
}
