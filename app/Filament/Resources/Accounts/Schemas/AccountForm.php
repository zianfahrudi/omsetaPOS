<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->default(fn () => \App\Models\Company::query()->value('id'))
                    ->required(),
                Select::make('type')
                    ->label('Tipe Akun')
                    ->options([
                        'asset' => 'Aset',
                        'liability' => 'Liabilitas',
                        'equity' => 'Ekuitas',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('code')
                    ->label('Kode Akun')
                    ->required()
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama Akun')
                    ->required()
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label('Akun Induk')
                    ->relationship(
                        'parent',
                        'name',
                        fn ($query, $get) => $query->where('company_id', $get('company_id'))
                    )
                    ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}")
                    ->searchable()
                    ->nullable(),
                Select::make('subtype')
                    ->label('Peran Sistem')
                    ->options(array_combine(
                        ['cash', 'bank', 'accounts_receivable', 'inventory', 'tax_input', 'fixed_asset', 'accumulated_depreciation', 'accounts_payable', 'tax_output', 'equity', 'retained_earnings', 'income_summary', 'sales', 'sales_return', 'sales_discount', 'cogs', 'operating_expense', 'other_income', 'other_expense'],
                        ['Kas', 'Bank', 'Piutang Usaha', 'Persediaan', 'PPN Masukan', 'Aset Tetap', 'Akumulasi Penyusutan', 'Hutang Usaha', 'PPN Keluaran', 'Modal', 'Laba Ditahan', 'Ikhtisar Laba Rugi', 'Penjualan', 'Retur Penjualan', 'Diskon Penjualan', 'Harga Pokok Penjualan', 'Beban Operasional', 'Pendapatan Lain', 'Beban Lain'],
                    ))
                    ->helperText('Hanya diisi untuk akun yang dipakai otomatis oleh sistem.')
                    ->nullable(),
                TextInput::make('opening_balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_postable')
                    ->label('Bisa Dijurnal')
                    ->helperText('Matikan untuk akun induk/header.')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
            ]);
    }
}
