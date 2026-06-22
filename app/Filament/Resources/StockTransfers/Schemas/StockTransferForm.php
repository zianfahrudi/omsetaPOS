<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Models\Company;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemindahan')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->default(fn () => Company::query()->value('id'))
                            ->required()
                            ->live(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                        Select::make('from_warehouse_id')
                            ->label('Dari Gudang')
                            ->options(fn ($get) => Warehouse::query()->where('company_id', $get('company_id'))->pluck('name', 'id'))
                            ->required(),
                        Select::make('to_warehouse_id')
                            ->label('Ke Gudang')
                            ->options(fn ($get) => Warehouse::query()->where('company_id', $get('company_id'))->pluck('name', 'id'))
                            ->required(),
                    ]),
                Section::make('Item')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->options(fn ($get) => Product::query()
                                        ->whereHas('store', fn ($q) => $q->where('company_id', $get('../../company_id')))
                                        ->where('product_type', '!=', 'service')
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(8),
                                TextInput::make('quantity')->label('Qty')->numeric()->default(1)->minValue(1)->required()->columnSpan(4),
                            ]),
                        Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                    ]),
            ]);
    }
}
