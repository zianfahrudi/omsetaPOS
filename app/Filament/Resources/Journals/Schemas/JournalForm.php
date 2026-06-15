<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\Account;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class JournalForm
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
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                TextInput::make('reference')
                    ->label('Referensi')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->label('Baris Jurnal')
                    ->columnSpanFull()
                    ->minItems(2)
                    ->defaultItems(2)
                    ->columns(12)
                    ->schema([
                        Select::make('account_id')
                            ->label('Akun')
                            ->options(fn ($get) => Account::query()
                                ->where('company_id', $get('../../company_id'))
                                ->where('is_postable', true)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"]))
                            ->searchable()
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('debit')
                            ->label('Debit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(3),
                        TextInput::make('credit')
                            ->label('Kredit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(3),
                        TextInput::make('memo')
                            ->label('Catatan')
                            ->columnSpan(12),
                        Select::make('department_id')
                            ->label('Departemen')
                            ->options(fn ($get) => \App\Models\Department::query()->where('company_id', $get('../../company_id'))->pluck('name', 'id'))
                            ->columnSpan(6),
                        Select::make('project_id')
                            ->label('Proyek')
                            ->options(fn ($get) => \App\Models\Project::query()->where('company_id', $get('../../company_id'))->pluck('name', 'id'))
                            ->columnSpan(6),
                    ]),
            ]);
    }
}
