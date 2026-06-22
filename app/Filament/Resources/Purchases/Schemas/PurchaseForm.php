<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Faktur')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->default(fn () => Company::query()->value('id'))
                            ->required()
                            ->live(),
                        Select::make('contact_id')
                            ->label('Supplier')
                            ->options(fn ($get) => Contact::query()
                                ->where('company_id', $get('company_id'))
                                ->where('type', 'supplier')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn ($get) => Warehouse::query()
                                ->where('company_id', $get('company_id'))
                                ->pluck('name', 'id'))
                            ->default(fn ($get) => Warehouse::query()
                                ->where('company_id', $get('company_id'))
                                ->where('is_default', true)
                                ->value('id')),
                        TextInput::make('supplier_invoice_no')
                            ->label('No. Faktur Supplier'),
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Jatuh Tempo'),
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
                                            $set('unit_cost', (float) $product->cost_price);
                                            $set('line_type', $product->tracksStock() ? 'goods' : 'expense');
                                        }
                                    })
                                    ->columnSpan(5),
                                TextInput::make('product_name')
                                    ->label('Nama')
                                    ->required()
                                    ->columnSpan(7),
                                Select::make('line_type')
                                    ->label('Jenis')
                                    ->options(['goods' => 'Persediaan', 'expense' => 'Beban'])
                                    ->default('goods')
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('unit_cost')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('tax_amount')
                                    ->label('PPN')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(3),
                            ]),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
