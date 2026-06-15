<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Faktur Pembelian')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('supplier.name')->label('Supplier'),
                        TextEntry::make('supplier_invoice_no')->label('No. Faktur Supplier')->placeholder('-'),
                        TextEntry::make('due_date')->label('Jatuh Tempo')->date()->placeholder('-'),
                        TextEntry::make('warehouse.name')->label('Gudang')->placeholder('-'),
                        TextEntry::make('subtotal')->label('Subtotal')->numeric(decimalPlaces: 2),
                        TextEntry::make('tax_total')->label('PPN')->numeric(decimalPlaces: 2),
                        TextEntry::make('grand_total')->label('Total')->numeric(decimalPlaces: 2),
                        TextEntry::make('paid_amount')->label('Dibayar')->numeric(decimalPlaces: 2),
                        TextEntry::make('outstanding_amount')->label('Sisa Hutang')->numeric(decimalPlaces: 2),
                    ]),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('product_name')->label('Item')->columnSpan(5),
                                TextEntry::make('line_type')->label('Jenis')->columnSpan(2),
                                TextEntry::make('quantity')->label('Qty')->columnSpan(2),
                                TextEntry::make('unit_cost')->label('Harga')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('line_total')->label('Total')->numeric(decimalPlaces: 2)->columnSpan(1),
                            ]),
                    ]),
                Section::make('Pembayaran')
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('number')->label('Nomor'),
                                TextEntry::make('date')->label('Tanggal')->date(),
                                TextEntry::make('method')->label('Metode'),
                                TextEntry::make('amount')->label('Nominal')->numeric(decimalPlaces: 2),
                            ]),
                    ]),
            ]);
    }
}
