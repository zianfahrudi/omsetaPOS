<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use App\Models\Company;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->options(fn () => Company::query()->pluck('name', 'id'))
                    ->default(fn () => Company::query()->value('id'))
                    ->required()
                    ->live(),
                Select::make('product_id')
                    ->label('Produk')
                    ->options(fn ($get) => Product::query()
                        ->whereHas('store', fn ($q) => $q->where('company_id', $get('company_id')))
                        ->where('product_type', '!=', 'service')
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live(),
                Placeholder::make('current_stock')
                    ->label('Stok Sistem Saat Ini')
                    ->content(fn ($get) => ($p = Product::find($get('product_id'))) ? $p->stock.' '.$p->unit : '-'),
                Select::make('reason')
                    ->label('Alasan')
                    ->options([
                        'opname' => 'Stock Opname',
                        'damaged' => 'Barang Rusak',
                        'lost' => 'Barang Hilang',
                        'expired' => 'Kadaluarsa',
                        'correction' => 'Koreksi',
                    ])
                    ->default('opname')
                    ->required(),
                TextInput::make('quantity_after')
                    ->label('Jumlah Aktual (Hasil Hitung)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
