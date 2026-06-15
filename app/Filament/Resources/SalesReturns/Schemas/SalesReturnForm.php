<?php

namespace App\Filament\Resources\SalesReturns\Schemas;

use App\Models\Company;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Retur')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->default(fn () => Company::query()->value('id'))
                            ->required()
                            ->live(),
                        Select::make('sales_invoice_id')
                            ->label('Faktur Penjualan')
                            ->options(fn ($get) => SalesInvoice::query()
                                ->where('company_id', $get('company_id'))
                                ->orderByDesc('date')
                                ->get()
                                ->mapWithKeys(fn (SalesInvoice $i) => [$i->id => "{$i->number} — ".($i->customer?->name ?? '')]))
                            ->searchable()
                            ->required()
                            ->live(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                        Textarea::make('reason')->label('Alasan')->columnSpanFull(),
                    ]),
                Section::make('Item Diretur')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->options(fn ($get) => SalesInvoiceItem::query()
                                        ->where('sales_invoice_id', $get('../../sales_invoice_id'))
                                        ->whereNotNull('product_id')
                                        ->get()
                                        ->mapWithKeys(fn (SalesInvoiceItem $i) => [$i->product_id => $i->product_name]))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $item = SalesInvoiceItem::query()
                                            ->where('sales_invoice_id', $get('../../sales_invoice_id'))
                                            ->where('product_id', $state)
                                            ->first();
                                        if ($item) {
                                            $set('unit_price', (float) $item->unit_price);
                                        }
                                    })
                                    ->required()
                                    ->columnSpan(6),
                                TextInput::make('quantity')->label('Qty')->numeric()->default(1)->minValue(1)->required()->columnSpan(3),
                                TextInput::make('unit_price')->label('Harga')->numeric()->default(0)->required()->columnSpan(3),
                            ]),
                    ]),
            ]);
    }
}
