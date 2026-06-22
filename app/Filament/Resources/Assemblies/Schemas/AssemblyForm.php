<?php

namespace App\Filament\Resources\Assemblies\Schemas;

use App\Models\Company;
use App\Models\Material;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssemblyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Produk Jadi')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn () => Company::query()->pluck('name', 'id'))
                            ->default(fn () => Company::query()->value('id'))
                            ->required()
                            ->live(),
                        DatePicker::make('date')->label('Tanggal')->default(now())->required(),
                        Select::make('product_id')
                            ->label('Produk Jadi (dari Produk)')
                            ->options(fn ($get) => Product::query()
                                ->whereHas('store', fn ($q) => $q->where('company_id', $get('company_id')))
                                ->where('product_type', '!=', 'service')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Kosongkan jika produk jadi diisi manual.'),
                        TextInput::make('product_name')->label('Atau Nama Produk Jadi (manual)')->maxLength(255),
                        TextInput::make('quantity')->label('Jumlah Diproduksi')->numeric()->default(1)->minValue(1)->required(),
                    ]),
                Section::make('Komponen (Material)')
                    ->schema([
                        Repeater::make('components')
                            ->hiddenLabel()
                            ->columns(12)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                Select::make('material_id')
                                    ->label('Material')
                                    ->options(fn ($get) => Material::query()
                                        ->where('company_id', $get('../../company_id'))
                                        ->where('is_active', true)
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
