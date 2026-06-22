<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use App\Models\Account;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashTransactionForm
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
                Select::make('type')
                    ->label('Jenis Transaksi')
                    ->options([
                        'in' => 'Kas Masuk',
                        'out' => 'Kas Keluar',
                        'transfer' => 'Transfer Kas',
                    ])
                    ->default('in')
                    ->required()
                    ->live(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                Select::make('account_id')
                    ->label(fn ($get) => $get('type') === 'transfer' ? 'Dari Akun (Kas/Bank)' : 'Akun Kas/Bank')
                    ->options(fn ($get) => self::cashAccounts($get('company_id')))
                    ->searchable()
                    ->required(),
                Select::make('to_account_id')
                    ->label('Ke Akun (Kas/Bank)')
                    ->options(fn ($get) => self::cashAccounts($get('company_id')))
                    ->searchable()
                    ->visible(fn ($get) => $get('type') === 'transfer')
                    ->required(fn ($get) => $get('type') === 'transfer'),
                Select::make('counter_account_id')
                    ->label(fn ($get) => $get('type') === 'out' ? 'Akun Beban/Tujuan' : 'Akun Sumber Dana')
                    ->options(fn ($get) => self::counterAccounts($get('company_id')))
                    ->searchable()
                    ->visible(fn ($get) => in_array($get('type'), ['in', 'out'], true))
                    ->required(fn ($get) => in_array($get('type'), ['in', 'out'], true)),
                TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function cashAccounts($companyId): array
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->whereIn('subtype', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function counterAccounts($companyId): array
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->whereNotIn('subtype', ['cash', 'bank'])
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"])
            ->all();
    }
}
