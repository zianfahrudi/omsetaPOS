<?php

namespace App\Filament\Resources\FixedAssets\Schemas;

use App\Models\Account;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FixedAssetForm
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
                TextInput::make('name')->label('Nama Aset')->required()->maxLength(255),
                TextInput::make('code')->label('Kode')->maxLength(50),
                DatePicker::make('acquisition_date')->label('Tanggal Perolehan')->default(now())->required(),
                TextInput::make('acquisition_cost')->label('Harga Perolehan')->numeric()->required(),
                TextInput::make('salvage_value')->label('Nilai Residu')->numeric()->default(0),
                TextInput::make('useful_life_months')->label('Masa Manfaat (bulan)')->numeric()->default(12)->minValue(1)->required(),
                Select::make('asset_account_id')
                    ->label('Akun Aset')
                    ->options(fn ($get) => self::accounts($get('company_id'), ['fixed_asset']))
                    ->default(fn ($get) => Account::query()->where('company_id', $get('company_id'))->where('subtype', 'fixed_asset')->value('id')),
                Select::make('accumulated_account_id')
                    ->label('Akun Akumulasi Penyusutan')
                    ->options(fn ($get) => self::accounts($get('company_id'), ['accumulated_depreciation']))
                    ->default(fn ($get) => Account::query()->where('company_id', $get('company_id'))->where('subtype', 'accumulated_depreciation')->value('id')),
                Select::make('expense_account_id')
                    ->label('Akun Beban Penyusutan')
                    ->options(fn ($get) => self::accounts($get('company_id'), ['operating_expense', 'other_expense']))
                    ->default(fn ($get) => Account::query()->where('company_id', $get('company_id'))->where('subtype', 'operating_expense')->value('id')),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<int, string>  $subtypes
     * @return array<int, string>
     */
    private static function accounts($companyId, array $subtypes): array
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->whereIn('subtype', $subtypes)
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"])
            ->all();
    }
}
