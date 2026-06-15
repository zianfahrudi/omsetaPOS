<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
