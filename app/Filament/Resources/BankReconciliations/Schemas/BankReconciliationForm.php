<?php

namespace App\Filament\Resources\BankReconciliations\Schemas;

use App\Models\Account;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BankReconciliationForm
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
                Select::make('account_id')
                    ->label('Akun Kas/Bank')
                    ->options(fn ($get) => Account::query()
                        ->where('company_id', $get('company_id'))
                        ->whereIn('subtype', ['cash', 'bank'])
                        ->pluck('name', 'id'))
                    ->required(),
                DatePicker::make('statement_date')->label('Tanggal Rekening Koran')->default(now())->required(),
                TextInput::make('statement_balance')->label('Saldo Rekening Koran')->numeric()->required(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]);
    }
}
