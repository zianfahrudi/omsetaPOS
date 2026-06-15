<?php

namespace App\Filament\Resources\Giros\Schemas;

use App\Models\Company;
use App\Models\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GiroForm
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
                Select::make('contact_id')
                    ->label('Pelanggan')
                    ->options(fn ($get) => Contact::query()
                        ->where('company_id', $get('company_id'))
                        ->where('type', 'customer')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('giro_number')->label('Nomor Giro/Cek')->maxLength(60),
                TextInput::make('bank_name')->label('Bank Penerbit')->maxLength(120),
                DatePicker::make('date')->label('Tanggal Terima')->default(now())->required(),
                DatePicker::make('due_date')->label('Jatuh Tempo'),
                TextInput::make('amount')->label('Nominal')->numeric()->minValue(1)->required(),
            ]);
    }
}
