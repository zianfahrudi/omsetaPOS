<?php

namespace App\Filament\Resources\Taxes\Schemas;

use App\Models\Account;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->default(fn () => Company::query()->value('id'))
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->label('Nama Pajak')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Jenis')
                    ->options([
                        'ppn' => 'PPN',
                        'pph' => 'PPh',
                        'other' => 'Lainnya',
                    ])
                    ->default('ppn')
                    ->required(),
                TextInput::make('rate')
                    ->label('Tarif (%)')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('account_id')
                    ->label('Akun Pajak')
                    ->options(fn ($get) => Account::query()
                        ->where('company_id', $get('company_id'))
                        ->whereIn('subtype', ['tax_output', 'tax_input'])
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
