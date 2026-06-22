<?php

namespace App\Filament\Resources\SalesQuotations\Schemas;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Penawaran')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->default(fn () => Company::query()->value('id'))
                            ->required()
                            ->live(),
                        Select::make('contact_id')
                            ->label('Pelanggan')
                            ->options(fn ($get) => Contact::query()
                                ->where('company_id', $get('company_id'))
                                ->where('type', 'customer')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                        DatePicker::make('valid_until')->label('Berlaku Sampai'),
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
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $product = $state ? Product::find($state) : null;
                                        if ($product) {
                                            $set('product_name', $product->name);
                                            $set('unit_price', (float) $product->sell_price);
                                        }
                                    })
                                    ->columnSpan(5),
                                TextInput::make('product_name')->label('Nama')->required()->columnSpan(7),
                                TextInput::make('quantity')->label('Qty')->numeric()->default(1)->minValue(1)->required()->columnSpan(4),
                                TextInput::make('unit_price')->label('Harga')->numeric()->default(0)->required()->columnSpan(4),
                                TextInput::make('tax_amount')->label('PPN')->numeric()->default(0)->columnSpan(4),
                            ]),
                        Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                    ]),
            ]);
    }
}
