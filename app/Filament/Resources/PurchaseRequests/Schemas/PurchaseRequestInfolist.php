<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permintaan Pembelian')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('supplier.name')->label('Supplier'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('needed_date')->label('Dibutuhkan')->date()->placeholder('-'),
                        TextEntry::make('order.number')->label('Pesanan')->placeholder('-'),
                        TextEntry::make('grand_total')->label('Estimasi Total')->numeric(decimalPlaces: 2),
                    ]),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('product_name')->label('Item')->columnSpan(6),
                                TextEntry::make('quantity')->label('Qty')->columnSpan(2),
                                TextEntry::make('unit_cost')->label('Estimasi')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('line_total')->label('Total')->numeric(decimalPlaces: 2)->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
