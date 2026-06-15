<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Company;
use App\Models\Contact;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
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
                TextInput::make('name')->label('Nama Proyek')->required()->maxLength(255),
                TextInput::make('code')->label('Kode')->maxLength(50),
                Select::make('contact_id')
                    ->label('Pelanggan')
                    ->options(fn ($get) => Contact::query()
                        ->where('company_id', $get('company_id'))
                        ->where('type', 'customer')
                        ->pluck('name', 'id'))
                    ->searchable(),
                TextInput::make('budget')->label('Anggaran')->numeric()->default(0),
                DatePicker::make('start_date')->label('Mulai'),
                DatePicker::make('end_date')->label('Selesai'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'planned' => 'Direncanakan',
                        'active' => 'Berjalan',
                        'done' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->default('active')
                    ->required(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
