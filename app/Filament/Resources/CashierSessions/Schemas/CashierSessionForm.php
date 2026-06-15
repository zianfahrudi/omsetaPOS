<?php

namespace App\Filament\Resources\CashierSessions\Schemas;

use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CashierSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->label('Toko')
                    ->options(fn () => Auth::user()?->accessibleStores()->pluck('name', 'id') ?? collect())
                    ->required(),
                TextInput::make('opening_cash')
                    ->label('Kas Awal')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
