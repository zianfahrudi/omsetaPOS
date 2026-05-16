<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('barcode'),
                TextInput::make('image_url')
                    ->label('URL gambar')
                    ->placeholder('/product-images/contoh.svg')
                    ->columnSpanFull(),
                Select::make('product_type')
                    ->label('Tipe produk')
                    ->options([
                        'goods' => 'Barang',
                        'service' => 'Jasa',
                    ])
                    ->required()
                    ->default('goods'),
                TextInput::make('cost_price')
                    ->label('Harga modal')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('sell_price')
                    ->label('Harga jual')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('fee_amount')
                    ->label('Fee produk')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                Select::make('product_service_fee_type')
                    ->label('Tipe service fee produk')
                    ->options([
                        'fixed' => 'Fixed price',
                        'percentage' => 'Persentase',
                    ])
                    ->required()
                    ->default('fixed'),
                TextInput::make('product_service_fee')
                    ->label('Nilai service fee produk')
                    ->helperText('Isi nominal rupiah untuk fixed price, atau angka persen untuk persentase.')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('product_tax_type')
                    ->label('Tipe tax produk')
                    ->options([
                        'fixed' => 'Fixed price',
                        'percentage' => 'Persentase',
                    ])
                    ->required()
                    ->default('fixed'),
                TextInput::make('product_tax_value')
                    ->label('Nilai tax produk')
                    ->helperText('Isi nominal rupiah untuk fixed price, atau angka persen untuk persentase.')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('minimum_stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('unit')
                    ->required()
                    ->default('pcs'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
