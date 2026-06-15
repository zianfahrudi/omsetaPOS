<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pesanan Penjualan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('customer.name')->label('Pelanggan'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('expected_date')->label('Perkiraan Kirim')->date()->placeholder('-'),
                        TextEntry::make('invoice.number')->label('Faktur')->placeholder('-'),
                        TextEntry::make('subtotal')->label('Subtotal')->numeric(decimalPlaces: 2),
                        TextEntry::make('tax_total')->label('PPN')->numeric(decimalPlaces: 2),
                        TextEntry::make('grand_total')->label('Total')->numeric(decimalPlaces: 2),
                    ]),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('product_name')->label('Item')->columnSpan(6),
                                TextEntry::make('quantity')->label('Qty')->columnSpan(2),
                                TextEntry::make('unit_price')->label('Harga')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('line_total')->label('Total')->numeric(decimalPlaces: 2)->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
