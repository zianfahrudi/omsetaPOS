<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('action')
                    ->required(),
                TextInput::make('subject_type'),
                TextInput::make('subject_id')
                    ->numeric(),
                TextInput::make('description')
                    ->required(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
                TextInput::make('ip_address'),
            ]);
    }
}
