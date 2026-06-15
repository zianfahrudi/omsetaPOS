<?php

namespace App\Filament\Resources\Assemblies\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssemblyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perakitan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('number')->label('Nomor'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('product.name')->label('Produk Jadi'),
                        TextEntry::make('quantity')->label('Jumlah'),
                        TextEntry::make('total_cost')->label('Total Biaya')->numeric(decimalPlaces: 2),
                        TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Komponen')
                    ->schema([
                        RepeatableEntry::make('components')
                            ->hiddenLabel()
                            ->columns(12)
                            ->schema([
                                TextEntry::make('product_name')->label('Komponen')->columnSpan(6),
                                TextEntry::make('quantity')->label('Qty')->columnSpan(2),
                                TextEntry::make('unit_cost')->label('Harga')->numeric(decimalPlaces: 2)->columnSpan(2),
                                TextEntry::make('line_total')->label('Total')->numeric(decimalPlaces: 2)->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
